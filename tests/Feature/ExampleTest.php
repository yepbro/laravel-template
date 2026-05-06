<?php

declare(strict_types=1);

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_route_displays_the_home_page(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('home')
            ->assertViewHas('title', 'Laravel Frontend Playground');
    }
}
