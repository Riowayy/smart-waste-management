<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'member@example.test']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('user.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        $user = User::factory()->create(['email' => 'member@example.test']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_creates_an_authenticated_user(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Eco User',
            'email' => 'new@example.test',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ]);

        $response->assertRedirect(route('user.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.test',
            'role' => 'user',
        ]);
    }

    public function test_registration_cannot_assign_admin_role(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Untrusted Role',
            'email' => 'role@example.test',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
            'role' => 'admin',
        ])->assertRedirect(route('user.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'role@example.test',
            'role' => 'user',
        ]);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_user_is_forbidden_from_admin_dashboard(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_access_user_dashboard(): void
    {
        $user = User::factory()->create()->refresh();

        $this->actingAs($user)->get(route('user.dashboard'))->assertOk();
    }

    public function test_user_dashboard_shows_only_the_authenticated_users_reports(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->wasteReports()->create([
            'description' => 'Laporan milik saya',
            'image_path' => 'reports/mine.jpg',
        ]);
        $otherUser->wasteReports()->create([
            'description' => 'Laporan milik pengguna lain',
            'image_path' => 'reports/other.jpg',
        ]);

        $this->actingAs($user->refresh())
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Laporan milik saya')
            ->assertDontSee('Laporan milik pengguna lain');
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['private_marker' => 'secret'])
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('private_marker');

        $this->assertGuest();
    }
}
