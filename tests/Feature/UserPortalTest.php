<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dashboard_aggregates_only_owned_reports_and_shows_user_navigation(): void
    {
        $user = $this->member();
        foreach (['pending', 'in_progress', 'completed', 'rejected'] as $status) {
            $this->report($user, ['status' => $status]);
        }
        $this->report($this->member(), ['description' => 'Rahasia pengguna lain']);

        $response = $this->actingAs($user)->get(route('user.dashboard'))->assertOk()
            ->assertSee('Buat Laporan')->assertSee('Laporan Saya')->assertSee('WITA')
            ->assertSee('ecotrack.userSidebar.collapsed')->assertDontSee('Manajemen Pengguna')
            ->assertDontSee('Pengaturan')->assertDontSee('Rahasia pengguna lain')
            ->assertViewHas('summary', fn ($summary) => (int) $summary->total === 4
                && (int) $summary->pending === 1 && (int) $summary->in_progress === 1 && (int) $summary->completed === 1);

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//section[@aria-label="Ringkasan laporan saya"]//*[self::a or self::button or @onclick or @tabindex or @role="button" or @role="link"]')->length);
        $this->assertSame(0, $xpath->query('//main//header//a')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi utama"]//a[@aria-label="Buat Laporan"]')->length);
        $response->assertDontSee('Laporan yang ditolak tetap tersimpan');

    }

    public function test_empty_user_can_open_dashboard_list_and_form_with_real_categories(): void
    {
        $category = $this->category();
        $this->actingAs($this->member());
        $this->get(route('user.dashboard'))->assertOk()->assertSee('Laporan pertama Anda dimulai di sini');
        $this->get(route('user.reports.index'))->assertOk()->assertSee('Laporan pertama Anda dimulai di sini');
        $this->get(route('user.reports.create'))->assertOk()->assertSee('Sampah Organik')
            ->assertSee('data-image-preview', false)->assertSee('maksimal 5 MB')
            ->assertSee('maxlength="1000"', false)->assertSee('data-description-count', false)
            ->assertSee('Google Maps')->assertSee('Latitude')->assertSee('Longitude')
            ->assertSee('value="'.$category->id.'"', false)->assertDontSee('name="user_id"', false);
    }

    public function test_user_can_submit_photo_and_server_sets_owner_status_and_ignores_ai_input(): void
    {
        Storage::fake('public');
        $owner = $this->member();
        $other = $this->member();
        $category = $this->category();
        $response = $this->actingAs($owner)->post(route('user.reports.store'), [
            'category_id' => $category->id,
            'description' => 'Sampah plastik dekat taman',
            'image' => $this->photo(),
            'latitude' => '-3.5', 'longitude' => '114.8',
            'user_id' => $other->id, 'status' => 'completed', 'ai_category_id' => $category->id, 'ai_confidence' => 1,
        ]);
        $report = WasteReport::sole();
        $response->assertRedirect(route('user.reports.show', $report))->assertSessionHas('status');
        $this->assertSame($owner->id, $report->user_id);
        $this->assertSame('pending', $report->status);
        $this->assertSame(-3.5, $report->latitude);
        $this->assertNull($report->ai_category_id);
        $this->assertNull($report->ai_confidence);
        $this->assertSame(0, $report->statusHistories()->count());
        Storage::disk('public')->assertExists($report->image_path);
    }

    public function test_submission_validates_required_fields_category_file_and_coordinates_in_indonesian(): void
    {
        Storage::fake('public');
        $this->actingAs($this->member())->post(route('user.reports.store'), [])
            ->assertSessionHasErrors(['description' => 'Deskripsi laporan wajib diisi.', 'image' => 'Foto laporan wajib diisi.', 'latitude' => 'Latitude wajib diisi.', 'longitude' => 'Longitude wajib diisi.']);
        $this->post(route('user.reports.store'), [
            'category_id' => 999, 'description' => str_repeat('x', 1001),
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'latitude' => 91, 'longitude' => -181,
        ])->assertSessionHasErrors(['category_id', 'image', 'description' => 'Deskripsi laporan maksimal 1000 karakter.', 'latitude' => 'Latitude harus antara -90 dan 90.', 'longitude' => 'Longitude harus antara -180 dan 180.']);
        $this->post(route('user.reports.store'), [
            'description' => 'Foto terlalu besar', 'image' => $this->photo()->size(5121),
        ])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('waste_reports', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_category_remains_optional_and_valid_coordinate_boundaries_and_description_limit_are_accepted(): void
    {
        Storage::fake('public');
        $this->actingAs($this->member())->post(route('user.reports.store'), [
            'description' => str_repeat('x', 1000), 'image' => $this->photo(),
            'latitude' => -90, 'longitude' => 180,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $report = WasteReport::sole();
        $this->assertNull($report->category_id);
        $this->assertSame(-90.0, $report->latitude);
        $this->assertSame(180.0, $report->longitude);
        $this->assertSame(1000, mb_strlen($report->description));
    }

    public function test_list_search_filters_and_pagination_never_include_another_owners_reports(): void
    {
        $owner = $this->member();
        $category = $this->category();
        for ($i = 0; $i < 12; $i++) {
            $this->report($owner, ['category_id' => $category->id, 'description' => 'Sampah taman '.$i]);
        }
        $this->report($owner, ['description' => 'Tidak sesuai filter', 'status' => 'completed']);
        $this->report($this->member(), ['category_id' => $category->id, 'description' => 'Sampah taman rahasia']);
        $filters = ['search' => 'taman', 'category' => $category->id, 'status' => 'pending'];
        $this->actingAs($owner)->get(route('user.reports.index', $filters))->assertOk()
            ->assertViewHas('reports', fn ($reports) => $reports->total() === 12 && $reports->count() === 10)
            ->assertSee('search=taman')->assertSee('status=pending')->assertSee('page=2')
            ->assertDontSee('Sampah taman rahasia')->assertDontSee('Tidak sesuai filter');
        $this->get(route('user.reports.index', $filters + ['page' => 2]))->assertOk()
            ->assertViewHas('reports', fn ($reports) => $reports->count() === 2);
        $this->get(route('user.reports.index', ['search' => 'tidak-ditemukan']))->assertOk()
            ->assertSee('Tidak ada laporan yang sesuai');
    }

    public function test_owned_detail_shows_indonesian_history_admin_note_and_zero_coordinates_without_status_control(): void
    {
        $owner = $this->member();
        $admin = $this->member()->forceFill(['role' => 'admin']);
        $admin->save();
        $report = $this->report($owner, ['category_id' => $this->category()->id, 'latitude' => 0, 'longitude' => 0, 'status' => 'in_progress']);
        $report->statusHistories()->create([
            'changed_by' => $admin->id, 'old_status' => 'pending', 'new_status' => 'in_progress', 'note' => 'Tim sedang menuju lokasi.',
        ]);
        $this->actingAs($owner)->get(route('user.reports.show', $report))->assertOk()
            ->assertSee('Sampah Organik')->assertSee('Dalam Proses')->assertSee('Tim sedang menuju lokasi.')
            ->assertSee('Lintang: 0')->assertSee('Bujur: 0')->assertDontSee('Koordinat tidak disertakan.')
            ->assertDontSee('name="status"', false)->assertDontSee('Simpan status');
        $this->actingAs($this->member())->get(route('user.reports.show', $report))->assertNotFound();
        $this->actingAs($owner)->patch(route('admin.reports.status', $report), ['status' => 'completed'])->assertForbidden();
        $this->assertSame('in_progress', $report->refresh()->status);
    }

    public function test_user_routes_require_login_and_reject_admin_and_user_cannot_access_admin(): void
    {
        foreach (['user.dashboard', 'user.reports.index', 'user.reports.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->post(route('user.reports.store'), [])->assertRedirect(route('login'));
        $admin = $this->member()->forceFill(['role' => 'admin']);
        $admin->save();
        $this->actingAs($admin)->get(route('user.reports.index'))->assertForbidden();
        $this->get(route('user.reports.create'))->assertForbidden();
        $this->post(route('user.reports.store'), [])->assertForbidden();
        $this->actingAs($this->member())->get(route('admin.dashboard'))->assertForbidden();
    }

    private function member(): User
    {
        return User::factory()->create()->refresh();
    }

    private function photo(): \Illuminate\Http\Testing\File
    {
        return UploadedFile::fake()->createWithContent('foto.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lV8AAAAASUVORK5CYII=',
        ));
    }

    private function category(): WasteCategory
    {
        return WasteCategory::create(['name' => 'Organic Waste', 'slug' => 'organic-waste']);
    }

    private function report(User $owner, array $attributes = []): WasteReport
    {
        return $owner->wasteReports()->create(array_merge([
            'description' => 'Laporan milik saya', 'image_path' => 'waste-reports/test.jpg', 'status' => 'pending',
        ], $attributes));
    }
}
