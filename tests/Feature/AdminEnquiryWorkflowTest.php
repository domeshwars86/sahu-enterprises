<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\CrmTask;
use App\Models\LoanApplication;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEnquiryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_agent_only_sees_assigned_enquiries_and_contact_is_masked(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['dsa-agent'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'dsa-agent', 'permissions' => $definition['permissions']]);
        $agent = User::factory()->create(['admin_role_id' => $role->id]);
        $this->assertTrue($agent->fresh()->is_active);
        $this->assertSame($role->id, $agent->fresh()->adminRole?->id);
        $own = LoanEnquiry::factory()->create(['assigned_user_id' => $agent->id]);
        LoanEnquiry::factory()->create();
        $agent->forceFill(['password' => 'A-strong-password-123'])->save();
        $this->post('/admin/login', ['email' => $agent->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');

        $this->get('/admin/enquiries')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Enquiries/Index')
            ->has('enquiries.data', 1)
            ->where('enquiries.data.0.reference', $own->reference_number)
            ->where('enquiries.data.0.mobile', '••••••3210'));
        $this->get('/admin/enquiries/'.$own->id)->assertOk();
    }

    public function test_status_note_followup_and_existing_cli_share_enquiry_records(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['sales-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'sales-manager', 'permissions' => $definition['permissions']]);
        $user = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $enquiry = LoanEnquiry::factory()->create();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');

        $this->patch('/admin/enquiries/'.$enquiry->id.'/status', ['status' => 'qualified', 'reason' => 'Customer confirmed requirements'])->assertRedirect();
        $this->assertDatabaseHas('enquiry_status_histories', ['loan_enquiry_id' => $enquiry->id, 'old_status' => 'new', 'new_status' => 'qualified']);
        $this->post('/admin/enquiries/'.$enquiry->id.'/notes', ['body' => 'Needs a callback after 4 PM'])->assertRedirect();
        $this->assertSame('Needs a callback after 4 PM', $enquiry->fresh()->notes()->firstOrFail()->body);
        $this->post('/admin/enquiries/'.$enquiry->id.'/follow-ups', ['purpose' => 'Collect documents', 'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'), 'priority' => 'high'])->assertRedirect();
        $this->assertDatabaseHas('follow_ups', ['loan_enquiry_id' => $enquiry->id, 'purpose' => 'Collect documents', 'status' => 'pending']);
        $this->artisan('enquiries:status', ['reference' => $enquiry->reference_number, 'status' => 'contacted'])->assertSuccessful();
        $this->assertSame('contacted', $enquiry->fresh()->status);
    }

    public function test_agent_cannot_update_an_unassigned_enquiry(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['dsa-agent'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'dsa-agent', 'permissions' => $definition['permissions']]);
        $agent = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $enquiry = LoanEnquiry::factory()->create();
        $this->post('/admin/login', ['email' => $agent->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');

        $this->patch('/admin/enquiries/'.$enquiry->id.'/status', ['status' => 'qualified'])->assertForbidden();
        $this->assertSame('new', $enquiry->fresh()->status);
    }

    public function test_staff_task_management_persists_and_completes_assigned_tasks(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['sales-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'sales-manager', 'permissions' => $definition['permissions']]);
        $manager = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $enquiry = LoanEnquiry::factory()->create();
        $this->post('/admin/login', ['email' => $manager->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');

        $this->post('/admin/tasks', ['loan_enquiry_id' => $enquiry->id, 'title' => 'Review income documents', 'description' => 'Customer supplied additional detail', 'due_at' => now()->addDay()->format('Y-m-d H:i:s'), 'priority' => 'high'])->assertRedirect();
        $task = CrmTask::query()->firstOrFail();
        $this->assertDatabaseMissing('crm_tasks', ['id' => $task->id, 'description' => 'Customer supplied additional detail']);
        $this->get('/admin/tasks')->assertOk();
        $this->patch('/admin/tasks/'.$task->id, ['status' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('crm_tasks', ['id' => $task->id, 'status' => 'completed']);
    }

    public function test_application_is_created_only_after_a_staff_recorded_submission_workflow_and_stage_changes_are_audited(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['sales-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'sales-manager', 'permissions' => $definition['permissions']]);
        $manager = User::factory()->create(['admin_role_id' => $role->id, 'password' => 'A-strong-password-123']);
        $enquiry = LoanEnquiry::factory()->create();
        $this->post('/admin/login', ['email' => $manager->email, 'password' => 'A-strong-password-123'])->assertRedirect('/admin');

        $this->post('/admin/applications', ['loan_enquiry_id' => $enquiry->id])->assertStatus(422);
        $this->assertDatabaseCount('loan_applications', 0);

        $enquiry->update(['status' => 'ready_for_submission']);
        $this->post('/admin/applications', ['loan_enquiry_id' => $enquiry->id, 'lender_name' => 'Staff-recorded lender'])->assertRedirect();
        $application = LoanApplication::query()->firstOrFail();
        $this->assertDatabaseHas('loan_application_status_histories', ['loan_application_id' => $application->id, 'new_stage' => 'preparation']);
        $this->patch('/admin/applications/'.$application->id.'/stage', ['stage' => 'submitted'])->assertStatus(422);
        $this->patch('/admin/applications/'.$application->id.'/stage', ['stage' => 'submitted', 'reason' => 'Staff confirmed the application was submitted'])->assertRedirect();
        $this->assertDatabaseHas('loan_application_status_histories', ['loan_application_id' => $application->id, 'old_stage' => 'preparation', 'new_stage' => 'submitted']);
    }
}
