<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use App\Support\ReportLabels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $statistics = WasteReport::query()
            ->selectRaw('COUNT(*) as total_reports')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_reports', ['pending'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_reports', ['in_progress'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_reports', ['completed'])
            ->first();

        $categories = WasteCategory::query()
            ->whereIn('slug', array_keys(WasteCategory::DEFAULT_NAMES))
            ->withCount('reports')
            ->get(['id', 'name', 'slug'])
            ->keyBy('slug');

        $colors = ['#10b981', '#005ac2', '#6366f1'];
        $categoryDistribution = collect(WasteCategory::DEFAULT_NAMES)
            ->map(fn (string $name, string $slug): array => [
                'name' => $name,
                'slug' => $slug,
                'label' => ReportLabels::CATEGORIES[$slug],
                'total' => (int) ($categories->get($slug)?->reports_count ?? 0),
            ])->values()->map(fn (array $category, int $index): array => [
                ...$category, 'color' => $colors[$index],
            ]);

        $categoryTotal = $categoryDistribution->sum('total');
        $circumference = 2 * M_PI * 48;
        $offset = 0.0;
        $categoryDistribution = $categoryDistribution->map(function (array $category) use (&$offset, $categoryTotal, $circumference): array {
            $length = $categoryTotal > 0 ? ($category['total'] / $categoryTotal) * $circumference : 0;
            $category['percent'] = $categoryTotal > 0 ? (int) round(($category['total'] / $categoryTotal) * 100) : 0;
            $category['dasharray'] = sprintf('%.2f %.2f', $length, $circumference - $length);
            $category['dashoffset'] = sprintf('%.2f', -$offset);
            $offset += $length;

            return $category;
        });

        $period = $request->query('period') === 'monthly' ? 'monthly' : 'weekly';
        $startDate = $period === 'monthly' ? CarbonImmutable::today()->startOfMonth() : CarbonImmutable::today()->subDays(6);
        $endDate = $period === 'monthly' ? $startDate->endOfMonth() : CarbonImmutable::today()->endOfDay();
        $dailyReports = WasteReport::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as report_date, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as handled', ['completed', 'rejected'])
            ->groupBy('report_date')
            ->get()
            ->keyBy('report_date');

        $chartDays = collect(range(0, (int) $startDate->diffInDays($endDate->startOfDay())))->map(function (int $dayOffset) use ($startDate, $dailyReports, $period): array {
            $date = $startDate->addDays($dayOffset);
            $row = $dailyReports->get($date->toDateString());

            return [
                'date' => $date->toDateString(),
                'label' => $date->locale('id')->translatedFormat($period === 'monthly' ? 'j' : 'D'),
                'reports' => (int) ($row?->total ?? 0),
                'handled' => (int) ($row?->handled ?? 0),
            ];
        });

        $observedMax = $chartDays->max(fn (array $day): int => max($day['reports'], $day['handled']));
        $chartMax = max(3, (int) (ceil($observedMax / 3) * 3));
        $chartDays = $chartDays->map(function (array $day, int $index) use ($chartMax, $chartDays): array {
            $day['x'] = round(50 + ($index * (620 / max(1, $chartDays->count() - 1))), 2);
            $day['reportY'] = round(190 - (($day['reports'] / $chartMax) * 155), 2);
            $day['handledY'] = round(190 - (($day['handled'] / $chartMax) * 155), 2);

            return $day;
        });

        $reportLine = $this->linePath($chartDays, 'reportY');
        $handledLine = $this->linePath($chartDays, 'handledY');
        $chartTicks = collect([1, 2, 3, 4])->map(fn (int $tick): int => (int) ceil($chartMax * (4 - $tick) / 3));

        $recentReports = WasteReport::query()
            ->with(['user:id,name,email', 'category:id,name'])
            ->latest()
            ->limit(6)
            ->get();

        return view('admin.dashboard.index', [
            'statistics' => [
                'total' => (int) ($statistics->total_reports ?? 0),
                'pending' => (int) ($statistics->pending_reports ?? 0),
                'inProgress' => (int) ($statistics->in_progress_reports ?? 0),
                'completed' => (int) ($statistics->completed_reports ?? 0),
            ],
            'categoryDistribution' => $categoryDistribution,
            'categoryTotal' => $categoryTotal,
            'chartDays' => $chartDays,
            'period' => $period,
            'chartMonth' => $startDate->locale('id')->translatedFormat('F Y'),
            'chartMax' => $chartMax,
            'chartTicks' => $chartTicks,
            'reportLine' => $reportLine,
            'handledLine' => $handledLine,
            'recentReports' => $recentReports,
        ]);
    }

    private function linePath(Collection $days, string $valueKey): string
    {
        return $days->map(fn (array $day, int $index): string => sprintf(
            '%s %.2f %.2f',
            $index === 0 ? 'M' : 'L',
            $day['x'],
            $day[$valueKey],
        ))->implode(' ');
    }
}
