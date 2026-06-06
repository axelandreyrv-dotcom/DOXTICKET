<?php

namespace Tests\Feature\Tickets;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_upload_private_attachment_to_ticket(): void
    {
        Storage::fake('private');

        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create(['status' => 'open']);
        $file = UploadedFile::fake()->createWithContent('diagnostico.txt', 'Resultado de diagnostico.');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.attachments.store', $ticket->public_key), [
                'attachment' => $file,
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $attachment = Attachment::withoutTenant()->firstOrFail();

        $this->assertSame($membership->company_id, $attachment->company_id);
        $this->assertSame($ticket->id, $attachment->ticket_id);
        $this->assertSame('diagnostico.txt', $attachment->filename);
        $this->assertSame('private', $attachment->disk);
        $this->assertStringStartsWith('attachments/'.$membership->company_id.'/'.$ticket->id.'/', $attachment->path);
        Storage::disk('private')->assertExists($attachment->path);

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.attachment_added',
        ]);
    }

    public function test_agent_can_download_attachment_from_active_company_only(): void
    {
        Storage::fake('private');

        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create(['status' => 'open']);
        $attachment = Attachment::withoutTenant()->create([
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'filename' => 'evidencia.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 9,
            'disk' => 'private',
            'path' => 'attachments/'.$membership->company_id.'/'.$ticket->id.'/evidencia.txt',
            'checksum_sha256' => hash('sha256', 'contenido'),
        ]);
        Storage::disk('private')->put($attachment->path, 'contenido');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.attachments.download', $attachment->uuid))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('cache-control', 'no-store, private');

        $otherCompany = Company::factory()->create();
        $otherAttachment = Attachment::withoutTenant()->create([
            'company_id' => $otherCompany->id,
            'ticket_id' => Ticket::factory()->for($otherCompany)->create()->id,
            'filename' => 'otra-empresa.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 5,
            'disk' => 'private',
            'path' => 'attachments/'.$otherCompany->id.'/1/otra-empresa.txt',
            'checksum_sha256' => hash('sha256', 'other'),
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.attachments.download', $otherAttachment->uuid))
            ->assertNotFound();
    }

    public function test_dangerous_attachment_is_blocked_and_recorded_as_internal_event(): void
    {
        Storage::fake('private');

        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create(['status' => 'open']);
        $file = UploadedFile::fake()->createWithContent('limpieza.bat', '@echo off');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.attachments.store', $ticket->public_key), [
                'attachment' => $file,
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('attachment');

        $this->assertSame(0, Attachment::withoutTenant()->count());
        Storage::disk('private')->assertMissing('limpieza.bat');

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.attachment_blocked',
        ]);
    }

    public function test_manual_attachment_upload_uses_configured_size_limit(): void
    {
        Storage::fake('private');
        Config::set('doxticket.attachments.max_bytes', 1024);

        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create(['status' => 'open']);
        $file = UploadedFile::fake()->createWithContent('evidencia.txt', str_repeat('x', 2048));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.attachments.store', $ticket->public_key), [
                'attachment' => $file,
            ])
            ->assertSessionHasErrors('attachment');

        $this->assertSame(0, Attachment::withoutTenant()->count());
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'agent', 'status' => 'active']);

        return [$user, $membership];
    }
}
