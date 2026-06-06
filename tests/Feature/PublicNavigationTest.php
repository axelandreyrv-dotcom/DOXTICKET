<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
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

    public function test_public_home_shows_installer_completed_when_setup_is_locked(): void
    {
        SystemSetting::put('setup.completed', true);

        $this->get('/')
            ->assertOk()
            ->assertSee('Instalador')
            ->assertSee('Completado')
            ->assertDontSee('Pendiente');
    }

    public function test_public_shell_uses_brand_logo_and_svg_favicon(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee('href="'.asset('brand/doxticket.svg').'"', false)
            ->assertSee('alt=""', false)
            ->assertSee('width="28"', false)
            ->assertSee('height="28"', false);
    }

    public function test_login_page_does_not_render_header_actions(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Setup');
        $this->assertSame(2, substr_count($response->getContent(), 'Entrar'));
    }

    public function test_public_forms_use_spanish_accents(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Contraseña')
            ->assertSee('Mantener sesión')
            ->assertDontSee('Contrasena')
            ->assertDontSee('Mantener sesion');

        $this->get('/setup')
            ->assertOk()
            ->assertSee('Español')
            ->assertSee('Contraseña')
            ->assertDontSee('Espanol')
            ->assertDontSee('Contrasena');
    }
}
