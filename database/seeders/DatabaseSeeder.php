<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\Unit;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            //UserSeeder::class,

            CategorySeeder::class,
            UnitSeeder::class,
            ProductSeeder::class
        ]);

        $orders = Order::factory(50)->create();
        $customers = Customer::factory(30)
            ->recycle($orders)
            ->create();


        $purchases = Purchase::factory(60)->create();
        $suppliers = Supplier::factory(20)->create();

        $users = User::factory(50)
            ->recycle($suppliers)
            ->recycle($purchases)
            ->create();

        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com'
        ]);

        /*
        for ($i=0; $i < 10; $i++) {
            Product::factory()->create([
                'product_code' => IdGenerator::generate([
                    'table' => 'products',
                    'field' => 'product_code',
                    'length' => 4,
                    'prefix' => 'PC'
                ]),
            ]);
        }
        */

    }
}
