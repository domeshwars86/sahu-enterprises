<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_create_edit_and_delete_custom_roles(): void
    {
        $role = app(DefaultAdminRoles::class)->sync();
        $superAdmin = User::factory()->create([
            'admin_role_id' => $role->id,
            'two_factor_enabled' => true,
            'two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
        ]);
        $this->actingAs($superAdmin)->withSession(['admin_mfa_verified_at' => now()->timestamp]);

        $this->post('/admin/roles', ['name' => 'Junior Agent', 'slug' => 'junior-agent', 'permissions' => ['dashboard.view', 'enquiries.view_assigned']])->assertRedirect();
        $customRole = AdminRole::query()->where('slug', 'junior-agent')->firstOrFail();
        $this->assertSame(['dashboard.view', 'enquiries.view_assigned'], $customRole->permissions);

        $this->patch('/admin/roles/'.$customRole->id, ['name' => 'Junior Agent', 'permissions' => ['dashboard.view', 'tasks.assigned']])->assertRedirect();
        $this->assertSame(['dashboard.view', 'tasks.assigned'], $customRole->fresh()->permissions);
        $this->delete('/admin/roles/'.$customRole->id)->assertRedirect();
        $this->assertDatabaseMissing('admin_roles', ['id' => $customRole->id]);
        $this->patch('/admin/roles/'.$role->id, ['name' => 'Super Admin', 'permissions' => []])->assertUnprocessable();
    }

    public function test_non_super_admin_cannot_manage_roles(): void
    {
        $definitions = app(DefaultAdminRoles::class)->definitions();
        $role = AdminRole::query()->create(['name' => $definitions['sales-manager']['name'], 'slug' => 'sales-manager', 'permissions' => $definitions['sales-manager']['permissions']]);
        $manager = User::factory()->create(['admin_role_id' => $role->id]);

        $this->actingAs($manager)->get('/admin/roles')->assertForbidden();
        $this->actingAs($manager)->post('/admin/roles', ['name' => 'Elevated', 'slug' => 'elevated', 'permissions' => ['users.manage']])->assertForbidden();
    }

    public function test_sync_command_refreshes_only_the_builtin_role_definitions(): void
    {
        $defaults = app(DefaultAdminRoles::class)->definitions();
        $builtIn = AdminRole::query()->create(['name' => 'Admin', 'slug' => 'admin', 'permissions' => ['dashboard.view']]);
        $custom = AdminRole::query()->create(['name' => 'Custom', 'slug' => 'custom', 'permissions' => ['reports.view']]);

        $this->artisan('crm:sync-admin-roles')->assertSuccessful();

        $this->assertSame($defaults['admin']['permissions'], $builtIn->fresh()->permissions);
        $this->assertSame(['reports.view'], $custom->fresh()->permissions);
    }
}
