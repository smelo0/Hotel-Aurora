<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/includes/sesion_seguridad.php';

require_once __DIR__ . '/configuracion/conexion.php';
require_once __DIR__ . '/configuracion/wompi.php';
require_once __DIR__ . '/includes/experiencias.php';
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
        'Confirmada' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'Pendiente' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'Cancelada' => 'bg-rose-100 text-rose-700 border border-rose-200',
        'Cancelado' => 'bg-rose-100 text-rose-700 border border-rose-200',
        'Finalizada' => 'bg-slate-200 text-slate-700 border border-slate-300',
        'En Casa' => 'bg-cyan-100 text-cyan-700 border border-cyan-200',
    ];

    return $map[$estado] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
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
$visibleRooms = 6; // mostrar sólo las primeras N habitaciones en el carrusel
$experiencias = $portalData['experiencias'];
$usuarioId = $portalData['usuarioId'];
$usuarioNombre = $portalData['usuarioNombre'];
$usuarioAutenticado = $portalData['usuarioAutenticado'];
$usuarioTieneReserva = $portalData['usuarioTieneReserva'];
$reservasEstanciaExperiencia = $portalData['reservasEstanciaExperiencia'];
$historialReservas = $portalData['historialReservas'];
$historialExperiencias = $portalData['historialExperiencias'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aurora | Reservas y Experiencias</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://checkout.wompi.co/widget.js"></script>
    <?php
    $recaptchaSiteKey = trim((string) (
        getenv('RECAPTCHA_SITE_KEY')
        ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')
    ));
    ?>
    <?php if ($recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/interfaz_usu.css?v=13">
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
    <nav class="site-nav fixed inset-x-0 top-0 z-50">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 md:px-6">
            <a href="#inicio" class="flex items-center gap-3 text-white">
                <span class="logo">
                <img src="assets/images/logo.jpeg" class="h-10 w-auto">
                
                </span>
                <div>
                    <p class="font-display text-2xl leading-none text-white">Hotel Aurora</p>
                </div>
            </a>
          
            <div class="hidden items-center gap-8 text-sm font-bold text-white/85 lg:flex">
                <a href="#habitaciones" class="transition hover:text-white">Habitaciones</a>
                <a href="#experiencias" class="transition hover:text-white">Experiencias</a>
                <a href="#planner" class="transition hover:text-white">Agenda</a>
            </div>

            <div class="flex items-center gap-3">
                <?php include __DIR__ . "/includes/translate.php";?>
                <?php if ($usuarioAutenticado): ?>
                    <span class="hidden rounded-full border border-white/12 bg-white/10 px-4 py-2 text-sm font-semibold text-white md:inline-flex">
                        Hola, <?php echo e($usuarioNombre); ?>
                    </span>
                    <button id="logoutButton" type="button" class="hero-button secondary-button px-4 py-3 text-sm">
                        Cerrar sesión

                    </button>
                <?php else: ?>
                    <a href="interfaz/loggins/index_usu.php" class="hero-button secondary-button px-4 py-3 text-sm">
                        Iniciar sesión
                    </a>
                    <a href="interfaz/loggins/index_usu.php?vista=registro" class="hero-button bg-white px-4 py-3 text-sm font-extrabold text-slate-900">
                        Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header id="inicio" class="hero-shell relative flex min-h-fit items-center px-4 pb-20 pt-36 md:px-6">
        <div class="mx-auto grid w-full max-w-7xl gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-end">
            <div class="reveal">
                <p class="mb-5 inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.24em] text-[#ffe3aa]">
                    Reservas premium frente al mar
                </p>
                <h1 class="font-display max-w-3xl text-5xl leading-tight text-white md:text-7xl">
                    Reserva tu próxima estancia con una experiencia visual impecable.
                </h1>
                <p class="hero-copy mt-6 max-w-2xl text-lg leading-8 text-white md:text-xl">
                    Elige tus fechas, ajusta tus huéspedes y encuentra habitaciones disponibles al instante con una búsqueda realmente funcional.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#bookingBar" class="hero-button primary-button inline-flex items-center gap-2 px-6 py-4 text-sm uppercase tracking-[0.18em]">
                        Reservar ahora
                    </a>
                    <a href="#experiencias" class="hero-button secondary-button inline-flex items-center gap-2 px-6 py-4 text-sm">
                        Explorar experiencias
                    </a>
                </div>
            </div>

            <div class="reveal lg:justify-self-end">
                <div id="bookingBar" class="glass-card booking-bar relative mx-auto max-w-2xl">
                    <div class="grid gap-3 md:grid-cols-[1.3fr_1fr_auto]">
                        <div class="booking-control p-5">
                            <button id="dateTrigger" type="button" class="flex h-full w-full items-center justify-between text-left">
                                <span>
                                    <span class="booking-label">Fechas</span>
                                    <span id="dateSummary" class="booking-value">Selecciona check-in y check-out</span>
                                </span>
                                <span class="material-symbols-outlined text-3xl text-[#17354f]">calendar_month</span>
                            </button>
                            <input id="dateRangeInput" type="text" class="pointer-events-none absolute left-3 top-3 h-px w-px opacity-0" aria-hidden="true">
                        </div>

                        <div class="booking-control p-5">
                            <button id="guestTrigger" type="button" class="flex h-full w-full items-center justify-between text-left">
                                <span>
                                    <span class="booking-label">Huéspedes</span>
                                    <span id="guestSummary" class="booking-value">2 adultos, 0 niños</span>
                                </span>
                                <span class="material-symbols-outlined text-3xl text-[#17354f]">groups</span>
                            </button>

                            <div id="guestPopover" class="guest-popover">
                                <div class="guest-popover__panel">
                                    <div class="guest-row">
                                        <div>
                                            <p class="font-black text-[#17354f]">Adultos</p>
                                            <p class="text-sm text-slate-500">Mayores de 12 años</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button id="adultsMinus" type="button" class="guest-stepper">-</button>
                                            <span id="adultsValue" class="min-w-[24px] text-center text-lg font-black text-[#17354f]">2</span>
                                            <button id="adultsPlus" type="button" class="guest-stepper">+</button>
                                        </div>
                                    </div>

                                    <div class="guest-row border-t border-slate-200">
                                        <div>
                                            <p class="font-black text-[#17354f]">Niños</p>
                                            <p class="text-sm text-slate-500">De 0 a 12 años</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button id="childrenMinus" type="button" class="guest-stepper">-</button>
                                            <span id="childrenValue" class="min-w-[24px] text-center text-lg font-black text-[#17354f]">0</span>
                                            <button id="childrenPlus" type="button" class="guest-stepper">+</button>
                                        </div>
                                    </div>

                                    <button id="closeGuestPopover" type="button" class="hero-button primary-button mt-4 w-full px-5 py-3 text-sm uppercase tracking-[0.18em]">
                                        Aplicar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button id="btnBuscarDisponibilidad" type="button" class="hero-button primary-button min-h-[78px] px-7 text-sm uppercase tracking-[0.18em]">
                            Buscar Disponibilidad
                        </button>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <p id="searchFeedback" class="hidden text-sm font-bold"></p>
                        
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 pb-20 md:px-6">
        <section id="vista-resultados" class="mx-auto mt-8 max-w-7xl reveal">
            <div id="habitaciones" class="glass-section rounded-[34px] px-6 py-8 md:px-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Habitaciones destacadas</p>
                        <h2 class="section-title font-display mt-3 text-4xl text-white">Descubre las estancias listas para tu próxima reserva.</h2>
                    </div>
                    <span class="soft-chip inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-black uppercase tracking-[0.18em]">
                        <span class="material-symbols-outlined text-base">verified</span>
                        Disponibilidad en tiempo real
                    </span>
                </div>

                <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" data-filter="all" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/6 px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white transition transform duration-200 hover:scale-105 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/25">Todas</button>
                        <button type="button" data-filter="suite" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/70 transition transform duration-200 hover:scale-105 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">Suite</button>
                        <button type="button" data-filter="doble" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/70 transition transform duration-200 hover:scale-105 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">Doble</button>
                        <button type="button" data-filter="sencilla" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/70 transition transform duration-200 hover:scale-105 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">Sencillas</button>
                    </div>
                    <div class="flex items-center gap-2 self-end md:self-auto">
                        <button id="roomsPrev" type="button" aria-label="Habitaciones anteriores" class="rooms-nav flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-md backdrop-blur transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </button>
                        <button id="roomsNext" type="button" aria-label="Siguientes habitaciones" class="rooms-nav flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white shadow-md backdrop-blur transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    </div>
                </div>

             <div class="mt-8 relative">
                <div id="roomsCarousel" class="rooms-carousel mt-0 flex gap-6 overflow-x-auto snap-x px-4 md:px-0">
                    <?php foreach (array_slice($habitaciones, 0, $visibleRooms) as $room): ?>
        <?php
        $roomName = 'Habitación ' . $room['num_hab'] . ' · ' . $room['tipo_hab'];
        $features = roomFeatures((string) $room['tipo_hab']);
        $tipoLower = mb_strtolower((string) $room['tipo_hab'], 'UTF-8');
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
        <article class="room-card min-w-[350px] sm:min-w-[380px] snap-start shrink-0" data-room-card="<?php echo (int) $room['cod_hab']; ?>" data-room-type="<?php echo $roomType; ?>">
            <img src="<?php echo e(roomImage((string) $room['tipo_hab'])); ?>" alt="<?php echo e($roomName); ?>" class="h-64 w-full object-cover" loading="lazy" decoding="async">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="section-kicker text-xs font-black uppercase tracking-[0.18em]"><?php echo e((string) $room['tipo_hab']); ?></p>
                        <h3 class="room-card__title mt-2 text-2xl font-black text-white"><?php echo e($roomName); ?></h3>
                    </div>
                    <span class="soft-chip rounded-full px-3 py-2 text-xs font-black uppercase tracking-[0.18em]">
                        Reserva online
                    </span>
                </div>

                <p class="muted-light mt-4 min-h-[72px] text-sm leading-7">
                    <?php echo e((string) $room['obs_hab']); ?>
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <?php foreach ($features as $feature): ?>
                        <span class="soft-chip rounded-full px-3 py-2 text-xs font-bold"><?php echo e($feature); ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/60">Tarifa por noche</p>
                        <p class="mt-1 text-2xl font-black text-white">$<?php echo number_format((float) $room['pre_hab'], 0, ',', '.'); ?></p>
                    </div>
                    <button
                        type="button"
                        class="hero-button primary-button px-5 py-4 text-sm uppercase tracking-[0.16em]"
                        data-room-select
                        data-room-id="<?php echo (int) $room['cod_hab']; ?>"
                        data-room-name="<?php echo e($roomName); ?>"
                        data-room-price="<?php echo (float) $room['pre_hab']; ?>"
                    >
                        Reservar
                    </button>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
                </div>
            </div>
                <div id="emptyRoomsState" class="hidden rounded-[28px] border border-dashed border-white/20 bg-white/8 p-10 text-center backdrop-blur-lg">
                    <p class="text-lg font-black text-white">No hay habitaciones disponibles para esas fechas.</p>
                    <p class="muted-light mt-2">Cambia el rango de fechas o vuelve a consultar en unos segundos.</p>
                </div>
            </div>
        </section>

        <section id="experiencias" class="mx-auto mt-10 max-w-7xl reveal">
            <?php if ($experiencias === []): ?>
                <p class="muted-light text-center text-sm">Muy pronto compartiremos nuevas experiencias.</p>
            <?php else: ?>
            <div class="grid gap-6 lg:grid-cols-3">
                <?php foreach ($experiencias as $indice => $experiencia): ?>
                    <article class="room-card">
                        <img src="<?php echo e(experienciaImagen($experiencia['categoria'], $indice, $experiencia['imagen'])); ?>" alt="<?php echo e($experiencia['nombre']); ?>" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                        <div class="p-6">
                            <p class="section-kicker text-xs font-black uppercase tracking-[0.18em]"><?php echo e($experiencia['categoria']); ?></p>
                            <h3 class="card-title mt-3 text-2xl font-black text-white"><?php echo e($experiencia['nombre']); ?></h3>
                            <p class="muted-light mt-3 text-sm leading-7"><?php echo nl2br(e($experiencia['descripcion'])); ?></p>
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
                            <div class="mt-5">
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <?php foreach ($experiencia['opciones'] as $indiceOpcion => $opcion): ?>
                                        <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/85">
                                            <?php echo e($opcion); ?> · <?php echo $experiencia['precios'][$indiceOpcion] === null ? 'Sin precio' : '$' . number_format((float) $experiencia['precios'][$indiceOpcion], 0, ',', '.') . ' COP'; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($horariosConfigurados): ?>
                                    <p class="mb-3 text-xs text-white/70">Fechas disponibles: <?php echo e(implode(', ', array_map(static fn(string $fechaProgramada): string => date('d/m/Y', strtotime($fechaProgramada)), $fechasConfiguradas))); ?></p>
                                <?php endif; ?>
                                <button
                                    type="button"
                                    class="experience-select-button w-full rounded-xl border border-white/30 bg-white/15 px-4 py-3 text-left text-sm font-bold text-white transition hover:border-white/60 hover:bg-white/25 disabled:cursor-not-allowed disabled:opacity-50"
                                    data-experience-id="<?php echo (int) $experiencia['id']; ?>"
                                    data-experience-name="<?php echo e($experiencia['nombre']); ?>"
                                    data-experience-options="<?php echo e(json_encode($experiencia['opciones'], JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                    data-experience-prices="<?php echo e(json_encode($experiencia['precios'], JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                    data-experience-option-indices="<?php echo e(json_encode($experiencia['numeros_opciones'], JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                    data-experience-schedule="<?php echo e(json_encode($experiencia['horarios'], JSON_UNESCAPED_UNICODE) ?: '{}'); ?>"
                                    data-reservation-stays="<?php echo e(json_encode($reservasEstanciaExperiencia, JSON_UNESCAPED_UNICODE) ?: '[]'); ?>"
                                    <?php echo $experiencia['opciones'] === [] || !$horariosConfigurados || !$usuarioAutenticado || !$usuarioTieneReserva ? 'disabled' : ''; ?>>
                                    <?php echo !$usuarioAutenticado ? 'Inicia sesión para programar' : (!$usuarioTieneReserva ? 'Reserva primero para programar' : 'Elegir esta experiencia'); ?>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section id="planner" class="mx-auto mt-10 max-w-7xl reveal">
            <div class="glass-section grid gap-8 rounded-[34px] px-6 py-8 md:px-8 lg:grid-cols-[1fr_0.95fr]">
                <div>
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.22em]">Agenda tu estancia</p>
                    <h2 class="section-title font-display mt-3 text-4xl text-white">Solicita una experiencia antes de llegar.</h2>
                    <p class="muted-light mt-4 max-w-2xl text-base leading-8">
                        Programa una sesión de spa, una cena especial o una actividad privada para que nuestro equipo la prepare con anticipación. La fecha debe estar dentro de tu estancia reservada.
                    </p>
                </div>

                <?php if (!$usuarioAutenticado): ?>
                    <div class="rounded-[28px] bg-white/12 p-6 shadow-[0_18px_40px_rgba(23,53,79,0.12)] backdrop-blur-xl">
                        <h3 class="text-xl font-black text-white">Inicia sesión para programar</h3>
                        <p class="muted-light mt-2 text-sm leading-6">Para solicitar una experiencia, primero inicia sesión con tu cuenta de huésped y realiza una reserva.</p>
                        <a href="interfaz/loggins/index_usu.php" class="hero-button primary-button mt-5 inline-flex px-5 py-3 text-sm font-bold">Iniciar sesión</a>
                    </div>
                <?php elseif (!$usuarioTieneReserva): ?>
                    <div class="rounded-[28px] bg-white/12 p-6 shadow-[0_18px_40px_rgba(23,53,79,0.12)] backdrop-blur-xl">
                        <h3 class="text-xl font-black text-white">Haz tu reserva primero</h3>
                        <p class="muted-light mt-2 text-sm leading-6">Cuando tengas una reserva activa, podrás elegir la experiencia, las opciones por persona y el horario.</p>
                        <a href="#habitaciones" class="hero-button primary-button mt-5 inline-flex px-5 py-3 text-sm font-bold">Ver habitaciones</a>
                    </div>
                <?php else: ?>
                    <div class="rounded-[28px] bg-white/12 p-6 shadow-[0_18px_40px_rgba(23,53,79,0.12)] backdrop-blur-xl">
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
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
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
                
         
<section id="historial" class="mx-auto mt-10 max-w-7xl reveal">
    <!-- Borde grueso gris medio transparente (border-8 border-slate-300/30) -->
    <div class="rounded-[34px] bg-transparent border-8 border-slate-300/30 px-6 py-8 text-white shadow-[0_20px_60px_rgba(15,23,42,0.15)] md:px-20">
        
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-300">Mi historial</p>
                <h2 class="mt-2 text-3xl font-black text-white">Reservas realizadas</h2>
            </div>
        
            <span class="rounded-full bg-slate-200 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-slate-800 border border-slate-200">
                <?php echo count($historialReservas); ?> registros
            </span>
        </div>

        <?php if (empty($historialReservas)): ?>
            <div class="rounded-[28px] border border-dashed border-slate-300 bg-slate-100 px-6 py-10 text-center">
                <span class="material-symbols-outlined text-4xl text-emerald-700">travel_explore</span>
                <p class="mt-3 text-lg font-black text-slate-800">Aún no tienes reservas registradas.</p>
                <p class="mt-2 text-sm text-slate-600">Cuando reserves una estancia, aparecerá aquí el historial completo.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-y-3 text-left">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-200">
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
                                : 'Habitación sin asignar';
                            ?>
                            
                            <tr class="rounded-2xl bg-emerald-950/50 text-sm text-white shadow-sm transition-colors hover:bg-emerald-700">
                                <td class="rounded-l-2xl px-4 py-4 font-black text-emerald-200">#<?php echo (int) $reserva['cod_res']; ?></td>
                                <td class="px-4 py-4 text-white"><?php echo $descripcionHabitacion; ?></td>
                                <td class="px-4 py-4 text-emerald-100"><?php echo formatReservationHistoryDate((string) $reserva['fec_ent_res']); ?></td>
                                <td class="px-4 py-4 text-emerald-100"><?php echo formatReservationHistoryDate((string) $reserva['fec_sal_res']); ?></td>
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
<section id="historial-experiencias" class="mx-auto mt-8 max-w-7xl reveal">
    <div class="rounded-[34px] border-8 border-slate-300/30 bg-transparent px-6 py-8 text-white shadow-[0_20px_60px_rgba(15,23,42,0.15)] md:px-20">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-300">Mi historial</p>
                <h2 class="mt-2 text-3xl font-black text-white">Experiencias programadas</h2>
            </div>
            <span class="rounded-full border border-slate-200 bg-slate-200 px-3 py-2 text-xs font-black uppercase tracking-[0.18em] text-slate-800">
                <?php echo count($historialExperiencias); ?> registros
            </span>
        </div>

        <?php if ($historialExperiencias === []): ?>
            <div class="rounded-[28px] border border-dashed border-slate-300 bg-slate-100 px-6 py-10 text-center">
                <span class="material-symbols-outlined text-4xl text-emerald-700">event_note</span>
                <p class="mt-3 text-lg font-black text-slate-800">Aún no has programado experiencias.</p>
                <p class="mt-2 text-sm text-slate-600">Tus solicitudes aparecerán aquí cuando programes una experiencia.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-y-3 text-left">
                    <thead>
                        <tr class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-200">
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
                            <tr class="bg-emerald-950/50 text-sm text-white shadow-sm transition-colors hover:bg-emerald-700">
                                <td class="rounded-l-2xl px-4 py-4 font-black text-white"><?php echo e((string) $solicitudExperiencia['actividad']); ?></td>
                                <td class="px-4 py-4 text-emerald-100">
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
                                <td class="px-4 py-4 text-emerald-100"><?php echo formatReservationHistoryDate((string) $solicitudExperiencia['fecha_agenda']); ?></td>
                                <td class="px-4 py-4 text-emerald-100"><?php echo e(substr((string) $solicitudExperiencia['hora_agenda'], 0, 5)); ?></td>
                                <td class="px-4 py-4 text-emerald-100"><?php echo $solicitudExperiencia['monto_experiencia'] === null ? 'No definido' : '$' . number_format((float) $solicitudExperiencia['monto_experiencia'], 0, ',', '.') . ' COP'; ?></td>
                                <td class="rounded-r-2xl px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.12em] <?php echo reservationStatusBadge((string) ($solicitudExperiencia['estado_agenda'] ?? 'Pendiente')); ?>">
                                        <?php echo e((string) ($solicitudExperiencia['estado_agenda'] ?? 'Pendiente')); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
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

    <footer class="px-4 pt-14 pb-8 md:px-6 text-sm" style="background: rgba(9, 14, 26, 0.55); border-top: 1px solid rgba(255,255,255,0.1);">
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
                    <li><a href="mailto:reservas@hotelaurora.com" class="hover:text-white">reservas@hotelaurora.com</a></li>
                    <!-- TODO: reemplaza estos datos por los reales del hotel -->
                    <li>Teléfono: +57 XXX XXX XXXX</li>
                    <li>Solo WhatsApp: XXX XXX XXXX</li>
                </ul>
            </div>

            <div>
                <h3 class="font-display text-white text-base mb-4">Síguenos en:</h3>
                <div class="flex items-center gap-4 text-white/70">
                    <!-- TODO: reemplaza los "#" por los enlaces reales a tus redes -->
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
                <!-- TODO: agrega la dirección real del hotel -->
                <p class="mt-6 text-white/60 text-xs">Dirección: [pendiente]</p>
            </div>
        </div>

        <div class="max-w-6xl mx-auto mt-10 pt-6 flex items-center justify-center gap-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <img src="assets/images/logo.jpeg" alt="Hotel Aurora" class="h-8 w-auto rounded-full">
            <span class="font-display text-white text-lg">Hotel Aurora</span>
        </div>
    </footer>



   
   

    
    <div class="system-help">
        <section id="systemHelpPanel" class="system-help__panel" role="dialog" aria-labelledby="systemHelpTitle" aria-hidden="true">
            <div class="flex items-start justify-between gap-4 bg-[#17354f] px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/60">Centro de ayuda</p>
                    <h2 id="systemHelpTitle" class="mt-1 text-lg font-black">¿Cómo podemos ayudarte?</h2>
                </div>
                <button id="closeSystemHelp" type="button" class="rounded-full p-1 text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="Cerrar ayuda">
                    <span class="material-symbols-outlined pointer-events-none">close</span>
                </button>
            </div>
            <div>
                <div class="border-t border-white/10 px-5 py-3">
                    <button type="button" data-start-system-tour class="text-sm font-extrabold text-white hover:text-[#ffe3aa]">
                        Iniciar recorrido paso a paso
                    </button>
                </div>
                <details class="system-help__question" open>
                    <summary>¿Cómo busco una habitación?</summary>
                    <p class="system-help__answer">Abre “Fechas”, selecciona tu check-in y check-out, ajusta los huéspedes y pulsa “Buscar disponibilidad”.</p>
                </details>
                <details class="system-help__question">
                    <summary>¿Qué necesito para reservar?</summary>
                    <p class="system-help__answer">Debes iniciar sesión, elegir fechas válidas y seleccionar una habitación disponible. Después revisa el total y el método de pago.</p>
                </details>
                <details class="system-help__question">
                    <summary>¿Puedo pagar solo una parte?</summary>
                    <p class="system-help__answer">Sí. En la confirmación puedes elegir pago total o un abono inicial del 50%. El saldo del abono se paga en recepción.</p>
                </details>
                <details class="system-help__question">
                    <summary>¿Cómo solicito una experiencia?</summary>
                    <p class="system-help__answer">En “Agenda tu estancia”, elige la experiencia, fecha, hora y tus datos de contacto. El equipo confirmará la solicitud.</p>
                </details>
                <div class="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
                    ¿Necesitas más ayuda? Escríbenos desde tu correo a <a href="mailto:reservas@hotelaurora.com" class="!mt-1 !text-left font-bold !text-[#17354f] hover:!text-[#c19046]">reservas@hotelaurora.com</a>.
                </div>
            </div>
        </section>
        <button id="systemHelpButton" type="button" class="hero-button primary-button flex items-center gap-2 px-4 py-3 text-sm font-black shadow-xl" aria-controls="systemHelpPanel" aria-expanded="false">
            <span class="material-symbols-outlined text-[20px]">help</span>
            <span>Ayuda</span>
        </button>
    </div>
    <?php
    $tourSistemaRol = !empty($_SESSION['user_auth']) ? 'usuario' : 'visitante';
    require_once __DIR__ . '/includes/system_tour.php';
    ?>

 <div id="bookingModal" class="booking-modal fixed inset-0 z-[90] flex items-center justify-center bg-emerald-950/40 px-4 py-10">
        
    <div class="flex min-h-full items-center justify-center">
        <div class="glass-card w-full max-w-5xl overflow-hidden rounded-[32px]">
            <div class="grid lg:grid-cols-[1.05fr_0.95fr]">
                <!-- Lateral con información del cobro -->
                <aside class="bg-[#17354f] px-7 py-8 text-white">
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-white/60">Confirmación y Cobro</p>
                    <h3 id="modalRoomName" class="mt-2 text-3xl font-black">Habitación seleccionada</h3>
                    <p class="mt-3 max-w-md text-white/74">Revisa el desglose financiero y confirma tu transacción.</p>

                    <div class="mt-6 space-y-4 rounded-[28px] border border-white/10 bg-white/6 p-5">
                        <div><p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/50">Check-in</p><p id="modalCheckin" class="mt-1 text-lg font-black">-</p></div>
                        <div><p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/50">Check-out</p><p id="modalCheckout" class="mt-1 text-lg font-black">-</p></div>
                        <div><p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/50">Huéspedes</p><p id="modalGuests" class="mt-1 text-lg font-black">2 adultos, 0 niños</p></div>
                    </div>

                    <!-- Cálculos automáticos -->
                    <div class="mt-6 border-t border-white/10 pt-4 space-y-2">
                        <div class="flex justify-between text-sm text-white/70">
                            <span>Subtotal estadía:</span>
                            <span id="modalSubtotal">$0</span>
                        </div>
                        <div class="flex justify-between text-sm text-white/70">
                            <span>Impuestos (IVA 19%):</span>
                            <span id="modalIva">$0</span>
                        </div>
                        <div class="flex justify-between text-base font-black text-white border-t border-white/10 pt-2">
                            <span>Total Reserva:</span>
                            <span id="modalTotal">$0</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-emerald-400 pt-1">
                            <span>Monto a pagar ahora:</span>
                            <span id="modalMontoPagarAhora">$0</span>
                        </div>
                    </div>
                </aside>

                <!-- Formulario de Cobro -->
                <section class="bg-white/88 px-7 py-8 backdrop-blur-lg overflow-y-auto max-h-[85vh]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-black-300">Proceso de Cobro</p>
                            <h4 class="mt-2 text-3xl font-black text-[#17354f]">Detalles de Pago</h4>
                        </div>
                       <button 
                        id="closeBookingModal" 
                        type="button" 
                        onclick="closeBookingModal()" 
                        class="rounded-full bg-slate-100 p-3 text-slate-500 hover:bg-slate-200 transition-colors cursor-pointer"
>                   
                        <span class="material-symbols-outlined pointer-events-none">close</span>
                        </button>
                    </div>

                    <!-- Selección 100% o 50% -->
                    <div class="mt-6">
                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-black-400">Modalidad de Cobro</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" class="payment-option is-active rounded-xl border border-slate-300 p-3 text-left transition hover:border-[#000000]" data-pago-tipo="100">
                                <p class="text-xs font-black uppercase text-[#000000]">Pago Total (100%)</p>
                                <p class="text-xs text-black-500">Liquida el valor completo ahora</p>
                            </button>
                            <button type="button" class="payment-option rounded-xl border border-slate-300 p-3 text-left transition hover:border-[#000000]" data-pago-tipo="50">
                                <p class="text-xs font-black uppercase text-[#000000]">Abono inicial (50%)</p>
                                <p class="text-xs text-black">Paga el resto en recepción</p>
                            </button>
                        </div>
                    </div>

                    <!-- Selección de Pasarela/Método -->
                    <div class="mt-6">
                        <p class=" mb-3 text-[10px] font-black uppercase tracking-[0.18em] text-black-300">Método de pago</p>
                        <div class="grid gap-3 md:grid-cols-3">

                            <button type="button" class="payment-chip px-3 py-3 text-xs font-black text-[#17354f]" data-payment="Transferencia">Transferencia / PSE · Wompi</button>
                            <button type="button" class="payment-chip px-3 py-3 text-xs font-black text-[#17354f]" data-payment="Recepción">Pago en Recepción</button>
                        </div>
                    </div>

                    <!-- Campos del formulario -->
                    <div id="paymentFormContainer" class="mt-5 rounded-2xl bg-slate-100 p-4 border border-slate-200">
                        <div class="mb-4 flex items-center gap-2 text-xs font-black uppercase tracking-[0.12em] text-[#17354f]">
                            <span class="material-symbols-outlined text-[18px]">verified_user</span>
                            Pago seguro procesado por Wompi
                        </div>
                        <div id="cardFields" class="grid gap-3">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Número de Tarjeta</label>
                                <input id="payCardNumber" type="text" maxlength="19" placeholder="0000 0000 0000 0000" class="w-full rounded-xl border-slate-300 bg-white text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Expiración</label>
                                    <input id="payCardExpiry" type="text" placeholder="MM/AA" maxlength="5" class="w-full rounded-xl border-slate-300 bg-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">CVC / CVV</label>
                                    <input id="payCardCvc" type="password" maxlength="4" placeholder="123" class="w-full rounded-xl border-slate-300 bg-white text-sm">
                                </div>
                            </div>
                        </div>
                        <div id="transferFields" class="hidden text-xs text-slate-600">
                            <p class="font-bold text-slate-800 mb-1">Datos bancarios para consignación:</p>
                            <p>Cuenta de Ahorros: <strong>123-456789-00</strong></p>
                            <p>Titular: <strong>Hotel Aurora S.A.S</strong></p>
                        </div>
                    </div>

                    <?php if ($recaptchaSiteKey !== ''): ?>
                        <div class="mt-5 flex justify-center" aria-label="Verificación de seguridad">
                            <div class="g-recaptcha" data-sitekey="<?php echo e($recaptchaSiteKey); ?>"></div>
                        </div>
                    <?php endif; ?>
                    <p id="modalFeedback" class="mt-4 hidden text-sm font-bold"></p>
                    <button 
                        id="confirmBookingBtn" 
                        type="button" 
                        onclick="processReservationPayment()" 
                        class="hero-button primary-button mt-6 w-full px-5 py-4 text-sm uppercase tracking-[0.18em] cursor-pointer"
                    >
                        Confirmar y Pagar
                    </button>
                </section>
            </div>
        </div>
    </div>
</div>

    <script src="assets/js/interfaz_usu.js?v=2"></script>
 <?php require_once 'includes/banner_cookies.php'; ?>
<!-- Modal y lógica de inactividad -->
<?php
$privacyPolicyCookiesUrl = 'interfaz/legal/politica_cookies.php';
$privacyPolicyUrl = 'interfaz/legal/politica_privacidad.php';
include __DIR__ . '/includes/privacy_policy_modal.php';
?>
<?php if (!empty($_SESSION['user_auth'])): ?>
    <?php require_once __DIR__ . '/includes/timeOut.php'; ?>
    <script src="assets/js/inactividad.js?v=3"></script>
    
<?php endif; ?>