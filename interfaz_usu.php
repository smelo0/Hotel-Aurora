<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/includes/sesion_seguridad.php';

require_once __DIR__ . '/configuracion/conexion.php';
require_once __DIR__ . '/configuracion/wompi.php';
require_once __DIR__ . '/src/Usuario/PortalRepository.php';
require_once __DIR__ . '/src/Usuario/PortalService.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$portalRepository = new \App\Usuario\PortalRepository();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function roomImage(string $type): string
{
    $type = mb_strtolower($type, 'UTF-8');

    if (str_contains($type, 'suite premium')) {
        return 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=85&w=1200';
    }
    if (str_contains($type, 'suite')) {
        return 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&q=85&w=1200';
    }
    if (str_contains($type, 'doble')) {
        return 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=85&w=1200';
    }

    return 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=85&w=1200';
}

function roomFeatures(string $type): array
{
    $type = mb_strtolower($type, 'UTF-8');

    if (str_contains($type, 'suite premium')) {
        return ['Vista al mar', 'Jacuzzi privado', 'Lounge exclusivo', 'Desayuno de autor'];
    }
    if (str_contains($type, 'suite')) {
        return ['Cama king', 'Balcón privado', 'Room service', 'Wi-Fi premium'];
    }
    if (str_contains($type, 'doble')) {
        return ['Cama queen', 'Escritorio', 'Smart TV', 'Climatización'];
    }

    return ['Cama doble', 'Baño privado', 'Wi-Fi', 'Caja fuerte'];
}

function formatReservationHistoryDate(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'Sin fecha';
    }

    $date = DateTime::createFromFormat('Y-m-d H:i:s', $value)
        ?: DateTime::createFromFormat('Y-m-d', $value);

    if ($date === false) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    return $date->format('d/m/Y');
}

function reservationStatusBadge(string $estado): string
{
    $estado = trim($estado);
    $map = [
        'Confirmada' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        'Pendiente' => 'bg-amber-100 text-amber-800 border border-amber-200',
        'Cancelada' => 'bg-rose-100 text-rose-800 border border-rose-200',
        'Cancelado' => 'bg-rose-100 text-rose-800 border border-rose-200',
        'Finalizada' => 'bg-slate-200 text-slate-800 border border-slate-300',
        'En Casa' => 'bg-cyan-100 text-cyan-800 border border-cyan-200',
    ];

    return $map[$estado] ?? 'bg-slate-100 text-slate-800 border border-slate-200';
}

