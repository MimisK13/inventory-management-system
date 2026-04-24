<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_api_url()
    {
        $this->withoutExceptionHandling();

        $this->createProduct();
        Product::factory()->create([
            'name' => 'Test Product 2',
            'slug' => 'test-product-2',
            'category_id' => $this->createCategory(),
            'unit_id' => $this->createUnit(),
        ]);

        $response = $this->get(route('api.product.index', absolute: false));

        $response->assertStatus(200);
        $response->assertSee('Test Product');
        $response->assertSee('Test Product 2');
    }

    public function test_product_url_with_query_string()
    {
        $category = $this->createCategory();

        Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category,
            'unit_id' => $this->createUnit(),
        ]);
        Product::factory()->create([
            'name' => 'Test Product 2',
            'slug' => 'test-product-2',
            'category_id' => $this->createCategory(),
            'unit_id' => $this->createUnit(),
        ]);

        $response = $this->get(route('api.product.index', ['category_id' => $category], absolute: false));

        $response->assertStatus(200);
        $response->assertSee('Test Product');
        $response->assertDontSee('Test Product 2');
    }

    public function createProduct()
    {
        return Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $this->createCategory(),
            'unit_id' => $this->createUnit(),
            'tax_type' => 1,
        ]);
    }

    public function createCategory()
    {
        return Category::factory()->create()->id;
    }

    public function createUnit()
    {
        return Unit::factory()->create()->id;
    }
}
