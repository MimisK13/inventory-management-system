<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProductCart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Gloudemans\Shoppingcart\Facades\Cart;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCartTest extends TestCase
{
    private function createProductForCart(array $overrides = []): Product
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create(['short_code' => 'pc']);

        return Product::query()->create(array_merge([
            'name' => 'Cart Product',
            'slug' => 'cart-product-'.uniqid(),
            'code' => 'CP-'.fake()->numerify('###'),
            'quantity' => 20,
            'buying_price' => 8000,
            'selling_price' => 12000,
            'quantity_alert' => 5,
            'tax' => 10,
            'tax_type' => 1,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cart::instance('sale')->destroy();
    }

    public function test_product_cart_mounts_with_default_values_and_renders(): void
    {
        Livewire::test(ProductCart::class, ['cartInstance' => 'sale'])
            ->assertStatus(200)
            ->assertSet('global_discount', 0)
            ->assertSet('global_tax', 0)
            ->assertSet('shipping', 0.0);
    }

    public function test_product_selected_adds_item_and_prevents_duplicates(): void
    {
        $product = $this->createProductForCart();

        $payload = [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'quantity' => $product->quantity,
            'unit_id' => $product->unit_id,
            'selling_price' => $product->selling_price,
            'tax' => $product->tax,
            'tax_type' => $product->tax_type->value,
        ];

        $component = Livewire::test(ProductCart::class, ['cartInstance' => 'sale'])
            ->call('productSelected', $payload);

        $this->assertSame(1, (int) Cart::instance('sale')->count());

        $component->call('productSelected', $payload);
        $this->assertSame(1, (int) Cart::instance('sale')->count());
    }

    public function test_update_quantity_blocks_when_requested_quantity_exceeds_stock(): void
    {
        $product = $this->createProductForCart(['quantity' => 1]);

        $payload = [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'quantity' => $product->quantity,
            'unit_id' => $product->unit_id,
            'selling_price' => $product->selling_price,
            'tax' => $product->tax,
            'tax_type' => $product->tax_type->value,
        ];

        $component = Livewire::test(ProductCart::class, ['cartInstance' => 'sale'])
            ->call('productSelected', $payload);

        $rowId = Cart::instance('sale')->content()->first()->rowId;

        $component->set("quantity.{$product->id}", 5)
            ->call('updateQuantity', $rowId, $product->id);

        $this->assertSame(1, (int) Cart::instance('sale')->get($rowId)->qty);
    }

    public function test_calculate_supports_tax_modes(): void
    {
        $component = Livewire::test(ProductCart::class, ['cartInstance' => 'sale']);

        $inclusiveTax = $component->instance()->calculate([
            'id' => 1,
            'selling_price' => 100,
            'tax' => 10,
            'tax_type' => 1,
        ]);

        $exclusiveTax = $component->instance()->calculate([
            'id' => 2,
            'selling_price' => 100,
            'tax' => 10,
            'tax_type' => 2,
        ]);

        $noTax = $component->instance()->calculate([
            'id' => 3,
            'selling_price' => 100,
            'tax' => 10,
            'tax_type' => 0,
        ]);

        $this->assertEquals(110.0, $inclusiveTax['price']);
        $this->assertEquals(100.0, $exclusiveTax['price']);
        $this->assertEquals(0.0, $noTax['tax']);
    }
}
