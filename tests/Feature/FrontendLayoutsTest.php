<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SpaController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FrontendLayoutsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_root_route_uses_single_action_controller(): void
    {
        $route = Route::getRoutes()->match(Request::create('/', 'GET'));

        $this->assertSame(HomeController::class, $route->getActionName());
    }

    public function test_root_page_serves_vue_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="app"', false);
        $response->assertSee('Laravel Frontend Playground', false);
        $response->assertDontSee('href="/islands"', false);
    }

    public function test_spa_route_uses_single_action_controller(): void
    {
        $route = Route::getRoutes()->match(Request::create('/spa', 'GET'));

        $this->assertSame(SpaController::class, $route->getActionName());
    }

    public function test_spa_page_uses_vue_layout_shell(): void
    {
        $response = $this->get('/spa');

        $response->assertOk();
        $response->assertSee('id="app"', false);
        $response->assertDontSee('href="/islands"', false);
    }

    public function test_account_spa_route_uses_single_action_controller(): void
    {
        $route = Route::getRoutes()->match(
            Request::create('/account/profile', 'GET'),
        );

        $this->assertSame(SpaController::class, $route->getActionName());
    }

    public function test_guest_access_to_account_root_redirects_to_login(): void
    {
        $response = $this->get('/account');

        $response->assertRedirect(route('login', [], absolute: false));
    }

    public function test_guest_access_to_account_spa_redirects_to_login(): void
    {
        $response = $this->get('/account/profile');

        $response->assertRedirect(route('login', [], absolute: false));
    }

    public function test_authenticated_account_root_serves_vue_shell(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account');

        $response->assertOk();
        $response->assertSee('id="app"', false);
        $response->assertDontSee('href="/islands"', false);
    }

    public function test_authenticated_account_spa_page_serves_vue_shell(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account/profile');

        $response->assertOk();
        $response->assertSee('id="app"', false);
        $response->assertDontSee('href="/islands"', false);
    }
}
