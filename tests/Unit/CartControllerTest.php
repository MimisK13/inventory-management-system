<?php

namespace Tests\Unit;

use App\Http\Controllers\CartController;
use Illuminate\Http\Request;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    public function test_add_method_throws_for_invalid_cart_payload(): void
    {
        $this->expectException(\Throwable::class);

        $controller = new CartController;
        $controller->add(Request::create('/cart/add', 'POST'));
    }

    public function test_update_and_delete_methods_return_null(): void
    {
        $controller = new CartController;

        $this->assertNull($controller->update());
        $this->assertNull($controller->delete());
    }
}
