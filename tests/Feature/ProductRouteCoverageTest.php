<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductRouteCoverageTest extends TestCase
{
    public function test_product_resource_routes_render_for_authenticated_user(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = Product::factory()->create([
            'name' => 'Sample Product',
            'slug' => 'sample-product',
            'code' => 'PC01',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);

        $this->actingAs($user)->get(route('products.index', absolute: false))
            ->assertOk()
            ->assertViewIs('products.index');
        $this->actingAs($user)->get(route('products.create', absolute: false))
            ->assertOk()
            ->assertViewIs('products.create');
        $this->actingAs($user)->get(route('products.show', $product, absolute: false))
            ->assertOk()
            ->assertViewIs('products.show')
            ->assertViewHas('barcode', function ($barcode) {
                return is_string($barcode) && str_contains($barcode, '<div style="font-size:0;position:relative;');
            });
        $this->actingAs($user)->get(route('products.edit', $product, absolute: false))
            ->assertOk()
            ->assertViewIs('products.edit');
    }

    public function test_product_update_and_destroy_routes_work(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $unit = $this->createUnit();
        $product = Product::factory()->create([
            'name' => 'Created Product',
            'slug' => 'created-product',
            'code' => 'PC02',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);

        $updateResponse = $this->actingAs($user)->put(route('products.update', $product, absolute: false), [
            'name' => 'Updated Product',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'quantity' => 15,
            'buying_price' => 550,
            'selling_price' => 900,
            'quantity_alert' => 3,
            'tax' => 5,
            'tax_type' => 0,
            'notes' => 'updated notes',
        ]);

        $updateResponse->assertRedirect(route('products.index', absolute: false));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'quantity' => 15,
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('products.destroy', $product->fresh(), absolute: false));

        $deleteResponse->assertRedirect(route('products.index', absolute: false));
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_import_routes_are_covered(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get(route('products.import.view', absolute: false))
            ->assertOk()
            ->assertViewIs('products.import');

        $missingFileResponse = $this->actingAs($user)->post(route('products.import.store', absolute: false), []);

        $missingFileResponse->assertSessionHasErrors('file');

        $invalidSpreadsheetResponse = $this->actingAs($user)->post(route('products.import.store', absolute: false), [
            'file' => UploadedFile::fake()->create('products.xlsx', 20, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ]);

        $invalidSpreadsheetResponse->assertRedirect(route('products.index', absolute: false));
        $invalidSpreadsheetResponse->assertSessionHas('error');
    }

    public function test_guest_cannot_access_product_export_route(): void
    {
        $response = $this->get(route('products.export.store', absolute: false));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_guest_cannot_access_product_store_route(): void
    {
        $response = $this->post(route('products.store', absolute: false), []);

        $response->assertRedirect(route('login', absolute: false));
    }
}
