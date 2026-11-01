<?php

namespace Tests\Feature;

use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WasteReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_search_and_filter_reports(): void
    {
        $admin = $this->admin();
        $category = WasteCategory::query()->create(['name' => 'Plastic Waste', 'slug' => 'plastic-waste']);
        $reporter = User::factory()->create();
        $matchingReport = $this->report($reporter, $category, ['description' => 'Botol plastik di taman']);
        $this->report($reporter, null, ['description' => 'Sisa makanan', 'status' => 'completed']);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'search' => 'Botol',
            'category' => $category->id,
            'status' => 'pending',
        ]));

        $response->assertOk()->assertSee('Botol plastik di taman')->assertDontSee('Sisa makanan');
        $this->assertSame($matchingReport->id, (int) $response->viewData('reports')->first()->id);
    }

    public function test_admin_can_open_create_detail_and_edit_pages(): void
    {
        $admin = $this->admin();
        $report = $this->report(User::factory()->create(), null);

        $this->actingAs($admin)->get(route('admin.reports.create'))->assertOk();
        $this->get(route('admin.reports.show', $report))->assertOk()->assertSee('Riwayat status');
        $this->get(route('admin.reports.edit', $report))->assertOk()->assertSee('Simpan perubahan');
    }

    public function test_admin_can_create_a_report(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $reporter = User::factory()->create();
        $category = WasteCategory::query()->create(['name' => 'Organic Waste', 'slug' => 'organic-waste']);

        $response = $this->actingAs($admin)->post(route('admin.reports.store'), [
            'user_id' => $reporter->id,
            'category_id' => $category->id,
            'description' => 'Sampah organik di taman',
            'image' => UploadedFile::fake()->create('report.jpg', 100, 'image/jpeg'),
            'latitude' => '-6.2',
            'longitude' => '106.8',
        ]);

        $report = WasteReport::query()->firstOrFail();
        $response->assertRedirect(route('admin.reports.show', $report));
        $this->assertSame($reporter->id, $report->user_id);
        $this->assertSame('pending', $report->status);
        Storage::disk('public')->assertExists($report->image_path);
        $this->assertDatabaseCount('report_status_histories', 0);
    }

    public function test_admin_can_edit_a_report_without_replacing_its_image(): void
    {
        $admin = $this->admin();
        $report = $this->report(User::factory()->create(), null);

        $response = $this->actingAs($admin)->put(route('admin.reports.update', $report), [
            'user_id' => $report->user_id,
            'description' => 'Deskripsi diperbarui',
            'latitude' => '-7.1',
            'longitude' => '110.2',
        ]);

        $response->assertRedirect(route('admin.reports.show', $report));
        $this->assertDatabaseHas('waste_reports', [
            'id' => $report->id,
            'description' => 'Deskripsi diperbarui',
            'image_path' => 'waste-reports/existing.jpg',
        ]);
    }

    public function test_admin_can_delete_a_report_and_its_status_history(): void
    {
        $admin = $this->admin();
        $report = $this->report(User::factory()->create(), null);
        $report->statusHistories()->create([
            'changed_by' => $admin->id,
            'old_status' => 'pending',
            'new_status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.reports.destroy', $report));

        $response->assertRedirect(route('admin.reports.index'));
        $this->assertDatabaseMissing('waste_reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('report_status_histories', ['report_id' => $report->id]);
    }

    public function test_admin_can_change_status_and_history_records_actor_and_transition(): void
    {
        $admin = $this->admin();
        $report = $this->report(User::factory()->create(), null);

        $response = $this->actingAs($admin)->patch(route('admin.reports.status', $report), [
            'status' => 'in_progress',
            'note' => 'Tim sudah ditugaskan.',
        ]);

        $response->assertRedirect(route('admin.reports.show', $report));
        $this->assertDatabaseHas('waste_reports', ['id' => $report->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'changed_by' => $admin->id,
            'old_status' => 'pending',
            'new_status' => 'in_progress',
            'note' => 'Tim sudah ditugaskan.',
        ]);
        $this->assertNotNull(ReportStatusHistory::query()->where('report_id', $report->id)->firstOrFail()->created_at);
    }

    public function test_unchanged_status_does_not_create_fake_history(): void
    {
        $admin = $this->admin();
        $report = $this->report(User::factory()->create(), null);

        $this->actingAs($admin)->patch(route('admin.reports.status', $report), [
            'status' => 'pending',
        ])->assertRedirect(route('admin.reports.show', $report));

        $this->assertDatabaseCount('report_status_histories', 0);
    }

    public function test_user_cannot_view_another_users_report_by_changing_the_id(): void
    {
        $owner = User::factory()->create();
        $report = $this->report($owner, null);
        $intruder = User::factory()->create()->refresh();

        $this->actingAs($intruder)
            ->get(route('user.reports.show', $report))
            ->assertNotFound();
    }

    public function test_user_can_open_their_own_report_detail(): void
    {
        $owner = User::factory()->create();
        $report = $this->report($owner, null);

        $this->actingAs($owner->refresh())
            ->get(route('user.reports.show', $report))
            ->assertOk()
            ->assertSee('Laporan #'.$report->id);
    }

    public function test_guest_cannot_access_admin_or_user_reports(): void
    {
        $report = $this->report(User::factory()->create(), null);

        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.show', $report))->assertRedirect(route('login'));
        $this->get(route('user.reports.show', $report))->assertRedirect(route('login'));
    }

    private function admin(): User
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        return $admin;
    }

    private function report(User $owner, ?WasteCategory $category, array $attributes = []): WasteReport
    {
        return WasteReport::query()->create(array_merge([
            'user_id' => $owner->id,
            'category_id' => $category?->id,
            'description' => 'Laporan contoh',
            'image_path' => 'waste-reports/existing.jpg',
            'status' => 'pending',
        ], $attributes));
    }
}
