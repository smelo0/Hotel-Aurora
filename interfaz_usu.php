<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/includes/sesion_seguridad.php';

require_once __DIR__ . '/configuracion/conexion.php';
require_once __DIR__ . '/configuracion/wompi.php';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function jsonResponse(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

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

function fetchRoomCatalog(mysqli $conexion): array
{
    $rooms = [];
    $todayCheckin = (new DateTimeImmutable('today'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
    $todayCheckout = (new DateTimeImmutable('tomorrow'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');

        $sql = "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.precio_hab, h.est_hab, h.obs_hab
            FROM habitacion h
            WHERE h.est_hab NOT IN ('Mantenimiento', 'Sucia')
              AND NOT EXISTS (
                  SELECT 1
                  FROM detalle d
                  INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                  WHERE d.cod_hab_det = h.cod_hab
                    AND r.est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')
                    AND ? < r.fec_sal_res
                    AND ? > r.fec_ent_res
              )
            ORDER BY h.num_hab ASC";
        // limitar el sistema a un máximo de 20 habitaciones
        $sql .= " LIMIT 20";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ss', $todayCheckin, $todayCheckout);
    $stmt->execute();
    $stmt->bind_result($codHab, $numHab, $tipoHab, $preHab, $precioHab, $estHab, $obsHab);

    while ($stmt->fetch()) {
        $precioFinal = (float) ($preHab ?: $precioHab ?: 0);
        $rooms[] = [
            'cod_hab' => (int) $codHab,
            'num_hab' => (int) $numHab,
            'tipo_hab' => (string) $tipoHab,
            'pre_hab' => $precioFinal,
            'est_hab' => (string) $estHab,
            'obs_hab' => $obsHab !== null && trim((string) $obsHab) !== ''
                ? (string) $obsHab
                : 'Habitación premium preparada para una estadía cómoda, luminosa y serena.',
        ];
    }

    $stmt->close();
    return $rooms;
}

$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($httpMethod === 'POST' && ($_POST['accion'] ?? '') === 'agendar_actividad') {
    header('Content-Type: application/json; charset=utf-8');

    $actividad = trim((string) ($_POST['actividad'] ?? ''));
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $hora = trim((string) ($_POST['hora'] ?? ''));
    $nombre = trim((string) ($_POST['nombre'] ?? ($_SESSION['user_auth']['nombre_usuario'] ?? '')));
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $idUsuario = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);

    if ($actividad === '' || $fecha === '' || $hora === '' || $nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Completa correctamente todos los campos de la experiencia.']);
    }

    try {
        $conexion->query(
            "CREATE TABLE IF NOT EXISTS agenda_actividad (
                id_agenda BIGINT AUTO_INCREMENT PRIMARY KEY,
                actividad VARCHAR(120) NOT NULL,
                fecha_agenda DATE NOT NULL,
                hora_agenda TIME NOT NULL,
                nombre_contacto VARCHAR(140) NOT NULL,
                correo_contacto VARCHAR(140) NOT NULL,
                id_usu_agenda BIGINT NULL,
                estado_agenda VARCHAR(30) DEFAULT 'Pendiente',
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $stmt = $conexion->prepare(
            "INSERT INTO agenda_actividad (actividad, fecha_agenda, hora_agenda, nombre_contacto, correo_contacto, id_usu_agenda)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssi', $actividad, $fecha, $hora, $nombre, $correo, $idUsuario);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['status' => 'exito', 'mensaje' => 'Experiencia programada correctamente.']);
    } catch (Throwable $error) {
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'No fue posible guardar la experiencia.']);
    }
}

if ($httpMethod === 'POST' && ($_POST['accion'] ?? '') === 'buscar_disponibilidad') {
    header('Content-Type: application/json; charset=utf-8');

    $checkin = trim((string) ($_POST['checkin'] ?? ''));
    $checkout = trim((string) ($_POST['checkout'] ?? ''));
    $adultos = max(1, (int) ($_POST['adultos'] ?? 2));
    $ninos = max(0, (int) ($_POST['ninos'] ?? 0));

    $checkinDate = DateTimeImmutable::createFromFormat('Y-m-d', $checkin);
    $checkoutDate = DateTimeImmutable::createFromFormat('Y-m-d', $checkout);

    if (!$checkinDate || !$checkoutDate) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona fechas válidas.']);
    }

    if ($checkoutDate <= $checkinDate) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'El check-out debe ser posterior al check-in.']);
    }

    $checkinSql = $checkinDate->setTime(15, 0, 0)->format('Y-m-d H:i:s');
    $checkoutSql = $checkoutDate->setTime(12, 0, 0)->format('Y-m-d H:i:s');
    $availableIds = [];

    $sqlDisponibles = "SELECT h.cod_hab
                       FROM habitacion h
                       WHERE h.est_hab NOT IN ('Mantenimiento', 'Sucia')
                         AND NOT EXISTS (
                             SELECT 1
                             FROM detalle d
                             INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                             WHERE d.cod_hab_det = h.cod_hab
                               AND r.est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')
                               AND ? < r.fec_sal_res
                               AND ? > r.fec_ent_res
                         )
                       ORDER BY h.num_hab ASC";
        // limitar la búsqueda de disponibilidad a las primeras 20 habitaciones
        $sqlDisponibles .= " LIMIT 20";
    $stmtDisponibles = $conexion->prepare($sqlDisponibles);

    if ($stmtDisponibles === false) {
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'No fue posible consultar la disponibilidad.']);
    }

    $stmtDisponibles->bind_param('ss', $checkinSql, $checkoutSql);
    $stmtDisponibles->execute();
    $availableRoomId = 0;
    $stmtDisponibles->bind_result($availableRoomId);

    while ($stmtDisponibles->fetch()) {
        $availableIds[] = (int) $availableRoomId;
    }

    $stmtDisponibles->close();

    jsonResponse(200, [
        'status' => 'exito',
        'mensaje' => 'Disponibilidad actualizada en tiempo real.',
        'total_disponibles' => count($availableIds),
        'habitaciones_ids' => $availableIds,
        'filtros' => [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adultos' => $adultos,
            'ninos' => $ninos,
        ],
    ]);
}

