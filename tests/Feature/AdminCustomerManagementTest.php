<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Customer;
use App\Models\EnquiryNote;
use App\Models\EnquiryStatusHistory;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_shows_scoped_history_and_records_preference_and_privacy_requests(): void
    {
        $role = app(DefaultAdminRoles::class)->sync();
        $admin = User::factory()->create(['admin_role_id' => $role->id, 'two_factor_enabled' => true, 'two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $customer = Customer::query()->create(['contact_fingerprint' => hash('sha256', 'customer-a')]);
        $enquiry = LoanEnquiry::factory()->create(['customer_id' => $customer->id, 'assigned_user_id' => $admin->id]);
        EnquiryNote::query()->create(['loan_enquiry_id' => $enquiry->id, 'user_id' => $admin->id, 'body' => 'Call after 4 PM']);
        EnquiryStatusHistory::query()->create(['loan_enquiry_id' => $enquiry->id, 'changed_by' => $admin->id, 'old_status' => 'new', 'new_status' => 'contacted', 'reason' => 'Spoke with customer']);
        $this->actingAs($admin)->withSession(['admin_mfa_verified_at' => now()->timestamp]);

        $this->get('/admin/customers/'.$customer->id)->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Show')
            ->where('customer.enquiries.0.notes.0.body', 'Call after 4 PM')
            ->where('customer.enquiries.0.statusHistory.0.newStatus', 'contacted'));
        $this->patch('/admin/customers/'.$customer->id.'/communication-preference', ['communication_preference' => 'email'])->assertRedirect();
        $this->post('/admin/customers/'.$customer->id.'/privacy-requests', ['request_type' => 'access'])->assertRedirect();

        $this->assertSame('email', $customer->fresh()->communication_preference);
        $this->assertNotNull($customer->fresh()->privacy_request_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.communication_preference_updated', 'entity_id' => $customer->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.privacy_request_recorded', 'entity_id' => $customer->id]);
    }

    public function test_customer_profile_hides_records_outside_agent_scope(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['dsa-agent'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'dsa-agent', 'permissions' => [...$definition['permissions'], 'customers.view']]);
        $agent = User::factory()->create(['admin_role_id' => $role->id]);
        $customer = Customer::query()->create(['contact_fingerprint' => hash('sha256', 'customer-b')]);
        LoanEnquiry::factory()->create(['customer_id' => $customer->id]);
        $this->actingAs($agent)->withSession(['admin_mfa_verified_at' => now()->timestamp]);

        $this->get('/admin/customers/'.$customer->id)->assertNotFound();
        $this->patch('/admin/customers/'.$customer->id.'/communication-preference', ['communication_preference' => 'email'])->assertForbidden();
    }
}
