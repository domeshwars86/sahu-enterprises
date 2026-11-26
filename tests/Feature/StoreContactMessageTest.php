<?php

namespace Tests\Feature;

use App\Mail\NewContactMessage;
use App\Models\AdminRole;
use App\Models\BusinessSetting;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use App\Services\Admin\DefaultAdminRoles;
use App\Services\Contact\StoreContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_contact_message_is_encrypted_persisted_and_notified(): void
    {
        Mail::fake();
        config()->set('services.sahu.enquiry_to', 'team@example.com');

        $this->postJson('/contact', $this->validMessage())
            ->assertCreated()
            ->assertJsonPath('message', 'Your message has been received.')
            ->assertJsonPath('reference_number', fn (string $reference): bool => preg_match('/^SAH-C-\d{8}-[A-Z0-9]{8}$/', $reference) === 1);

        $message = ContactMessage::query()->sole();
        $this->assertSame('Asha Patel', $message->name);
        $this->assertSame('9876543210', $message->mobile_number);
        $this->assertSame('Pune vehicle finance question', $message->message);
        $this->assertTrue($message->privacy_consent);
        $this->assertNotSame('Asha Patel', $message->getRawOriginal('name'));
        $this->assertNotSame('9876543210', $message->getRawOriginal('mobile_number'));

        Mail::assertSent(NewContactMessage::class, fn (NewContactMessage $mail): bool => $mail->hasTo('team@example.com'));
    }

    public function test_duplicate_contact_submission_returns_the_original_reference(): void
    {
        Mail::fake();
        $payload = $this->validMessage();

        $first = $this->postJson('/contact', $payload)->assertCreated()->json('reference_number');
        $second = $this->postJson('/contact', $payload)->assertOk()->json('reference_number');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_company_notifications_use_the_editable_business_email_when_no_override_is_configured(): void
    {
        Mail::fake();
        config()->set('services.sahu.enquiry_to', null);
        BusinessSetting::query()->create(['key' => 'email', 'value' => 'sahuenterprises0707@gmail.com']);

        $this->postJson('/contact', $this->validMessage())->assertCreated();

        Mail::assertSent(NewContactMessage::class, fn (NewContactMessage $mail): bool => $mail->hasTo('sahuenterprises0707@gmail.com'));
    }

    public function test_contact_form_requires_privacy_consent_and_rejects_honeypot_submissions(): void
    {
        Mail::fake();

        $this->postJson('/contact', array_merge($this->validMessage(), ['privacy_consent' => false]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('privacy_consent');

        $this->postJson('/contact', array_merge($this->validMessage(), ['website' => 'https://spam.example']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_new_contact_request_notifies_support_only_once(): void
    {
        Mail::fake();
        Notification::fake();
        $definition = app(DefaultAdminRoles::class)->definitions()['customer-support'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'customer-support', 'permissions' => $definition['permissions']]);
        $support = User::factory()->create(['admin_role_id' => $role->id, 'is_active' => true]);
        $payload = $this->validMessage();

        $this->postJson('/contact', $payload)->assertCreated();
        $this->postJson('/contact', $payload)->assertOk();

        Notification::assertSentToTimes($support, NewContactMessageNotification::class, 1);
    }

    public function test_contact_message_inbox_masks_sensitive_fields_without_permission(): void
    {
        $definition = app(DefaultAdminRoles::class)->definitions()['sales-manager'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'sales-manager', 'permissions' => $definition['permissions']]);
        $manager = User::factory()->create(['admin_role_id' => $role->id, 'is_active' => true]);
        $message = app(StoreContactMessage::class)->store($this->validMessage());

        $this->actingAs($manager)->get('/admin/contact-messages')->assertInertia(fn ($page) => $page
            ->where('messages.data.0.name', 'Asha …')
            ->where('messages.data.0.email', 'a•••@example.com')
            ->where('messages.data.0.message', null));
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id]);
    }

    /** @return array<string, mixed> */
    private function validMessage(): array
    {
        return [
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Asha Patel',
            'mobile_number' => '9876543210',
            'email' => 'asha@example.com',
            'subject' => 'loan_assistance',
            'message' => 'Pune vehicle finance question',
            'privacy_consent' => true,
            'website' => '',
        ];
    }
}