$habitaciones = fetchRoomCatalog($conexion);
$visibleRooms = 6; // mostrar sólo las primeras N habitaciones en el carrusel
$usuarioSesion = $_SESSION['user_auth'] ?? $_SESSION['emp_auth'] ?? [];
$usuarioId = (int) ($usuarioSesion['id_usuario'] ?? 0);
$usuarioNombre = (string) ($usuarioSesion['nombre_usuario'] ?? '');
$usuarioAutenticado = $usuarioId > 0 && ((int) ($usuarioSesion['rol_usuario'] ?? 0) === 6);

$historialReservas = [];
if ($usuarioAutenticado) {
    $sqlHistorial = "SELECT r.cod_res, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res,
                            h.num_hab, h.tipo_hab
                     FROM reservas r
                     LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
                     LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                     WHERE r.id_usu_res = ?
                     ORDER BY r.fec_ent_res DESC";

    $stmtHistorial = $conexion->prepare($sqlHistorial);
    if ($stmtHistorial) {
        $stmtHistorial->bind_param('i', $usuarioId);
        $stmtHistorial->execute();
        $resultHistorial = $stmtHistorial->get_result();

        while ($fila = $resultHistorial->fetch_assoc()) {
            $historialReservas[] = $fila;
        }

        $stmtHistorial->close();
    }
}

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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://checkout.wompi.co/widget.js"></script>
    <link rel="stylesheet" href="assets/css/interfaz_usu.css"> 
    <script>const WOMPI_PUBLIC_KEY = <?php echo json_encode(WOMPI_PUBLIC_KEY); ?>;</script>
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

            <?php include __DIR__ . "/includes/translate.php";?>
            
            <div class="flex items-center gap-3">
                <?php if ($usuarioAutenticado): ?>
                    <span class="hidden rounded-full border border-white/12 bg-white/10 px-4 py-2 text-sm font-semibold text-white md:inline-flex">
                        Hola, <?php echo ($usuarioNombre); ?>
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
        <div class="mx-auto grid w-full max-w-7xl gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-end" >
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
        <section id="vista-resultados" class="mx-auto mt-[-72px] max-w-7xl reveal">
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

                <div class="mt-4 flex items-center justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" data-filter="all" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/6 px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white transition transform duration-200 hover:scale-105 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/25">Todas</button>
                        <button type="button" data-filter="suite" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/70 transition transform duration-200 hover:scale-105 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">Suite</button>
                        <button type="button" data-filter="doble" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/70 transition transform duration-200 hover:scale-105 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">Doble</button>
                        <button type="button" data-filter="sencilla" class="room-filter inline-flex items-center gap-2 rounded-full border border-white/20 bg-transparent px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white/70 transition transform duration-200 hover:scale-105 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">Sencillas</button>
                    </div>
                </div>

             <div class="mt-8 relative">
                <button id="roomsPrev" aria-label="Anterior" class="rooms-nav absolute z-30 flex h-12 w-12 top-1/2 left-4 -translate-y-1/2 rounded-full bg-gradient-to-br from-white/10 to-white/5 text-white backdrop-blur shadow-md transition transform duration-200 hover:scale-110 hover:shadow-xl items-center justify-center">
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>

                <div id="roomsCarousel" class="mt-0 flex gap-6 overflow-x-auto pb-4 snap-x px-4 md:px-0">
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

                <button id="roomsNext" aria-label="Siguiente" class="rooms-nav absolute z-30 flex h-12 w-12 top-1/2 right-4 -translate-y-1/2 rounded-full bg-gradient-to-br from-white/10 to-white/5 text-white backdrop-blur shadow-md transition transform duration-200 hover:scale-110 hover:shadow-xl items-center justify-center">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>
            </div>
                <div id="emptyRoomsState" class="hidden rounded-[28px] border border-dashed border-white/20 bg-white/8 p-10 text-center backdrop-blur-lg">
                    <p class="text-lg font-black text-white">No hay habitaciones disponibles para esas fechas.</p>
                    <p class="muted-light mt-2">Cambia el rango de fechas o vuelve a consultar en unos segundos.</p>
                </div>
            </div>
        </section>

   <?php
