<?php

namespace Tests\Feature\Livewire\Tables;

use App\Livewire\Tables\ProductTable;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTableTest extends TestCase
{
    public function test_renders_successfully(): void
    {
        Livewire::test(ProductTable::class)
            ->assertStatus(200);
    }
}
