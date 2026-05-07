<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
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
        $response->assertSee('Laravel Frontend Playground', false);
        $response->assertSee('Clean Laravel frontends', false);
        $response->assertSee(
            'A clean starting point for building modern Laravel applications with Tailwind and contemporary UI practices.',
            false,
        );
        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
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
        $response->assertDontSee('href="/islands"', false);
    }
}
