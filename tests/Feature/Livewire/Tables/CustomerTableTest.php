<?php

namespace Tests\Feature\Livewire\Tables;

use App\Livewire\Tables\CustomerTable;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerTableTest extends TestCase
{
    public function test_renders_successfully(): void
    {
        Livewire::test(CustomerTable::class)
            ->assertStatus(200);
    }
}
