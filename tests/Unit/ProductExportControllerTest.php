<?php

namespace App\Http\Controllers\Product {
    function header(string $header): void
    {
        throw new \Exception("Header blocked in tests: {$header}");
    }
}

namespace Tests\Unit {

    use App\Http\Controllers\Product\ProductExportController;
    use App\Models\Category;
    use App\Models\Product;
    use App\Models\Unit;
    use Mockery;
    use Tests\TestCase;

    class ProductExportControllerTest extends TestCase
    {
        public function test_create_builds_export_array_and_calls_store(): void
        {
            $category = Category::factory()->create();
            $unit = Unit::factory()->create(['short_code' => 'pc']);

            Product::query()->create([
                'name' => 'Export Product',
                'slug' => 'export-product',
                'code' => 'EXP-001',
                'quantity' => 10,
                'buying_price' => 10000,
                'selling_price' => 12000,
                'quantity_alert' => 2,
                'tax' => 5,
                'tax_type' => 1,
                'category_id' => $category->id,
                'unit_id' => $unit->id,
            ]);

            $controller = Mockery::mock(ProductExportController::class)->makePartial();
            $controller->shouldReceive('store')
                ->once()
                ->with(Mockery::on(function ($payload) {
                    return is_array($payload)
                        && count($payload) === 2
                        && $payload[0][0] === 'Product Name'
                        && $payload[1]['Product Code'] === 'EXP-001';
                }));

            $this->assertNull($controller->create());
        }

        public function test_store_returns_null_when_header_throws_exception(): void
        {
            $controller = new ProductExportController;
            $result = $controller->store([['col1', 'col2']]);

            $this->assertNull($result);
        }
    }
}
