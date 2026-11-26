<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\FollowUp;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_manager_dashboard_does_not_expose_customer_or_followup_records(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['content-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'content-manager', 'permissions' => $definition['permissions']]);
        $contentManager = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $enquiry = LoanEnquiry::factory()->create();
        FollowUp::query()->create(['loan_enquiry_id' => $enquiry->id, 'assigned_user_id' => $contentManager->id, 'created_by' => $contentManager->id, 'purpose' => 'Private callback', 'scheduled_at' => now()->addDay(), 'status' => 'pending']);

        $this->post('/admin/login', ['email' => $contentManager->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');
        $this->get('/admin')->assertInertia(fn ($page) => $page
            ->where('metrics.totalEnquiries', 0)
            ->where('metrics.pendingFollowUps', 0)
            ->where('recentEnquiries', [])
            ->where('upcomingFollowUps', []));
        $this->get('/admin/enquiries')->assertForbidden();
    }

    public function test_report_export_requires_export_permission_and_escapes_spreadsheet_formulas(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['sales-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'sales-manager', 'permissions' => $definition['permissions']]);
        $manager = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $agentRoleDefinition = app(DefaultAdminRoles::class)->definitions()['dsa-agent'];
        $agentRole = AdminRole::query()->create(['name' => $agentRoleDefinition['name'], 'slug' => 'dsa-agent', 'permissions' => $agentRoleDefinition['permissions']]);
        $unsafeAgent = User::factory()->create(['name' => '=HYPERLINK("https://bad.example")', 'admin_role_id' => $agentRole->id, 'team_manager_id' => $manager->id]);
        $enquiry = LoanEnquiry::factory()->create(['assigned_user_id' => $unsafeAgent->id, 'customer_name' => 'Private Customer']);

        $this->post('/admin/login', ['email' => $manager->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');
        $response = $this->get('/admin/reports/enquiries.csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Private Customer', $csv);
        $this->assertStringContainsString($enquiry->reference_number, $csv);
    }

    public function test_sales_manager_is_limited_to_team_and_unassigned_enquiries(): void
    {
        $managerDefinition = app(DefaultAdminRoles::class)->definitions()['sales-manager'];
        $managerRole = AdminRole::query()->create(['name' => $managerDefinition['name'], 'slug' => 'sales-manager', 'permissions' => $managerDefinition['permissions']]);
        $manager = User::factory()->create(['admin_role_id' => $managerRole->id, 'is_active' => true, 'password' => 'A-strong-password-123']);
        $agentDefinition = app(DefaultAdminRoles::class)->definitions()['dsa-agent'];
        $agentRole = AdminRole::query()->create(['name' => $agentDefinition['name'], 'slug' => 'dsa-agent', 'permissions' => $agentDefinition['permissions']]);
        $teamAgent = User::factory()->create(['admin_role_id' => $agentRole->id, 'team_manager_id' => $manager->id, 'is_active' => true]);
        $otherAgent = User::factory()->create(['admin_role_id' => $agentRole->id, 'is_active' => true]);
        $teamEnquiry = LoanEnquiry::factory()->create(['assigned_user_id' => $teamAgent->id]);
        $otherEnquiry = LoanEnquiry::factory()->create(['assigned_user_id' => $otherAgent->id]);
        $unassignedEnquiry = LoanEnquiry::factory()->create(['assigned_user_id' => null]);

        $this->post('/admin/login', ['email' => $manager->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');
        $expectedReferences = collect([$unassignedEnquiry->reference_number, $teamEnquiry->reference_number])->sort()->values()->all();
        $this->get('/admin/enquiries')->assertInertia(fn ($page) => $page
            ->has('enquiries.data', 2)
            ->where('enquiries.data', fn ($rows): bool => collect($rows)->pluck('reference')->sort()->values()->all() === $expectedReferences));
        $this->get('/admin/enquiries/'.$otherEnquiry->id)->assertForbidden();
        $this->patch('/admin/enquiries/'.$otherEnquiry->id.'/assignment', ['assigned_user_id' => $teamAgent->id])->assertForbidden();
    }
}
