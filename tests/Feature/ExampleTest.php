<?php

declare(strict_types=1);

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_route_redirects_to_the_spa_showcase(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/spa');
    }
}
