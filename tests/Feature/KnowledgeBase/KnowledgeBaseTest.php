<?php

namespace Tests\Feature\KnowledgeBase;

use App\Models\Company;
use App\Models\KbArticle;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_search_and_read_published_articles_from_active_company(): void
    {
        [$user, $membership] = $this->tenantFixture('agent');
        $otherCompany = Company::factory()->create();

        KbArticle::factory()->for($membership->company)->create([
            'title' => 'Reinicio de VPN',
            'body_markdown' => 'Pasos internos para reiniciar el servicio.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        KbArticle::factory()->for($membership->company)->create([
            'title' => 'Borrador interno',
            'status' => 'draft',
        ]);

        KbArticle::factory()->for($otherCompany)->create([
            'title' => 'Articulo de otra empresa',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.index', ['q' => 'VPN']))
            ->assertOk()
            ->assertSee('Reinicio de VPN')
            ->assertDontSee('Borrador interno')
            ->assertDontSee('Articulo de otra empresa');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.show', 'reinicio-de-vpn'))
            ->assertOk()
            ->assertSee('Pasos internos para reiniciar el servicio.');
    }

    public function test_admin_can_create_a_published_article_with_sanitized_markdown(): void
    {
        [$user, $membership] = $this->tenantFixture('admin');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.kb.store'), [
                'title' => 'Reset seguro de impresora',
                'body_markdown' => "## Procedimiento\n\n<script>alert('x')</script>\n\nReiniciar desde panel.",
                'status' => 'published',
            ])
            ->assertRedirect(route('app.kb.show', 'reset-seguro-de-impresora'));

        $this->assertDatabaseHas('kb_articles', [
            'company_id' => $membership->company_id,
            'author_membership_id' => $membership->id,
            'title' => 'Reset seguro de impresora',
            'slug' => 'reset-seguro-de-impresora',
            'status' => 'published',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.show', 'reset-seguro-de-impresora'))
            ->assertOk()
            ->assertSee('<h2>Procedimiento</h2>', false)
            ->assertSee('Reiniciar desde panel.')
            ->assertDontSee('<script>', false);
    }

    public function test_agent_cannot_create_articles(): void
    {
        [$user, $membership] = $this->tenantFixture('agent');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.kb.store'), [
                'title' => 'No autorizado',
                'body_markdown' => 'Contenido',
                'status' => 'published',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('kb_articles', [
            'title' => 'No autorizado',
        ]);
    }

    public function test_admin_can_edit_archive_and_delete_an_article(): void
    {
        [$user, $membership] = $this->tenantFixture('admin');
        $article = KbArticle::factory()->for($membership->company)->create([
            'title' => 'Procedimiento antiguo',
            'body_markdown' => 'Contenido anterior.',
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.edit', $article->slug))
            ->assertOk()
            ->assertSee('Procedimiento antiguo')
            ->assertSee(route('app.kb.update', $article->slug), false);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->patch(route('app.kb.update', $article->slug), [
                'title' => 'Procedimiento actualizado',
                'body_markdown' => 'Contenido actualizado.',
                'status' => 'published',
            ])
            ->assertRedirect(route('app.kb.show', 'procedimiento-actualizado'));

        $article->refresh();
        $this->assertSame('Procedimiento actualizado', $article->title);
        $this->assertSame('procedimiento-actualizado', $article->slug);
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->patch(route('app.kb.archive', $article->slug))
            ->assertRedirect(route('app.kb.show', $article->fresh()->slug));

        $this->assertSame('archived', $article->fresh()->status);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.show', $article->fresh()->slug))
            ->assertOk()
            ->assertSee('Archivado');

        $agent = User::factory()->create();
        $agentMembership = Membership::factory()->for($agent)->for($membership->company)->create(['role' => 'agent', 'status' => 'active']);

        $this->actingAs($agent)
            ->withSession(['active_membership_id' => $agentMembership->id])
            ->get(route('app.kb.show', $article->fresh()->slug))
            ->assertNotFound();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->delete(route('app.kb.destroy', $article->fresh()->slug))
            ->assertRedirect(route('app.kb.index'));

        $this->assertSoftDeleted('kb_articles', [
            'id' => $article->id,
        ]);
    }

    public function test_agent_cannot_edit_articles(): void
    {
        [$user, $membership] = $this->tenantFixture('agent');
        $article = KbArticle::factory()->for($membership->company)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.edit', $article->slug))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->patch(route('app.kb.update', $article->slug), [
                'title' => 'Cambio no permitido',
                'body_markdown' => 'Contenido',
                'status' => 'published',
            ])
            ->assertForbidden();
    }

    public function test_management_actions_include_confirmation_messages(): void
    {
        [$user, $membership] = $this->tenantFixture('admin');
        $article = KbArticle::factory()->for($membership->company)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.show', $article->slug))
            ->assertOk()
            ->assertSee('data-confirm="Archivar este artículo lo ocultará para agentes. ¿Continuar?"', false)
            ->assertSee('data-confirm="Borrar este artículo lo enviará a la papelera interna. ¿Continuar?"', false)
            ->assertSee('id="confirm-dialog"', false)
            ->assertSee('aria-labelledby="confirm-dialog-title"', false)
            ->assertSee('aria-describedby="confirm-dialog-message"', false)
            ->assertSee('data-confirm-cancel', false)
            ->assertSee('data-confirm-accept', false);
    }

    public function test_knowledge_base_search_field_uses_explicit_browser_metadata(): void
    {
        [$user, $membership] = $this->tenantFixture('agent');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.kb.index'))
            ->assertOk()
            ->assertSee('type="search" name="q"', false)
            ->assertSee('placeholder="Título o contenido…"', false)
            ->assertSee('autocomplete="off"', false);
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(string $role): array
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => $role, 'status' => 'active']);

        return [$user, $membership];
    }
}
