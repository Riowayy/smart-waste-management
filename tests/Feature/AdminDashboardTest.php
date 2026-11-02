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

    public function test_admin_sidebar_renders_local_clock_and_guarded_post_logout_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('data-sidebar-clock', false)
            ->assertSee('data-clock-date', false)
            ->assertSee('data-clock-time', false)
            ->assertSee('data-logout-confirm', false)
            ->assertSee('name="_token"', false)
            ->assertSee('method="POST"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'action="'.route('logout').'"'));
    }

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

        $response->assertOk()
            ->assertSee('Reporter Latest')
            ->assertSee('Organic Waste');
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

    public function test_monthly_chart_covers_entire_current_month_with_actual_counts(): void
    {
        $this->travelTo(Carbon::parse('2026-02-15 12:00:00'));
        $admin = $this->admin();
        $this->report($admin, null, 'rejected', Carbon::parse('2026-02-01 10:00:00'));
        $this->report($admin, null, 'completed', Carbon::parse('2026-01-31 10:00:00'));
        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'monthly']));
        $response->assertOk()->assertSee('Februari 2026')->assertSee('data-chart-point', false);
        $days = $response->viewData('chartDays');
        $this->assertCount(28, $days);
        $this->assertSame('2026-02-01', $days->first()['date']);
        $this->assertSame('2026-02-28', $days->last()['date']);
        $this->assertSame(1, $days->first()['reports']);
        $this->assertSame(1, $days->first()['handled']);
        $this->assertSame(0, $days->last()['reports']);
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
