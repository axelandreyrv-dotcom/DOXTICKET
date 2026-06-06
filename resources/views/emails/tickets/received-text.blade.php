Hola{{ $ticket->requester_name ? ' '.$ticket->requester_name : '' }},

Recibimos tu solicitud y fue registrada como {{ $ticket->public_key }}.

Asunto: {{ $ticket->subject }}

Nuestro equipo la revisara y respondera por este mismo correo.

--
{{ config('app.name', 'DoxTicket') }} / {{ $ticket->public_key }}
