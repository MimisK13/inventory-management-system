<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;
use Tests\TestCase;

class PosInvoiceOrderDueRouteCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cart::destroy();
        Cart::instance('order')->destroy();
        Cart::instance('quotation')->destroy();
    }

    public function test_pos_routes_cover_index_and_add_cart_item(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'POS Product',
            'slug' => 'pos-product',
            'code' => 'POS001',
            'selling_price' => 1500,
        ]);

        $this->actingAs($user)->get(route('pos.index', absolute: false))
            ->assertOk()
            ->assertViewIs('pos.index');

        $addResponse = $this->actingAs($user)->post(route('pos.addCartItem', absolute: false), [
            'id' => $product->id,
            'name' => $product->name,
            'selling_price' => $product->selling_price,
        ]);

        $addResponse->assertRedirect();
        $addResponse->assertSessionHas('success');
        $this->assertCount(1, Cart::content());

    }

    public function test_guest_cannot_access_pos_update_and_delete_routes(): void
    {
        $this->post(route('pos.updateCartItem', ['rowId' => 'test-row-id'], absolute: false), [
            'quantity' => 2,
        ])->assertRedirect(route('login', absolute: false));

        $this->delete(route('pos.deleteCartItem', ['rowId' => 'test-row-id'], absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_invoice_create_route_renders_invoice_page(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        $response = $this->actingAs($user)->post(route('invoice.create', absolute: false), [
            'customer_id' => $customer->id,
        ]);

        $response->assertOk();
        $response->assertViewIs('invoices.index');
        $response->assertSee($customer->name);
    }

    public function test_order_routes_cover_index_create_show_and_download_invoice(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Order Product',
            'slug' => 'order-product',
            'code' => 'ORD001',
            'quantity' => 50,
            'selling_price' => 100,
        ]);

        $this->actingAs($user)->get(route('orders.index', absolute: false))
            ->assertOk()
            ->assertViewIs('orders.index');
        $this->actingAs($user)->get(route('orders.create', absolute: false))
            ->assertOk()
            ->assertViewIs('orders.create');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => OrderStatus::PENDING,
            'invoice_no' => 'INV-ROUTE-1001',
            'payment_type' => 'cash',
        ]);

        OrderDetails::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unitcost' => 100,
            'total' => 200,
        ]);

        $showResponse = $this->actingAs($user)->get(route('orders.show', $order, absolute: false));

        $showResponse->assertOk();
        $showResponse->assertViewIs('orders.show');
        $showResponse->assertSee($order->invoice_no);

        $downloadResponse = $this->actingAs($user)->get(route('order.downloadInvoice', ['order_id' => $order->id], absolute: false));

        $downloadResponse->assertOk();
        $downloadResponse->assertViewIs('orders.print-invoice');
        $downloadResponse->assertSee($order->invoice_no);
    }

    public function test_guest_cannot_access_order_store_route(): void
    {
        $response = $this->post(route('orders.store', absolute: false), []);

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_due_update_route_validates_and_updates_order_due_values(): void
    {
        $user = $this->createUser();
        $order = Order::factory()->create([
            'order_status' => OrderStatus::PENDING,
            'pay' => 50,
            'due' => 100,
        ]);

        $invalidResponse = $this->actingAs($user)->put(route('due.update', $order, absolute: false), []);
        $invalidResponse->assertSessionHasErrors('pay');

        $response = $this->actingAs($user)->put(route('due.update', $order, absolute: false), [
            'pay' => 30,
        ]);

        $response->assertRedirect(route('due.index', absolute: false));
        $this->assertSame(80, $order->fresh()->pay);
        $this->assertSame(70, $order->fresh()->due);
    }
}
