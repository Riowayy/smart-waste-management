<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WasteReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $statistics = $request->user()->wasteReports()
            ->selectRaw('COUNT(*) as total_reports')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_reports', ['pending'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_reports', ['in_progress'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_reports', ['completed'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected_reports', ['rejected'])
            ->first();

        $recentReports = $request->user()->wasteReports()
            ->with('category:id,name,slug,description')
            ->latest()
            ->limit(5)
            ->get();

        return $this->respond([
            'statistics' => [
                'total' => (int) ($statistics->total_reports ?? 0),
                'pending' => (int) ($statistics->pending_reports ?? 0),
                'in_progress' => (int) ($statistics->in_progress_reports ?? 0),
                'completed' => (int) ($statistics->completed_reports ?? 0),
                'rejected' => (int) ($statistics->rejected_reports ?? 0),
            ],
            'recent_reports' => WasteReportResource::collection($recentReports)->resolve(),
        ], 'Dashboard berhasil diambil.');
    }
}
