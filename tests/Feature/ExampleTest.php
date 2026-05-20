<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_returns_html_with_vue_mount_point(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('id="app"', false);
    }
}
