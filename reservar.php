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

$portalData = (new \App\Usuario\PortalService($portalRepository))->load($conexion, $_SESSION);
$habitaciones = $portalData['habitaciones'];
$usuarioId = $portalData['usuarioId'];
$usuarioNombre = $portalData['usuarioNombre'];
$usuarioAutenticado = $portalData['usuarioAutenticado'];

$catalogoNormalizado = [];
foreach ($habitaciones as $room) {
    $codHab = (int) ($room['cod_hab'] ?? $room['id'] ?? 0);
    if ($codHab <= 0) {
        continue;
    }
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

    $catalogoNormalizado[] = [
        'id' => $codHab,
        'num' => $numHab,
        'typeLabel' => $tipoHab,
        'typeKey' => $roomType,
        'name' => $roomName,
        'price' => $precioHab,
        'photo' => $roomPhoto,
        'features' => array_values((array) $features),
        'description' => (string) ($room['obs_hab'] ?? 'Habitación confortable con todas las comodidades.'),
    ];
}

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
    <title>Completar Reserva | Hotel Aurora</title>
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
            'userName' => $usuarioNombre,
            'roomCatalog' => $catalogoNormalizado,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="site-nav fixed inset-x-0 top-0 z-50">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
            <div class="flex items-center gap-4">
                <a href="interfaz_usu.php" class="flex items-center gap-3 text-white shrink-0">
                    <span class="logo">
                        <img src="assets/images/logo.jpeg" alt="Logo Hotel Aurora" class="h-full w-full object-cover">
                    </span>
                    <span class="font-display text-2xl leading-none text-white sm:text-[26px]">Hotel Aurora</span>
                </a>
                <a href="interfaz_usu.php#habitaciones" class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white/90 transition hover:bg-white/20">
                    <span class="material-symbols-outlined text-[16px] notranslate" translate="no">arrow_back</span>
                    <span>Volver al catálogo</span>
                </a>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
                <?php include __DIR__ . '/includes/translate.php'; ?>

                <?php if ($usuarioAutenticado): ?>
                    <a href="interfaz_usu.php#historial" class="inline-flex items-center gap-1.5 rounded-full border border-[#f0d39a]/40 bg-[#f0d39a]/15 px-3 py-1.5 text-xs font-black uppercase tracking-[0.1em] text-[#f0d39a] transition hover:bg-[#f0d39a]/25">
                        <span class="material-symbols-outlined text-[16px] notranslate" translate="no">receipt_long</span>
                        <span>Mis reservas</span>
                    </a>
                    <span class="hidden max-w-[170px] truncate rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold text-white md:inline-block">
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

    <main class="mx-auto max-w-7xl px-4 pb-24 pt-28 md:px-6 md:pt-32">
        <!-- Encabezado y barra de pasos -->
        <header class="mb-8 reveal">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <a href="interfaz_usu.php#habitaciones" class="inline-flex sm:hidden items-center gap-1.5 text-xs font-bold text-[#f0d39a] mb-2">
                        <span class="material-symbols-outlined text-[16px] notranslate" translate="no">arrow_back</span>
                        <span>Volver al catálogo</span>
                    </a>
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Asistente de Reserva</p>
                    <h1 class="font-display mt-1 text-3xl text-white sm:text-5xl">Configura y confirma tu estancia</h1>
                </div>

                <!-- Indicador de pasos -->
                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-white/15 bg-white/8 p-2 backdrop-blur-md">
                    <a href="#paso-calendario" class="flex items-center gap-2 rounded-xl bg-[#f0d39a]/20 px-3.5 py-2 text-xs font-extrabold text-[#f0d39a]">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#f0d39a] text-[11px] font-black text-slate-950">1</span>
                        <span>Fechas y calendario</span>
                    </a>
                    <a href="#paso-cuartos" class="flex items-center gap-2 rounded-xl bg-white/5 px-3.5 py-2 text-xs font-bold text-white/85 hover:bg-white/10">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[11px] font-black text-white">2</span>
                        <span>Cuartos y precios</span>
                    </a>
                    <a href="#paso-pago" class="flex items-center gap-2 rounded-xl bg-white/5 px-3.5 py-2 text-xs font-bold text-white/85 hover:bg-white/10">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[11px] font-black text-white">3</span>
                        <span>Resumen y reserva</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Layout de 2 columnas: Configuración a la izquierda + Resumen y Pago fijo a la derecha -->
        <div class="grid gap-8 lg:grid-cols-[1.35fr_0.85fr] lg:items-start">
            <!-- COLUMNA IZQUIERDA -->
            <div class="space-y-8">
                <!-- PASO 1: CALENDARIO Y HUÉSPEDES -->
                <section id="paso-calendario" class="glass-section rounded-[28px] p-5 sm:p-7 reveal">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div>
                            <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.18em] text-[#f0d39a]">
                                <span class="material-symbols-outlined text-[18px] notranslate" translate="no">calendar_month</span>
                                <span>Paso 1 · Fechas y Huéspedes</span>
                            </span>
                            <h2 class="font-display mt-1 text-2xl text-white sm:text-3xl">Escoge tus fechas en el calendario</h2>
                            <p class="mt-1 text-xs sm:text-sm text-white/75">Selecciona el día de llegada (check-in) y el día de salida (check-out).</p>
                        </div>
                        <span id="availabilityBadge" class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/30 bg-emerald-950/60 px-3.5 py-1.5 text-xs font-bold text-emerald-200">
                            <span class="material-symbols-outlined text-[16px] notranslate" translate="no">bolt</span>
                            <span id="availabilityBadgeText">Selecciona tus fechas</span>
                        </span>
                    </div>

                    <!-- Tarjetas de resumen de fecha y controles de huéspedes -->
                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/15 bg-white/8 p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-[#f0d39a]">Llegada (Check-in)</p>
                            <p id="displayCheckin" class="mt-1 text-base font-extrabold text-white">Sin seleccionar</p>
                            <p class="mt-0.5 text-[11px] text-white/60">Desde las 15:00 h</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/8 p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-[#f0d39a]">Salida (Check-out)</p>
                            <p id="displayCheckout" class="mt-1 text-base font-extrabold text-white">Sin seleccionar</p>
                            <p class="mt-0.5 text-[11px] text-white/60">Hasta las 12:00 h</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-400/25 bg-emerald-950/40 p-4">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-emerald-300">Duración total</p>
                            <p id="displayNights" class="mt-1 text-xl font-black text-white">1 noche</p>
                            <p class="mt-0.5 text-[11px] text-emerald-200/75">Se multiplica por cada cuarto</p>
                        </div>
                    </div>

                    <!-- Calendario interactivo embebido -->
                    <div class="mt-5 rounded-2xl bg-white p-3 sm:p-5 text-slate-900 shadow-inner">
                        <input id="inlineCalendarInput" type="text" class="hidden" aria-label="Calendario de check-in y check-out">
                        <div id="inlineCalendarContainer" class="flex justify-center"></div>
                    </div>

                    <!-- Controles de Adultos y Niños -->
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="flex items-center justify-between rounded-2xl border border-white/15 bg-white/8 px-4 py-3.5">
                            <div>
                                <p class="text-sm font-extrabold text-white">Adultos</p>
                                <p class="text-xs text-white/65">Mayores de 12 años</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button id="resAdultsMinus" type="button" class="guest-stepper">-</button>
                                <span id="resAdultsValue" class="min-w-[24px] text-center text-base font-black text-white">2</span>
                                <button id="resAdultsPlus" type="button" class="guest-stepper">+</button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl border border-white/15 bg-white/8 px-4 py-3.5">
                            <div>
                                <p class="text-sm font-extrabold text-white">Niños</p>
                                <p class="text-xs text-white/65">De 0 a 12 años</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button id="resChildrenMinus" type="button" class="guest-stepper">-</button>
                                <span id="resChildrenValue" class="min-w-[24px] text-center text-base font-black text-white">0</span>
                                <button id="resChildrenPlus" type="button" class="guest-stepper">+</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- PASO 2: CUARTOS SELECCIONADOS Y PRECIO POR UNIDAD -->
                <section id="paso-cuartos" class="glass-section rounded-[28px] p-5 sm:p-7 reveal">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-5">
                        <div>
                            <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.18em] text-[#f0d39a]">
                                <span class="material-symbols-outlined text-[18px] notranslate" translate="no">bed</span>
                                <span>Paso 2 · Cuartos Seleccionados</span>
                            </span>
                            <h2 class="font-display mt-1 text-2xl text-white sm:text-3xl">Tus habitaciones y precio por unidad</h2>
                            <p class="mt-1 text-xs sm:text-sm text-white/75">Revisa el valor por unidad/noche de cada cuarto o agrega más habitaciones a tu reserva.</p>
                        </div>
                        <button id="clearAllRoomsBtn" type="button" class="rounded-full border border-white/20 bg-white/8 px-4 py-2 text-xs font-bold text-white/80 transition hover:bg-rose-500/20 hover:border-rose-300/40 hover:text-rose-200">
                            Vaciar selección
                        </button>
                    </div>

                    <!-- Lista dinámica de cuartos seleccionados -->
                    <div id="selectedRoomsList" class="mt-5 space-y-4"></div>

                    <!-- Estado vacío cuando aún no hay cuartos seleccionados -->
                    <div id="emptySelectedRooms" class="mt-5 hidden rounded-2xl border border-dashed border-white/25 bg-white/5 p-8 text-center">
                        <span class="material-symbols-outlined text-4xl text-[#f0d39a] notranslate" translate="no">hotel</span>
                        <p class="mt-2 text-lg font-extrabold text-white">Aún no has seleccionado ningún cuarto</p>
                        <p class="mt-1 text-sm text-white/70">Elige una o más habitaciones del catálogo disponible aquí abajo para ver su precio por unidad.</p>
                    </div>

                    <!-- Catálogo integrado para agregar más cuartos sin salir de la página -->
                    <div class="mt-8 border-t border-white/15 pt-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-lg font-extrabold text-white">Agregar habitaciones disponibles</h3>
                                <p class="text-xs text-white/70">Puedes combinar varias habitaciones dentro de una misma reserva.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-catalog-filter="all" class="catalog-filter rounded-full border border-white/25 bg-white/15 px-3 py-1 text-xs font-bold text-white">Todas</button>
                                <button type="button" data-catalog-filter="suite" class="catalog-filter rounded-full border border-white/15 bg-transparent px-3 py-1 text-xs font-bold text-white/70">Suite</button>
                                <button type="button" data-catalog-filter="doble" class="catalog-filter rounded-full border border-white/15 bg-transparent px-3 py-1 text-xs font-bold text-white/70">Doble</button>
                                <button type="button" data-catalog-filter="sencilla" class="catalog-filter rounded-full border border-white/15 bg-transparent px-3 py-1 text-xs font-bold text-white/70">Sencillas</button>
                            </div>
                        </div>

                        <div id="availableCatalogGrid" class="grid gap-4 sm:grid-cols-2"></div>
                    </div>
                </section>

                <!-- NOTAS ADICIONALES -->
                <section class="glass-section rounded-[28px] p-5 sm:p-7 reveal">
                    <label for="reservationNotes" class="block text-xs font-black uppercase tracking-[0.18em] text-[#f0d39a]">
                        Solicitudes especiales o notas de llegada (Opcional)
                    </label>
                    <p class="mt-1 text-xs text-white/70">Indícanos tu hora aproximada de llegada o cualquier preferencia para tus habitaciones.</p>
                    <textarea
                        id="reservationNotes"
                        rows="3"
                        maxlength="400"
                        placeholder="Ej. Llegada después de las 18:00 h, preferencia por piso alto..."
                        class="mt-3 w-full rounded-2xl border border-white/20 bg-white/95 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-emerald-600"
                    ></textarea>
                </section>
            </div>

            <!-- COLUMNA DERECHA: RESUMEN FINANCIERO Y BOTÓN DE RESERVA (STICKY) -->
            <aside id="paso-pago" class="glass-section rounded-[28px] p-5 sm:p-7 lg:sticky lg:top-24 reveal">
                <div class="border-b border-white/10 pb-4">
                    <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.18em] text-[#f0d39a]">
                        <span class="material-symbols-outlined text-[18px] notranslate" translate="no">receipt_long</span>
                        <span>Paso 3 · Resumen y Confirmación</span>
                    </span>
                    <h2 class="font-display mt-1 text-2xl text-white sm:text-3xl">Desglose de tu reserva</h2>
                </div>

                <!-- Resumen de fechas y huéspedes -->
                <div class="mt-4 rounded-2xl border border-white/12 bg-white/6 p-4 space-y-2.5 text-xs sm:text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-white/70">Check-in:</span>
                        <span id="summaryCheckin" class="font-extrabold text-white">Selecciona fecha</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-white/70">Check-out:</span>
                        <span id="summaryCheckout" class="font-extrabold text-white">Selecciona fecha</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-white/70">Duración:</span>
                        <span id="summaryNights" class="font-extrabold text-[#f0d39a]">1 noche</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-white/70">Huéspedes:</span>
                        <span id="summaryGuests" class="font-extrabold text-white">2 adultos, 0 niños</span>
                    </div>
                </div>

                <!-- Desglose por unidad de los cuartos seleccionados -->
                <div class="mt-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-white/65 mb-2.5">Detalle por habitación</p>
                    <div id="summaryRoomsBreakdown" class="space-y-2 text-xs sm:text-sm"></div>
                </div>

                <!-- Totales financieros -->
                <div class="mt-5 border-t border-white/15 pt-4 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between text-white/80">
                        <span>Precio por noche total:</span>
                        <span id="summaryPerNight" class="font-bold">$0</span>
                    </div>
                    <div class="flex items-center justify-between text-white/80">
                        <span>Subtotal estadía:</span>
                        <span id="summarySubtotal" class="font-bold">$0</span>
                    </div>
                    <div class="flex items-center justify-between text-white/80">
                        <span>Impuestos (IVA 19%):</span>
                        <span id="summaryIva" class="font-bold">$0</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/15 pt-3 text-base font-black text-white">
                        <span>Total de la reserva:</span>
                        <span id="summaryTotal" class="text-xl text-[#f0d39a]">$0</span>
                    </div>
                </div>

                <!-- Selección de Modalidad de Cobro (100% vs 50%) -->
                <div class="mt-6">
                    <label class="mb-2 block text-[11px] font-black uppercase tracking-[0.16em] text-white/75">Modalidad de cobro</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button type="button" data-pago-tipo="100" class="payment-option is-active rounded-2xl p-3 text-left">
                            <p class="text-xs font-black uppercase text-slate-900">Pago Total (100%)</p>
                            <p class="mt-0.5 text-[11px] text-slate-600">Liquida el total ahora</p>
                        </button>
                        <button type="button" data-pago-tipo="50" class="payment-option rounded-2xl p-3 text-left">
                            <p class="text-xs font-black uppercase text-slate-900">Abono (50%)</p>
                            <p class="mt-0.5 text-[11px] text-slate-600">50% hoy · 50% al llegar</p>
                        </button>
                    </div>
                </div>

                <!-- Selección de Método de Pago -->
                <div class="mt-5">
                    <label class="mb-2 block text-[11px] font-black uppercase tracking-[0.16em] text-white/75">Método de pago</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button type="button" data-payment="Transferencia" class="payment-chip is-active p-3 text-left">
                            <div class="flex items-center gap-1.5 text-slate-900 font-black text-xs">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700 notranslate" translate="no">verified_user</span>
                                <span>Pago en línea</span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-600">Wompi · PSE / Tarjeta</p>
                        </button>
                        <button type="button" data-payment="Recepción" class="payment-chip p-3 text-left">
                            <div class="flex items-center gap-1.5 text-slate-900 font-black text-xs">
                                <span class="material-symbols-outlined text-[18px] text-emerald-700 notranslate" translate="no">storefront</span>
                                <span>En Recepción</span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-600">Pagar en el hotel</p>
                        </button>
                    </div>
                </div>

                <!-- Caja de monto a pagar ahora -->
                <div class="mt-5 rounded-2xl border border-emerald-400/35 bg-emerald-950/60 p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.14em] text-emerald-300">Monto a pagar ahora</p>
                        <p id="summaryPayNowHint" class="text-[11px] text-white/70">100% del total con impuestos</p>
                    </div>
                    <p id="summaryPayNow" class="text-2xl font-black text-emerald-300">$0</p>
                </div>

                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="mt-5 flex justify-center" aria-label="Verificación de seguridad">
                        <div class="g-recaptcha" data-sitekey="<?php echo e($recaptchaSiteKey); ?>"></div>
                    </div>
                <?php endif; ?>

                <p id="checkoutFeedback" class="mt-4 hidden rounded-xl border px-4 py-3 text-xs font-bold"></p>

                <?php if (!$usuarioAutenticado): ?>
                    <div class="mt-5 rounded-2xl border border-amber-300/35 bg-amber-950/50 p-4 text-xs text-amber-100">
                        <p class="font-extrabold text-amber-200">Inicia sesión para finalizar tu reserva</p>
                        <p class="mt-1 text-white/80">Tus cuartos y fechas seleccionados quedarán guardados automáticamente.</p>
                        <div class="mt-3 flex gap-2">
                            <a href="interfaz/loggins/index_usu.php" class="hero-button gold-button flex-1 py-2.5 text-center text-xs font-black uppercase tracking-[0.12em]">
                                Iniciar sesión
                            </a>
                            <a href="interfaz/loggins/index_usu.php?vista=registro" class="hero-button secondary-button flex-1 py-2.5 text-center text-xs font-bold">
                                Registrarse
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <button
                    id="confirmReservationBtn"
                    type="button"
                    class="hero-button gold-button mt-5 w-full py-4 px-6 text-sm font-black uppercase tracking-[0.16em] inline-flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-[20px] notranslate" translate="no">check_circle</span>
                    <span id="confirmReservationBtnLabel">Confirmar y Reservar</span>
                </button>
            </aside>
        </div>
    </main>

    <script src="assets/js/reservar.js?v=25"></script>
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
