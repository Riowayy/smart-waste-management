<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WasteCategory;
use App\Http\Requests\User\StoreWasteReportRequest;
use App\Support\ReportLabels;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class WasteReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:waste_categories,id'],
            'status' => ['nullable', 'in:pending,in_progress,completed,rejected'],
        ]);
        $reports = $request->user()->wasteReports()->with('category:id,name,slug')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($query) => $query->where('description', 'like', "%{$search}%")
                    ->when(ctype_digit(ltrim($search, '#')), fn ($query) => $query->orWhere('id', ltrim($search, '#'))),
            ))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->latest('id')->paginate(10)->withQueryString();

        return view('user.reports.index', [
            'reports' => $reports,
            'categories' => WasteCategory::orderBy('name')->get(),
            'statuses' => ReportLabels::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('user.reports.create', ['categories' => WasteCategory::orderBy('name')->get()]);
    }

    public function store(StoreWasteReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $imagePath = $request->file('image')->store('waste-reports', 'public');
        if (! $imagePath) {
            throw new RuntimeException('Penyimpanan foto laporan gagal.');
        }

        try {
            $report = $request->user()->wasteReports()->create([
                'category_id' => $validated['category_id'] ?? null,
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => 'pending',
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);
            throw $exception;
        }

        return redirect()->route('user.reports.show', $report)->with('status', 'Laporan berhasil dikirim. Anda dapat memantau perkembangannya di halaman ini.');
    }

    public function show(Request $request, int $report): View
    {
        $wasteReport = $request->user()
            ->wasteReports()
            ->with(['category:id,name,slug', 'statusHistories' => fn ($query) => $query->orderBy('created_at')->orderBy('id'), 'statusHistories.changedBy:id,name'])
            ->findOrFail($report);

        return view('user.reports.show', ['report' => $wasteReport]);
    }
}
