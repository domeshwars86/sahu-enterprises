<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use App\Services\Admin\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_require_an_authenticated_admin_user(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/enquiries')->assertRedirect('/admin/login');
    }

    public function test_active_staff_can_sign_in_and_sign_out(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['dsa-agent'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'dsa-agent', 'permissions' => $definition['permissions']]);
        $user = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'A-strong-password-123'])
            ->assertRedirect('/admin')
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_inactive_staff_cannot_sign_in(): void
    {
        $role = app(DefaultAdminRoles::class)->sync();
        $user = User::factory()->create(['admin_role_id' => $role->id, 'is_active' => false, 'password' => 'A-strong-password-123']);

        $this->from('/admin/login')->post('/admin/login', ['email' => $user->email, 'password' => 'A-strong-password-123'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_privileged_account_must_enroll_mfa_before_dashboard_access(): void
    {
        $role = app(DefaultAdminRoles::class)->sync();
        $user = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'A-strong-password-123'])
            ->assertRedirect('/admin/security/two-factor/setup');
        $this->get('/admin')->assertRedirect('/admin/security/two-factor/setup');
        $this->get('/admin/security/two-factor/setup')->assertOk();
    }

    public function test_mfa_enrollment_shows_one_time_recovery_codes(): void
    {
        $role = app(DefaultAdminRoles::class)->sync();
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $user = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123', 'two_factor_secret' => $secret]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin/security/two-factor/setup');
        $code = app(Totp::class)->code($secret, intdiv(time(), 30));
        $this->post('/admin/security/two-factor/setup', ['code' => $code])->assertRedirect('/admin/security/two-factor/recovery-codes');
        $this->get('/admin/security/two-factor/recovery-codes')->assertInertia(fn ($page) => $page->component('Admin/RecoveryCodes')->has('codes', 8));
        $this->get('/admin/security/two-factor/recovery-codes')->assertNotFound();
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_mfa_recovery_code_can_only_be_used_once(): void
    {
        $role = app(DefaultAdminRoles::class)->sync();
        $user = User::factory()->create([
            'admin_role_id' => $role->id,
            'password' => 'A-strong-password-123',
            'two_factor_enabled' => true,
            'two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'two_factor_recovery_codes' => [Hash::make('BACKUPCODE1234')],
        ]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin/security/two-factor/challenge');
        $this->post('/admin/security/two-factor/challenge', ['code' => 'BACKUPCODE1234'])->assertRedirect('/admin');
        $this->assertSame([], $user->fresh()->two_factor_recovery_codes);
        $this->from('/admin/security/two-factor/challenge')
            ->post('/admin/security/two-factor/challenge', ['code' => 'BACKUPCODE1234'])
            ->assertRedirect('/admin/security/two-factor/challenge')
            ->assertSessionHasErrors('code');
    }

    public function test_staff_can_update_their_own_profile_and_password(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['content-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'content-manager', 'permissions' => $definition['permissions']]);
        $user = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->adminRole?->exists ?? false);

        $this->actingAs($user)->from('/admin/profile')->put('/admin/profile', ['name' => 'Updated Operator', 'email' => 'updated@example.test'])->assertRedirect('/admin/profile')->assertSessionHas('success', 'Profile updated.')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Operator', 'email' => 'updated@example.test']);
        $this->put('/admin/profile/password', [
            'current_password' => 'A-strong-password-123',
            'password' => 'Another-strong-password-456',
            'password_confirmation' => 'Another-strong-password-456',
        ])->assertRedirect();
        $this->assertTrue(password_verify('Another-strong-password-456', $user->fresh()->password));
    }
}
