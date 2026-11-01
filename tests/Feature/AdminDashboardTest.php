<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_statistics_match_report_statuses_in_the_database(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        foreach (['pending', 'pending', 'in_progress', 'completed', 'rejected'] as $status) {
            $this->report($owner, null, $status);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSame([
            'total' => 5,
            'pending' => 2,
            'inProgress' => 1,
            'completed' => 1,
        ], $response->viewData('statistics'));
        $response->assertSee('5')->assertSee('2');
    }

    public function test_category_distribution_uses_real_report_counts_for_each_required_category(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $organic = $this->category('Organic Waste');
        $plastic = $this->category('Plastic Waste');
        $paper = $this->category('Paper Waste');

        $this->report($owner, $organic);
        $this->report($owner, $organic);
        $this->report($owner, $plastic);
        $this->report($owner, null);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $distribution = $response->viewData('categoryDistribution')->keyBy('name');
        $this->assertSame(2, $distribution->get('Organic Waste')['total']);
        $this->assertSame(1, $distribution->get('Plastic Waste')['total']);
        $this->assertSame(0, $distribution->get('Paper Waste')['total']);
        $this->assertSame(3, $response->viewData('categoryTotal'));
        $this->assertSame(0, $paper->reports()->count());
    }

    public function test_chart_contains_real_daily_report_and_handled_counts_and_fills_missing_days_with_zero(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00'));
        $admin = $this->admin();
        $owner = User::factory()->create();

        $this->report($owner, null, 'completed', now()->subDays(6)->setTime(9, 0));
        $this->report($owner, null, 'pending', now()->subDays(3)->setTime(9, 0));
        $this->report($owner, null, 'rejected', now()->subDays(3)->setTime(10, 0));
        $this->report($owner, null, 'in_progress', now()->subDays(8)->setTime(9, 0));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $chartDays = $response->viewData('chartDays');
        $this->assertCount(7, $chartDays);
        $this->assertSame(1, $chartDays[0]['reports']);
        $this->assertSame(1, $chartDays[0]['handled']);
        $this->assertSame(0, $chartDays[1]['reports']);
        $this->assertSame(0, $chartDays[1]['handled']);
        $this->assertSame(2, $chartDays[3]['reports']);
        $this->assertSame(1, $chartDays[3]['handled']);
        $this->assertStringContainsString('M 50.00', $response->viewData('reportLine'));
    }

    public function test_recent_reports_show_the_six_newest_records_with_related_reporter_and_category(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00'));
        $admin = $this->admin();
        $owner = User::factory()->create(['name' => 'Reporter Latest']);
        $category = $this->category('Organic Waste');
        $reports = [];

        foreach (range(1, 8) as $day) {
            $reports[] = $this->report($owner, $category, 'pending', now()->subDays($day));
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()->assertSee('Reporter Latest')->assertSee('Organic Waste');
        $recentReports = $response->viewData('recentReports');
        $this->assertCount(6, $recentReports);
        $this->assertSame($reports[0]->id, $recentReports->first()->id);
        $this->assertSame($reports[5]->id, $recentReports->last()->id);
        $response->assertSee('#'.$reports[0]->id);
    }

    public function test_guest_is_redirected_and_regular_user_is_forbidden_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create()->refresh();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    private function admin(): User
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        return $admin;
    }

    private function category(string $name): WasteCategory
    {
        return WasteCategory::query()->create([
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);
    }

    private function report(User $owner, ?WasteCategory $category, string $status = 'pending', ?Carbon $createdAt = null): WasteReport
    {
        $report = WasteReport::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category?->id,
            'description' => 'Dashboard report '.$status,
            'image_path' => 'waste-reports/dashboard.jpg',
            'status' => $status,
        ]);

        if ($createdAt) {
            $report->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        return $report;
    }
}
