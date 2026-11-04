<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminReportCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('validCoordinates')]
    public function test_admin_can_store_and_update_coordinates(mixed $latitude, mixed $longitude, ?float $expectedLatitude, ?float $expectedLongitude): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = ['user_id' => $admin->id, 'description' => 'Koordinat laporan', 'latitude' => $latitude, 'longitude' => $longitude];
        $this->actingAs($admin)->post(route('admin.reports.store'), [
            ...$payload, 'image' => UploadedFile::fake()->create('report.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $report = WasteReport::query()->sole();
        foreach (['latitude' => $expectedLatitude, 'longitude' => $expectedLongitude] as $field => $expected) {
            if ($expected === null) {
                $this->assertNull($report->$field);
            } else {
                $this->assertEqualsWithDelta($expected, $report->$field, 0.0000001);
            }
        }
        $report->update(['latitude' => 1, 'longitude' => 2]);
        $this->put(route('admin.reports.update', $report), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $report->refresh();
        foreach (['latitude' => $expectedLatitude, 'longitude' => $expectedLongitude] as $field => $expected) {
            if ($expected === null) {
                $this->assertNull($report->$field);
            } else {
                $this->assertEqualsWithDelta($expected, $report->$field, 0.0000001);
                $this->get(route('admin.reports.show', $report))->assertOk()->assertSee((string) $report->$field);
                $this->get(route('admin.reports.edit', $report))->assertOk()->assertSee('value="'.$report->$field.'"', false);
            }
        }
    }

    public static function validCoordinates(): array
    {
        return [
            'decimal' => ['-21.156389', '115.484083', -21.156389, 115.484083],
            'Google Maps DMS' => ['21°9\'23.0"S', '115°29\'02.7"E', -21.1563888889, 115.4840833333],
            'north west unicode' => [' 21° 9′ 23.0″ n ', '115°29’02.7”w', 21.1563888889, -115.4840833333],
            'positive limits' => ['90°0\'0"N', '180°0\'0"E', 90.0, 180.0],
            'negative limits' => ['90°0\'0"S', '180°0\'0"W', -90.0, -180.0],
            'decimal limits' => [-90, 180, -90.0, 180.0],
            'zero' => ['0°0\'0"S', '0', 0.0, 0.0],
            'optional' => ['', null, null, null],
        ];
    }

    #[DataProvider('invalidCoordinates')]
    public function test_invalid_coordinates_are_rejected_for_create_and_edit(mixed $latitude, mixed $longitude): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $report = WasteReport::query()->create(['user_id' => $admin->id, 'description' => 'Laporan asli', 'image_path' => 'existing.jpg', 'latitude' => 1, 'longitude' => 2]);
        $payload = ['user_id' => $admin->id, 'description' => 'Perubahan', 'latitude' => $latitude, 'longitude' => $longitude];
        $this->actingAs($admin)->post(route('admin.reports.store'), [
            ...$payload, 'image' => UploadedFile::fake()->create('report.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors(['latitude', 'longitude']);
        $this->put(route('admin.reports.update', $report), $payload)->assertSessionHasErrors(['latitude', 'longitude']);
        $this->assertDatabaseCount('waste_reports', 1);
        $this->assertSame(1.0, $report->fresh()->latitude);
        $this->assertSame(2.0, $report->fresh()->longitude);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function invalidCoordinates(): array
    {
        return [
            'decimal range' => ['90.0000001', '-180.0000001'],
            'DMS range' => ['90°0\'0.1"N', '180°0\'0.1"W'],
            'minutes' => ['21°60\'0"S', '115°60\'0"E'],
            'seconds' => ['21°0\'60"S', '115°0\'60"E'],
            'wrong axes' => ['21°0\'0"E', '115°0\'0"N'],
            'conflicting signs' => ['-21°0\'0"N', '-115°0\'0"E'],
            'missing directions' => ['21°0\'0"', '115°0\'0"'],
            'text' => ['abc', '115°29\'02.7"E extra'],
            'arrays' => [['21'], ['115']],
        ];
    }

    public function test_coordinate_fields_accept_text_and_describe_supported_formats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = WasteReport::query()->create(['user_id' => $admin->id, 'description' => 'Laporan', 'image_path' => 'existing.jpg']);
        $this->actingAs($admin);
        foreach ([route('admin.reports.create'), route('admin.reports.edit', $report)] as $url) {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            foreach (['latitude', 'longitude'] as $field) {
                $input = $document->getElementById($field);
                $this->assertSame('text', $input->getAttribute('type'));
                $this->assertStringContainsString('atau', $input->getAttribute('placeholder'));
                $this->assertSame('', $input->getAttribute('aria-describedby'));
                $this->assertNull($document->getElementById($field.'-help'));
                $label = (new \DOMXPath($document))->query('//label[@for="'.$field.'"]')->item(0);
                $this->assertStringContainsString(ucfirst($field), $label->textContent);
                $this->assertStringContainsString($field === 'latitude' ? '-21.156389' : '115.484083', $input->getAttribute('placeholder'));
                $this->assertStringContainsString($field === 'latitude' ? '21°9\'23.0"S' : '115°29\'02.7"E', $input->getAttribute('placeholder'));
            }
            $response->assertDontSee('Lintang')->assertDontSee('Bujur');
        }
    }

    #[DataProvider('mapCoordinates')]
    public function test_detail_only_links_to_maps_for_valid_coordinates(?float $latitude, ?float $longitude, bool $hasLink): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = WasteReport::query()->create([
            'user_id' => $admin->id, 'description' => 'Lokasi laporan', 'image_path' => 'existing.jpg',
            'latitude' => $latitude, 'longitude' => $longitude,
        ]);
        $response = $this->actingAs($admin)->get(route('admin.reports.show', $report))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $links = (new \DOMXPath($document))->query('//a[normalize-space(.)="Buka di GMaps"]');
        $this->assertSame($hasLink ? 1 : 0, $links->length);
        if ($hasLink) {
            $link = $links->item(0);
            $this->assertSame('https://www.google.com/maps?q='.$report->latitude.','.$report->longitude, $link->getAttribute('href'));
            $this->assertSame('_blank', $link->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
        }
    }

    public static function mapCoordinates(): array
    {
        return [
            'normal' => [-21.1563889, 115.4840833, true],
            'zero' => [0.0, 0.0, true],
            'positive limits' => [90.0, 180.0, true],
            'negative limits' => [-90.0, -180.0, true],
            'missing latitude' => [null, 115.0, false],
            'missing longitude' => [-21.0, null, false],
            'missing both' => [null, null, false],
            'latitude too high' => [90.1, 115.0, false],
            'latitude too low' => [-90.1, 115.0, false],
            'longitude too high' => [-21.0, 180.1, false],
            'longitude too low' => [-21.0, -180.1, false],
        ];
    }

    public function test_dms_support_does_not_change_api_or_user_portal_validation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create()->refresh();
        $payload = [
            'description' => 'Lokasi DMS khusus admin',
            'latitude' => '21°9\'23.0"S', 'longitude' => '115°29\'02.7"E',
            'image' => UploadedFile::fake()->create('report.jpg', 10, 'image/jpeg'),
        ];
        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/reports', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
        $this->actingAs($user)->post(route('user.reports.store'), $payload)
            ->assertSessionHasErrors(['latitude', 'longitude']);
        $this->assertDatabaseCount('waste_reports', 0);
    }
}
