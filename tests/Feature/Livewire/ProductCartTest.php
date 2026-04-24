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

    public function test_mount_with_data_populates_existing_cart_values(): void
    {
        Cart::instance('sale')->add([
            'id' => 777,
            'name' => 'Existing Item',
            'qty' => 2,
            'price' => 100,
            'weight' => 1,
            'options' => [
                'stock' => 10,
                'product_discount_type' => 'percentage',
                'product_discount' => 10,
                'code' => 'EX-001',
                'unit' => 1,
                'product_tax' => 0,
                'unit_price' => 100,
                'sub_total' => 200,
            ],
        ]);

        $data = (object) [
            'discount_percentage' => 5,
            'tax_percentage' => 10,
            'shipping_amount' => 7.5,
        ];

        Livewire::test(ProductCart::class, ['cartInstance' => 'sale', 'data' => $data])
            ->assertSet('global_discount', 5)
            ->assertSet('global_tax', 10)
            ->assertSet('shipping', 7.5)
            ->assertSet('quantity.777', 2)
            ->assertSet('discount_type.777', 'percentage')
            ->assertSet('item_discount.777', 10);
    }

    public function test_product_discounts_price_updates_and_removal_work(): void
    {
        $product = $this->createProductForCart([
            'selling_price' => 12000,
            'tax' => 10,
            'tax_type' => 1,
        ]);

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

        $component->set("discount_type.{$product->id}", 'fixed')
            ->set("item_discount.{$product->id}", 100)
            ->call('setProductDiscount', $rowId, $product->id);

        $rowId = Cart::instance('sale')->content()->first()->rowId;
        $afterFixed = Cart::instance('sale')->get($rowId);
        $this->assertSame(100.0, (float) $afterFixed->options->product_discount);
        $this->assertSame('fixed', $afterFixed->options->product_discount_type);

        $component->set("discount_type.{$product->id}", 'percentage')
            ->set("item_discount.{$product->id}", 10)
            ->call('setProductDiscount', $rowId, $product->id);

        $rowId = Cart::instance('sale')->content()->first()->rowId;
        $afterPercentage = Cart::instance('sale')->get($rowId);
        $this->assertSame('percentage', $afterPercentage->options->product_discount_type);
        $this->assertSame(1320.0, (float) $afterPercentage->options->product_discount);

        $rowId = Cart::instance('sale')->content()->first()->rowId;

        $component->set("unit_price.{$product->id}", 20000)
            ->call('updatePrice', $rowId, $product->id)
            ->set("discount_type.{$product->id}", 'fixed')
            ->set("item_discount.{$product->id}", 50)
            ->set("discount_type.{$product->id}", 'percentage');

        $rowId = Cart::instance('sale')->content()->first()->rowId;
        $afterPriceUpdate = Cart::instance('sale')->get($rowId);
        $this->assertSame(20000.0, (float) $afterPriceUpdate->price);
        $this->assertSame(20000.0, (float) $afterPriceUpdate->options->sub_total);
        $this->assertSame(0, $component->get("item_discount.{$product->id}"));

        $component->set('global_tax', 7)
            ->set('global_discount', 3)
            ->set("quantity.{$product->id}", 1)
            ->set("check_quantity.{$product->id}", 5);

        $rowId = Cart::instance('sale')->content()->first()->rowId;

        $component->call('discountModalRefresh', $product->id, $rowId)
            ->call('removeItem', $rowId);

        $this->assertSame(0, (int) Cart::instance('sale')->count());
    }
}
