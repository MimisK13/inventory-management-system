<?php

namespace Tests\Feature\Livewire;

use App\Livewire\OrderForm;
use App\Livewire\PurchaseForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Gloudemans\Shoppingcart\Facades\Cart;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderFormsTest extends TestCase
{
    private function createProductForForm(): Product
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create(['short_code' => 'pc']);

        return Product::query()->create([
            'name' => 'Form Product',
            'slug' => 'form-product',
            'code' => 'FP-001',
            'quantity' => 50,
            'buying_price' => 10000,
            'selling_price' => 15000,
            'quantity_alert' => 5,
            'tax' => 10,
            'tax_type' => 1,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_purchase_form_adds_saves_and_removes_products(): void
    {
        $product = $this->createProductForForm();

        $component = Livewire::test(PurchaseForm::class)
            ->assertStatus(200)
            ->call('addProduct')
            ->set('invoiceProducts.0.product_id', $product->id)
            ->set('invoiceProducts.0.quantity', 2)
            ->call('saveProduct', 0)
            ->assertSet('invoiceProducts.0.is_saved', true)
            ->call('addProduct');

        $this->assertCount(2, $component->get('invoiceProducts'));

        $component->call('removeProduct', 0);

        $this->assertCount(1, $component->get('invoiceProducts'));
    }

    public function test_purchase_form_prevents_adding_a_new_line_when_existing_line_is_unsaved(): void
    {
        $component = Livewire::test(PurchaseForm::class)
            ->call('addProduct')
            ->call('addProduct');

        $component->assertHasErrors(['invoiceProducts.0']);
        $this->assertCount(1, $component->get('invoiceProducts'));
    }

    public function test_order_form_adds_product_to_cart_and_prevents_duplicates(): void
    {
        Cart::instance('sale')->destroy();
        $product = $this->createProductForForm();

        $component = Livewire::test(OrderForm::class, ['cartInstance' => 'sale'])
            ->assertStatus(200)
            ->call('addProduct')
            ->set('invoiceProducts.0.product_id', $product->id)
            ->set('invoiceProducts.0.quantity', 1)
            ->call('saveProduct', 0);

        $this->assertSame(1, (int) Cart::instance('sale')->count());

        $component->call('saveProduct', 0);

        $this->assertSame(1, (int) Cart::instance('sale')->count());
    }
}
