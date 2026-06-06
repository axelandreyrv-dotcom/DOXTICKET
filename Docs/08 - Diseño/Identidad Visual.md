# Identidad Visual — DoxTicket

## Proposito
Definir la identidad visual inicial de DoxTicket como proyecto open source self-hosted.

## Nombre
Correcto: **DoxTicket**

Incorrecto: Doxticket, doxticket, Dox Ticket.

## Descripcion corta
> DoxTicket es un helpdesk open source para equipos de TI que quieren controlar su correo, tickets y datos en una instalacion propia.

## Personalidad visual
- Minimalista.
- Clara.
- Profesional.
- Calmada.
- Rapida de entender.
- Sin apariencia generica de IA.

## Branding obligatorio
- `Powered by DoxTicket` debe mantenerse en el footer de cada instalacion.
- Logo principal: `Brand/DoxTicketSVG.svg`.
- Las vistas usan `public/brand/doxticket.svg` como logo visible y favicon SVG.
- Cuando el nombre DoxTicket aparece junto al simbolo, el `<img>` del logo se trata como decorativo con `alt=""` para evitar lectura duplicada.

## Color
Modo claro es la experiencia principal de v1. La paleta debe ser profesional, calmada y utilitaria:
- Fondo casi blanco o gris muy suave.
- Superficies blancas con borde fino.
- Texto en charcoal/slate, nunca negro puro.
- Acento azul profesional usado con moderacion.
- Estados semanticos distinguibles por texto/icono ademas de color.
- Sin gradientes dominantes, sombras pesadas ni efectos decorativos.

Tokens iniciales:

```css
:root {
  --color-bg-page: #F8FAFC;
  --color-bg-surface: #FFFFFF;
  --color-bg-surface-alt: #F1F5F9;
  --color-text-primary: #0F172A;
  --color-text-secondary: #475569;
  --color-text-muted: #94A3B8;
  --color-border-default: #E2E8F0;
  --color-action-primary: #2563EB;
  --color-action-primary-hover: #1D4ED8;
  --color-success: #15803D;
  --color-warning: #B45309;
  --color-danger: #B91C1C;
  --color-info: #2563EB;
}
```

Modo oscuro no es compromiso v1. Si se implementa despues, debe ser funcional y sobrio, no una reinterpretacion visual completa.

## Tipografia
Evitar una apariencia generica. Opciones recomendadas:
- UI: Geist Sans, SF Pro, Helvetica Neue, Switzer o system sans.
- Monospace: Geist Mono, SF Mono o JetBrains Mono.

La seleccion final debe priorizar legibilidad en tablas, tickets y dashboards.

## Iconografia
- Consistente en toda la app.
- En Filament se puede usar el set nativo.
- En vistas propias, preferir iconos sobrios y reconocibles.
- No mezclar estilos.

## Tono de comunicacion
- Directo.
- Humano.
- Tecnico solo cuando ayuda.
- Sin copy exagerado.
- Espanol por defecto, ingles disponible.

## Donaciones
Los enlaces de donacion son discretos. Nunca interrumpen la operacion.

## Relacion
- `UI UX.md`
- `Logo.md`
