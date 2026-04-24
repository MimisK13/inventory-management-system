<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use Tests\TestCase;

class PurchaseExportReportTest extends TestCase
{
    public function test_purchase_export_report_requires_valid_date_range(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('purchases.exportPurchaseReport', absolute: false), [
            'start_date' => '',
            'end_date' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_purchase_export_report_returns_excel_download_for_approved_purchases(): void
    {
        $user = $this->createUser();

        $product = Product::factory()->create([
            'category_id' => $this->createCategory()->id,
            'unit_id' => $this->createUnit()->id,
            'name' => 'Export Product',
            'code' => 'EXP-PRD-001',
        ]);

        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::APPROVED,
            'created_by' => $user->id,
            'date' => now()->toDateString(),
            'purchase_no' => 'EXP-001',
        ]);

        PurchaseDetails::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unitcost' => 150,
            'total' => 300,
        ]);

        $response = $this->actingAs($user)->post(route('purchases.exportPurchaseReport', absolute: false), [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel');
        $response->assertHeader('content-disposition', 'attachment; filename=purchase-report.xls');
    }
}