// Opciones administradas agrupadas por categoría (esto proviene de tu base de datos)
$experiencias_admin = [
    'planes' => [
        'titulo' => 'Planes Especiales',
        'subtitulo' => 'Rituales y experiencias de relajación programadas por la administración:',
        'opciones' => [
            ['id' => 101, 'nombre' => 'Circuito Spa Hidromasaje', 'descripcion' => '60 minutos de circuito de hidroterapia con aromaterapia.'],
            ['id' => 102, 'nombre' => 'Ritual de Masaje en Pareja', 'descripcion' => 'Masaje relajante corporal completo con aceites esenciales.'],
            ['id' => 103, 'nombre' => 'Pasadía Detox Premium', 'descripcion' => 'Acceso a todas las zonas húmedas, zumos naturales y mascarilla facial.']
        ]
    ],
    'actividades' => [
        'titulo' => 'Actividades',
        'subtitulo' => 'Aventuras y eventos programados por el equipo:',
        'opciones' => [
            ['id' => 201, 'nombre' => 'Caminata Ecológica Guiada', 'descripcion' => 'Recorrido por senderos naturales al amanecer con guía profesional.'],
            ['id' => 202, 'nombre' => 'Taller de Cocina Costera', 'descripcion' => 'Aprende a preparar los mejores platillos marinos con nuestros chefs.'],
            ['id' => 203, 'nombre' => 'Noche de Fogata & Música', 'descripcion' => 'Música en vivo frente al mar con bebida de bienvenida incluida.']
        ]
    ],
    'gastronomia' => [
        'titulo' => 'Gastronomía',
        'subtitulo' => 'Menús especiales y degustaciones exclusivas:',
        'opciones' => [
            ['id' => 301, 'nombre' => 'Cena Maridaje de 5 Tiempos', 'descripcion' => 'Selección de platillos de autor acompañados de vinos seleccionados.'],
            ['id' => 302, 'nombre' => 'Degustación de Coctelería Costera', 'descripcion' => 'Muestra de 4 cócteles de la casa con piqueos del mar.'],
            ['id' => 303, 'nombre' => 'Almuerzo Buffet del Mar', 'descripcion' => 'Acceso libre a la barra de mariscos y pescados del día.']
        ]
    ]
];
?>

<section id="experiencias" class="mx-auto mt-10 max-w-7xl px-4 reveal">
    <div class="grid gap-6 lg:grid-cols-3">
        
        <!-- Tarjeta 1: Planes Especiales -->
        <article class="room-card overflow-hidden bg-gray-900 rounded-none shadow-xl border border-gray-800 flex flex-col justify-between">
            <div>
                <img src="https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&q=85&w=900" alt="Spa y bienestar" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                <div class="p-6">
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em] text-gray-400">Diversión y entretenimiento</p>
                    <h3 class="card-title mt-3 text-2xl font-black text-white">Planes especiales</h3>
                    <p class="muted-light mt-3 text-sm leading-7 text-gray-300">Rituales de relajación, masajes premium y circuitos privados para renovar cuerpo y mente.</p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <button type="button" onclick="abrirModal('planes')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-none transition duration-200 uppercase tracking-wider text-xs">
                    Ver Opciones
                </button>
            </div>
        </article>

        <!-- Tarjeta 2: Actividades -->
        <article class="room-card overflow-hidden bg-gray-900 rounded-none shadow-xl border border-gray-800 flex flex-col justify-between">
            <div>
                <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&q=85&w=900" alt="Actividades" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                <div class="p-6">
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em] text-gray-400">Sale de tu zona de confort</p>
                    <h3 class="card-title mt-3 text-2xl font-black text-white">Actividades</h3>
                    <p class="muted-light mt-3 text-sm leading-7 text-gray-300">Explora las experiencias programadas y elige tus opciones preferidas.</p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <button type="button" onclick="abrirModal('actividades')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-none transition duration-200 uppercase tracking-wider text-xs">
                    Ver Opciones
                </button>
            </div>
        </article>

        <!-- Tarjeta 3: Gastronomía -->
        <article class="room-card overflow-hidden bg-gray-900 rounded-none shadow-xl border border-gray-800 flex flex-col justify-between">
            <div>
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=85&w=900" alt="Alta gastronomía" class="h-64 w-full object-cover" loading="lazy" decoding="async">
                <div class="p-6">
                    <p class="section-kicker text-xs font-black uppercase tracking-[0.18em] text-gray-400">Sabores exclusivos</p>
                    <h3 class="card-title mt-3 text-2xl font-black text-white">Gastronomía</h3>
                    <p class="muted-light mt-3 text-sm leading-7 text-gray-300">Menú costero, cocina de autor y maridajes elegantes para una velada inolvidable.</p>
                </div>
            </div>
            <div class="p-6 pt-0">
                <button type="button" onclick="abrirModal('gastronomia')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-none transition duration-200 uppercase tracking-wider text-xs">
                    Ver Opciones
                </button>
            </div>
        </article>

    </div>
</section>

<!-- Ventana Modal Única y Dinámica -->
<div id="modalOverlay" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-gray-900 border border-gray-800 p-6 shadow-2xl max-w-lg w-full relative text-white">
        
        <!-- Botón para cerrar (X) -->
        <button id="closeModalBtn" type="button" class="absolute top-4 right-4 text-gray-400 hover:text-white font-bold text-2xl transition">
            &times;
        </button>

        <h3 id="modalTitulo" class="text-xl font-black uppercase tracking-wider border-b border-gray-800 pb-3 mb-2 text-white">
            <!-- Título dinámico -->
        </h3>
        
        <p id="modalSubtitulo" class="text-gray-400 text-xs mb-4">
            <!-- Subtítulo dinámico -->
        </p>

        <!-- Modales Dinámicos creados con PHP para cada categoría -->
        <?php foreach ($experiencias_admin as $categoria =>$datos): ?>
            <div id="categoria-<?= $categoria; ?>" class="categoria-contenido hidden space-y-3 max-h-72 overflow-y-auto pr-1">
                <?php foreach ($datos['opciones'] as$opcion): ?>
                    <div class="p-4 bg-gray-800/80 border border-gray-700/60 flex flex-col justify-between gap-3 hover:border-blue-500 transition">
                        <div>
                            <h4 class="font-bold text-base text-blue-400"><?= htmlspecialchars($opcion['nombre']); ?></h4>
                            <p class="text-xs text-gray-300 mt-1 leading-relaxed"><?= htmlspecialchars($opcion['descripcion']); ?></p>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="seleccionarOpcion(<?= $opcion['id']; ?>, '<?= htmlspecialchars($opcion['nombre']); ?>')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition">
                                Reservar / Seleccionar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="flex justify-end mt-5 border-t border-gray-800 pt-3">
            <button id="actionCloseBtn" type="button" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-5 py-2 text-xs font-semibold uppercase transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- JavaScript para Manejar el Modal Dinámico -->
