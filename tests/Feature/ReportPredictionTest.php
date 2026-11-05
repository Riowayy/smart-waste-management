<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Database\Seeders\WasteCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportPredictionTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9WQAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ml.url' => 'http://ml.test:8001/']);
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->seed(WasteCategorySeeder::class);
    }

    #[DataProvider('successfulPredictions')]
    public function test_each_create_flow_saves_real_prediction_separately_from_selected_category(string $flow, string $slug, string $label, float $confidence): void
    {
        $options = [];
        Http::fake(function (Request $request, array $requestOptions) use ($slug, $label, $confidence, &$options) {
            $options = $requestOptions;

            return Http::response(['slug' => $slug, 'category' => $label, 'confidence' => $confidence]);
        });

        $report = $this->createReport($flow);
        $this->assertSame(WasteCategory::where('slug', $slug)->value('id'), $report->ai_category_id);
        $this->assertSame(round($confidence * 100, 2), $report->ai_confidence);
        $this->assertEquals(2, $options['connect_timeout']);
        $this->assertEquals(5, $options['timeout']);
        $this->assertFalse($options['allow_redirects']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://ml.test:8001/predict'
            && $request->method() === 'POST'
            && $request->hasFile('image', base64_decode(self::PNG), 'report.png')
            && $request->hasHeader('Accept', 'application/json')
            && ! $request->hasHeader('Authorization'));
    }

    public static function successfulPredictions(): array
    {
        $cases = [];
        foreach (['admin', 'user', 'api'] as $flow) {
            foreach ([['organic-waste', 'Organic Waste', 0.923456], ['plastic-waste', 'Plastic Waste', 0.0], ['paper-waste', 'Paper Waste', 1.0]] as $prediction) {
                $cases[$flow.'-'.$prediction[0]] = [$flow, ...$prediction];
            }
        }

        return $cases;
    }

    #[DataProvider('failedPredictions')]
    public function test_each_create_flow_survives_ml_failure(string $flow, string $failure): void
    {
        $calls = 0;
        Http::fake(function () use ($failure, &$calls) {
            $calls++;
            if ($failure === 'timeout') {
                throw new ConnectionException('Simulasi timeout ML.');
            }

            return $failure === 'http'
                ? Http::response(['detail' => 'Internal ML error'], 503)
                : Http::response('not-json', 200);
        });

        $report = $this->createReport($flow);
        $this->assertNull($report->ai_category_id);
        $this->assertNull($report->ai_confidence);
        $this->assertSame(1, $calls);
    }

    public static function failedPredictions(): array
    {
        $cases = [];
        foreach (['admin', 'user', 'api'] as $flow) {
            foreach (['timeout', 'http', 'invalid'] as $failure) {
                $cases[$flow.'-'.$failure] = [$flow, $failure];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidPredictions')]
    public function test_invalid_prediction_fields_never_become_ai_data(array $prediction): void
    {
        Http::fake(['*' => Http::response($prediction)]);
        $report = $this->createReport('api');
        $this->assertNull($report->ai_category_id);
        $this->assertNull($report->ai_confidence);
    }

    public static function invalidPredictions(): array
    {
        $valid = ['slug' => 'organic-waste', 'category' => 'Organic Waste', 'confidence' => 0.9];

        return [
            'missing fields' => [[]],
            'unknown slug' => [array_replace($valid, ['slug' => 'metal-waste'])],
            'array slug' => [array_replace($valid, ['slug' => ['organic-waste']])],
            'mismatched category' => [array_replace($valid, ['category' => 'Plastic Waste'])],
            'negative confidence' => [array_replace($valid, ['confidence' => -0.1])],
            'percent instead of probability' => [array_replace($valid, ['confidence' => 90])],
            'string confidence' => [array_replace($valid, ['confidence' => '0.9'])],
            'boolean confidence' => [array_replace($valid, ['confidence' => true])],
            'missing confidence' => [['slug' => 'organic-waste', 'category' => 'Organic Waste']],
        ];
    }

    public function test_missing_database_category_does_not_create_a_category_or_fake_prediction(): void
    {
        WasteCategory::where('slug', 'organic-waste')->delete();
        Http::fake(['*' => Http::response(['slug' => 'organic-waste', 'category' => 'Organic Waste', 'confidence' => 0.9])]);
        $report = $this->createReport('api');
        $this->assertNull($report->ai_category_id);
        $this->assertNull($report->ai_confidence);
        $this->assertDatabaseMissing('waste_categories', ['slug' => 'organic-waste']);
    }

    public function test_empty_service_url_skips_inference(): void
    {
        config(['services.ml.url' => null]);
        Http::fake();
        $report = $this->createReport('api');
        $this->assertNull($report->ai_category_id);
        $this->assertNull($report->ai_confidence);
        Http::assertNothingSent();
    }

    private function createReport(string $flow): WasteReport
    {
        $user = User::factory()->create()->refresh();
        $selected = WasteCategory::create(['name' => 'Selected category', 'slug' => 'selected-category']);
        $payload = [
            'user_id' => $user->id,
            'category_id' => $selected->id,
            'description' => 'Foto laporan uji integrasi ML',
            'image' => UploadedFile::fake()->createWithContent('report.png', base64_decode(self::PNG)),
            'latitude' => 0, 'longitude' => 115,
            'ai_category_id' => $selected->id, 'ai_confidence' => 100,
        ];

        if ($flow === 'admin') {
            $admin = User::factory()->create();
            $admin->forceFill(['role' => 'admin'])->save();
            $response = $this->actingAs($admin)->post(route('admin.reports.store'), $payload);
        } elseif ($flow === 'user') {
            $response = $this->actingAs($user)->post(route('user.reports.store'), $payload);
        } else {
            $response = $this->withToken($user->createToken('mobile')->plainTextToken)
                ->postJson('/api/v1/reports', $payload);
            $response->assertCreated()->assertJsonPath('success', true)
                ->assertJsonPath('data.category.id', $selected->id)
                ->assertJsonMissingPath('data.ai_category_id')->assertJsonMissingPath('data.ai_confidence');
        }

        $response->assertSessionHasNoErrors();
        if ($flow !== 'api') {
            $response->assertRedirect();
        }
        $report = WasteReport::sole();
        if ($flow !== 'api') {
            $response->assertRedirect(route($flow.'.reports.show', $report))->assertSessionHas('status');
        }
        $this->assertSame($selected->id, $report->category_id);
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame('pending', $report->status);
        Storage::disk('public')->assertExists($report->image_path);

        return $report;
    }
}
