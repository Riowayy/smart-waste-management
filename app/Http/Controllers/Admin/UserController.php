<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(): View
    {
        $filters = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:admin,user'],
        ]);

        $summary = User::query()
            ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS admins, SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS users', ['admin', 'user'])
            ->first();

        $users = User::query()
            ->withCount('wasteReports')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'summary'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->forceFill(['role' => $validated['role'] ?? 'user'])->save();

            return $user;
        });

        return redirect()->route('admin.users.show', $user)->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function show(User $user): View
    {
        $user->loadCount(['wasteReports', 'changedReportStatuses']);
        $adminCount = User::query()->where('role', 'admin')->count();

        return view('admin.users.show', compact('user', 'adminCount'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $actor = $request->user();

        if ($actor->is($user) && $validated['role'] !== $user->role) {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role akun yang sedang digunakan.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $updated = DB::transaction(function () use ($validated, $user): bool {
            $admins = User::query()->where('role', 'admin')->lockForUpdate()->get(['id']);
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->role === 'admin' && $validated['role'] !== 'admin' && $admins->count() <= 1) {
                return false;
            }

            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            if (filled($validated['password'] ?? null)) {
                $attributes['password'] = $validated['password'];
            }

            $roleChanged = $lockedUser->role !== $validated['role'];
            $lockedUser->forceFill($attributes)->save();

            if (isset($attributes['password']) || $roleChanged) {
                $lockedUser->tokens()->delete();
            }

            return true;
        });

        if (! $updated) {
            return back()->withErrors(['role' => 'Role Admin terakhir tidak dapat diubah.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'Informasi pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $actor = request()->user();
        $result = DB::transaction(function () use ($actor, $user): string {
            $admins = User::query()->where('role', 'admin')->lockForUpdate()->get(['id']);
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->role === 'admin' && $admins->count() <= 1) {
                return 'last_admin';
            }

            if ($actor->is($lockedUser)) {
                return 'self';
            }

            if ($lockedUser->wasteReports()->exists()) {
                return 'has_reports';
            }

            $lockedUser->delete();

            return 'deleted';
        });

        if ($result === 'last_admin') {
            return back()->withErrors(['user' => 'Admin terakhir tidak dapat dihapus.']);
        }

        if ($result === 'self') {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.']);
        }

        if ($result === 'has_reports') {
            return back()->withErrors(['user' => 'Akun ini masih memiliki laporan dan tidak dapat dihapus agar riwayat laporan tetap tersimpan.']);
        }

        return redirect()->route('admin.users.index')->with('status', 'Akun pengguna berhasil dihapus.');
    }
}
