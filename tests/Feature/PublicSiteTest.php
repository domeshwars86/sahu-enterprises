<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_verified_public_learning_pages_render_and_receive_only_enabled_loan_products(): void
    {
        $this->get('/videos')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->where('path', '/videos')
            ->has('loanProducts', 5)
            ->where('businessConfig.name', 'Sahu Enterprises'));

        $this->get('/financial-resources')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Site')->where('path', '/financial-resources'));
    }

    public function test_unknown_page_returns_a_real_not_found_response(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }

    public function test_calculator_suite_routes_render_and_reject_unknown_calculator_names(): void
    {
        foreach ([
            '/calculators', '/calculators/personal-loan-emi', '/calculators/home-loan-emi',
            '/calculators/business-loan-emi', '/calculators/car-loan-emi', '/calculators/two-wheeler-loan',
            '/calculators/loan-against-property', '/calculators/balance-transfer', '/calculators/down-payment',
            '/calculators/tenure-comparison', '/calculators/amortization',
        ] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/calculators/not-a-calculator')->assertNotFound();
    }
}
