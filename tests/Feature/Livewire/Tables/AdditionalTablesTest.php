<?php

namespace Tests\Feature\Livewire\Tables;

use App\Livewire\Tables\CategoryTable;
use App\Livewire\Tables\ProductByCategoryTable;
use App\Livewire\Tables\ProductByUnitTable;
use App\Livewire\Tables\PurchaseTable;
use App\Livewire\Tables\SupplierTable;
use App\Livewire\Tables\UnitTable;
use App\Livewire\Tables\UserTable;
use App\Models\Category;
use App\Models\Unit;
use Livewire\Livewire;
use Tests\TestCase;

class AdditionalTablesTest extends TestCase
{
    public function test_category_table_renders_and_toggles_sort_direction(): void
    {
        Livewire::test(CategoryTable::class)
            ->assertStatus(200)
            ->assertSet('sortAsc', false)
            ->call('sortBy', 'name')
            ->assertSet('sortAsc', true)
            ->call('sortBy', 'name')
            ->assertSet('sortAsc', false);
    }

    public function test_unit_table_renders_and_toggles_sort_direction(): void
    {
        Livewire::test(UnitTable::class)
            ->assertStatus(200)
            ->assertSet('sortAsc', true)
            ->call('sortBy', 'name')
            ->assertSet('sortAsc', false);
    }

    public function test_user_table_renders_and_switches_sort_field(): void
    {
        Livewire::test(UserTable::class)
            ->assertStatus(200)
            ->call('sortBy', 'email')
            ->assertSet('sortField', 'email')
            ->assertSet('sortAsc', true);
    }

    public function test_supplier_table_renders_and_toggles_sort_direction(): void
    {
        Livewire::test(SupplierTable::class)
            ->assertStatus(200)
            ->assertSet('sortAsc', false)
            ->call('sortBy', 'name')
            ->assertSet('sortAsc', true);
    }

    public function test_purchase_table_renders_and_switches_sort_field(): void
    {
        Livewire::test(PurchaseTable::class)
            ->assertStatus(200)
            ->call('sortBy', 'date')
            ->assertSet('sortField', 'date')
            ->assertSet('sortAsc', true);
    }

    public function test_product_by_category_table_renders_with_mount_parameter_and_toggles_sort(): void
    {
        $category = Category::factory()->create();

        Livewire::test(ProductByCategoryTable::class, ['category' => $category])
            ->assertStatus(200)
            ->assertSet('sortAsc', true)
            ->call('sortBy', 'name')
            ->assertSet('sortAsc', false);
    }

    public function test_product_by_unit_table_renders_with_mount_parameter_and_toggles_sort(): void
    {
        $unit = Unit::factory()->create();

        Livewire::test(ProductByUnitTable::class, ['unit' => $unit])
            ->assertStatus(200)
            ->assertSet('sortAsc', true)
            ->call('sortBy', 'name')
            ->assertSet('sortAsc', false);
    }
}
