<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_array_email_is_validation_error_instead_of_server_error(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => ['bad'], 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_throttle_preserves_retry_after_header(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'limit@example.test', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/v1/auth/login', ['email' => 'limit@example.test', 'password' => 'wrong'])
            ->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_pagination_rejects_non_scalar_or_negative_page(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('mobile')->plainTextToken);
        foreach (['page=-1', 'page[]=1', 'per_page=51', 'status=unknown'] as $query) {
            $this->getJson('/api/v1/reports?'.$query)->assertUnprocessable();
        }
    }

    public function test_password_change_revokes_mobile_tokens(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $user = User::factory()->create();
        $user->createToken('mobile');
        $this->actingAs($admin)->put('/admin/users/'.$user->id, [
            'name' => $user->name, 'email' => $user->email, 'role' => 'user',
            'password' => 'replacement-password', 'password_confirmation' => 'replacement-password',
        ])->assertRedirect();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_real_png_upload_persists_bytes_and_rejects_disguised_script(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->withToken($user->createToken('mobile')->plainTextToken);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9WQAAAAASUVORK5CYII=');
        $response = $this->post('/api/v1/reports', [
            'description' => str_repeat('a', 10000), 'latitude' => 0, 'longitude' => 0,
            'image' => UploadedFile::fake()->createWithContent('photo.png', $png),
            'user_id' => 999, 'status' => 'completed',
        ], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('data.status', 'pending');
        $report = WasteReport::findOrFail($response->json('data.id'));
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame($png, Storage::disk('public')->get($report->image_path));
        $temporary = tmpfile();
        fwrite($temporary, '<?php echo 1;');
        try {
            $this->post('/api/v1/reports', [
                'description' => 'invalid',
                'image' => new UploadedFile(stream_get_meta_data($temporary)['uri'], 'photo.jpg', 'image/jpeg', null, true),
            ], ['Accept' => 'application/json'])->assertUnprocessable();
        } finally {
            fclose($temporary);
        }
        $this->assertDatabaseCount('waste_reports', 1);
    }

    public function test_failed_admin_upload_does_not_create_report(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);

        $this->actingAs($admin)->post('/admin/reports', [
            'user_id' => $admin->id,
            'description' => 'Penyimpanan gagal',
            'image' => UploadedFile::fake()->create('photo.png', 10, 'image/png'),
        ])->assertStatus(500);
        $this->assertDatabaseCount('waste_reports', 0);
    }
}
