<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_dashboard_shell_renders_through_inertia(): void
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('appName', 'RebateMailerV3')
            );
    }
}
