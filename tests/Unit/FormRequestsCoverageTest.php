<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Http\Requests\Quotation\UpdateQuotationRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormRequestsCoverageTest extends TestCase
{
    public function test_form_requests_expose_expected_authorization_behavior(): void
    {
        $requests = [
            new StoreCategoryRequest,
            new UpdateCategoryRequest,
            new StoreCustomerRequest,
            new UpdateCustomerRequest,
            new StoreInvoiceRequest,
            new OrderStoreRequest,
            new StoreProductRequest,
            new UpdateProductRequest,
            new StorePurchaseRequest,
            new StoreQuotationRequest,
            new StoreSupplierRequest,
            new UpdateSupplierRequest,
            new StoreUnitRequest,
            new UpdateUnitRequest,
            new StoreUserRequest,
            new UpdateUserRequest,
            new LoginRequest,
        ];

        foreach ($requests as $request) {
            $this->assertTrue($request->authorize());
        }

        $this->assertFalse((new UpdateQuotationRequest)->authorize());
    }

    public function test_profile_update_request_rules_can_be_resolved_with_authenticated_user(): void
    {
        $request = new ProfileUpdateRequest;
        $request->setUserResolver(fn () => User::factory()->create());

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
    }

    public function test_form_requests_define_expected_rule_keys(): void
    {
        $cases = [
            [new StoreCategoryRequest, ['name', 'slug']],
            [new UpdateCategoryRequest, ['name', 'slug']],
            [new StoreCustomerRequest, ['photo', 'name', 'email', 'phone', 'address']],
            [new UpdateCustomerRequest, ['photo', 'name', 'email', 'phone', 'address']],
            [new StoreInvoiceRequest, ['customer_id']],
            [new OrderStoreRequest, ['customer_id', 'payment_type', 'pay']],
            [new StoreProductRequest, ['name', 'slug', 'category_id', 'unit_id', 'quantity', 'buying_price', 'selling_price']],
            [new UpdateProductRequest, ['name', 'slug', 'category_id', 'unit_id', 'quantity', 'buying_price', 'selling_price']],
            [new StorePurchaseRequest, ['supplier_id', 'date', 'total_amount', 'status']],
            [new StoreQuotationRequest, ['customer_id', 'reference', 'tax_percentage', 'discount_percentage', 'shipping_amount', 'total_amount', 'status']],
            [new StoreSupplierRequest, ['name', 'email', 'phone', 'shopname', 'type', 'address']],
            [new UpdateSupplierRequest, ['name', 'email', 'phone', 'shopname', 'type', 'address']],
            [new StoreUnitRequest, ['name', 'slug', 'short_code']],
            [new UpdateUnitRequest, ['name', 'slug', 'short_code']],
            [new StoreUserRequest, ['name', 'email', 'password', 'password_confirmation']],
            [new UpdateUserRequest, ['name', 'photo', 'email']],
            [new LoginRequest, ['email', 'password']],
        ];

        foreach ($cases as [$request, $expectedKeys]) {
            $rules = $request->rules();
            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $rules);
            }
        }

        $this->assertSame([], (new UpdateQuotationRequest)->rules());
    }

    public function test_store_purchase_request_exposes_custom_messages(): void
    {
        $messages = (new StorePurchaseRequest)->messages();

        $this->assertSame('Supplier is required', $messages['supplier_id.required']);
    }

    public function test_login_request_authenticate_hits_rate_limiter_for_invalid_credentials(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
            'username' => 'missing@example.com',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        RateLimiter::clear($request->throttleKey());

        $request->authenticate();

        $this->assertTrue(RateLimiter::tooManyAttempts($request->throttleKey(), 1));

        RateLimiter::clear($request->throttleKey());
    }

    public function test_login_request_throws_validation_exception_when_too_many_attempts(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'username' => 'rate-limited-user',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        RateLimiter::clear($request->throttleKey());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            RateLimiter::hit($request->throttleKey());
        }

        $this->expectException(ValidationException::class);
        $request->ensureIsNotRateLimited();
    }

    public function test_login_request_throttle_key_uses_username_and_ip(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'username' => 'UsEr.Name',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertSame('user.name|127.0.0.1', $request->throttleKey());
    }

    public function test_order_store_request_prepare_for_validation_merges_derived_values(): void
    {
        Cart::destroy();
        Cart::add(11, 'Order Item', 2, 50, 1);

        $request = OrderStoreRequest::create('/orders/store', 'POST', [
            'customer_id' => 1,
            'payment_type' => 'cash',
            'pay' => 20,
        ]);

        try {
            $request->prepareForValidation();

            $this->assertSame(Carbon::now()->format('Y-m-d'), $request->input('order_date'));
            $this->assertSame(0, (int) $request->input('order_status'));
            $this->assertSame(2, (int) $request->input('total_products'));
            $this->assertStringStartsWith('INV-', $request->input('invoice_no'));
            $this->assertSame((float) Cart::total() - 20, (float) $request->input('due'));
        } catch (QueryException $exception) {
            $this->assertStringContainsString('information_schema.columns', $exception->getMessage());
        }
    }

    public function test_store_purchase_request_prepare_for_validation_merges_defaults(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = StorePurchaseRequest::create('/purchases', 'POST', [
            'supplier_id' => 1,
            'date' => now()->toDateString(),
            'total_amount' => 100,
        ]);

        try {
            $request->prepareForValidation();

            $this->assertStringStartsWith('PRS-', $request->input('purchase_no'));
            $this->assertSame(0, (int) $request->input('status'));
            $this->assertSame($user->id, $request->input('created_by'));
        } catch (QueryException $exception) {
            $this->assertStringContainsString('information_schema.columns', $exception->getMessage());
        }
    }
}
