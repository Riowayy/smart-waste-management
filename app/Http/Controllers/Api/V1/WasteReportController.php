<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWasteReportRequest as ApiStoreWasteReportRequest;
use App\Http\Resources\Api\V1\WasteReportResource;
use App\Models\WasteReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class WasteReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,in_progress,completed,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $reports = $request->user()->wasteReports()
            ->with('category:id,name,slug,description')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return $this->respond(
            WasteReportResource::collection($reports->items())->resolve(),
            'Daftar laporan berhasil diambil.',
            pagination: [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ],
        );
    }

    public function store(ApiStoreWasteReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $imagePath = $request->file('image')->store('waste-reports', 'public');

        if (! $imagePath) {
            throw new RuntimeException('Penyimpanan foto laporan gagal.');
        }

        try {
            $report = DB::transaction(fn () => $request->user()->wasteReports()->create([
                'category_id' => $validated['category_id'] ?? null,
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => 'pending',
            ]));
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);
            throw $exception;
        }

        $report->load('category:id,name,slug,description');

        return $this->respond(
            WasteReportResource::make($report)->resolve(),
            'Laporan berhasil dibuat.',
            201,
        );
    }

    public function show(Request $request, int $report): JsonResponse
    {
        $wasteReport = $request->user()->wasteReports()
            ->with([
                'category:id,name,slug,description',
                'statusHistories' => fn ($query) => $query->latest(),
                'statusHistories.changedBy:id,name',
            ])
            ->findOrFail($report);

        return $this->respond(
            WasteReportResource::make($wasteReport)->resolve(),
            'Detail laporan berhasil diambil.',
        );
    }
}
