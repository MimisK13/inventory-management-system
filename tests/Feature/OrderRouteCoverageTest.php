<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Tests\TestCase;

class OrderRouteCoverageTest extends TestCase
{
    public function test_pending_orders_route_shows_only_pending_orders(): void
    {
        $user = $this->createUser();

        $pendingOrder = Order::factory()->create([
            'order_status' => OrderStatus::PENDING,
            'invoice_no' => 'PENDING-1001',
        ]);
        $completeOrder = Order::factory()->create([
            'order_status' => OrderStatus::COMPLETE,
            'invoice_no' => 'COMPLETE-1002',
        ]);

        $response = $this->actingAs($user)->get(route('orders.pending', absolute: false));

        $response->assertOk();
        $response->assertViewIs('orders.pending-orders');
        $response->assertSee($pendingOrder->invoice_no);
        $response->assertDontSee($completeOrder->invoice_no);
    }

    public function test_complete_orders_route_shows_only_completed_orders(): void
    {
        $user = $this->createUser();

        $completeOrder = Order::factory()->create([
            'order_status' => OrderStatus::COMPLETE,
            'invoice_no' => 'COMPLETE-2001',
        ]);
        $pendingOrder = Order::factory()->create([
            'order_status' => OrderStatus::PENDING,
            'invoice_no' => 'PENDING-2002',
        ]);

        $response = $this->actingAs($user)->get(route('orders.complete', absolute: false));

        $response->assertOk();
        $response->assertViewIs('orders.complete-orders');
        $response->assertSee($completeOrder->invoice_no);
        $response->assertDontSee($pendingOrder->invoice_no);
    }

    public function test_due_index_route_shows_only_orders_with_due_amount(): void
    {
        $user = $this->createUser();

        $dueOrder = Order::factory()->create([
            'invoice_no' => 'DUE-3001',
            'due' => 500,
        ]);
        $fullyPaidOrder = Order::factory()->create([
            'invoice_no' => 'PAID-3002',
            'due' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('due.index', absolute: false));

        $response->assertOk();
        $response->assertViewIs('due.index');
        $response->assertSee($dueOrder->invoice_no);
        $response->assertDontSee($fullyPaidOrder->invoice_no);
    }

    public function test_due_show_route_renders_selected_order_details_page(): void
    {
        $user = $this->createUser();

        $order = Order::factory()->create([
            'invoice_no' => 'DUE-SHOW-4001',
            'due' => 200,
        ]);

        $response = $this->actingAs($user)->get(route('due.show', $order, absolute: false));

        $response->assertOk();
        $response->assertViewIs('due.show');
        $response->assertSee('DUE-SHOW-4001');
        $response->assertSee($order->customer->name);
    }

    public function test_due_edit_route_renders_selected_order_and_customers(): void
    {
        $user = $this->createUser();

        $order = Order::factory()->create([
            'invoice_no' => 'DUE-EDIT-5001',
            'due' => 350,
        ]);

        $otherCustomer = Customer::factory()->create([
            'name' => 'Extra Customer For Due Edit',
        ]);

        $response = $this->actingAs($user)->get(route('due.edit', $order, absolute: false));

        $response->assertOk();
        $response->assertViewIs('due.edit');
        $response->assertSee('DUE-EDIT-5001');
        $response->assertSee($order->customer->name);
        $response->assertSee($otherCustomer->name);
    }
}
