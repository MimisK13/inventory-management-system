<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ControllerImageAndImportBranchesTest extends TestCase
{
    private function createProductForControllerTests(): Product
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create(['short_code' => 'pc']);

        return Product::query()->create([
            'name' => 'Controller Product',
            'slug' => 'controller-product',
            'code' => 'CTRL-001',
            'quantity' => 50,
            'buying_price' => 10000,
            'selling_price' => 15000,
            'quantity_alert' => 5,
            'tax' => 10,
            'tax_type' => 1,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);
    }

    private function ensurePublicStorageFile(string $folder, string $filename): void
    {
        $directory = public_path("storage/{$folder}");

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents("{$directory}/{$filename}", 'old-file');
    }

    public function test_product_update_and_destroy_cover_image_branches(): void
    {
        $user = $this->createUser();
        $product = $this->createProductForControllerTests();
        $product->update(['product_image' => 'old-product.jpg']);
        $this->ensurePublicStorageFile('products', 'old-product.jpg');

        $response = $this->actingAs($user)->put(route('products.update', $product, absolute: false), [
            'name' => 'Controller Product Updated',
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'quantity' => 15,
            'buying_price' => 500,
            'selling_price' => 900,
            'quantity_alert' => 3,
            'tax' => 5,
            'tax_type' => 1,
            'notes' => 'notes',
            'product_image' => UploadedFile::fake()->image('new-product.jpg'),
        ]);

        $response->assertRedirect(route('products.index', absolute: false));
        $product->refresh();
        $this->assertNotSame('old-product.jpg', $product->product_image);
        $this->ensurePublicStorageFile('products', $product->product_image);

        $deleteResponse = $this->actingAs($user)->delete(route('products.destroy', $product, absolute: false));
        $deleteResponse->assertRedirect(route('products.index', absolute: false));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_customer_update_and_destroy_cover_photo_branches(): void
    {
        $user = $this->createUser();
        $customer = Customer::factory()->create([
            'photo' => 'old-customer.jpg',
        ]);
        $this->ensurePublicStorageFile('customers', 'old-customer.jpg');

        $updateResponse = $this->actingAs($user)->put(route('customers.update', $customer, absolute: false), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'account_holder' => $customer->account_holder,
            'account_number' => $customer->account_number,
            'bank_name' => $customer->bank_name,
            'photo' => UploadedFile::fake()->image('new-customer.jpg'),
        ]);

        $updateResponse->assertRedirect(route('customers.index', absolute: false));
        $customer->refresh();
        $this->assertNotSame('old-customer.jpg', $customer->photo);
        $this->ensurePublicStorageFile('customers', $customer->photo);

        $deleteResponse = $this->actingAs($user)->delete(route('customers.destroy', $customer, absolute: false));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_supplier_update_and_destroy_cover_photo_branches(): void
    {
        $user = $this->createUser();
        $supplier = Supplier::factory()->create([
            'photo' => 'old-supplier.jpg',
        ]);
        $this->ensurePublicStorageFile('suppliers', 'old-supplier.jpg');

        $updateResponse = $this->actingAs($user)->put(route('suppliers.update', $supplier, absolute: false), [
            'name' => $supplier->name,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'shopname' => $supplier->shopname,
            'type' => $supplier->type->value,
            'address' => $supplier->address,
            'account_holder' => $supplier->account_holder,
            'account_number' => $supplier->account_number,
            'bank_name' => $supplier->bank_name,
            'photo' => UploadedFile::fake()->image('new-supplier.jpg'),
        ]);

        $updateResponse->assertRedirect(route('suppliers.index', absolute: false));
        $supplier->refresh();
        $this->assertNotSame('old-supplier.jpg', $supplier->photo);
        $this->ensurePublicStorageFile('suppliers', $supplier->photo);

        $deleteResponse = $this->actingAs($user)->delete(route('suppliers.destroy', $supplier, absolute: false));
        $deleteResponse->assertRedirect(route('suppliers.index', absolute: false));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_product_import_store_handles_spreadsheet_and_returns_error_on_insert_failure(): void
    {
        $user = $this->createUser();
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'name');
        $sheet->setCellValue('B1', 'category_id');
        $sheet->setCellValue('C1', 'unit_id');
        $sheet->setCellValue('D1', 'code');
        $sheet->setCellValue('E1', 'quantity');
        $sheet->setCellValue('F1', 'buying_price');
        $sheet->setCellValue('G1', 'selling_price');
        $sheet->setCellValue('H1', 'product_image');
        $sheet->setCellValue('A2', 'Imported Product');
        $sheet->setCellValue('B2', (string) $category->id);
        $sheet->setCellValue('C2', (string) $unit->id);
        $sheet->setCellValue('D2', 'IMP-001');
        $sheet->setCellValue('E2', '10');
        $sheet->setCellValue('F2', '1000');
        $sheet->setCellValue('G2', '1500');
        $sheet->setCellValue('H2', 'image.jpg');

        $tempPath = storage_path('app/test-products-import.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'products.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($user)->post(route('products.import.store', absolute: false), [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect(route('products.index', absolute: false));
        $response->assertSessionHas('error');

        @unlink($tempPath);
    }
}
