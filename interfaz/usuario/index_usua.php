<?php
require_once __DIR__ . '/../../includes/sesion_seguridad.php';

require_once '../../configuracion/conexion.php';

$usuario = $_SESSION['user_auth'] ?? [];
if (!isset($usuario['id_usuario']) || (int) ($usuario['rol_usuario'] ?? 0) !== 6) {
    header('Location: ../loggins/index_usu.php');
    exit();
}

function formatear_fecha_historial($fecha) {
    if (empty($fecha)) {
        return 'Sin fecha';
    }

    $fechaObj = DateTime::createFromFormat('Y-m-d H:i:s', $fecha)
        ?: DateTime::createFromFormat('Y-m-d', $fecha);

    if (!$fechaObj) {
        return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');
    }

    return $fechaObj->format('d/m/Y');
}

function badge_estado_reserva($estado) {
    $estado = trim((string) $estado);
    $mapa = [
        'Confirmada' => 'bg-emerald-100 text-emerald-700',
        'Pendiente' => 'bg-amber-100 text-amber-700',
        'Cancelada' => 'bg-rose-100 text-rose-700',
        'Cancelado' => 'bg-rose-100 text-rose-700',
        'Finalizada' => 'bg-slate-200 text-slate-700',
        'En Casa' => 'bg-cyan-100 text-cyan-700',
    ];

    return $mapa[$estado] ?? 'bg-slate-100 text-slate-700';
}

$idUsuarioActual = (int) $usuario['id_usuario'];
$reservasUsuario = [];

$sqlReservas = "SELECT r.cod_res, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res, d.cod_hab_det,
                      h.num_hab, h.tipo_hab
                FROM reservas r
                LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
                LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                WHERE r.id_usu_res = ?
                ORDER BY r.fec_ent_res DESC";
/**@var mysqli $conexion */
$stmtReservas = $conexion->prepare($sqlReservas);
if ($stmtReservas) {
    $stmtReservas->bind_param('i', $idUsuarioActual);
    $stmtReservas->execute();
    $resultadoReservas = $stmtReservas->get_result();

    while ($fila = $resultadoReservas->fetch_assoc()) {
        $reservasUsuario[] = $fila;
    }

    $stmtReservas->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aurora | Mi Estancia</title>
    <script src=""></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800">
    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b border-slate-200">
        <div class="text-xl font-bold text-teal-700 flex items-center gap-2">
            <span class="material-symbols-outlined">hotel</span>
            Hotel Aurora
        </div>
        <div class="flex items-center gap-4">
            <span class="font-medium text-slate-600">Hola, <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></span>
            <a href="../../controladores/logout.php" class="text-red-500 hover:bg-red-50 px-4 py-2 rounded-xl transition flex items-center gap-2 font-bold text-sm">
                <span class="material-symbols-outlined text-sm">logout</span> Salir
            </a>
        </div>
    </nav>

    <main class="w-full pb-20 md:px-6">
        <div class="bg-white rounded-3xl shadow-sm p-10 border border-slate-100 text-center">
            <div class="w-20 h-20 bg-teal-50 text-teal-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-4xl">waving_hand</span>
            </div>
            <h1 class="text-3xl font-black text-slate-800 mb-4">Bienvenido a tu panel, <?php echo htmlspecialchars($usuario['nombre_usuario']); ?>!</h1>
            <p class="text-slate-500 max-w-xl mx-auto">
                Aquí puedes revisar tu historial de reservas, ver el estado actual de cada estancia y seguir el progreso de tus próximas visitas.
            </p>
        </div>

        <section id="mis-reservas" class="mt-8 max-w-6xl mx-auto">
            <div class="flex items-center justify-between gap-4 mb-5">
                <div>
                    <a href="#mis-reservas" class="text-xs font-black uppercase tracking-[0.2em] text-teal-700 hover:underline">Historial</a>
                    <h2 class="text-2xl font-black text-slate-800">Tus reservas</h2>
                </div>
                <a href="../../interfaz_usu.php" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700 transition">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Nueva reserva
                </a>
            </div>

            <?php if (empty($reservasUsuario)): ?>
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                    <span class="material-symbols-outlined text-4xl text-slate-300">travel_explore</span>
                    <h3 class="mt-4 text-xl font-black text-slate-700">Aún no tienes reservas registradas</h3>
                    <p class="mt-2 text-slate-500">Cuando realices tu primera estancia, aparecerá aquí su historial completo.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($reservasUsuario as $reserva): ?>
                        <?php
                        $estado = $reserva['est_res'] ?? 'Pendiente';
                        $habitacion = !empty($reserva['tipo_hab'])
                            ? ($reserva['num_hab'] ? 'Habitación ' . $reserva['num_hab'] . ' · ' . $reserva['tipo_hab'] : $reserva['tipo_hab'])
                            : 'Habitación sin asignar';
                        $notas = trim((string) ($reserva['not_res'] ?? ''));
                        $fechas = formatear_fecha_historial($reserva['fec_ent_res']) . ' - ' . formatear_fecha_historial($reserva['fec_sal_res']);
                        ?>
                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <span class="text-sm font-black uppercase tracking-[0.15em] text-slate-500">Reserva #<?php echo (int) $reserva['cod_res']; ?></span>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold <?php echo badge_estado_reserva($estado); ?>"><?php echo htmlspecialchars($estado); ?></span>
                                    </div>
                                    <h3 class="mt-3 text-xl font-black text-slate-800"><?php echo htmlspecialchars($habitacion); ?></h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black uppercase tracking-[0.15em] text-slate-500">Fechas</p>
                                    <p class="mt-1 text-sm font-bold text-slate-700"><?php echo htmlspecialchars($fechas); ?></p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Check-in</p>
                                    <p class="mt-2 text-base font-bold text-slate-800"><?php echo formatear_fecha_historial($reserva['fec_ent_res']); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Check-out</p>
                                    <p class="mt-2 text-base font-bold text-slate-800"><?php echo formatear_fecha_historial($reserva['fec_sal_res']); ?></p>
                                </div>
                            </div>

                            <?php if ($notas !== ''): ?>
                                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Notas</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($notas)); ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php
    $ayudaSistemaRol = 'usuario';
    require_once '../../includes/system_help.php';
    ?>
</body>
</html>
