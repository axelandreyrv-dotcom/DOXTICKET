<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_audit(): void
    {
        $this->get('/admin/audit')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_audit(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get('/admin/audit')
            ->assertForbidden();
    }

    public function test_superadmin_can_view_audit_log_entries(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'QA Company']);
        $agent = User::factory()->create([
            'name' => 'Ana Mesa',
            'email' => 'ana@example.test',
        ]);
        $membership = Membership::factory()->for($company)->for($agent)->create();

        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $agent->id,
            'actor_membership_id' => $membership->id,
            'action' => 'membership.accepted',
            'subject_type' => Membership::class,
            'subject_id' => $membership->id,
            'meta' => [
                'accepted_via' => 'password_reset',
            ],
            'ip' => '127.0.0.1',
            'user_agent' => 'Feature Test',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit')
            ->assertOk()
            ->assertSee('Auditoria')
            ->assertSee('membership.accepted')
            ->assertSee('QA Company')
            ->assertSee('Ana Mesa')
            ->assertSee('ana@example.test')
            ->assertSee('Membership #'.$membership->id)
            ->assertSee('password_reset')
            ->assertSee('Powered by DoxTicket');
    }

    public function test_admin_audit_redacts_sensitive_meta_values(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        AuditLog::query()->create([
            'action' => 'settings.changed',
            'meta' => [
                'smtp_password' => 'plain-secret',
                'oauth_token' => 'token-value',
                'safe_key' => 'visible-value',
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit')
            ->assertOk()
            ->assertSee('[redacted]')
            ->assertSee('visible-value')
            ->assertDontSee('plain-secret')
            ->assertDontSee('token-value');
    }

    public function test_admin_dashboard_links_to_audit_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Auditoria')
            ->assertSee('href="'.route('admin.audit.index').'"', false);
    }

    public function test_superadmin_can_filter_audit_logs_by_action_company_actor_and_date(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'Filtered Company']);
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);
        $actor = User::factory()->create([
            'name' => 'Filtered Actor',
            'email' => 'filtered@example.test',
        ]);
        $otherActor = User::factory()->create([
            'name' => 'Other Actor',
            'email' => 'other@example.test',
        ]);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'created_at' => now()->setDate(2026, 6, 5)->setTime(10, 0),
        ]);
        AuditLog::query()->create([
            'company_id' => $otherCompany->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'created_at' => now()->setDate(2026, 6, 5)->setTime(11, 0),
        ]);
        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $otherActor->id,
            'action' => 'admin.company.updated',
            'created_at' => now()->setDate(2026, 6, 5)->setTime(12, 0),
        ]);
        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.user.invited',
            'created_at' => now()->setDate(2026, 6, 5)->setTime(13, 0),
        ]);
        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'created_at' => now()->setDate(2026, 6, 4)->setTime(10, 0),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit?'.http_build_query([
                'action' => 'admin.company.updated',
                'company_id' => $company->id,
                'actor_user_id' => $actor->id,
                'date_from' => '2026-06-05',
                'date_to' => '2026-06-05',
            ]))
            ->assertOk()
            ->assertSee('admin.company.updated')
            ->assertSee('Filtered Company')
            ->assertSee('Filtered Actor')
            ->assertSee('2026-06-05 10:00')
            ->assertDontSee('2026-06-05 11:00')
            ->assertDontSee('2026-06-05 12:00')
            ->assertDontSee('2026-06-05 13:00')
            ->assertDontSee('2026-06-04 10:00');
    }

    public function test_admin_audit_filter_form_is_visible_and_preserves_values(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'QA Company']);
        $actor = User::factory()->create([
            'name' => 'Ana Mesa',
            'email' => 'ana@example.test',
        ]);
        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit?'.http_build_query([
                'action' => 'admin.company.updated',
                'company_id' => $company->id,
                'actor_user_id' => $actor->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-05',
            ]))
            ->assertOk()
            ->assertSee('Filtros')
            ->assertSee('name="action"', false)
            ->assertSee('value="admin.company.updated"', false)
            ->assertSee('name="company_id"', false)
            ->assertSee('value="'.$company->id.'" selected', false)
            ->assertSee('name="actor_user_id"', false)
            ->assertSee('value="'.$actor->id.'" selected', false)
            ->assertSee('name="date_from"', false)
            ->assertSee('value="2026-06-01"', false)
            ->assertSee('name="date_to"', false)
            ->assertSee('value="2026-06-05"', false);
    }

    public function test_superadmin_can_search_audit_logs_by_action_company_actor_or_subject(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'Searchable Company']);
        $actor = User::factory()->create([
            'name' => 'Searchable Actor',
            'email' => 'searchable@example.test',
        ]);
        $membership = Membership::factory()->for($company)->for($actor)->create();

        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'subject_type' => Membership::class,
            'subject_id' => $membership->id,
            'created_at' => now()->setDate(2026, 6, 5)->setTime(10, 0),
        ]);
        AuditLog::query()->create([
            'action' => 'admin.backup.manual_run',
            'created_at' => now()->setDate(2026, 6, 5)->setTime(11, 0),
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit?q=searchable%40example.test')
            ->assertOk()
            ->assertSee('Searchable Actor')
            ->assertSee('admin.company.updated')
            ->assertSee('2026-06-05 10:00')
            ->assertDontSee('2026-06-05 11:00');

        $this->actingAs($superadmin)
            ->get('/admin/audit?q=Membership')
            ->assertOk()
            ->assertSee('Membership #'.$membership->id)
            ->assertSee('2026-06-05 10:00')
            ->assertDontSee('2026-06-05 11:00');
    }

    public function test_admin_audit_search_field_is_visible_and_preserves_value(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit?q=backup')
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee('value="backup"', false);
    }

    public function test_regular_user_cannot_export_admin_audit(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/audit/export')
            ->assertForbidden();
    }

    public function test_superadmin_can_export_filtered_audit_logs_as_csv_without_sensitive_meta(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'Export Company']);
        $actor = User::factory()->create([
            'name' => 'Export Actor',
            'email' => 'export@example.test',
        ]);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'meta' => [
                'safe_key' => 'visible-value',
                'api_token' => 'secret-token',
            ],
            'created_at' => now()->setDate(2026, 6, 5)->setTime(10, 0),
        ]);
        AuditLog::query()->create([
            'action' => 'admin.backup.manual_run',
            'meta' => [
                'safe_key' => 'other-value',
            ],
            'created_at' => now()->setDate(2026, 6, 5)->setTime(11, 0),
        ]);

        $response = $this->actingAs($superadmin)
            ->get('/admin/audit/export?'.http_build_query([
                'q' => 'export@example.test',
                'action' => 'admin.company.updated',
                'company_id' => $company->id,
                'actor_user_id' => $actor->id,
                'date_from' => '2026-06-05',
                'date_to' => '2026-06-05',
            ]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('created_at,action,company,actor,subject,meta', $csv);
        $this->assertStringContainsString('admin.company.updated', $csv);
        $this->assertStringContainsString('Export Company', $csv);
        $this->assertStringContainsString('Export Actor (export@example.test)', $csv);
        $this->assertStringContainsString('visible-value', $csv);
        $this->assertStringContainsString('[redacted]', $csv);
        $this->assertStringNotContainsString('secret-token', $csv);
        $this->assertStringNotContainsString('admin.backup.manual_run', $csv);
    }

    public function test_admin_audit_export_is_audited_without_being_included_in_same_csv(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'Export Audit Company']);
        $actor = User::factory()->create(['email' => 'export-audit@example.test']);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'actor_user_id' => $actor->id,
            'action' => 'admin.company.updated',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($superadmin)
            ->get('/admin/audit/export?'.http_build_query([
                'q' => 'export-audit@example.test',
                'action' => 'admin.company.updated',
            ]));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('admin.company.updated', $csv);
        $this->assertStringNotContainsString('admin.audit.exported', $csv);

        $audit = AuditLog::query()
            ->where('action', 'admin.audit.exported')
            ->where('actor_user_id', $superadmin->id)
            ->firstOrFail();

        $this->assertSame([
            'q' => 'export-audit@example.test',
            'action' => 'admin.company.updated',
            'company_id' => null,
            'actor_user_id' => null,
            'date_from' => null,
            'date_to' => null,
        ], $audit->meta['filters']);
        $this->assertSame(1, $audit->meta['row_count']);
    }

    public function test_admin_audit_export_link_preserves_current_query(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/audit?q=backup&action=admin.backup.manual_run')
            ->assertOk()
            ->assertSee('href="'.str_replace('&', '&amp;', route('admin.audit.export', [
                'q' => 'backup',
                'action' => 'admin.backup.manual_run',
            ])).'"', false);
    }
}
