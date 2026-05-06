<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\IslandsController;
use App\Http\Controllers\SpaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FrontendLayoutsTest extends TestCase
{
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

    public function test_root_page_renders_centered_home_stub_without_showcase_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Laravel Frontend Playground');
        $response->assertSee('A compact starter for trying different frontend approaches in Laravel.');
        $response->assertDontSee('href="/spa"', false);
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
        $response->assertSee('href="/spa"', false);
        $response->assertSee('href="/islands"', false);
    }

    public function test_islands_route_uses_single_action_controller(): void
    {
        $route = Route::getRoutes()->match(Request::create('/islands', 'GET'));

        $this->assertSame(IslandsController::class, $route->getActionName());
    }

    public function test_islands_page_uses_blade_layout_with_vue_mount_points(): void
    {
        $response = $this->get('/islands');

        $response->assertOk();
        $response->assertSee('Blade + Vue islands');
        $response->assertSee('id="toast-root"', false);
        $response->assertSee('href="/spa"', false);
        $response->assertSee('href="/islands"', false);
        $response->assertSee('data-island="form-demo"', false);
        $response->assertSee('data-island="table-demo"', false);
        $response->assertSee('data-island="toast-demo"', false);
    }
}
