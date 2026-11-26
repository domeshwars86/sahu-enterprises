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
            ->where('businessConfig.name', 'SAHU ENTERPRISES')
            ->where('businessConfig.proprietor', 'Khuleshwar Sahu')
            ->where('businessConfig.phone', '+91 9131840194')
            ->where('businessConfig.officePhone', '+91 8982868291')
            ->where('businessConfig.whatsapp', '+91 9131840194')
            ->where('businessConfig.email', 'sahuenterprises0707@gmail.com')
            ->where('businessConfig.address', '327, 3rd Floor, Orbit Tower, Sunder Nagar')
            ->where('businessConfig.city', 'Raipur')
            ->where('businessConfig.state', 'Chhattisgarh')
            ->where('businessConfig.postalCode', '492001')
            ->where('businessConfig.directionsUrl', fn (string $url): bool => str_contains($url, 'google.com/maps/dir') && str_contains($url, 'destination=')));

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