function experienciaImagen(string $categoria, int $indice, ?string $imagen = null): string
{
    if ($imagen !== null && preg_match('#^assets/uploads/experiencias/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $imagen)) {
        return $imagen;
    }

    $categoria = mb_strtolower($categoria, 'UTF-8');

    if (str_contains($categoria, 'gastronom') || str_contains($categoria, 'sabor')) {
        return 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=85&w=900';
    }
    if (str_contains($categoria, 'spa') || str_contains($categoria, 'bienestar') || str_contains($categoria, 'relax') || str_contains($categoria, 'comfor')) {
        return 'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&q=85&w=900';
    }
    if (str_contains($categoria, 'aventura') || str_contains($categoria, 'nautic') || str_contains($categoria, 'diversion') || str_contains($categoria, 'entretenimiento')) {
        return 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&q=85&w=900';
    }

    $galeria = [
        'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&q=85&w=900',
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=85&w=900',
        'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&q=85&w=900',
    ];

    return $galeria[$indice % count($galeria)];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require __DIR__ . '/controladores/portal_huesped.php';
    exit;
}

$portalData = (new \App\Usuario\PortalService($portalRepository))->load($conexion, $_SESSION);
$habitaciones = $portalData['habitaciones'];
$experiencias = $portalData['experiencias'];
$usuarioId = $portalData['usuarioId'];
$usuarioNombre = $portalData['usuarioNombre'];
$usuarioAutenticado = $portalData['usuarioAutenticado'];
$usuarioTieneReserva = $portalData['usuarioTieneReserva'];
$reservasEstanciaExperiencia = $portalData['reservasEstanciaExperiencia'];
$historialReservas = $portalData['historialReservas'];
$historialExperiencias = $portalData['historialExperiencias'];

$recaptchaReservaActivo = filter_var(
    getenv('RECAPTCHA_RESERVAS') ?: ($_ENV['RECAPTCHA_RESERVAS'] ?? $_SERVER['RECAPTCHA_RESERVAS'] ?? 'false'),
    FILTER_VALIDATE_BOOLEAN
);
$recaptchaSiteKey = $recaptchaReservaActivo ? trim((string) (
    getenv('RECAPTCHA_SITE_KEY')
    ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')
)) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aurora | Reservas y Experiencias</title>
    <link rel="stylesheet" href="assets/css/estilos_base.css?v=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;700" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/flatpickr/flatpickr.min.css">
    <script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
    <script src="assets/vendor/flatpickr/es.js"></script>
    <script src="assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="https://checkout.wompi.co/widget.js" async defer></script>
    <?php if ($recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/interfaz_usu.css?v=25">
    <script>
        window.PORTAL_CONFIG = <?php echo json_encode([
            'wompiPublicKey' => WOMPI_PUBLIC_KEY,
            'csrfToken' => csrf_token(),
            'recaptchaSiteKey' => $recaptchaSiteKey,
            'isUserAuthenticated' => $usuarioAutenticado,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body>
    <!-- Barra flotante de cuartos seleccionados -->
    <div id="roomCart" class="fixed bottom-5 left-1/2 z-[85] hidden w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2">
        <div class="glass-card flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-emerald-400/35 px-5 py-4 shadow-2xl sm:rounded-full sm:px-6">
            <div class="flex items-center gap-3.5 min-w-0">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                    <span class="material-symbols-outlined notranslate" translate="no">bed</span>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-emerald-200">Cuartos seleccionados</p>
                    <p id="roomCartSummary" class="truncate text-sm font-extrabold text-white sm:text-base">0 habitaciones</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 ml-auto">
                <button id="roomCartClear" type="button" class="rounded-full border border-white/20 px-4 py-2.5 text-xs font-black uppercase tracking-[0.14em] text-white/80 transition hover:bg-white/10">
                    Limpiar
                </button>
                <button id="roomCartCheckout" type="button" class="hero-button gold-button inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-xs font-black uppercase tracking-[0.14em]">
                    <span>Continuar a la reserva</span>
                    <span class="material-symbols-outlined text-[18px] notranslate" translate="no">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Barra de navegación principal -->
    <nav class="site-nav fixed inset-x-0 top-0 z-50">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
            <a href="#inicio" class="flex items-center gap-3 text-white shrink-0">
                <span class="logo">
                    <img src="assets/images/logo.jpeg" alt="Logo Hotel Aurora" class="h-full w-full object-cover">
                </span>
                <span class="font-display text-2xl leading-none text-white sm:text-[26px]">Hotel Aurora</span>
            </a>

            <div class="hidden items-center gap-6 text-sm font-bold text-white/85 lg:flex">
                <a href="#habitaciones" class="transition hover:text-[#f0d39a]">Habitaciones</a>
                <a href="#experiencias" class="transition hover:text-[#f0d39a]">Experiencias</a>
                <a href="#planner" class="transition hover:text-[#f0d39a]">Agenda</a>
                <?php if ($usuarioAutenticado): ?>
                    <a href="#room-service" class="transition hover:text-[#f0d39a]">Room service</a>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                <?php include __DIR__ . '/includes/translate.php'; ?>

                <?php if ($usuarioAutenticado): ?>
                    <?php if ($usuarioTieneReserva || !empty($historialReservas)): ?>
                        <a href="#historial" class="inline-flex items-center gap-1.5 rounded-full border border-[#f0d39a]/40 bg-[#f0d39a]/15 px-3.5 py-1.5 text-xs font-black uppercase tracking-[0.1em] text-[#f0d39a] transition hover:bg-[#f0d39a]/25">
                            <span class="material-symbols-outlined text-[16px] notranslate" translate="no">receipt_long</span>
                            <span>Mis reservas</span>
                            <?php if (!empty($historialReservas)): ?>
                                <span class="rounded-full bg-[#f0d39a] px-1.5 py-0.2 text-[10px] font-black text-slate-950"><?php echo count($historialReservas); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <span class="hidden max-w-[170px] truncate rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold text-white md:inline-block" title="<?php echo e($usuarioNombre); ?>">
                        <?php echo e($usuarioNombre); ?>
                    </span>
                    <button id="logoutButton" type="button" class="hero-button secondary-button px-3 py-1.5 text-xs font-bold transition">
                        Salir
                    </button>
                <?php else: ?>
                    <a href="interfaz/loggins/index_usu.php" class="hero-button secondary-button px-3.5 py-2 text-xs font-bold sm:text-sm">
                        Iniciar sesión
                    </a>
                    <a href="interfaz/loggins/index_usu.php?vista=registro" class="hero-button bg-white px-3.5 py-2 text-xs font-extrabold text-slate-900 sm:text-sm hover:bg-[#f0d39a] transition">
                        Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Header / Hero enfocado en lo útil y relevante -->
    <header id="inicio" class="hero-shell relative flex min-h-fit flex-col justify-center px-4 pb-16 pt-24 md:px-6 md:pt-32">
        <div class="mx-auto w-full max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[1.18fr_0.82fr] lg:items-center">
                <div class="reveal">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-[#f0d39a]/30 bg-white/10 px-4 py-1.5 text-xs font-black uppercase tracking-[0.22em] text-[#f0d39a]">
                        <span class="material-symbols-outlined text-[16px] notranslate" translate="no">beach_access</span>
                        <span>Reservas premium frente al mar</span>
                    </p>
                    <h1 class="font-display max-w-3xl text-4xl leading-[1.08] text-white sm:text-5xl lg:text-6xl">
                        Reserva tu próxima estancia con una experiencia visual impecable.
                    </h1>
                    <p class="hero-copy mt-4 max-w-2xl text-base leading-7 text-white/85 sm:text-lg">
                        Tarifas directas por noche, disponibilidad al instante y reserva cómoda en un entorno frente al mar con servicio de clase mundial.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="#habitaciones" class="hero-button gold-button inline-flex items-center gap-2 px-6 py-3.5 text-xs uppercase tracking-[0.14em] sm:text-sm">
                            <span class="material-symbols-outlined text-[18px] notranslate" translate="no">bed</span>
                            <span>Ver habitaciones y tarifas</span>
                        </a>
                        <a href="#experiencias" class="hero-button secondary-button inline-flex items-center gap-2 px-5 py-3.5 text-xs sm:text-sm">
                            <span>Explorar experiencias</span>
                        </a>
                    </div>
                </div>

                <!-- Beneficios clave útiles para el huésped (en vez de tutoriales estorbosos) -->
                <div class="reveal hidden lg:block">
                    <div class="glass-card rounded-[26px] p-6 border border-white/15">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#f0d39a] flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] notranslate" translate="no">verified</span>
                            <span>Ventajas de reservar directo</span>
                        </p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3.5">
                                <span class="material-symbols-outlined text-2xl text-[#f0d39a] notranslate" translate="no">price_check</span>
                                <p class="mt-2 text-sm font-extrabold text-white">Tarifa directa</p>
                                <p class="mt-0.5 text-xs text-white/70">Sin cargos ocultos ni comisiones de intermediarios.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3.5">
                                <span class="material-symbols-outlined text-2xl text-[#f0d39a] notranslate" translate="no">waves</span>
                                <p class="mt-2 text-sm font-extrabold text-white">Vista al mar</p>
                                <p class="mt-0.5 text-xs text-white/70">Ubicación privilegiada en primera línea de playa.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3.5">
                                <span class="material-symbols-outlined text-2xl text-[#f0d39a] notranslate" translate="no">schedule</span>
                                <p class="mt-2 text-sm font-extrabold text-white">Horarios claros</p>
                                <p class="mt-0.5 text-xs text-white/70">Check-in desde 15:00 · Check-out hasta 12:00.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3.5">
                                <span class="material-symbols-outlined text-2xl text-[#f0d39a] notranslate" translate="no">support_agent</span>
                                <p class="mt-2 text-sm font-extrabold text-white">Atención 24/7</p>
                                <p class="mt-0.5 text-xs text-white/70">Recepción y room service durante toda tu estancia.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barra horizontal de búsqueda de disponibilidad (ancho completo, sin aplastar texto) -->
            <div class="mt-10 reveal">
                <div id="bookingBar" class="booking-bar">
                    <div class="booking-bar__grid">
                        <!-- Control 1: Fechas -->
                        <div class="booking-control">
                            <button id="dateTrigger" type="button" class="booking-control__btn">
                                <span class="booking-control__text">
                                    <span class="booking-label">Fechas de estancia</span>
                                    <span id="dateSummary" class="booking-value">Selecciona check-in y check-out</span>
                                </span>
                                <span class="booking-control__icon">
                                    <span class="material-symbols-outlined text-2xl notranslate" translate="no">calendar_month</span>
                                </span>
                            </button>
                            <input id="dateRangeInput" type="text" class="pointer-events-none absolute left-3 top-3 h-px w-px opacity-0" aria-hidden="true">
                        </div>

                        <!-- Control 2: Huéspedes -->
                        <div class="booking-control">
                            <button id="guestTrigger" type="button" class="booking-control__btn">
                                <span class="booking-control__text">
                                    <span class="booking-label">Huéspedes</span>
                                    <span id="guestSummary" class="booking-value">2 adultos, 0 niños</span>
                                </span>
                                <span class="booking-control__icon">
                                    <span class="material-symbols-outlined text-2xl notranslate" translate="no">groups</span>
                                </span>
                            </button>

                            <div id="guestPopover" class="guest-popover">
                                <div class="guest-popover__panel">
                                    <div class="guest-row">
                                        <div>
                                            <p class="font-black text-slate-900">Adultos</p>
                                            <p class="text-xs text-slate-500">Mayores de 12 años</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button id="adultsMinus" type="button" class="guest-stepper">-</button>
                                            <span id="adultsValue" class="min-w-[24px] text-center text-base font-black text-slate-900">2</span>
                                            <button id="adultsPlus" type="button" class="guest-stepper">+</button>
                                        </div>
                                    </div>

                                    <div class="guest-row border-t border-slate-200">
                                        <div>
                                            <p class="font-black text-slate-900">Niños</p>
                                            <p class="text-xs text-slate-500">De 0 a 12 años</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button id="childrenMinus" type="button" class="guest-stepper">-</button>
                                            <span id="childrenValue" class="min-w-[24px] text-center text-base font-black text-slate-900">0</span>
                                            <button id="childrenPlus" type="button" class="guest-stepper">+</button>
                                        </div>
                                    </div>

                                    <button id="closeGuestPopover" type="button" class="hero-button primary-button mt-3 w-full px-5 py-2.5 text-xs uppercase tracking-[0.16em]">
                                        Aplicar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Control 3: Botón de búsqueda -->
                        <div class="booking-bar__submit flex flex-col sm:flex-row gap-2.5">
                            <button id="btnBuscarDisponibilidad" type="button" class="hero-button primary-button flex-1 min-h-[76px] px-7 py-4 text-xs sm:text-sm uppercase tracking-[0.16em] inline-flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[20px] notranslate" translate="no">search</span>
                                <span>Verificar disponibilidad</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 px-1">
                        <p id="searchFeedback" class="hidden text-sm font-bold"></p>
                        <p class="text-xs text-white/65 ml-auto">
                            ¿Ya sabes qué cuartos deseas? <a href="reservar.php" class="font-bold text-[#f0d39a] underline hover:text-white">Abre el asistente de reserva completo →</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 pb-24 md:px-6">
        <!-- SECCIÓN HABITACIONES (RESPONSIVE GRID SIN CORTES) -->
        <section id="vista-resultados" class="mx-auto mt-10 max-w-7xl reveal">
            <div id="habitaciones" class="glass-section rounded-[32px] px-5 py-8 sm:px-8 md:py-10">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Catálogo de habitaciones</p>
                        <h2 class="section-title font-display mt-2 text-3xl text-white sm:text-4xl">
                            Elige tus cuartos con precio por unidad transparente.
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm text-white/75">
                            Selecciona una o varias habitaciones para armar tu reserva y continúa a la página de resumen y calendario.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="soft-chip inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.14em]">
                            <span class="material-symbols-outlined text-base text-emerald-300 notranslate" translate="no">verified</span>
                            <span>Disponibilidad en tiempo real</span>
                        </span>
                        <a href="reservar.php" class="hero-button gold-button inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.14em]">
                            <span class="material-symbols-outlined text-base notranslate" translate="no">shopping_bag</span>
                            <span>Ir a reservar</span>
                        </a>
                    </div>
                </div>

                <!-- Filtros y selector de vista -->
                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button type="button" data-filter="all" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/15 px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white transition hover:bg-white/25">
                            Todas
                        </button>
                        <button type="button" data-filter="suite" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/75 transition hover:bg-white/15">
                            Suite
                        </button>
                        <button type="button" data-filter="doble" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/75 transition hover:bg-white/15">
                            Doble
                        </button>
                        <button type="button" data-filter="sencilla" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/75 transition hover:bg-white/15">
                            Sencillas
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button id="toggleRoomsViewBtn" type="button" class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-bold text-white/90 transition hover:bg-white/20" title="Cambiar entre vista cuadrícula y carrusel">
                            <span id="toggleRoomsViewIcon" class="material-symbols-outlined text-[18px] notranslate" translate="no">view_carousel</span>
                            <span id="toggleRoomsViewLabel">Vista carrusel</span>
                        </button>
                        <div id="roomsCarouselNav" class="hidden items-center gap-1.5">
                            <button id="roomsPrev" type="button" aria-label="Habitaciones anteriores" class="rooms-nav flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40">
                                <span class="material-symbols-outlined notranslate" translate="no">chevron_left</span>
                            </button>
                            <button id="roomsNext" type="button" aria-label="Siguientes habitaciones" class="rooms-nav flex h-10 w-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40">
                                <span class="material-symbols-outlined notranslate" translate="no">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenedor de tarjetas de habitaciones (por defecto Grilla Responsive para que nunca se corten) -->
                <div class="mt-7 relative">
                    <?php if (!empty($habitaciones)): ?>
                        <div id="roomsCarousel" class="rooms-grid-view">
                            <?php foreach ($habitaciones as $room): ?>
                                <?php
                                $codHab = (int) ($room['cod_hab'] ?? $room['id'] ?? 0);
                                $numHab = (string) ($room['num_hab'] ?? $room['numero'] ?? '');
                                $tipoHab = (string) ($room['tipo_hab'] ?? $room['tipo'] ?? 'Habitación');
                                $precioHab = (float) ($room['pre_hab'] ?? $room['precio_hab'] ?? $room['precio'] ?? 0);
                                $roomName = 'Habitación ' . $numHab . ' · ' . $tipoHab;

                                $features = !empty($room['car_hab']) ? $room['car_hab'] : roomFeatures($tipoHab);
                                $roomPhoto = !empty($room['img_hab']) ? (string) $room['img_hab'] : roomImage($tipoHab);

                                $tipoLower = mb_strtolower($tipoHab, 'UTF-8');
                                if (str_contains($tipoLower, 'suite')) {
                                    $roomType = 'suite';
                                } elseif (str_contains($tipoLower, 'doble')) {
                                    $roomType = 'doble';
                                } elseif (str_contains($tipoLower, 'sencilla') || str_contains($tipoLower, 'simple')) {
                                    $roomType = 'sencilla';
                                } else {
                                    $roomType = 'otra';
                                }
                                ?>
                                <article
                                    class="room-card"
                                    data-room-card="<?php echo $codHab; ?>"
                                    data-room-type="<?php echo $roomType; ?>"
                                    data-room-price="<?php echo $precioHab; ?>"
                                    data-room-ids="[<?php echo $codHab; ?>]"
                                >
                                    <div class="room-card__media">
                                        <img src="<?php echo e($roomPhoto); ?>" alt="<?php echo e($roomName); ?>" loading="lazy" decoding="async">
                                        <div class="absolute inset-x-3 top-3 flex items-center justify-between gap-2">
                                            <span class="rounded-full bg-slate-950/75 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#f0d39a] backdrop-blur-md border border-white/15">
                                                <?php echo e($tipoHab); ?>
                                            </span>
                                            <span class="rounded-full bg-emerald-950/80 px-3 py-1 text-[11px] font-bold text-emerald-200 backdrop-blur-md border border-emerald-400/30">
                                                Cuarto #<?php echo e($numHab); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="room-card__body">
                                        <div>
                                            <h3 class="room-card__title text-2xl font-black text-white">
                                                <?php echo e($roomName); ?>
                                            </h3>

                                            <p class="room-card__desc mt-2.5">
                                                <?php echo e((string) ($room['obs_hab'] ?? 'Habitación confortable con acabados de lujo y todas las comodidades para tu descanso.')); ?>
                                            </p>

                                            <div class="room-card__features mt-4">
                                                <?php foreach ($features as $feature): ?>
                                                    <span class="soft-chip rounded-full px-3 py-1 text-xs font-bold"><?php echo e($feature); ?></span>
                                                <?php endforeach; ?>
                                            </div>

                                            <p data-room-availability class="hidden mt-4 rounded-xl border border-rose-300/30 bg-rose-950/50 px-3.5 py-2.5 text-xs font-bold text-rose-200" role="status"></p>
                                        </div>

                                        <div class="room-card__footer">
                                            <div>
                                                <p class="text-[11px] font-black uppercase tracking-[0.14em] text-white/60">Precio por unidad / noche</p>
                                                <p class="mt-0.5 text-2xl font-black text-[#f0d39a]">
                                                    $<?php echo number_format($precioHab, 0, ',', '.'); ?> <span class="text-xs font-bold text-white/65">COP</span>
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="hero-button secondary-button px-3.5 py-2.5 text-xs uppercase tracking-[0.12em]"
                                                    data-room-add
                                                    data-room-select
                                                    data-room-id="<?php echo $codHab; ?>"
                                                    data-room-num="<?php echo e($numHab); ?>"
                                                    data-room-type-label="<?php echo e($tipoHab); ?>"
                                                    data-room-name="<?php echo e($roomName); ?>"
                                                    data-room-price="<?php echo $precioHab; ?>"
                                                    data-room-photo="<?php echo e($roomPhoto); ?>"
                                                >
                                                    <span class="material-symbols-outlined text-[16px] mr-1 notranslate" translate="no" data-room-add-icon>add_shopping_cart</span>
                                                    <span data-room-add-label>Seleccionar</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="hero-button primary-button px-4 py-2.5 text-xs uppercase tracking-[0.12em]"
                                                    data-room-book-now
                                                    data-room-id="<?php echo $codHab; ?>"
                                                    data-room-num="<?php echo e($numHab); ?>"
                                                    data-room-type-label="<?php echo e($tipoHab); ?>"
                                                    data-room-name="<?php echo e($roomName); ?>"
                                                    data-room-price="<?php echo $precioHab; ?>"
                                                    data-room-photo="<?php echo e($roomPhoto); ?>"
                                                >
                                                    Reservar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div id="emptyRoomsState" class="<?php echo !empty($habitaciones) ? 'hidden' : ''; ?> rounded-[28px] border border-dashed border-white/20 bg-white/8 p-10 text-center backdrop-blur-lg">
                        <span class="material-symbols-outlined text-4xl text-[#f0d39a] notranslate" translate="no">event_busy</span>
                        <p class="mt-2 text-lg font-black text-white">No hay habitaciones disponibles para ese filtro o rango de fechas.</p>
                        <p id="emptyRoomsDetail" class="muted-light mt-1 text-sm">Cambia el filtro o selecciona otras fechas en el calendario.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECCIÓN EXPERIENCIAS -->
        <section id="experiencias" class="mx-auto mt-10 max-w-7xl reveal">
            <div class="glass-section rounded-[32px] px-5 py-8 sm:px-8 md:py-10">
                <div class="mb-7 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Momentos inolvidables</p>
                        <h2 class="section-title font-display mt-2 text-3xl text-white sm:text-4xl">Experiencias exclusivas en el hotel</h2>
                    </div>
                    <p class="max-w-md text-sm text-white/75">
                        Complementa tu estancia con actividades gastronómicas, bienestar y aventura diseñadas para nuestros huéspedes.
                    </p>
                </div>

                <?php if ($experiencias === []): ?>
                    <p class="muted-light text-center text-sm py-8">Muy pronto compartiremos nuevas experiencias.</p>
                <?php else: ?>
                    <div class="grid gap-6">
                        <?php foreach ($experiencias as $indice => $experiencia): ?>
                            <article class="room-card">
                                <div class="room-card__media">
                                    <img src="<?php echo e(experienciaImagen($experiencia['categoria'], $indice, $experiencia['imagen'])); ?>" alt="<?php echo e($experiencia['nombre']); ?>" loading="lazy" decoding="async">
                                    <div class="absolute left-3 top-3">
                                        <span class="rounded-full bg-slate-950/75 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#f0d39a] backdrop-blur-md border border-white/15">
                                            <?php echo e($experiencia['categoria']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="room-card__body">
                                    <div>
                                        <h3 class="card-title text-2xl font-black text-white"><?php echo e($experiencia['nombre']); ?></h3>
                                        <p class="room-card__desc mt-2.5"><?php echo nl2br(e($experiencia['descripcion'])); ?></p>
                                        <?php
                                            $fechasConfiguradas = [];
                                            $fechaHoyExperiencia = (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d');
                                            foreach (array_keys($experiencia['opciones']) as $indiceOpcion) {
                                                $numeroOpcion = $experiencia['numeros_opciones'][$indiceOpcion] ?? ($indiceOpcion + 1);
                                                foreach (array_keys($experiencia['horarios'][(string) $numeroOpcion] ?? []) as $fechaProgramada) {
                                                    if (is_string($fechaProgramada) && $fechaProgramada >= $fechaHoyExperiencia) {
                                                        $fechasConfiguradas[$fechaProgramada] = true;
                                                    }
                                                }
                                            }
                                            $fechasConfiguradas = array_keys($fechasConfiguradas);
                                            sort($fechasConfiguradas, SORT_STRING);
                                            $horariosConfigurados = $fechasConfiguradas !== [];
                                        ?>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <?php foreach ($experiencia['opciones'] as $indiceOpcion => $opcion): ?>
                                                <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/90">
                                                    <?php echo e($opcion); ?> · <?php echo $experiencia['precios'][$indiceOpcion] === null ? 'Sin precio' : '$' . number_format((float) $experiencia['precios'][$indiceOpcion], 0, ',', '.') . ' COP'; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-white/10">
                                        <?php if ($horariosConfigurados): ?>
                                            <p class="mb-3 text-xs text-white/70">
                                                Fechas disponibles: <?php echo e(implode(', ', array_map(static fn(string $fechaProgramada): string => date('d/m/Y', strtotime($fechaProgramada)), $fechasConfiguradas))); ?>
                                            </p>
                                        <?php endif; ?>
                                        <button
                                            type="button"
                                            class="experience-select-button rounded-xl border border-white/25 bg-white/15 px-4 py-3 text-sm font-bold text-white transition hover:border-[#f0d39a] hover:bg-white/25 disabled:cursor-not-allowed disabled:opacity-50"
                                            data-experience-id="<?php echo (int) $experiencia['id']; ?>"
                                            data-experience-name="<?php echo e($experiencia['nombre']); ?>"
                                            data-experience-options="<?php echo e(json_encode($experiencia['opciones'], JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                            data-experience-prices="<?php echo e(json_encode($experiencia['precios'], JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                            data-experience-option-indices="<?php echo e(json_encode($experiencia['numeros_opciones'], JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                            data-experience-schedule="<?php echo e(json_encode($experiencia['horarios'], JSON_UNESCAPED_UNICODE) ?: '{}'); ?>"
                                            data-reservation-stays="<?php echo e(json_encode($reservasEstanciaExperiencia, JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                            <?php echo $experiencia['opciones'] === [] || !$horariosConfigurados || !$usuarioAutenticado || !$usuarioTieneReserva ? 'disabled' : ''; ?>>
                                            <?php echo !$usuarioAutenticado ? 'Inicia sesión para programar' : (!$usuarioTieneReserva ? 'Reserva una habitación primero' : 'Elegir esta experiencia'); ?>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SECCIÓN AGENDA TU ESTANCIA -->
        <section id="planner" class="mx-auto mt-10 max-w-7xl reveal">
            <div class="glass-section grid gap-8 rounded-[32px] px-5 py-8 sm:px-8 md:py-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                <div>
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Agenda tu estancia</p>
                    <h2 class="section-title font-display mt-2 text-3xl text-white sm:text-4xl">Solicita una experiencia antes de llegar.</h2>
                    <p class="muted-light mt-3 max-w-2xl text-sm leading-7 sm:text-base">
                        Programa una sesión de spa, una cena especial o una actividad privada para que nuestro equipo la prepare con anticipación dentro de las fechas de tu reserva.
                    </p>
                </div>

                <?php if (!$usuarioAutenticado): ?>
                    <div class="rounded-[24px] border border-white/15 bg-white/10 p-6 backdrop-blur-xl">
                        <h3 class="text-xl font-black text-white">Inicia sesión para programar</h3>
                        <p class="muted-light mt-2 text-sm leading-6">Para solicitar una experiencia, primero inicia sesión con tu cuenta de huésped y realiza una reserva.</p>
                        <a href="interfaz/loggins/index_usu.php" class="hero-button primary-button mt-5 inline-flex px-5 py-3 text-sm font-bold">Iniciar sesión</a>
                    </div>
                <?php elseif (!$usuarioTieneReserva): ?>
                    <div class="rounded-[24px] border border-white/15 bg-white/10 p-6 backdrop-blur-xl">
                        <h3 class="text-xl font-black text-white">Haz tu reserva primero</h3>
                        <p class="muted-light mt-2 text-sm leading-6">Cuando tengas una reserva activa, podrás elegir la experiencia, las opciones por persona y el horario.</p>
                        <a href="reservar.php" class="hero-button gold-button mt-5 inline-flex px-5 py-3 text-sm font-bold">Ir a reservar habitación</a>
                    </div>
                <?php else: ?>
                    <div class="rounded-[24px] border border-white/15 bg-white/10 p-6 backdrop-blur-xl">
                        <h3 class="text-xl font-black text-white">Prepara algo especial</h3>
                        <p class="muted-light mt-2 text-sm leading-6">Elige una experiencia de las opciones disponibles o solicita una propuesta personalizada.</p>
                        <button id="openActivityModal" type="button" class="hero-button primary-button mt-5 w-full px-5 py-4 text-sm uppercase tracking-[0.18em]">
                            Programar experiencia
                        </button>
                    </div>

                    <dialog id="activityModal" class="activity-modal" aria-labelledby="activityModalTitle">
                        <div class="activity-modal__content">
                            <div class="activity-modal__header">
                                <div>
                                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em]">Agenda tu estancia</p>
                                    <h3 id="activityModalTitle" class="font-display mt-2 text-3xl text-white" tabindex="-1">Programa tu experiencia</h3>
                                </div>
                                <button id="closeActivityModal" type="button" class="activity-modal__close" aria-label="Cerrar ventana de programación">
                                    <span class="material-symbols-outlined notranslate" translate="no" aria-hidden="true">close</span>
                                </button>
                            </div>

                            <form id="activityForm" class="activity-modal__form">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <p class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70">Selección</p>
                                        <div id="seleccionExperiencia" class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white/70">Elige una experiencia en una de las tarjetas.</div>
                                        <input id="experienciaIdActividad" name="experiencia_id" type="hidden">
                                        <label class="mt-3 flex items-center gap-3 rounded-2xl border border-white/20 bg-white/10 px-3 py-2 text-sm text-white/80">
                                            <input id="experienciaPersonalizadaToggle" name="solicitud_personalizada" value="1" type="checkbox" class="h-4 w-4 rounded border-white/30 bg-white/10 text-emerald-500 focus:ring-emerald-400" aria-label="Solicitar una experiencia personalizada">
                                            <span>Solicitar experiencia personalizada</span>
                                        </label>
                                        <select id="experienciaPersonalizadaCategoria" name="categoria_personalizada" class="mt-3 hidden w-full rounded-2xl border-white/20 bg-white/90 text-slate-900">
                                            <option value="">Selecciona una categoría (opcional)</option>
                                            <option value="Spa y bienestar">Spa y bienestar</option>
                                            <option value="Gastronomía">Gastronomía</option>
                                            <option value="Aventura">Aventura</option>
                                            <option value="Romántica">Romántica</option>
                                            <option value="Celebración">Celebración</option>
                                            <option value="Otra">Otra</option>
                                        </select>
                                        <input id="experienciaPersonalizadaNombre" name="experiencia_personalizada" type="text" placeholder="Opcional: describe la idea o servicio que buscas" class="mt-3 hidden w-full rounded-2xl border-white/20 bg-white/90 text-slate-900 placeholder:text-slate-500">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="cantidadPersonasActividad">Personas</label>
                                        <input id="cantidadPersonasActividad" name="cantidad_personas" type="number" min="1" max="20" value="1" required disabled class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900 disabled:opacity-60">
                                        <div id="opcionesPorPersona" class="mt-4 grid gap-3 sm:grid-cols-2"></div>
                                        <input id="participantesActividad" name="participantes" type="hidden">
                                        <p id="resumenPrecioExperiencia" class="mt-3 text-sm font-bold text-white/85" aria-live="polite">Elige las opciones por persona para consultar el precio.</p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="fechaActividad">Fecha</label>
                                        <select id="fechaActividad" name="fecha" required disabled class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900 disabled:opacity-60">
                                            <option value="">Primero elige una experiencia</option>
                                        </select>
                                    </div>

                                    <div>
                                        <p id="etiquetaHoraActividad" class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70">Hora</p>
                                        <input id="horaActividad" name="hora" type="hidden">
                                        <div id="listaHorasActividad" class="experience-time-list mt-2 rounded-2xl border border-white/20 bg-white/90 p-2 text-slate-900" role="listbox" aria-labelledby="etiquetaHoraActividad" aria-disabled="true">
                                            <p class="px-3 py-2 text-sm text-slate-500">Primero elige una fecha</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="nombreActividad">Nombre</label>
                                        <input id="nombreActividad" name="nombre" type="text" value="<?php echo e($usuarioNombre); ?>" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900" readonly>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="correoActividad">Correo</label>
                                        <input id="correoActividad" name="correo" type="email" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900" placeholder="ejemplo@correo.com">
                                    </div>
                                </div>

                                <button type="submit" class="hero-button primary-button mt-5 w-full px-5 py-4 text-sm uppercase tracking-[0.18em]">
                                    Enviar solicitud
                                </button>
                            </form>
                        </div>
                    </dialog>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($usuarioAutenticado): ?>
        <!-- SECCIÓN ROOM SERVICE -->
        <section id="room-service" class="mx-auto mt-10 max-w-7xl scroll-mt-24 reveal">
            <div class="glass-section rounded-[32px] px-5 py-8 text-white sm:px-8 md:py-10">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Room service</p>
                        <h2 class="font-display mt-2 text-3xl font-black text-white sm:text-4xl">Pide a tu habitación</h2>
                        <p class="mt-2 max-w-2xl text-sm text-white/75">Comidas y objetos para tu estancia, directo a tu puerta. Tu pedido llega en aproximadamente <span id="rsEta">5</span> minutos y puedes cancelarlo durante los primeros <span id="rsVentana">3</span>.</p>
                    </div>
                </div>

                <div id="rsAviso" class="mb-5 hidden rounded-2xl border px-5 py-4 text-sm font-bold" role="status" aria-live="polite"></div>
                <div id="rsSinEstancia" class="hidden rounded-[24px] border border-dashed border-white/20 bg-white/10 px-6 py-10 text-center">
                    <span class="material-symbols-outlined text-4xl text-[#f0d39a] notranslate" translate="no">room_service</span>
                    <p class="mt-3 text-lg font-black text-white">El room service está disponible durante tu estancia.</p>
                    <p class="mt-2 text-sm text-white/75">Cuando tengas una reserva en curso, podrás pedir comidas y objetos para tu habitación desde aquí.</p>
                </div>

                <div id="rsContenido" class="grid gap-8 lg:grid-cols-[1.6fr_1fr]">
                    <div>
                        <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Tipo de pedido">
                            <button type="button" data-rs-tipo="comida" role="tab" class="rounded-full px-5 py-2 text-sm font-black transition">Comidas</button>
                            <button type="button" data-rs-tipo="objeto" role="tab" class="rounded-full px-5 py-2 text-sm font-black transition">Objetos</button>
                        </div>
                        <div id="rsCategorias" class="mb-4 flex flex-wrap gap-2"></div>
                        <div id="rsCatalogo" class="grid gap-4 sm:grid-cols-2"></div>
                    </div>

                    <aside id="rsCarrito" class="h-fit rounded-3xl border border-white/15 bg-emerald-950/60 p-6 lg:sticky lg:top-24">
                        <h3 class="flex items-center gap-2 text-lg font-black text-white"><span class="material-symbols-outlined notranslate" translate="no">shopping_cart</span> Tu pedido</h3>
                        <div id="rsCarritoLineas" class="mt-4 space-y-3"></div>
                        <div id="rsHabitacionBox" class="mt-4 hidden">
                            <label for="rsHabitacion" class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-emerald-200">Enviar a</label>
                            <select id="rsHabitacion" class="w-full rounded-xl border-0 bg-white px-3 py-3 text-sm font-bold text-slate-800"></select>
                        </div>
                        <label for="rsNotas" class="mb-1 mt-4 block text-[10px] font-black uppercase tracking-[0.18em] text-emerald-200">Indicaciones (opcional)</label>
                        <textarea id="rsNotas" rows="2" maxlength="300" placeholder="Ej. sin cebolla, tocar suavemente la puerta…" class="w-full rounded-xl border-0 bg-white px-3 py-2 text-sm text-slate-800"></textarea>
                        <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4">
                            <span class="text-sm font-bold text-white/70">Total</span>
                            <span id="rsTotal" class="text-2xl font-black text-white">$0</span>
                        </div>
                        <p class="mt-1 text-[11px] text-white/50">El cobro se agrega a tu cuenta y se paga en recepción.</p>
                        <button id="rsPedir" type="button" class="mt-4 w-full rounded-2xl bg-emerald-400 py-3 text-sm font-black uppercase tracking-[0.14em] text-emerald-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-50" disabled>Pedir a mi habitación</button>
                    </aside>
                </div>

                <div class="mt-10">
                    <h3 class="text-xl font-black text-white">Mis pedidos de room service</h3>
                    <div id="rsPedidos" class="mt-4 space-y-3"></div>
                </div>
            </div>
        </section>
        <button id="rsIrCarrito" type="button" class="fixed bottom-6 right-6 z-[70] hidden items-center gap-2 rounded-full bg-emerald-400 px-5 py-3 text-sm font-black text-emerald-950 shadow-2xl lg:!hidden">
            <span class="material-symbols-outlined notranslate" translate="no">shopping_cart</span> <span id="rsIrCarritoTexto">Ver pedido</span>
        </button>
        <script src="assets/js/room_service_usu.js?v=2" defer></script>

        <!-- SECCIÓN HISTORIAL DE RESERVAS -->
        <section id="historial" class="mx-auto mt-10 max-w-7xl reveal">
            <div class="glass-section rounded-[32px] px-5 py-8 text-white sm:px-8 md:py-10">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Mi historial</p>
                        <h2 class="font-display mt-2 text-3xl font-black text-white sm:text-4xl">Reservas realizadas</h2>
                    </div>
                    <span class="soft-chip rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.16em]">
                        <?php echo count($historialReservas); ?> registros
                    </span>
                </div>

                <?php if (empty($historialReservas)): ?>
                    <div class="rounded-[24px] border border-dashed border-white/20 bg-white/10 px-6 py-10 text-center">
                        <span class="material-symbols-outlined text-4xl text-[#f0d39a] notranslate" translate="no">travel_explore</span>
                        <p class="mt-3 text-lg font-black text-white">Aún no tienes reservas registradas.</p>
                        <p class="mt-2 text-sm text-white/75">Cuando reserves una estancia, aparecerá aquí el historial completo.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2.5 text-left">
                            <thead>
                                <tr class="text-[11px] font-black uppercase tracking-[0.16em] text-[#f0d39a]">
                                    <th class="px-4 py-2">Reserva</th>
                                    <th class="px-4 py-2">Habitación</th>
                                    <th class="px-4 py-2">Check-in</th>
                                    <th class="px-4 py-2">Check-out</th>
                                    <th class="px-4 py-2">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historialReservas as $reserva): ?>
                                    <?php
                                    $descripcionHabitacion = !empty($reserva['tipo_hab'])
                                        ? (!empty($reserva['num_hab']) ? 'Habitación ' . (int) $reserva['num_hab'] . ' · ' . htmlspecialchars($reserva['tipo_hab'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($reserva['tipo_hab'], ENT_QUOTES, 'UTF-8'))
                                        : 'Habitación asignada';
                                    ?>
                                    <tr class="rounded-2xl bg-white/8 text-sm text-white transition-colors hover:bg-white/15">
                                        <td class="rounded-l-2xl px-4 py-4 font-black text-[#f0d39a]">#<?php echo (int) $reserva['cod_res']; ?></td>
                                        <td class="px-4 py-4 font-semibold text-white"><?php echo $descripcionHabitacion; ?></td>
                                        <td class="px-4 py-4 text-white/85"><?php echo formatReservationHistoryDate((string) $reserva['fec_ent_res']); ?></td>
                                        <td class="px-4 py-4 text-white/85"><?php echo formatReservationHistoryDate((string) $reserva['fec_sal_res']); ?></td>
                                        <td class="rounded-r-2xl px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.12em] <?php echo reservationStatusBadge((string) ($reserva['est_res'] ?? 'Pendiente')); ?>">
                                                <?php echo e((string) ($reserva['est_res'] ?? 'Pendiente')); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SECCIÓN HISTORIAL DE EXPERIENCIAS -->
        <section id="historial-experiencias" class="mx-auto mt-8 max-w-7xl reveal">
            <div class="glass-section rounded-[32px] px-5 py-8 text-white sm:px-8 md:py-10">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Mi historial</p>
                        <h2 class="font-display mt-2 text-3xl font-black text-white sm:text-4xl">Experiencias programadas</h2>
                    </div>
                    <span class="soft-chip rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.16em]">
                        <?php echo count($historialExperiencias); ?> registros
                    </span>
                </div>

                <?php if ($historialExperiencias === []): ?>
                    <div class="rounded-[24px] border border-dashed border-white/20 bg-white/10 px-6 py-10 text-center">
                        <span class="material-symbols-outlined text-4xl text-[#f0d39a] notranslate" translate="no">event_note</span>
                        <p class="mt-3 text-lg font-black text-white">Aún no has programado experiencias.</p>
                        <p class="mt-2 text-sm text-white/75">Tus solicitudes aparecerán aquí cuando programes una experiencia.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2.5 text-left">
                            <thead>
                                <tr class="text-[11px] font-black uppercase tracking-[0.16em] text-[#f0d39a]">
                                    <th class="px-4 py-2">Experiencia</th>
                                    <th class="px-4 py-2">Opciones por persona</th>
                                    <th class="px-4 py-2">Fecha</th>
                                    <th class="px-4 py-2">Hora</th>
                                    <th class="px-4 py-2">Precio estimado</th>
                                    <th class="px-4 py-2">Estado</th>
                                    <th class="px-4 py-2">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historialExperiencias as $solicitudExperiencia): ?>
                                    <?php
                                    $puedeCancelarExperiencia = (int) ($solicitudExperiencia['puede_cancelar'] ?? 0) === 1;
                                    ?>
                                    <tr class="bg-white/8 text-sm text-white transition-colors hover:bg-white/15">
                                        <td class="rounded-l-2xl px-4 py-4 font-black text-white"><?php echo e((string) $solicitudExperiencia['actividad']); ?></td>
                                        <td class="px-4 py-4 text-white/85">
                                            <?php foreach ($solicitudExperiencia['selecciones_personas'] as $indicePersona => $opcionPersona): ?>
                                                <span class="block"><?php echo e((string) $opcionPersona); ?>
                                                    <?php if (isset($solicitudExperiencia['precios_personas'][$indicePersona]) && $solicitudExperiencia['precios_personas'][$indicePersona] !== null): ?>
                                                        · $<?php echo number_format((float) $solicitudExperiencia['precios_personas'][$indicePersona], 0, ',', '.'); ?> COP
                                                    <?php else: ?>
                                                        · Sin precio
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="px-4 py-4 text-white/85"><?php echo formatReservationHistoryDate((string) $solicitudExperiencia['fecha_agenda']); ?></td>
                                        <td class="px-4 py-4 text-white/85"><?php echo e(substr((string) $solicitudExperiencia['hora_agenda'], 0, 5)); ?></td>
                                        <td class="px-4 py-4 text-white/85"><?php echo $solicitudExperiencia['monto_experiencia'] === null ? 'No definido' : '$' . number_format((float) $solicitudExperiencia['monto_experiencia'], 0, ',', '.') . ' COP'; ?></td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.12em] <?php echo reservationStatusBadge((string) ($solicitudExperiencia['estado_agenda'] ?? 'Pendiente')); ?>">
                                                <?php echo e((string) ($solicitudExperiencia['estado_agenda'] ?? 'Pendiente')); ?>
                                            </span>
                                        </td>
                                        <td class="rounded-r-2xl px-4 py-4">
                                            <?php if ($puedeCancelarExperiencia): ?>
                                                <button type="button" data-cancelar-experiencia="<?php echo (int) $solicitudExperiencia['id_agenda']; ?>" data-segundos-cancelacion="<?php echo (int) ($solicitudExperiencia['segundos_cancelacion'] ?? 0); ?>" class="rounded-lg border border-rose-300/50 px-3 py-2 text-xs font-bold text-rose-100 transition hover:bg-rose-500/20">Cancelar (5 min)</button>
                                            <?php else: ?>
                                                <span class="text-xs text-white/50"><?php echo ($solicitudExperiencia['estado_agenda'] ?? '') === 'Cancelada' ? 'Cancelada' : 'No disponible'; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <footer class="px-4 pt-14 pb-8 md:px-6 text-sm" style="background: rgba(5, 16, 12, 0.85); border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="max-w-6xl mx-auto grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-4">
            <div>
                <img src="assets/images/logo.jpeg" alt="Hotel Aurora" class="h-12 w-auto rounded-full mb-4">
                <p class="text-white/60 text-xs">Hotel Aurora © <?php echo date('Y'); ?> · Todos los derechos reservados</p>
            </div>

            <div>
                <ul class="space-y-3 text-white/70">
                    <li><a href="interfaz/institucional/quienes_somos.php" class="hover:text-white">¿Quiénes somos?</a></li>
                    <li><a href="#politica-privacidad" class="hover:text-white" data-open-privacy-policy>Protección de datos personales</a></li>
                    <li><a href="#politica-cookies" class="hover:text-white" data-open-cookie-policy>Cookies</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-display text-white text-base mb-4">Reservas</h3>
                <ul class="space-y-2 text-white/70">
                    <li><a href="reservar.php" class="text-[#f0d39a] font-bold hover:underline">Página de reservas</a></li>
                    <li><a href="mailto:reservas@hotelaurora.com" class="hover:text-white">reservas@hotelaurora.com</a></li>
                    <li>Teléfono: +57 XXX XXX XXXX</li>
                </ul>
            </div>

            <div>
                <h3 class="font-display text-white text-base mb-4">Síguenos en:</h3>
                <div class="flex items-center gap-4 text-white/70">
                    <a href="#" class="hover:text-white" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.89h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"/></svg>
                    </a>
                    <a href="#" class="hover:text-white" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M12 2.2c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.72 3.72 0 0 1-1.38-.9 3.72 3.72 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.21 8.8 2.2 12 2.2zm0 3.33a6.47 6.47 0 1 0 0 12.94 6.47 6.47 0 0 0 0-12.94zm0 10.67a4.2 4.2 0 1 1 0-8.4 4.2 4.2 0 0 1 0 8.4zm6.8-10.93a1.51 1.51 0 1 1-3.02 0 1.51 1.51 0 0 1 3.02 0z"/></svg>
                    </a>
                    <a href="#" class="hover:text-white" aria-label="TikTok">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M16.5 2h-3v13.7a2.9 2.9 0 1 1-2.06-2.78v-3.1a6 6 0 1 0 5.06 5.93V8.4a7.6 7.6 0 0 0 4.5 1.46V6.8A4.6 4.6 0 0 1 16.5 2z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto mt-10 pt-6 flex items-center justify-center gap-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <img src="assets/images/logo.jpeg" alt="Hotel Aurora" class="h-8 w-auto rounded-full">
            <span class="font-display text-white text-lg">Hotel Aurora</span>
        </div>
    </footer>

    <!-- Botón flotante de ayuda -->
    <div class="system-help">
        <section id="systemHelpPanel" class="system-help__panel" role="dialog" aria-labelledby="systemHelpTitle" aria-hidden="true">
            <div class="flex items-start justify-between gap-4 bg-[#0b3b27] px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#f0d39a]">Centro de ayuda</p>
                    <h2 id="systemHelpTitle" class="mt-1 text-lg font-black">¿Cómo podemos ayudarte?</h2>
                </div>
                <button id="closeSystemHelp" type="button" class="rounded-full p-1 text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Cerrar ayuda">
                    <span class="material-symbols-outlined pointer-events-none notranslate" translate="no">close</span>
                </button>
            </div>
            <div>
                <div class="border-t border-white/10 bg-[#072619] px-5 py-3">
                    <button type="button" data-start-system-tour class="text-sm font-extrabold text-[#f0d39a] hover:underline">
                        Iniciar recorrido paso a paso
                    </button>
                </div>
                <details class="system-help__question" open>
                    <summary>¿Cómo selecciono y reservo habitaciones?</summary>
                    <p class="system-help__answer">Pulsa “Seleccionar” en uno o varios cuartos para ver el precio por unidad, y luego haz clic en “Continuar a la reserva” para elegir tus fechas en el calendario y confirmar.</p>
                </details>
                <details class="system-help__question">
                    <summary>¿Qué necesito para reservar?</summary>
                    <p class="system-help__answer">Debes iniciar sesión, elegir fechas válidas de check-in y check-out, y confirmar tu pago total (100%), abono inicial (50%) o pago en recepción.</p>
                </details>
                <details class="system-help__question">
                    <summary>¿Cómo solicito una experiencia?</summary>
                    <p class="system-help__answer">En “Agenda tu estancia”, elige la experiencia, fecha, hora y tus datos de contacto. El equipo confirmará la solicitud.</p>
                </details>
            </div>
        </section>
        <button id="systemHelpButton" type="button" class="hero-button primary-button flex items-center gap-2 px-4 py-3 text-sm font-black shadow-xl" aria-controls="systemHelpPanel" aria-expanded="false">
            <span class="material-symbols-outlined text-[20px] notranslate" translate="no">help</span>
            <span>Ayuda</span>
        </button>
    </div>

    <?php
    $tourSistemaRol = !empty($_SESSION['user_auth']) ? 'usuario' : 'visitante';
    require_once __DIR__ . '/includes/system_tour.php';
    ?>

    <script src="assets/js/interfaz_usu.js?v=25"></script>
    <?php require_once 'includes/banner_cookies.php'; ?>
    <?php
    $privacyPolicyCookiesUrl = 'interfaz/legal/politica_cookies.php';
    $privacyPolicyUrl = 'interfaz/legal/politica_privacidad.php';
    include __DIR__ . '/includes/privacy_policy_modal.php';
    ?>
    <?php if (!empty($_SESSION['user_auth'])): ?>
        <?php require_once __DIR__ . '/includes/timeOut.php'; ?>
        <script src="assets/js/inactividad.js?v=3"></script>
    <?php endif; ?>
    <?php require_once __DIR__ . '/includes/dev_livereload.php'; ?>
</body>
</html>
