<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\LoanProduct;
use App\Models\User;
use App\Services\Admin\DefaultAdminRoles;
use App\Services\Admin\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_product_visibility_controls_public_catalogue_and_route(): void
    {
        $this->artisan('crm:sync-products')->assertSuccessful();
        $product = LoanProduct::query()->where('slug', 'loans/car-loan')->firstOrFail();
        $definition = app(DefaultAdminRoles::class)->definitions()['admin'];
        $role = AdminRole::query()->create(['name' => $definition['name'], 'slug' => 'admin', 'permissions' => $definition['permissions']]);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $staff = User::factory()->create([
            'admin_role_id' => $role->id,
            'password' => 'A-strong-password-123',
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);

        $this->post('/admin/login', ['email' => $staff->email, 'password' => 'A-strong-password-123'])
            ->assertRedirect('/admin/security/two-factor/challenge');
        $code = app(Totp::class)->code($secret, intdiv(time(), 30));
        $this->post('/admin/security/two-factor/challenge', ['code' => $code])->assertRedirect('/admin');

        $this->patch('/admin/products/'.$product->id, ['is_enabled' => false, 'is_featured' => false, 'description' => $product->description])->assertRedirect();
        $this->assertDatabaseHas('loan_products', ['id' => $product->id, 'is_enabled' => false]);
        $this->get('/')->assertInertia(fn ($page) => $page
            ->component('Site')
            ->where('loanProducts', fn ($products): bool => ! collect($products)->contains('slug', '/'.$product->slug)));
        $this->get('/loans/car-loan')->assertNotFound();
    }
}
