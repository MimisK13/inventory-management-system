<?php

namespace Tests\Unit;

use App\Enums\QuotationStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationDetails;
use App\Models\Unit;
use Tests\TestCase;

class QuotationModelsTest extends TestCase
{
    private function makeProductRecord(): Product
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create(['short_code' => 'pc']);

        return Product::query()->create([
            'name' => 'Quotation Model Product',
            'slug' => 'quotation-model-product',
            'code' => 'QM-001',
            'quantity' => 5,
            'buying_price' => 10000,
            'selling_price' => 13000,
            'quantity_alert' => 1,
            'tax' => 24,
            'tax_type' => 1,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_quotation_generates_reference_and_scopes_by_customer_or_reference(): void
    {
        $customer = Customer::factory()->create(['name' => 'Scope Customer']);

        $first = Quotation::query()->create([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'tax_percentage' => 10,
            'tax_amount' => 10,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 2,
            'total_amount' => 102,
            'status' => QuotationStatus::PENDING->value,
            'note' => null,
        ]);

        $second = Quotation::query()->create([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => 'Another Name',
            'tax_percentage' => 5,
            'tax_amount' => 5,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 1,
            'total_amount' => 51,
            'status' => QuotationStatus::SENT->value,
            'note' => null,
        ]);

        $this->assertSame('QT-00001', $first->reference);
        $this->assertSame('QT-00002', $second->reference);
        $this->assertCount(1, Quotation::query()->search('Scope Customer')->get());
        $this->assertCount(1, Quotation::query()->search('QT-00002')->get());
    }

    public function test_quotation_and_quotation_details_relations_and_money_accessors_work(): void
    {
        $customer = Customer::factory()->create();
        $product = $this->makeProductRecord();

        $quotation = Quotation::query()->create([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'tax_percentage' => 24,
            'tax_amount' => 24,
            'discount_percentage' => 10,
            'discount_amount' => 10,
            'shipping_amount' => 5,
            'total_amount' => 119,
            'status' => QuotationStatus::PENDING->value,
            'note' => 'model test',
        ]);

        $detail = QuotationDetails::query()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'quantity' => 1,
            'price' => 10,
            'unit_price' => 10,
            'sub_total' => 10,
            'product_discount_amount' => 1,
            'product_discount_type' => 'fixed',
            'product_tax_amount' => 2.4,
        ]);

        $this->assertTrue($quotation->customer->is($customer));
        $this->assertTrue($quotation->quotationDetails->first()->is($detail));
        $this->assertTrue($detail->quotation->is($quotation));
        $this->assertTrue($detail->product->is($product));

        $this->assertEquals(5.0, $quotation->shipping_amount);
        $this->assertEquals(119.0, $quotation->total_amount);
        $this->assertEquals(24.0, $quotation->tax_amount);
        $this->assertEquals(10.0, $quotation->discount_amount);
        $this->assertEquals(10.0, $detail->price);
        $this->assertEquals(10.0, $detail->unit_price);
        $this->assertEquals(10.0, $detail->sub_total);
        $this->assertEquals(1.0, $detail->product_discount_amount);
        $this->assertEquals(2.4, $detail->product_tax_amount);
    }
}
