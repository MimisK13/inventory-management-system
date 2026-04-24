<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Tests\TestCase;

class PurchaseQuotationAuthRouteCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cart::destroy();
        Cart::instance('quotation')->destroy();
    }

    public function test_dashboard_and_profile_settings_routes_render_for_authenticated_user(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertViewIs('dashboard');
        $this->actingAs($user)->get(route('profile.settings', absolute: false))
            ->assertOk()
            ->assertViewIs('profile.settings');
    }

    public function test_verification_send_and_logout_routes_work(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationResponse = $this->actingAs($user)
            ->from(route('verification.notice', absolute: false))
            ->post(route('verification.send', absolute: false));

        $verificationResponse->assertRedirect(route('verification.notice', absolute: false));
        $verificationResponse->assertSessionHas('status', 'verification-link-sent');

        $logoutResponse = $this->actingAs($user)->post(route('logout', absolute: false));
        $logoutResponse->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_purchase_routes_cover_create_show_edit_report_and_delete(): void
    {
        $user = $this->createUser();
        $supplier = Supplier::factory()->create();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'code' => 'PUR001',
        ]);

        $this->actingAs($user)->get(route('purchases.create', absolute: false))
            ->assertOk()
            ->assertViewIs('purchases.create');
        $this->actingAs($user)->get(route('purchases.getPurchaseReport', absolute: false))
            ->assertOk()
            ->assertViewIs('purchases.report-purchase');

        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'total_amount' => 500,
            'created_by' => $user->id,
        ]);

        $purchase->details()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unitcost' => 120,
            'total' => 240,
        ]);

        $this->assertDatabaseHas('purchase_details', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $showResponse = $this->actingAs($user)->get(route('purchases.show', $purchase, absolute: false));
        $showResponse->assertOk();
        $showResponse->assertViewIs('purchases.show');

        $editResponse = $this->actingAs($user)->get(route('purchases.edit', $purchase, absolute: false));
        $editResponse->assertOk();
        $editResponse->assertViewIs('purchases.edit');

        $deleteResponse = $this->actingAs($user)->delete(route('purchases.delete', $purchase, absolute: false));
        $deleteResponse->assertRedirect(route('purchases.index', absolute: false));
        $this->assertDatabaseMissing('purchases', [
            'id' => $purchase->id,
        ]);
    }

    public function test_guest_cannot_access_purchase_store_route(): void
    {
        $response = $this->post(route('purchases.store', absolute: false), []);

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_quotation_routes_cover_index_create_store_and_destroy(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Quotation Product',
            'slug' => 'quotation-product',
            'code' => 'QTN001',
            'selling_price' => 200,
            'tax' => 10,
            'tax_type' => 1,
        ]);

        $this->actingAs($user)->get(route('quotations.index', absolute: false))
            ->assertOk()
            ->assertViewIs('quotations.index');
        $this->actingAs($user)->get(route('quotations.create', absolute: false))
            ->assertOk()
            ->assertViewIs('quotations.create');

        Cart::instance('quotation')->add($product->id, $product->name, 1, 200, 1, [
            'code' => $product->code,
            'unit_price' => 200,
            'sub_total' => 200,
            'product_discount' => 0,
            'product_discount_type' => 'fixed',
            'product_tax' => 20,
        ]);

        $storeResponse = $this->actingAs($user)->post(route('quotations.store', absolute: false), [
            'customer_id' => $customer->id,
            'reference' => 'QT-TEMP-0001',
            'tax_percentage' => 10,
            'discount_percentage' => 0,
            'shipping_amount' => 0,
            'total_amount' => 200,
            'status' => '0',
            'note' => 'new quotation',
            'date' => now()->toDateString(),
        ]);

        $storeResponse->assertRedirect(route('quotations.index', absolute: false));

        $quotation = Quotation::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('quotation_details', [
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('quotations.destroy', $quotation, absolute: false));
        $deleteResponse->assertRedirect(route('quotations.index', absolute: false));
        $this->assertDatabaseMissing('quotations', [
            'id' => $quotation->id,
        ]);
    }

    public function test_guest_is_redirected_on_quotation_show_edit_and_update_routes(): void
    {
        $quotation = Quotation::query()->create([
            'date' => now()->toDateString(),
            'reference' => 'QT-00001',
            'customer_id' => null,
            'customer_name' => 'Guest',
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 100,
            'status' => 0,
            'note' => null,
        ]);

        $this->get(route('quotations.show', $quotation, absolute: false))
            ->assertRedirect(route('login', absolute: false));
        $this->get(route('quotations.edit', $quotation, absolute: false))
            ->assertRedirect(route('login', absolute: false));
        $this->put(route('quotations.update', $quotation, absolute: false), [])
            ->assertRedirect(route('login', absolute: false));
    }
}
