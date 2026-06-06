<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminActionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_actions_are_audited(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/companies', [
                'name' => 'Audit Company',
                'slug' => 'audit-company',
                'country' => 'CR',
                'phone' => '',
                'status' => 'active',
                'locale_default' => 'es',
            ])
            ->assertRedirect('/admin/companies');

        $company = Company::query()->where('slug', 'audit-company')->firstOrFail();

        $this->actingAs($superadmin)
            ->put("/admin/companies/{$company->id}", [
                'name' => 'Audit Company Updated',
                'slug' => 'audit-company',
                'country' => 'CR',
                'phone' => '',
                'status' => 'active',
                'locale_default' => 'es',
            ])
            ->assertRedirect('/admin/companies');

        $this->actingAs($superadmin)
            ->post("/admin/companies/{$company->id}/status", [
                'status' => 'disabled',
            ])
            ->assertRedirect('/admin/companies');

        $this->actingAs($superadmin)
            ->delete("/admin/companies/{$company->id}")
            ->assertRedirect('/admin/companies');

        $this->assertAdminAuditExists('admin.company.created', $superadmin, $company);
        $this->assertAdminAuditExists('admin.company.updated', $superadmin, $company);
        $this->assertAdminAuditExists('admin.company.status_changed', $superadmin, $company);
        $this->assertAdminAuditExists('admin.company.deleted', $superadmin, $company);
    }

    public function test_user_and_membership_admin_actions_are_audited(): void
    {
        Mail::fake();

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['status' => 'active']);

        $this->actingAs($superadmin)
            ->post('/admin/users/invite', [
                'name' => 'Audit Agent',
                'email' => 'audit-agent@example.test',
                'company_id' => $company->id,
                'role' => 'agent',
            ])
            ->assertRedirect('/admin/users');

        $agent = User::query()->where('email', 'audit-agent@example.test')->firstOrFail();
        $membership = Membership::query()
            ->where('company_id', $company->id)
            ->where('user_id', $agent->id)
            ->firstOrFail();

        $this->actingAs($superadmin)
            ->post("/admin/users/{$agent->id}/status", [
                'is_active' => '0',
            ])
            ->assertRedirect('/admin/users');

        $this->actingAs($superadmin)
            ->put("/admin/memberships/{$membership->id}", [
                'role' => 'supervisor',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/users');

        $this->assertAdminAuditExists('admin.user.invited', $superadmin, $membership);
        $this->assertAdminAuditExists('admin.user.status_changed', $superadmin, $agent);
        $this->assertAdminAuditExists('admin.membership.updated', $superadmin, $membership);
    }

    public function test_telemetry_and_update_check_actions_are_audited(): void
    {
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        Http::fake([
            'https://api.github.com/repos/doxsuite/doxticket/releases/latest' => Http::response([
                'tag_name' => 'v1.0.1',
                'html_url' => 'https://github.com/doxsuite/doxticket/releases/tag/v1.0.1',
                'published_at' => '2026-06-01T00:00:00Z',
            ], 200),
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/telemetry', ['telemetry_enabled' => true])
            ->assertRedirect('/admin');

        $this->actingAs($superadmin)
            ->post('/admin/updates/check')
            ->assertRedirect('/admin');

        $this->assertAdminAuditExists('admin.telemetry.updated', $superadmin);
        $this->assertAdminAuditExists('admin.updates.checked', $superadmin);
    }

    public function test_backup_and_rollback_actions_are_audited(): void
    {
        Storage::fake('private');

        $databasePath = storage_path('framework/testing-admin-action-audit.sqlite');
        file_put_contents($databasePath, 'sqlite-admin-action-audit-content');

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $databasePath);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/backups')
            ->assertRedirect('/admin');

        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'size_bytes' => 1024,
            'meta' => ['rollback_available' => true],
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/rollback')
            ->assertRedirect('/admin');

        $this->assertAdminAuditExists('admin.backup.manual_run', $superadmin);
        $this->assertAdminAuditExists('admin.rollback.preflight_requested', $superadmin);

        @unlink($databasePath);
    }

    private function assertAdminAuditExists(string $action, User $actor, ?object $subject = null): void
    {
        $query = AuditLog::query()
            ->where('action', $action)
            ->where('actor_user_id', $actor->id);

        if ($subject !== null) {
            $query->where('subject_type', $subject::class)
                ->where('subject_id', $subject->id);
        }

        $this->assertTrue($query->exists(), "Audit log [{$action}] was not recorded.");
    }
}
