<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    public function test_guest_cannot_approve_purchase(): void
    {
        $purchase = Purchase::factory()->create();

        $response = $this->put(route('purchases.update', $purchase, absolute: false));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_purchase_approval_increments_stock_and_is_idempotent(): void
    {
        $user = $this->createUser();

        $product = Product::factory()->create([
            'category_id' => $this->createCategory()->id,
            'unit_id' => $this->createUnit()->id,
            'quantity' => 10,
        ]);

        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::PENDING,
            'created_by' => $user->id,
            'updated_by' => null,
        ]);

        PurchaseDetails::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unitcost' => 100,
            'total' => 500,
        ]);

        $firstApproval = $this->actingAs($user)->put(route('purchases.update', $purchase, absolute: false));

        $firstApproval->assertRedirect(route('purchases.index', absolute: false));
        $this->assertSame(15, $product->fresh()->quantity);
        $this->assertSame(PurchaseStatus::APPROVED, $purchase->fresh()->status);

        $secondApproval = $this->actingAs($user)->put(route('purchases.update', $purchase, absolute: false));

        $secondApproval->assertRedirect(route('purchases.index', absolute: false));
        $this->assertSame(15, $product->fresh()->quantity);
        $this->assertSame(PurchaseStatus::APPROVED, $purchase->fresh()->status);
    }
}
