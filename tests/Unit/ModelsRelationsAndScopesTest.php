<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\QuotationStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Quotation;
use App\Models\QuotationDetails;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class ModelsRelationsAndScopesTest extends TestCase
{
    private function makeProductRecord(): Product
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create(['short_code' => 'pc']);

        return Product::query()->create([
            'name' => 'Scoped Product',
            'slug' => 'scoped-product',
            'code' => 'SC-001',
            'quantity' => 20,
            'buying_price' => 10000,
            'selling_price' => 15000,
            'quantity_alert' => 2,
            'tax' => 10,
            'tax_type' => 1,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_category_and_unit_route_keys_and_scopes_work(): void
    {
        Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
        Category::factory()->create(['name' => 'Video', 'slug' => 'video']);
        Unit::factory()->create(['name' => 'Piece', 'slug' => 'piece', 'short_code' => 'pc']);
        Unit::factory()->create(['name' => 'Meter', 'slug' => 'meter', 'short_code' => 'mtr']);

        $this->assertSame('slug', (new Category)->getRouteKeyName());
        $this->assertSame('slug', (new Unit)->getRouteKeyName());
        $this->assertCount(1, Category::query()->search('aud')->get());
        $this->assertCount(1, Unit::query()->search('mtr')->get());
    }

    public function test_customer_supplier_order_purchase_and_quotation_scopes_work(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'John Scope',
            'email' => 'john.scope@example.com',
            'phone' => '123-456',
        ]);
        $supplier = Supplier::factory()->create([
            'name' => 'Scope Supplier',
            'email' => 'scope@supplier.test',
            'phone' => '555-123',
            'shopname' => 'Scope Shop',
        ]);
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-SCOPE-001',
            'order_status' => OrderStatus::PENDING->value,
            'payment_type' => 'Cash',
        ]);
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'purchase_no' => 'PRS-SCOPE-001',
            'status' => PurchaseStatus::PENDING->value,
        ]);
        $quotation = Quotation::query()->create([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => 'John Scope',
            'tax_percentage' => 10,
            'tax_amount' => 1000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'total_amount' => 10500,
            'status' => QuotationStatus::PENDING->value,
            'note' => 'scope note',
        ]);

        $this->assertCount(1, Customer::query()->search('john.scope@')->get());
        $this->assertCount(1, Supplier::query()->search('scope shop')->get());
        $this->assertCount(1, Order::query()->search('INV-SCOPE-001')->get());
        $this->assertCount(1, Purchase::query()->search('PRS-SCOPE-001')->get());
        $this->assertCount(1, Quotation::query()->search('John Scope')->get());
        $this->assertStringStartsWith('QT-', $quotation->reference);
    }

    public function test_detail_model_relations_and_money_casts_work(): void
    {
        $product = $this->makeProductRecord();
        $customer = Customer::factory()->create();
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create();

        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
        ]);
        $quotation = Quotation::query()->create([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'tax_percentage' => 10,
            'tax_amount' => 10,
            'discount_percentage' => 5,
            'discount_amount' => 5,
            'shipping_amount' => 5,
            'total_amount' => 110,
            'status' => QuotationStatus::SENT->value,
            'note' => null,
        ]);

        $orderDetail = OrderDetails::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unitcost' => 1000,
            'total' => 2000,
        ]);
        $purchaseDetail = PurchaseDetails::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unitcost' => 1200,
            'total' => 3600,
        ]);
        $quotationDetail = QuotationDetails::query()->create([
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

        $this->assertTrue($orderDetail->product->is($product));
        $this->assertTrue($orderDetail->order->is($order));
        $this->assertTrue($purchaseDetail->product->is($product));
        $this->assertTrue($purchaseDetail->purchase->is($purchase));
        $this->assertTrue($quotationDetail->product->is($product));
        $this->assertTrue($quotationDetail->quotation->is($quotation));
        $this->assertEquals(10.0, $quotationDetail->price);
        $this->assertEquals(10.0, $quotationDetail->unit_price);
        $this->assertEquals(10.0, $quotationDetail->sub_total);
        $this->assertEquals(1.0, $quotationDetail->product_discount_amount);
        $this->assertEquals(2.4, $quotationDetail->product_tax_amount);
        $this->assertEquals(5.0, $quotation->shipping_amount);
        $this->assertEquals(110.0, $quotation->total_amount);
        $this->assertEquals(10.0, $quotation->tax_amount);
        $this->assertEquals(5.0, $quotation->discount_amount);
    }
}
