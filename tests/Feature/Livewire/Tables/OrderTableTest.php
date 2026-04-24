<?php

namespace Tests\Feature\Livewire\Tables;

use App\Livewire\Tables\OrderTable;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTableTest extends TestCase
{
    public function test_renders_successfully(): void
    {
        Livewire::test(OrderTable::class)
            ->assertStatus(200);
    }
}
