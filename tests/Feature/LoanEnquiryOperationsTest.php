<?php

namespace Tests\Feature;

use App\Models\LoanEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanEnquiryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_enquiry_list_shows_reference_and_status_without_customer_details(): void
    {
        $enquiry = LoanEnquiry::factory()->create(['customer_name' => 'Private Customer']);

        $this->artisan('enquiries:list')
            ->expectsOutputToContain($enquiry->reference_number)
            ->doesntExpectOutputToContain('Private Customer')
            ->assertSuccessful();
    }

    public function test_enquiry_status_can_be_updated_and_invalid_status_is_rejected(): void
    {
        $enquiry = LoanEnquiry::factory()->create();

        $this->artisan('enquiries:status', ['reference' => $enquiry->reference_number, 'status' => 'contacted'])
            ->assertSuccessful();

        $this->assertDatabaseHas('loan_enquiries', ['id' => $enquiry->id, 'status' => 'contacted']);

        $this->artisan('enquiries:status', ['reference' => $enquiry->reference_number, 'status' => 'not-a-status'])
            ->assertFailed();
    }

    public function test_enquiry_detail_command_decrypts_a_requested_record(): void
    {
        $enquiry = LoanEnquiry::factory()->create(['customer_name' => 'Private Customer']);

        $this->artisan('enquiries:show', ['reference' => $enquiry->reference_number])
            ->expectsOutputToContain('Private Customer')
            ->assertSuccessful();
    }
}