<script>
// Títulos y subtítulos para la cabecera del modal según la categoría
const infoCategorias = {
    'planes': {
        titulo: 'Planes Especiales Disponibles',
        subtitulo: 'Selecciona la experiencia o paquete que deseas reservar:'
    },
    'actividades': {
        titulo: 'Actividades Propuestas',
        subtitulo: 'Selecciona la actividad que deseas realizar durante tu estadía:'
    },
    'gastronomia': {
        titulo: 'Experiencias Gastronómicas',
        subtitulo: 'Reserva tu menú especial o degustación para la velada:'
    }
};

const modalOverlay = document.getElementById('modalOverlay');
const closeModalBtn = document.getElementById('closeModalBtn');
const actionCloseBtn = document.getElementById('actionCloseBtn');

function abrirModal(categoria) {
    // 1. Cambiar textos del modal
    document.getElementById('modalTitulo').textContent = infoCategorias[categoria].titulo;
    document.getElementById('modalSubtitulo').textContent = infoCategorias[categoria].subtitulo;

    // 2. Ocultar todos los contenedores de opciones
    document.querySelectorAll('.categoria-contenido').forEach(el => el.classList.add('hidden'));

    // 3. Mostrar solo las opciones de la categoría seleccionada
    const categoriaActiva = document.getElementById(`categoria-${categoria}`);
    if (categoriaActiva) {
        categoriaActiva.classList.remove('hidden');
    }

    // 4. Mostrar el modal
    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('flex');
}

function cerrarModal() {
    modalOverlay.classList.add('hidden');
    modalOverlay.classList.remove('flex');
}

closeModalBtn.addEventListener('click', cerrarModal);
actionCloseBtn.addEventListener('click', cerrarModal);

modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) cerrarModal();
});

