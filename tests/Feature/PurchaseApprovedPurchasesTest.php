<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use Tests\TestCase;

class PurchaseApprovedPurchasesTest extends TestCase
{
    public function test_approved_purchases_page_renders_and_filters_only_approved_purchases(): void
    {
        $user = $this->createUser();

        $approvedPurchase = Purchase::factory()->create([
            'status' => PurchaseStatus::APPROVED,
            'created_by' => $user->id,
            'purchase_no' => 'APP-1001',
        ]);
        $pendingPurchase = Purchase::factory()->create([
            'status' => PurchaseStatus::PENDING,
            'created_by' => $user->id,
            'purchase_no' => 'PND-1002',
        ]);

        $response = $this->actingAs($user)->get(route('purchases.approvedPurchases', absolute: false));

        $response->assertOk();
        $response->assertSee($approvedPurchase->purchase_no);
        $response->assertDontSee($pendingPurchase->purchase_no);
        $response->assertSee(__('Approved'));
    }
}
