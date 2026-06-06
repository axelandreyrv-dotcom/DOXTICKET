<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'ticket_id', 'actor_user_id', 'actor_membership_id', 'type', 'payload'])]
class TicketEvent extends Model
{
    use BelongsToCompany;

    public const LABELS = [
        'ticket.created_manual' => 'Ticket creado manualmente',
        'ticket.created_from_mail' => 'Ticket creado desde correo',
        'mail.ticket_created' => 'Ticket creado desde correo',
        'mail.auto_reply_failed' => 'Confirmación automática fallida',
        'ticket.opened' => 'Ticket abierto',
        'ticket.status_changed' => 'Estado actualizado',
        'ticket.assigned_self' => 'Ticket tomado por agente',
        'ticket.assigned' => 'Agente actualizado',
        'ticket.priority_changed' => 'Prioridad actualizada',
        'ticket.type_changed' => 'Tipo actualizado',
        'ticket.note_added' => 'Nota interna agregada',
        'ticket.reply_sent' => 'Respuesta enviada',
        'ticket.merged' => 'Ticket fusionado',
        'ticket.merge_received' => 'Ticket duplicado fusionado',
        'ticket.attachment_added' => 'Adjunto agregado',
        'ticket.attachment_blocked' => 'Adjunto bloqueado',
        'ticket.mail_message_added' => 'Correo registrado',
        'mail.reply_received' => 'Respuesta recibida',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class)->withTrashed();
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'actor_membership_id');
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? 'Actividad registrada';
    }
}
