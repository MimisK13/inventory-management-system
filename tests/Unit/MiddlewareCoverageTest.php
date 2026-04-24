<?php

namespace Tests\Unit;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MiddlewareCoverageTest extends TestCase
{
    public function test_authenticate_redirect_to_returns_login_route_for_non_json_requests(): void
    {
        $middleware = new class(app('auth')) extends Authenticate
        {
            public function publicRedirectTo(Request $request): ?string
            {
                return $this->redirectTo($request);
            }
        };

        $request = Request::create('/protected', 'GET');

        $this->assertSame(route('login'), $middleware->publicRedirectTo($request));
    }

    public function test_authenticate_redirect_to_returns_null_for_json_requests(): void
    {
        $middleware = new class(app('auth')) extends Authenticate
        {
            public function publicRedirectTo(Request $request): ?string
            {
                return $this->redirectTo($request);
            }
        };

        $request = Request::create('/api/protected', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertNull($middleware->publicRedirectTo($request));
    }

    public function test_redirect_if_authenticated_redirects_authenticated_users_to_home(): void
    {
        if (! class_exists(RouteServiceProvider::class)) {
            eval('namespace App\Providers; class RouteServiceProvider { public const HOME = "/dashboard"; }');
        }

        $this->actingAs($this->createUser());

        $middleware = new RedirectIfAuthenticated;
        $request = Request::create('/login', 'GET');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('/dashboard', (string) $response->headers->get('Location'));
    }

    public function test_redirect_if_authenticated_allows_guests_to_continue(): void
    {
        $middleware = new RedirectIfAuthenticated;
        $request = Request::create('/login', 'GET');

        $response = $middleware->handle(
            $request,
            fn () => new Response('ok', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
