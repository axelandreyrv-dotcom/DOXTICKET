Hola {{ $membership->user->name }}.

Te invitaron a DoxTicket para trabajar en {{ $membership->company->name }} con el rol {{ $membership->role }}.

@if ($passwordSetupUrl)
Define tu contraseña aquí:
{{ $passwordSetupUrl }}
@endif

Entra a {{ url('/login') }} con tu correo {{ $membership->user->email }}.

Si no esperabas esta invitacion, ignora este mensaje o contacta al administrador de la instalacion.

Powered by DoxTicket
