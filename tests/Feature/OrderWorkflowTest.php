<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    public function test_guest_cannot_complete_order(): void
    {
        $order = Order::factory()->create();

        $response = $this->put(route('orders.update', $order, absolute: false));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_order_completion_decrements_stock_and_is_idempotent(): void
    {
        $user = $this->createUser();

        $product = Product::factory()->create([
            'category_id' => $this->createCategory()->id,
            'unit_id' => $this->createUnit()->id,
            'quantity' => 12,
        ]);

        $order = Order::factory()->create([
            'order_status' => OrderStatus::PENDING,
        ]);

        OrderDetails::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unitcost' => 100,
            'total' => 400,
        ]);

        $firstCompletion = $this->actingAs($user)->put(route('orders.update', $order, absolute: false));

        $firstCompletion->assertRedirect(route('orders.complete', absolute: false));
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertSame(OrderStatus::COMPLETE, $order->fresh()->order_status);

        $secondCompletion = $this->actingAs($user)->put(route('orders.update', $order, absolute: false));

        $secondCompletion->assertRedirect(route('orders.complete', absolute: false));
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertSame(OrderStatus::COMPLETE, $order->fresh()->order_status);
    }
}
