<?php

namespace Tests\Unit;

use App\Enums\QuotationStatus;
use App\Livewire\PowerGrid\CategoriesTable;
use App\Livewire\PowerGrid\CustomersTable;
use App\Livewire\PowerGrid\OrderTable as PowerGridOrderTable;
use App\Livewire\PowerGrid\ProductsTable;
use App\Livewire\PowerGrid\QuotationsTable;
use App\Livewire\PowerGrid\SuppliersTable;
use App\Livewire\PowerGrid\UnitsTable;
use App\Livewire\PowerGrid\UserTable as PowerGridUserTable;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\PowerGridColumns;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use Tests\TestCase;

class PowerGridComponentsTest extends TestCase
{
    private static bool $createdSvgDir = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::loadPowerGridStubsIfMissing();
    }

    private static function loadPowerGridStubsIfMissing(): void
    {
        if (class_exists(PowerGridComponent::class)) {
            return;
        }

        eval('namespace PowerComponents\LivewirePowerGrid; class PowerGridComponent extends \Livewire\Component {}');
        eval('namespace PowerComponents\LivewirePowerGrid; class Button { public static function make(...$args): self { return new self(); } public static function add(...$args): self { return new self(); } public function class(...$args): self { return $this; } public function tooltip(...$args): self { return $this; } public function route(...$args): self { return $this; } public function method(...$args): self { return $this; } public function slot(...$args): self { return $this; } public function id(...$args): self { return $this; } public function dispatch(...$args): self { return $this; } }');
        eval('namespace PowerComponents\LivewirePowerGrid; class Column { public static function make(...$args): self { return new self(); } public static function add(...$args): self { return new self(); } public static function action(...$args): self { return new self(); } public function headerAttribute(...$args): self { return $this; } public function bodyAttribute(...$args): self { return $this; } public function searchable(...$args): self { return $this; } public function sortable(...$args): self { return $this; } public function hidden(...$args): self { return $this; } public function contentClasses(...$args): self { return $this; } public function title(...$args): self { return $this; } public function field(...$args): self { return $this; } }');
        eval('namespace PowerComponents\LivewirePowerGrid; class Footer { public static function make(...$args): self { return new self(); } public function showPerPage(...$args): self { return $this; } public function showRecordCount(...$args): self { return $this; } }');
        eval('namespace PowerComponents\LivewirePowerGrid; class Header { public static function make(...$args): self { return new self(); } public function showSearchInput(...$args): self { return $this; } }');
        eval('namespace PowerComponents\LivewirePowerGrid; class Exportable { public const TYPE_XLS = "xls"; public const TYPE_CSV = "csv"; public static function make(...$args): self { return new self(); } public function striped(...$args): self { return $this; } public function type(...$args): self { return $this; } }');
        eval('namespace PowerComponents\LivewirePowerGrid; class PowerGrid { public static function columns(): \PowerComponents\LivewirePowerGrid\PowerGridColumns { return new \PowerComponents\LivewirePowerGrid\PowerGridColumns(); } }');
        eval('namespace PowerComponents\LivewirePowerGrid; class PowerGridColumns { public function addColumn(...$args): self { return $this; } }');
        eval('namespace PowerComponents\LivewirePowerGrid\Facades; class Filter {}');
        eval('namespace PowerComponents\LivewirePowerGrid\Traits; trait WithExport {}');
    }

    public static function tearDownAfterClass(): void
    {
        $svgDir = base_path('assets/svg');

        foreach (['eye.svg', 'edit.svg', 'trash.svg'] as $icon) {
            $iconPath = $svgDir.'/'.$icon;
            if (file_exists($iconPath)) {
                unlink($iconPath);
            }
        }

        if (self::$createdSvgDir && is_dir($svgDir)) {
            @rmdir($svgDir);
            @rmdir(base_path('assets'));
        }

        parent::tearDownAfterClass();
    }

    public function test_powergrid_components_define_basic_configuration(): void
    {
        $components = [
            new CategoriesTable,
            new CustomersTable,
            new PowerGridOrderTable,
            new ProductsTable,
            new QuotationsTable,
            new SuppliersTable,
            new UnitsTable,
            new PowerGridUserTable,
        ];

        foreach ($components as $component) {
            $this->assertNotEmpty($component->setUp());
            $this->assertInstanceOf(Builder::class, $component->datasource());
            $this->assertInstanceOf(PowerGridColumns::class, $component->addColumns());
            $this->assertNotEmpty($component->columns());
        }
    }

    public function test_powergrid_components_build_action_buttons_for_rows(): void
    {
        $svgDir = base_path('assets/svg');
        if (! is_dir($svgDir)) {
            mkdir($svgDir, 0777, true);
            self::$createdSvgDir = true;
        }

        file_put_contents($svgDir.'/eye.svg', '<svg></svg>');
        file_put_contents($svgDir.'/edit.svg', '<svg></svg>');
        file_put_contents($svgDir.'/trash.svg', '<svg></svg>');

        $category = Category::factory()->create();
        $customer = Customer::factory()->create();
        $supplier = Supplier::factory()->create();
        $unit = Unit::factory()->create(['short_code' => 'pc']);
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $product = Product::query()->create([
            'name' => 'PowerGrid Product',
            'slug' => 'powergrid-product',
            'code' => 'PG-001',
            'quantity' => 20,
            'buying_price' => 10000,
            'selling_price' => 15000,
            'quantity_alert' => 5,
            'tax' => 10,
            'tax_type' => 1,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);

        $quotation = Quotation::query()->create([
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'tax_percentage' => 10,
            'tax_amount' => 1000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'total_amount' => 11500,
            'status' => QuotationStatus::PENDING->value,
            'note' => null,
        ]);

        $this->assertCount(3, (new CategoriesTable)->actions($category));
        $this->assertCount(3, (new CustomersTable)->actions($customer));
        $this->assertCount(1, (new PowerGridOrderTable)->actions($order));
        $this->assertCount(3, (new ProductsTable)->actions($product));
        $this->assertCount(3, (new QuotationsTable)->actions($quotation));
        $this->assertCount(3, (new SuppliersTable)->actions($supplier));
        $this->assertCount(3, (new UnitsTable)->actions($unit));
        $this->assertCount(3, (new PowerGridUserTable)->actions($user));
    }
}
