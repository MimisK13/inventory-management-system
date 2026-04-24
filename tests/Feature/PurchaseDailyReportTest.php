<?php

namespace Tests\Feature;

use App\Models\Purchase;
use Tests\TestCase;

class PurchaseDailyReportTest extends TestCase
{
    public function test_daily_purchase_report_page_renders_today_purchases(): void
    {
        $user = $this->createUser();

        $todayPurchase = Purchase::factory()->create([
            'date' => now()->toDateString(),
            'created_by' => $user->id,
            'purchase_no' => 'RPT-TODAY-01',
        ]);

        Purchase::factory()->create([
            'date' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
            'purchase_no' => 'RPT-YDAY-02',
        ]);

        $response = $this->actingAs($user)->get(route('purchases.dailyPurchaseReport', absolute: false));

        $response->assertOk();
        $response->assertSee($todayPurchase->purchase_no);
        $response->assertDontSee('RPT-YDAY-02');
    }
}
