<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\CrmTask;
use App\Models\EducationalArticle;
use App\Models\EducationalFaq;
use App\Models\EducationalVideo;
use App\Models\FollowUp;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Notifications\CrmReminderNotification;
use App\Services\Admin\DefaultAdminRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminContentSettingsNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_manager_can_publish_articles_and_only_valid_youtube_links(): void
    {
        $staff = $this->staffWithRole('content-manager');

        $this->actingAs($staff)
            ->post('/admin/content/articles', ['title' => 'Money Basics', 'slug' => '', 'body' => 'Learn how borrowing works.', 'status' => 'published'])
            ->assertRedirect();

        $article = EducationalArticle::query()->firstOrFail();
        $this->assertSame('money-basics', $article->slug);
        $this->get('/education/money-basics')->assertInertia(fn ($page) => $page->where('managedArticle.title', 'Money Basics'));

        $this->actingAs($staff)
            ->post('/admin/content/videos', ['title' => 'Loan basics', 'url' => 'https://www.youtube.com/watch?v=abc123', 'is_visible' => true, 'is_featured' => false])
            ->assertRedirect();
        $this->assertDatabaseHas('educational_videos', ['title' => 'Loan basics', 'is_visible' => true]);

        $this->actingAs($staff)
            ->post('/admin/content/videos', ['title' => 'Bad link', 'url' => 'https://www.youtube.com.evil.example/watch?v=abc123', 'is_visible' => true, 'is_featured' => false])
            ->assertSessionHasErrors('url');
        $this->assertSame(1, EducationalVideo::query()->count());
    }

    public function test_content_manager_can_publish_and_update_public_faqs(): void
    {
        $staff = $this->staffWithRole('content-manager');

        $this->actingAs($staff)->post('/admin/content/faqs', ['question' => 'Can you guarantee approval?', 'answer' => 'No. The lender decides.', 'display_order' => 2, 'is_visible' => true])->assertRedirect();
        $faq = EducationalFaq::query()->firstOrFail();
        $this->get('/faq')->assertInertia(fn ($page) => $page->where('publishedFaqs.0.question', 'Can you guarantee approval?'));

        $this->actingAs($staff)->patch('/admin/content/faqs/'.$faq->id, ['is_visible' => false])->assertRedirect();
        $this->get('/faq')->assertInertia(fn ($page) => $page->where('publishedFaqs', []));
    }

    public function test_settings_update_and_notifications_are_scoped_to_the_signed_in_user(): void
    {
        $admin = $this->staffWithRole('admin', true);
        $otherStaff = $this->staffWithRole('content-manager');

        $this->actingAs($admin)->withSession(['admin_mfa_verified_at' => now()->timestamp])
            ->put('/admin/settings', ['name' => 'SAHU ENTERPRISES', 'proprietor' => 'Khuleshwar Sahu', 'phone' => '+91 9131840194', 'office_phone' => '+91 8982868291', 'whatsapp' => '+91 9131840194', 'email' => 'sahuenterprises0707@gmail.com', 'address' => '327, 3rd Floor, Orbit Tower, Sunder Nagar', 'city' => 'Raipur', 'state' => 'Chhattisgarh', 'postal_code' => '492001', 'new_enquiry_notifications_enabled' => true, 'contact_notifications_enabled' => true, 'news_sync_enabled' => true, 'crm_reminder_email_enabled' => false])
            ->assertRedirect();
        $this->assertDatabaseHas('business_settings', ['key' => 'name', 'value' => 'SAHU ENTERPRISES']);
        $this->assertDatabaseHas('business_settings', ['key' => 'email', 'value' => 'sahuenterprises0707@gmail.com']);
        $this->assertDatabaseHas('business_settings', ['key' => 'proprietor', 'value' => 'Khuleshwar Sahu']);
        $this->assertDatabaseHas('business_settings', ['key' => 'office_phone', 'value' => '+91 8982868291']);
        $this->assertDatabaseHas('business_settings', ['key' => 'whatsapp', 'value' => '+91 9131840194']);

        $admin->notify(new CrmReminderNotification(['title' => 'Your task is due', 'message' => 'Call the customer.', 'url' => '/admin/tasks']));
        $notificationId = $admin->notifications()->firstOrFail()->id;

        $this->actingAs($otherStaff)->patch('/admin/notifications/'.$notificationId.'/read')->assertNotFound();
        $this->assertNull($admin->notifications()->firstOrFail()->read_at);
        $this->actingAs($admin)->withSession(['admin_mfa_verified_at' => now()->timestamp])
            ->patch('/admin/notifications/'.$notificationId.'/read')->assertRedirect();
        $this->assertNotNull($admin->notifications()->firstOrFail()->fresh()->read_at);
    }

    public function test_due_followup_and_task_reminders_are_sent_once(): void
    {
        Notification::fake();
        $staff = $this->staffWithRole('content-manager');
        $enquiry = LoanEnquiry::factory()->create();
        FollowUp::query()->create(['loan_enquiry_id' => $enquiry->id, 'assigned_user_id' => $staff->id, 'created_by' => $staff->id, 'purpose' => 'Call back', 'scheduled_at' => now()->subMinute(), 'status' => 'pending']);
        CrmTask::query()->create(['loan_enquiry_id' => $enquiry->id, 'assigned_user_id' => $staff->id, 'created_by' => $staff->id, 'title' => 'Review documents', 'due_at' => now()->subMinute(), 'status' => 'pending']);
        CrmTask::query()->create(['loan_enquiry_id' => $enquiry->id, 'assigned_user_id' => $staff->id, 'created_by' => $staff->id, 'title' => 'Confirm appointment', 'due_at' => now()->subMinute(), 'status' => 'in_progress']);

        $this->artisan('crm:notify-due')->assertSuccessful();
        $this->artisan('crm:notify-due')->assertSuccessful();

        Notification::assertSentToTimes($staff, CrmReminderNotification::class, 3);
    }

    private function staffWithRole(string $slug, bool $withMfa = false): User
    {
        $definition = app(DefaultAdminRoles::class)->definitions()[$slug];
        $role = AdminRole::query()->firstOrCreate(['slug' => $slug], ['name' => $definition['name'], 'permissions' => $definition['permissions']]);

        return User::factory()->create([
            'admin_role_id' => $role->id,
            'is_active' => true,
            'two_factor_enabled' => $withMfa,
            'two_factor_secret' => $withMfa ? 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ' : null,
        ]);
    }
}