function seleccionarOpcion(id, nombre) {
    alert(`Has seleccionado: ${nombre} (ID: ${id})`);
    // Aquí conectas con tu sistema de reservas o carrito en PHP
}
</script>



                <form id="activityForm" class="rounded-[28px] bg-white/12 p-6 shadow-[0_18px_40px_rgba(23,53,79,0.12)] backdrop-blur-xl">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="actividad">Experiencia</label>
                            <select id="actividad" name="actividad" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900">
                                <option value="Spa privado">Spa privado</option>
                                <option value="Cena de autor">Cena de autor</option>
                                <option value="Paseo náutico">Paseo náutico</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="fechaActividad">Fecha</label>
                            <input id="fechaActividad" name="fecha" type="date" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900">
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="horaActividad">Hora</label>
                            <input id="horaActividad" name="hora" type="time" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900">
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="nombreActividad">Nombre</label>
                            <input id="nombreActividad" name="nombre" type="text" value="<?php echo e($usuarioNombre); ?>" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900" <?php echo $usuarioAutenticado ? 'readonly' : ''; ?>>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.16em] text-white/70" for="correoActividad">Correo</label>
                            <input id="correoActividad" name="correo" type="email" class="w-full rounded-2xl border-white/20 bg-white/90 text-slate-900" placeholder="ejemplo@correo.com">
                        </div>
                    </div>

                    <button type="submit" class="hero-button primary-button mt-5 w-full px-5 py-4 text-sm uppercase tracking-[0.18em]">
                        Programar experiencia
                    </button>
                </form>
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
<?php endif; ?>
    </main>

    <footer class="px-4 pb-10 text-center text-sm text-white/72 md:px-6">
        Hotel Aurora © <?php echo date('Y'); ?> · Exclusividad, calma y servicio frente al mar.
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

    <script>
        (function(){
            const carousel = document.getElementById('roomsCarousel');
            const prev = document.getElementById('roomsPrev');
            const next = document.getElementById('roomsNext');
            const filters = Array.from(document.querySelectorAll('.room-filter'));
            if (!carousel || !prev || !next) return;

            // Nav enable/disable based on overflow
            function updateNavState(){
                const hasOverflow = carousel.scrollWidth > carousel.clientWidth + 1;
                prev.disabled = !hasOverflow;
                next.disabled = !hasOverflow;
                prev.setAttribute('aria-disabled', String(!hasOverflow));
                next.setAttribute('aria-disabled', String(!hasOverflow));
                prev.classList.toggle('opacity-40', !hasOverflow);
                next.classList.toggle('opacity-40', !hasOverflow);
            }
            updateNavState();
            window.addEventListener('resize', updateNavState);

            const step = () => Math.round(carousel.clientWidth * 0.8) || 380;

            prev.addEventListener('click', () => {
                carousel.scrollBy({ left: -step(), behavior: 'smooth' });
            });

            next.addEventListener('click', () => {
                carousel.scrollBy({ left: step(), behavior: 'smooth' });
            });

            // mousewheel horizontal scroll
            carousel.addEventListener('wheel', (e) => {
                if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                    e.preventDefault();
                    carousel.scrollBy({ left: e.deltaY, behavior: 'auto' });
                }
            }, { passive: false });

            // Filtering logic
            function applyFilter(filter){
                const cards = Array.from(carousel.querySelectorAll('[data-room-card]'));
                cards.forEach(card => {
                    const type = (card.getAttribute('data-room-type') || 'otra');
                    const show = filter === 'all' || type === filter;
                    card.style.display = show ? '' : 'none';
                });
                // reset scroll and update nav
                carousel.scrollLeft = 0;
                setTimeout(updateNavState, 120);
            }

            filters.forEach(btn => {
                btn.addEventListener('click', () => {
                    filters.forEach(b => b.classList.remove('bg-white/6', 'text-white'));
                    btn.classList.add('bg-white/6', 'text-white');
                    const filter = btn.getAttribute('data-filter') || 'all';
                    applyFilter(filter);
                });
            });

             
    const openBtn = document.getElementById('openModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const actionCloseBtn = document.getElementById('actionCloseBtn');
    const modal = document.getElementById('modalOverlay');

    const toggleModal = () => {
      modal.classList.toggle('hidden');
      modal.classList.toggle('flex');
    };

    // Abrir al hacer clic en el botón
    openBtn.addEventListener('click', toggleModal);

    // Cerrar al hacer clic en la X o en el botón inferior
    closeBtn.addEventListener('click', toggleModal);
    actionCloseBtn.addEventListener('click', toggleModal);

    // Cerrar si hace clic fuera del contenido de la ventana
    modal.addEventListener('click', (e) => {
      if (e.target === modal) toggleModal();
    });
  

            // initialize: ensure 'Todas' is active
            const active = document.querySelector('.room-filter[data-filter="all"]');
            if (active) active.classList.add('bg-white/6','text-white');
            applyFilter('all');
        })();
    </script>
   <script>
    // 1. Helper selector de IDs
    const $ = (id) => document.getElementById(id);



    // 2. Estado global
    const isUserAuthenticated = <?php echo $usuarioAutenticado ? 'true' : 'false'; ?>;
    const guests = { adults: 2, children: 0 };
    const searchState = { checkin: '', checkout: '' };
    
    const selectedReservation = { 
        roomId: 0, 
        roomName: '', 
        roomPrice: 0, 
        sourceButton: null, 
        payment: 'Tarjeta',
        porcentajePago: 100,
        montoTotal: 0,
        montoAPagar: 0
    };

    let bookingCalendar = null;
    let roomSyncChannel = null;
    let availabilityRefreshTimer = null;

    // 3. Funciones auxiliares
    function formatShortDate(date) {
        return new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short' }).format(date);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }
    

    function setControlState(element, enabled) {
        if (!element) return;
        element.classList.toggle('is-active', enabled);
    }

    function calculateNights() {
        if (!currentSearchIsReady()) return 1;
        const checkin = new Date(`${searchState.checkin}T00:00:00`);
        const checkout = new Date(`${searchState.checkout}T00:00:00`);
        return Math.max(1, Math.round((checkout - checkin) / 86400000));
    }

    function calcularTotalesCobro(noches) {
        const subtotal = selectedReservation.roomPrice * noches;
        const iva = subtotal * 0.19; 
        const total = subtotal + iva;
        const aPagar = (total * selectedReservation.porcentajePago) / 100;

        selectedReservation.montoTotal = total;
        selectedReservation.montoAPagar = aPagar;

        if ($('modalSubtotal')) $('modalSubtotal').innerText = formatCurrency(subtotal);
        if ($('modalIva')) $('modalIva').innerText = formatCurrency(iva);
        if ($('modalTotal')) $('modalTotal').innerText = formatCurrency(total);
        if ($('modalMontoPagarAhora')) $('modalMontoPagarAhora').innerText = formatCurrency(aPagar);
    }

    function currentSearchIsReady() {
        return searchState.checkin !== '' && searchState.checkout !== '';
    }

    function normalizePaymentMethod(method) {
        const value = String(method || '').trim();
        const normalized = value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        if (normalized.includes('recepcion')) return 'Recepción';
        if (normalized.includes('transferencia') || normalized.includes('pse')) return 'Transferencia';
        if (normalized.includes('tarjeta') || normalized.includes('credito') || normalized.includes('debito')) return 'Tarjeta';
        if (normalized.includes('wompi')) return 'Wompi';

        return value || 'Tarjeta';
    }

    function scrollToResults() {
        const target = $('vista-resultados');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // 4. Búsqueda y Filtros
    function updateDateSummary(selectedDates = []) {
        const summary = $('dateSummary');
        if (selectedDates.length === 2) {
            summary.innerText = `${formatShortDate(selectedDates[0])} · ${formatShortDate(selectedDates[1])}`;
            return;
        }
        if (selectedDates.length === 1) {
            summary.innerText = `${formatShortDate(selectedDates[0])} · Check-out`;
            return;
        }
        summary.innerText = 'Selecciona check-in y check-out';
    }

    function initializeCalendar() {
        const input = $('dateRangeInput');
        const trigger = $('dateTrigger');

        if (!input || !trigger || typeof flatpickr === 'undefined') return;

        bookingCalendar = flatpickr(input, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'Y-m-d',
            locale: flatpickr.l10ns.es,
            disableMobile: true,
            showMonths: window.matchMedia('(min-width: 900px)').matches ? 2 : 1,
            appendTo: document.body,
            positionElement: trigger,
            onReady: (_, __, instance) => {
                instance.calendarContainer.classList.add('aurora-calendar');
            },
            onOpen: () => setControlState(trigger.closest('.booking-control'), true),
            onClose: () => setControlState(trigger.closest('.booking-control'), false),
            onChange: (selectedDates, _dateStr, instance) => {
                searchState.checkin = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
                searchState.checkout = selectedDates[1] ? instance.formatDate(selectedDates[1], 'Y-m-d') : '';
                updateDateSummary(selectedDates);

                if (selectedDates.length === 2) {
                    setTimeout(() => instance.close(), 140);
                }
            }
        });

        trigger.addEventListener('click', () => bookingCalendar.open());
    }

    function updateGuestSummary() {
        if ($('adultsValue')) $('adultsValue').innerText = String(guests.adults);
        if ($('childrenValue')) $('childrenValue').innerText = String(guests.children);
        if ($('guestSummary')) $('guestSummary').innerText = `${guests.adults} adultos, ${guests.children} niños`;
        if ($('adultsMinus')) $('adultsMinus').disabled = guests.adults <= 1;
        if ($('childrenMinus')) $('childrenMinus').disabled = guests.children <= 0;
    }

    function toggleGuestPopover(forceState = null) {
        const popover = $('guestPopover');
        const trigger = $('guestTrigger');
        if (!popover || !trigger) return;
        const control = trigger.closest('.booking-control');
        const shouldOpen = forceState === null ? !popover.classList.contains('is-open') : forceState;

        popover.classList.toggle('is-open', shouldOpen);
        setControlState(control, shouldOpen);
    }

    function changeGuest(type, delta) {
        const min = type === 'adults' ? 1 : 0;
        guests[type] = Math.max(min, guests[type] + delta);
        updateGuestSummary();
    }

    async function readJsonSafely(response) {
        const text = (await response.text()).replace(/^\uFEFF/, '').trim();
        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error('Respuesta inválida del servidor.');
        }
    }

    function updateVisibleRooms(visibleIds) {
        const cards = document.querySelectorAll('[data-room-card]');
        let visibleCount = 0;

        cards.forEach(card => {
            const isVisible = visibleIds.has(card.getAttribute('data-room-card'));
            card.classList.toggle('hidden', !isVisible);
            if (isVisible) visibleCount += 1;
        });

        if ($('emptyRoomsState')) $('emptyRoomsState').classList.toggle('hidden', visibleCount > 0);
    }

    async function searchAvailability(showFeedback = true) {
        const feedback = $('searchFeedback');
        const button = $('btnBuscarDisponibilidad');

        if (!currentSearchIsReady()) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = 'Selecciona tus fechas para consultar disponibilidad.';
                feedback.classList.remove('hidden');
            }
            scrollToResults();
            return;
        }

        const formData = new FormData();
        formData.append('accion', 'buscar_disponibilidad');
        formData.append('checkin', searchState.checkin);
        formData.append('checkout', searchState.checkout);
        formData.append('adultos', String(guests.adults));
        formData.append('ninos', String(guests.children));

        if (button) {
            button.disabled = true;
            button.innerText = 'Buscando disponibilidad...';
        }

        try {
            const response = await fetch('interfaz_usu.php', {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const result = await readJsonSafely(response);

            if (!response.ok || result.status !== 'exito') {
                throw new Error(result.mensaje || 'No se pudo consultar disponibilidad.');
            }

            const ids = new Set((result.habitaciones_ids || []).map(String));
            updateVisibleRooms(ids);

            if (showFeedback && feedback) {
                feedback.className = 'text-sm font-bold text-emerald-300';
                feedback.innerText = result.mensaje;
                feedback.classList.remove('hidden');
            }

            scrollToResults();
        } catch (error) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = error.message;
                feedback.classList.remove('hidden');
            }
            scrollToResults();
        } finally {
            if (button) {
                button.disabled = false;
                button.innerText = 'Buscar Disponibilidad';
            }
            toggleGuestPopover(false);
        }
    }

    // 5. Gestión del Modal de Reserva
    function openBookingModal(roomId, roomName, roomPrice, sourceButton) {
        if (!isUserAuthenticated) {
            Swal.fire({
                icon: 'info',
                title: 'Inicia sesión para continuar',
                text: 'Debes iniciar sesión o registrarte para realizar una reserva.',
                showCancelButton: true,
                confirmButtonText: 'Iniciar sesión',
                cancelButtonText: 'Crear cuenta',
                confirmButtonColor: '#074d2a',
                cancelButtonColor: '#b89f12',
                background: '#fffdf8'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'interfaz/loggins/index_usu.php';
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = 'interfaz/loggins/index_usu.php?vista=registro';
                }
            });
            return;
        }

        const feedback = $('searchFeedback');

        if (!currentSearchIsReady()) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = 'Selecciona tus fechas antes de elegir una habitación.';
                feedback.classList.remove('hidden');
            }
            if ($('bookingBar')) $('bookingBar').scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (bookingCalendar) bookingCalendar.open();
            return;
        }

        selectedReservation.roomId = Number(roomId);
        selectedReservation.roomName = roomName;
        selectedReservation.roomPrice = Number(roomPrice || 0);
        selectedReservation.sourceButton = sourceButton;

        const nights = calculateNights();
        if ($('modalRoomName')) $('modalRoomName').innerText = roomName;
        if ($('modalCheckin')) $('modalCheckin').innerText = searchState.checkin;
        if ($('modalCheckout')) $('modalCheckout').innerText = searchState.checkout;
        if ($('modalGuests')) $('modalGuests').innerText = `${guests.adults} adultos, ${guests.children} niños`;
        if ($('modalNights')) $('modalNights').innerText = String(nights);
        if ($('modalNightPrice')) $('modalNightPrice').innerText = formatCurrency(selectedReservation.roomPrice);

        calcularTotalesCobro(nights);

        if ($('modalFeedback')) $('modalFeedback').className = 'mt-4 hidden text-sm font-bold';
        
        const modal = $('bookingModal');
        if (modal) {
            modal.classList.add('is-open');
            modal.style.display = 'flex';
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeBookingModal() {
        const modal = $('bookingModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.style.display = 'none';
            document.body.classList.remove('overflow-hidden');
        }
    }
async function processReservationPayment() {
    const feedback = document.getElementById('modalFeedback');
    const btn = document.getElementById('confirmBookingBtn');
    const metodoPago = normalizePaymentMethod(selectedReservation.payment);
    selectedReservation.payment = metodoPago;

    // Validar datos esenciales
    if (!selectedReservation.roomId || !searchState.checkin || !searchState.checkout) {
        if (feedback) {
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = 'Faltan datos obligatorios para la reserva (habitación o fechas).';
            feedback.classList.remove('hidden');
        }
        return;
    }

    if (metodoPago !== 'Recepción') {
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'ABRIENDO WOMPI...';
        }
    }

    try {
        let transaction = null;
        if (metodoPago !== 'Recepción') {
            if (typeof WidgetCheckout === 'undefined') {
                throw new Error('No se pudo cargar el widget de Wompi.');
            }

            const reference = `AURORA-${Date.now()}-${selectedReservation.roomId}`;
            const widgetCheckout = new WidgetCheckout({
                currency: 'COP',
                amountInCents: Math.round(selectedReservation.montoAPagar * 100),
                reference,
                publicKey: WOMPI_PUBLIC_KEY
            });

            const paymentResult = await new Promise((resolve, reject) => {
                const timeoutId = window.setTimeout(() => {
                    reject(new Error('Wompi no pudo abrirse. Verifica la conexión, bloqueadores del navegador o la llave pública.'));
                }, 15000);

                widgetCheckout.open(result => {
                    window.clearTimeout(timeoutId);
                    if (result?.transaction?.id) {
                        resolve(result);
                    } else {
                        reject(new Error('El pago fue cancelado o no recibió confirmación.'));
                    }
                });
            });

            if (btn) btn.innerText = 'VALIDANDO PAGO...';
            transaction = paymentResult.transaction;
        }

        const fd = new FormData();
        fd.append('id_habitacion', selectedReservation.roomId);
        fd.append('fecha_in', searchState.checkin);
        fd.append('fecha_out', searchState.checkout);
        fd.append('cant_adultos', guests.adults);
        fd.append('cant_ninos', guests.children);
        fd.append('metodo_pago', transaction ? 'Wompi' : metodoPago);
        fd.append('porcentaje_pago', selectedReservation.porcentajePago);
        fd.append('total_reserva', selectedReservation.montoAPagar);
        if (transaction) {
            fd.append('referencia_pago', transaction.reference || '');
            fd.append('wompi_transaction_id', transaction.id);
        }
        fd.append('tipo_huesped', '');
        fd.append('notas_reserva', `Porcentaje de cobro: ${selectedReservation.porcentajePago}%`);

        const res = await fetch('controladores/guardar_reserva.php', {
            method: 'POST',
            body: fd,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        const rawText = await res.text();
        console.log("Respuesta del servidor PHP:", rawText);

        let result;
        try {
            result = JSON.parse(rawText.trim());
        } catch(e) {
            throw new Error('El backend no devolvió una respuesta JSON válida.');
        }

        // Si PHP devuelve status !== 'exito' (por ejemplo 400, 409 o 422)
        if (!res.ok || result.status !== 'exito') {
            throw new Error(result.mensaje || 'No se pudo guardar la reserva en la base de datos.');
        }

        // Éxito real en base de datos
        closeBookingModal();
        
        await Swal.fire({ 
            icon: 'success', 
            title: '¡Reserva Registrada!', 
            text: result.mensaje || 'Se ha guardado la reserva correctamente en el sistema.', 
            confirmButtonColor: '#17354f' 
        });

        window.location.reload();

    } catch (err) {
        console.error("Error en la reserva:", err);
        if (feedback) {
            const errorMessage = err?.message || (typeof err === 'string' ? err : '') || 'No se pudo iniciar o validar el pago con Wompi.';
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = errorMessage;
            feedback.classList.remove('hidden');
        }
    } finally {
        if (btn) {
            btn.disabled = false; 
            btn.innerText = 'CONFIRMAR Y PAGAR';
        }
    }
}

    async function submitActivityForm(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        formData.append('accion', 'agendar_actividad');

        try {
            const response = await fetch('interfaz_usu.php', {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const result = await readJsonSafely(response);

            if (!response.ok || result.status !== 'exito') {
                throw new Error(result.mensaje || 'No se pudo programar la experiencia.');
            }

            form.reset();
            await Swal.fire({
                icon: 'success',
                title: 'Solicitud enviada',
                text: 'Nuestro equipo preparará tu experiencia.',
                confirmButtonColor: '#1c582b91',
                background: '#fffdf8'
            });
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#0d3a1a'
            });
        }
    }

    function attachLogoutFlow() {
        const logoutButton = $('logoutButton');
        if (!logoutButton) return;

        logoutButton.addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'Cerrar sesión',
                text: '¿Deseas salir de tu cuenta?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#519b5b',
                cancelButtonColor: '#b37922'
            });

            if (result.isConfirmed) {
                window.location.href = 'controladores/logout.php?panel=user';
            }
        });
    }

    function toggleSystemHelp(forceState = null) {
        const panel = $('systemHelpPanel');
        const button = $('systemHelpButton');
        if (!panel || !button) return;

        const shouldOpen = forceState === null ? !panel.classList.contains('is-open') : forceState;
        panel.classList.toggle('is-open', shouldOpen);
        panel.setAttribute('aria-hidden', String(!shouldOpen));
        button.setAttribute('aria-expanded', String(shouldOpen));

        if (shouldOpen) {
            $('closeSystemHelp')?.focus();
        }
    }

    function toggleSystemHelp(forceState = null) {
        const panel = $('systemHelpPanel');
        const button = $('systemHelpButton');
        if (!panel || !button) return;

        const shouldOpen = forceState === null ? !panel.classList.contains('is-open') : forceState;
        panel.classList.toggle('is-open', shouldOpen);
        panel.setAttribute('aria-hidden', String(!shouldOpen));
        button.setAttribute('aria-expanded', String(shouldOpen));

        if (shouldOpen) {
            $('closeSystemHelp')?.focus();
        }
    }

    // 6. Event Delegator Global
    document.addEventListener('click', event => {
        // Reservar Habitación
        const roomButton = event.target.closest('[data-room-select]');
        if (roomButton) {
            openBookingModal(roomButton.dataset.roomId, roomButton.dataset.roomName, roomButton.dataset.roomPrice, roomButton);
            return;
        }

        // Modalidad de pago (100% / 50%)
        const pagoOption = event.target.closest('[data-pago-tipo]');
        if (pagoOption) {
            selectedReservation.porcentajePago = Number(pagoOption.dataset.pagoTipo);
            document.querySelectorAll('[data-pago-tipo]').forEach(node => node.classList.remove('is-active'));
            pagoOption.classList.add('is-active');
            calcularTotalesCobro(calculateNights());
            return;
        }

        // Método de pago (Tarjeta, Transferencia, Recepción)
        const paymentButton = event.target.closest('[data-payment]');
        if (paymentButton) {
            selectedReservation.payment = normalizePaymentMethod(paymentButton.dataset.payment);
            document.querySelectorAll('[data-payment]').forEach(node => node.classList.remove('is-active'));
            paymentButton.classList.add('is-active');

            const cardFields = $('cardFields');
            const transferFields = $('transferFields');

            if (selectedReservation.payment === 'Tarjeta') {
                if (cardFields) cardFields.classList.remove('hidden');
                if (transferFields) transferFields.classList.add('hidden');
            } else if (selectedReservation.payment === 'Transferencia') {
                if (cardFields) cardFields.classList.add('hidden');
                if (transferFields) transferFields.classList.remove('hidden');
            } else {
                if (cardFields) cardFields.classList.add('hidden');
                if (transferFields) transferFields.classList.add('hidden');
            }
            return;
        }

        if (!event.target.closest('#guestPopover') && !event.target.closest('#guestTrigger')) {
            toggleGuestPopover(false);
        }

        if (event.target.id === 'bookingModal') {
            closeBookingModal();
        }

        if (!event.target.closest('.system-help')) {
            toggleSystemHelp(false);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') toggleSystemHelp(false);
    });

    // 7. Inicialización al cargar el DOM
    document.addEventListener('DOMContentLoaded', () => {
        initializeCalendar();
        updateGuestSummary();
        attachLogoutFlow();

        if ($('systemHelpButton')) $('systemHelpButton').addEventListener('click', () => toggleSystemHelp());
        if ($('closeSystemHelp')) $('closeSystemHelp').addEventListener('click', () => toggleSystemHelp(false));

        if ($('guestTrigger')) $('guestTrigger').addEventListener('click', () => toggleGuestPopover());
        if ($('closeGuestPopover')) $('closeGuestPopover').addEventListener('click', () => toggleGuestPopover(false));
        if ($('adultsMinus')) $('adultsMinus').addEventListener('click', () => changeGuest('adults', -1));
        if ($('adultsPlus')) $('adultsPlus').addEventListener('click', () => changeGuest('adults', 1));
        if ($('childrenMinus')) $('childrenMinus').addEventListener('click', () => changeGuest('children', -1));
        if ($('childrenPlus')) $('childrenPlus').addEventListener('click', () => changeGuest('children', 1));
        if ($('btnBuscarDisponibilidad')) $('btnBuscarDisponibilidad').addEventListener('click', () => searchAvailability(true));
        if ($('activityForm')) $('activityForm').addEventListener('submit', submitActivityForm);
    });
</script>
 <?php require_once 'includes/banner_cookies.php'; ?>
<!-- Modal y lógica de inactividad -->
<?php if (!empty($_SESSION['user_auth'])): ?>
    <?php require_once __DIR__ . '/includes/timeOut.php'; ?>
    <script src="assets/js/inactividad.js"></script>
    <?php include 'experiencias.php'; ?>
    
<?php endif; ?>