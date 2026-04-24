<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ControllerCoverageBoostTest extends TestCase
{
    private function ensurePublicStorageFile(string $folder, string $filename): void
    {
        $directory = public_path("storage/{$folder}");

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents("{$directory}/{$filename}", 'existing');
    }

    public function test_category_controller_store_update_and_destroy_work(): void
    {
        $user = $this->createUser();

        $storeResponse = $this->actingAs($user)->post(route('categories.store', absolute: false), [
            'name' => 'Laptops',
            'slug' => 'laptops',
        ]);
        $storeResponse->assertRedirect(route('categories.index', absolute: false));

        $category = Category::query()->where('slug', 'laptops')->firstOrFail();

        $updateResponse = $this->actingAs($user)->put(route('categories.update', $category, absolute: false), [
            'name' => 'Gaming Laptops',
            'slug' => 'gaming-laptops',
        ]);
        $updateResponse->assertRedirect(route('categories.index', absolute: false));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'slug' => 'gaming-laptops',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('categories.destroy', $category->fresh(), absolute: false));
        $deleteResponse->assertRedirect(route('categories.index', absolute: false));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_product_create_filters_categories_and_units_by_query_parameters(): void
    {
        $user = $this->createUser();
        $categoryA = Category::factory()->create(['slug' => 'audio', 'name' => 'Audio']);
        $categoryB = Category::factory()->create(['slug' => 'video', 'name' => 'Video']);
        $unitA = Unit::factory()->create(['slug' => 'piece', 'name' => 'Piece']);
        $unitB = Unit::factory()->create(['slug' => 'meter', 'name' => 'Meter']);

        $response = $this->actingAs($user)->get(route('products.create', [
            'category' => $categoryA->slug,
            'unit' => $unitA->slug,
        ], absolute: false));

        $response->assertOk()
            ->assertViewIs('products.create')
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 1 && $categories->first()->id === $categoryA->id)
            ->assertViewHas('units', fn ($units) => $units->count() === 1 && $units->first()->id === $unitA->id);

        $this->assertNotEquals($categoryA->id, $categoryB->id);
        $this->assertNotEquals($unitA->id, $unitB->id);
    }

    public function test_customer_and_supplier_store_with_photo_branches(): void
    {
        $user = $this->createUser();

        $customerResponse = $this->actingAs($user)->post(route('customers.store', absolute: false), [
            'name' => 'Photo Customer',
            'email' => 'photo-customer@example.com',
            'phone' => '123456789',
            'address' => 'Athens',
            'photo' => UploadedFile::fake()->image('customer.jpg'),
        ]);
        $customerResponse->assertRedirect(route('customers.index', absolute: false));
        $this->assertDatabaseHas('customers', ['email' => 'photo-customer@example.com']);
        $this->assertNotNull(Customer::query()->where('email', 'photo-customer@example.com')->value('photo'));

        $supplierResponse = $this->actingAs($user)->post(route('suppliers.store', absolute: false), [
            'name' => 'Photo Supplier',
            'email' => 'photo-supplier@example.com',
            'phone' => '987654321',
            'shopname' => 'Photo Shop',
            'type' => 'distributor',
            'address' => 'Athens',
            'photo' => UploadedFile::fake()->image('supplier.jpg'),
        ]);
        $supplierResponse->assertRedirect(route('suppliers.index', absolute: false));
        $this->assertDatabaseHas('suppliers', ['email' => 'photo-supplier@example.com']);
        $this->assertNotNull(Supplier::query()->where('email', 'photo-supplier@example.com')->value('photo'));
    }

    public function test_user_controller_photo_update_destroy_and_password_validation_paths(): void
    {
        $admin = $this->createUser();

        $storeResponse = $this->actingAs($admin)->post(route('users.store', absolute: false), [
            'name' => 'Managed',
            'email' => 'managed-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'photo' => UploadedFile::fake()->image('user.jpg'),
        ]);
        $storeResponse->assertRedirect(route('users.index', absolute: false));

        $managed = User::query()->where('email', 'managed-user@example.com')->firstOrFail();
        $managed->username = 'managed-user';
        $managed->save();
        $this->assertNotNull($managed->photo);

        $oldPhoto = $managed->photo;
        $this->ensurePublicStorageFile('profile', $oldPhoto);

        $updateResponse = $this->actingAs($admin)->put(route('users.update', $managed, absolute: false), [
            'name' => 'Managed Updated',
            'email' => 'managed-user@example.com',
            'photo' => UploadedFile::fake()->image('updated-user.jpg'),
        ]);
        $updateResponse->assertRedirect(route('users.index', absolute: false));

        $managed->refresh();
        $this->assertNotSame($oldPhoto, $managed->photo);
        $this->ensurePublicStorageFile('profile', $managed->photo);

        $passwordValidationResponse = $this->actingAs($admin)->put(route('users.updatePassword', [
            'username' => $managed->username,
        ], absolute: false), [
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);
        $passwordValidationResponse->assertSessionHasErrors(['password', 'password_confirmation']);

        $passwordUpdateResponse = $this->actingAs($admin)->put(route('users.updatePassword', [
            'username' => $managed->username,
        ], absolute: false), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);
        $passwordUpdateResponse->assertRedirect(route('users.index', absolute: false));
        $this->assertTrue(Hash::check('new-password-123', $managed->fresh()->password));

        $deleteResponse = $this->actingAs($admin)->delete(route('users.destroy', $managed->fresh(), absolute: false));
        $deleteResponse->assertRedirect(route('users.index', absolute: false));
        $this->assertDatabaseMissing('users', ['id' => $managed->id]);
    }
}
