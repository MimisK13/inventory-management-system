<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Name;
use App\Livewire\SearchProduct;
use App\Livewire\Slug;
use App\Livewire\SupplierDropdown;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Livewire\Livewire;
use Tests\TestCase;

class BasicComponentsTest extends TestCase
{
    public function test_slug_component_generates_slug_from_selected_name_event_payload(): void
    {
        Livewire::test(Slug::class)
            ->call('generateSlug', 'New Product Name')
            ->assertSet('slug', 'new-product-name');
    }

    public function test_name_component_dispatches_name_selected_event(): void
    {
        Livewire::test(Name::class)
            ->set('name', 'Sample Name')
            ->call('selectedName')
            ->assertDispatched('name-selected');
    }

    public function test_search_product_component_filters_loads_more_and_resets_query(): void
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        Product::factory()->create([
            'name' => 'Laptop Pro',
            'code' => 'LAP-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
        Product::factory()->create([
            'name' => 'Desk Lamp',
            'code' => 'LAMP-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);

        $component = Livewire::test(SearchProduct::class)
            ->set('query', 'lap');

        $this->assertCount(1, $component->get('search_results'));

        $component->call('loadMore')
            ->assertSet('how_many', 10);

        $component->call('resetQuery')
            ->assertSet('query', '')
            ->assertSet('how_many', 5);

        $this->assertCount(0, $component->get('search_results'));
    }

    public function test_search_product_component_dispatches_selected_product_event(): void
    {
        Livewire::test(SearchProduct::class)
            ->call('selectProduct', ['id' => 10, 'name' => 'Any'])
            ->assertDispatched('productSelected');
    }

    public function test_supplier_dropdown_component_renders_and_loads_suppliers(): void
    {
        $supplier = Supplier::factory()->create();
        Supplier::factory()->create();

        $component = Livewire::test(SupplierDropdown::class, ['supplier' => $supplier])
            ->assertStatus(200);

        $this->assertCount(2, $component->get('suppliers'));
    }
}
