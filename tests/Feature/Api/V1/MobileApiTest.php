<?php

namespace Tests\Feature\Api\V1;

use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_registration_creates_only_a_user_and_returns_a_bearer_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mobile Member',
            'email' => 'mobile@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Akun berhasil dibuat.')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseHas('users', ['email' => 'mobile@example.test', 'role' => 'user']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseCount('users', 1);

        $user = User::query()->findOrFail($response->json('data.user.id'));
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $token = PersonalAccessToken::findToken($response->json('data.token'));
        $this->assertNotNull($token);
        $this->assertTrue($token->tokenable->is($user));
        $this->assertSame('mobile', $token->name);

        $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_api_registration_rolls_back_user_and_token_when_token_creation_fails(): void
    {
        $payload = [
            'name' => 'Mobile Member',
            'email' => 'mobile@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ];
        $dispatcher = PersonalAccessToken::getEventDispatcher();
        $failedUserId = null;
        PersonalAccessToken::setEventDispatcher(clone $dispatcher);

        try {
            PersonalAccessToken::created(function (PersonalAccessToken $token) use (&$failedUserId): void {
                $failedUserId = $token->tokenable_id;

                throw new RuntimeException('Simulasi kegagalan pembuatan token.');
            });

            $this->postJson('/api/v1/auth/register', $payload)
                ->assertStatus(500)
                ->assertExactJson([
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada server.',
                    'data' => null,
                    'errors' => null,
                ]);
        } finally {
            PersonalAccessToken::setEventDispatcher($dispatcher);
        }

        $this->assertNotNull($failedUserId);
        $this->assertDatabaseMissing('users', ['id' => $failedUserId]);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/v1/auth/register', $payload)->assertCreated();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_api_login_returns_a_token_for_valid_credentials(): void
    {
        $user = $this->user();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.token_type', 'Bearer');
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_api_login_returns_401_for_invalid_credentials(): void
    {
        $this->user(['email' => 'mobile@example.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile@example.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);
    }

    public function test_api_logout_revokes_only_the_active_token(): void
    {
        $user = $this->user();
        $activeToken = $user->createToken('mobile')->plainTextToken;
        $otherToken = $user->createToken('other-device')->accessToken;

        $this->withToken($activeToken)->postJson('/api/v1/auth/logout')
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();
        $this->withToken($activeToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_protected_api_endpoint_requires_a_bearer_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Autentikasi diperlukan.');
    }

    public function test_user_dashboard_returns_database_statistics_scoped_to_the_user(): void
    {
        $user = $this->user();
        $otherUser = $this->user(['email' => 'other@example.test']);
        foreach (['pending', 'pending', 'in_progress', 'completed', 'rejected'] as $status) {
            $this->report($user, null, $status);
        }
        $this->report($otherUser, null, 'completed');

        $response = $this->withToken($user->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.statistics.total', 5)
            ->assertJsonPath('data.statistics.pending', 2)
            ->assertJsonPath('data.statistics.in_progress', 1)
            ->assertJsonPath('data.statistics.completed', 1)
            ->assertJsonPath('data.statistics.rejected', 1)
            ->assertJsonCount(5, 'data.recent_reports');
    }

    public function test_category_endpoint_returns_categories_from_the_database(): void
    {
        $user = $this->user();
        $this->category('Organic Waste');
        $this->category('Plastic Waste');

        $this->withToken($user->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Organic Waste'])
            ->assertJsonFragment(['name' => 'Plastic Waste']);
    }

    public function test_user_can_create_a_report_with_an_uploaded_photo(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $category = $this->category('Paper Waste');

        $response = $this->withToken($user->createToken('mobile')->plainTextToken)
            ->postJson('/api/v1/reports', [
                'category_id' => $category->id,
                'description' => 'Kardus bekas di taman',
                'image' => UploadedFile::fake()->create('report.jpg', 120, 'image/jpeg'),
                'latitude' => '-6.2',
                'longitude' => '106.8',
                'status' => 'completed',
                'user_id' => 99999,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.latitude', -6.2)
            ->assertJsonPath('data.longitude', 106.8);

        $report = WasteReport::query()->firstOrFail();
        $this->assertSame($user->id, $report->user_id);
        Storage::disk('public')->assertExists($report->image_path);
        $this->assertStringContainsString('/storage/waste-reports/', $response->json('data.image_url'));
        $this->assertDatabaseCount('report_status_histories', 0);
    }

    public function test_report_listing_supports_pagination_and_status_filter(): void
    {
        $user = $this->user();
        $otherUser = $this->user(['email' => 'other@example.test']);
        $pending = $this->report($user, null, 'pending');
        $completed = $this->report($user, null, 'completed');
        $this->report($otherUser, null, 'pending');

        $response = $this->withToken($user->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/reports?status=pending&per_page=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 1);
        $this->assertNotSame($completed->id, $response->json('data.0.id'));
    }

    public function test_user_can_view_their_report_and_status_history(): void
    {
        $user = $this->user();
        $admin = $this->user(['email' => 'admin@example.test'])->forceFill(['role' => 'admin']);
        $admin->save();
        $category = $this->category('Organic Waste');
        $report = $this->report($user, $category, 'in_progress');
        $report->statusHistories()->create([
            'changed_by' => $admin->id,
            'old_status' => 'pending',
            'new_status' => 'in_progress',
            'note' => 'Sedang ditangani.',
        ]);

        $this->withToken($user->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/reports/'.$report->id)
            ->assertOk()
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.status_history.0.old_status', 'pending')
            ->assertJsonPath('data.status_history.0.new_status', 'in_progress')
            ->assertJsonPath('data.status_history.0.changed_by', $admin->name)
            ->assertJsonPath('data.status_history.0.note', 'Sedang ditangani.');
    }

    public function test_user_cannot_view_another_users_report(): void
    {
        $owner = $this->user();
        $viewer = $this->user(['email' => 'viewer@example.test']);
        $report = $this->report($owner, null);

        $this->withToken($viewer->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/reports/'.$report->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource tidak ditemukan.');
    }

    public function test_api_validation_returns_consistent_422_response(): void
    {
        $user = $this->user();

        $this->withToken($user->createToken('mobile')->plainTextToken)
            ->postJson('/api/v1/reports', [
                'description' => '',
                'image' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'),
                'latitude' => 100,
                'longitude' => 200,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['description', 'image', 'latitude', 'longitude']]);
    }

    public function test_registration_validation_uses_the_api_error_envelope(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['name', 'email', 'password']]);
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes)->refresh();
    }

    private function category(string $name): WasteCategory
    {
        return WasteCategory::query()->create(['name' => $name, 'slug' => str($name)->slug()]);
    }

    private function report(User $user, ?WasteCategory $category, string $status = 'pending'): WasteReport
    {
        return $user->wasteReports()->create([
            'category_id' => $category?->id,
            'description' => 'Laporan '.$status,
            'image_path' => 'waste-reports/test.jpg',
            'status' => $status,
        ]);
    }
}
