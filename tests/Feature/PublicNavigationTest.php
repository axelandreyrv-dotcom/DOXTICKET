<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_keeps_login_visible_without_setup_link(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Login');
        $response->assertDontSee('Setup');
    }

    public function test_login_page_does_not_render_header_actions(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Setup');
        $this->assertSame(2, substr_count($response->getContent(), 'Entrar'));
    }
}
