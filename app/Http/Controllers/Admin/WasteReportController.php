<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWasteReportRequest;
use App\Http\Requests\UpdateWasteReportRequest;
use App\Http\Requests\UpdateWasteReportStatusRequest;
use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use App\Services\WasteClassifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WasteReportController extends Controller
{
    public function index(): View
    {
        $filters = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:waste_categories,id'],
            'status' => ['nullable', 'in:pending,in_progress,completed,rejected'],
        ]);

        $reports = WasteReport::query()
            ->with(['user:id,name,email', 'category:id,name,slug'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'categories' => WasteCategory::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.reports.create', $this->formOptions());
    }

    public function store(StoreWasteReportRequest $request, WasteClassifier $classifier): RedirectResponse
    {
        $validated = $request->validated();
        $imagePath = $request->file('image')->store('waste-reports', 'public');
        if (! $imagePath) {
            throw new \RuntimeException('Penyimpanan foto laporan gagal.');
        }

        try {
            $prediction = $classifier->predict($request->file('image'));
            $report = DB::transaction(fn () => WasteReport::create([
                'user_id' => $validated['user_id'],
                'category_id' => $validated['category_id'] ?? null,
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => 'pending',
                ...$prediction,
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($imagePath);
            throw $exception;
        }

        return redirect()->route('admin.reports.show', $report)->with('status', 'Laporan berhasil dibuat.');
    }

    public function show(WasteReport $report): View
    {
        $report->load([
            'user:id,name,email',
            'category:id,name,slug',
            'statusHistories.changedBy:id,name',
        ]);

        return view('admin.reports.show', [
            'report' => $report,
            'statuses' => $this->statuses(),
        ]);
    }

    public function edit(WasteReport $report): View
    {
        return view('admin.reports.edit', [
            ...$this->formOptions(),
            'report' => $report->load(['user:id,name,email', 'category:id,name,slug']),
        ]);
    }

    public function update(UpdateWasteReportRequest $request, WasteReport $report): RedirectResponse
    {
        $validated = $request->validated();
        $oldImagePath = $report->image_path;
        $newImagePath = $request->hasFile('image')
            ? $request->file('image')->store('waste-reports', 'public')
            : null;
        if ($newImagePath === false) {
            throw new \RuntimeException('Penyimpanan foto laporan gagal.');
        }

        try {
            DB::transaction(function () use ($validated, $report, $newImagePath): void {
                $report->update([
                    'user_id' => $validated['user_id'],
                    'category_id' => $validated['category_id'] ?? null,
                    'description' => $validated['description'],
                    'image_path' => $newImagePath ?? $report->image_path,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath && $oldImagePath !== $newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('admin.reports.show', $report)->with('status', 'Laporan berhasil diperbarui.');
    }

    public function updateStatus(UpdateWasteReportStatusRequest $request, WasteReport $report): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $report, $request): void {
            $lockedReport = WasteReport::query()->lockForUpdate()->findOrFail($report->id);
            $oldStatus = $lockedReport->status;
            $newStatus = $validated['status'];

            if ($oldStatus === $newStatus) {
                return;
            }

            $lockedReport->update(['status' => $newStatus]);

            $lockedReport->statusHistories()->create([
                'changed_by' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()->route('admin.reports.show', $report)->with('status', 'Status laporan berhasil diperbarui.');
    }

    public function destroy(WasteReport $report): RedirectResponse
    {
        $imagePath = $report->image_path;

        DB::transaction(fn () => $report->delete());

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()->route('admin.reports.index')->with('status', 'Laporan berhasil dihapus.');
    }

    private function formOptions(): array
    {
        return [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'categories' => WasteCategory::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ];
    }

    private function statuses(): array
    {
        return \App\Support\ReportLabels::STATUSES;
    }
}
