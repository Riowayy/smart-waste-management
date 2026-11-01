<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_regular_user_is_forbidden_from_user_management(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_admin_can_list_and_view_users_without_exposing_passwords(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'name' => 'Eco Member',
            'email' => 'member@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Eco Member')
            ->assertSee('member@example.test')
            ->assertSee('Manajemen Pengguna');

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Eco Member')
            ->assertSee('Informasi password tidak ditampilkan.')
            ->assertDontSee($user->password);
    }

    public function test_user_list_shows_database_summary_and_keeps_role_filter_in_pagination(): void
    {
        $admin = $this->admin();
        User::factory()->count(16)->create(['name' => 'Eco Member']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'Eco', 'role' => 'user']))
            ->assertOk()
            ->assertSee('Total Pengguna')
            ->assertSee('Admin')
            ->assertSee('User')
            ->assertSee('17')
            ->assertSee('16')
            ->assertSee('Tambah Pengguna')
            ->assertSee('page=2')
            ->assertSee('search=Eco')
            ->assertSee('role=user');
    }

    public function test_admin_cannot_accidentally_change_the_role_of_the_account_in_use(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $admin))
            ->assertOk()
            ->assertSee('Role akun yang sedang digunakan tidak dapat diubah.', false)
            ->assertSee('name="role" value="admin"', false)
            ->assertSee('name="role_display"', false)
            ->assertSee('disabled', false);
    }

    public function test_admin_can_create_user_with_default_user_role_and_hashed_password(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'New Eco User',
                'email' => 'new-user@example.test',
                'password' => 'Secure-Password-123!',
                'password_confirmation' => 'Secure-Password-123!',
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'new-user@example.test')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertTrue(Hash::check('Secure-Password-123!', $user->password));
        $this->assertNotSame('Secure-Password-123!', $user->password);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Secure-Password-123!',
        ])->assertRedirect(route('user.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_assign_a_role_explicitly_and_update_user_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Appointed Admin',
            'email' => 'appointed-admin@example.test',
            'password' => 'Secure-Password-123!',
            'password_confirmation' => 'Secure-Password-123!',
            'role' => 'admin',
        ])->assertRedirect();

        $target = User::query()->where('email', 'appointed-admin@example.test')->firstOrFail();
        $this->assertSame('admin', $target->role);

        $this->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'user',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.show', $target));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'user']);
    }

    public function test_user_cannot_create_an_account_or_change_their_own_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'Forged Admin',
            'email' => 'forged-admin@example.test',
            'password' => 'Secure-Password-123!',
            'password_confirmation' => 'Secure-Password-123!',
            'role' => 'admin',
        ])->assertForbidden();

        $this->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'forged-admin@example.test']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user']);
    }

    public function test_user_management_rejects_duplicate_email_and_weak_password(): void
    {
        $this->admin();
        $existing = User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs(User::query()->where('role', 'admin')->firstOrFail())
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate',
                'email' => $existing->email,
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_cannot_delete_or_demote_the_last_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.users.show', $admin))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHasErrors(['user' => 'Admin terakhir tidak dapat dihapus.']);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);

        $this->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'user',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_can_delete_an_account_without_reports(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_account_with_reports_cannot_be_deleted_to_preserve_report_data(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $report = $user->wasteReports()->create([
            'description' => 'Laporan tetap disimpan',
            'image_path' => 'waste-reports/preserve.jpg',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $user))
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('waste_reports', ['id' => $report->id, 'user_id' => $user->id]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        return $admin->refresh();
    }
}
