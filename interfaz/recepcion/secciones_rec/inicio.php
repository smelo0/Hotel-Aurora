<?php
// Inicio de recepción: ocupación, llegadas y salidas de hoy. Sin cifras de dinero ni accesos de administración.
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'dashboard.ver')) {
    return;
}

$repositorioInicio = new \App\Admin\AdminDashboardRepository($conexion);
$resumenInicio = $repositorioInicio->obtenerResumen();

$hoyHotel = (new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota')))->format('Y-m-d');
$esc = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');

$consultarMovimientos = static function (mysqli $db, string $campoFecha, string $fecha): array {
    $sql = "SELECT r.cod_res, r.est_res, r.fec_ent_res, r.fec_sal_res,
                   COALESCE(NULLIF(u.nom_usu, ''), 'Huésped') AS huesped,
                   GROUP_CONCAT(DISTINCT h.num_hab ORDER BY h.num_hab SEPARATOR ', ') AS habitaciones
              FROM reservas r
              LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
              LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
              LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             WHERE DATE(r.$campoFecha) = ?
               AND LOWER(TRIM(r.est_res)) NOT IN ('cancelada', 'cancelado', 'finalizada')
             GROUP BY r.cod_res, r.est_res, r.fec_ent_res, r.fec_sal_res, u.nom_usu
             ORDER BY r.$campoFecha ASC, r.cod_res ASC";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('s', $fecha);
    $filas = [];
    if ($stmt->execute()) {
        $resultado = $stmt->get_result();
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
    }
    $stmt->close();
    return $filas;
};

$llegadasHoy = $consultarMovimientos($conexion, 'fec_ent_res', $hoyHotel);
$salidasHoy = $consultarMovimientos($conexion, 'fec_sal_res', $hoyHotel);
$puedeVerReservas = usuario_tiene_permiso($conexion, 'reservas.ver');

$colorEstado = static function (string $estado): string {
    return match (strtolower(trim($estado))) {
        'confirmada' => 'bg-green-100 text-green-700',
        'pendiente' => 'bg-amber-100 text-amber-700',
        'en casa' => 'bg-blue-100 text-blue-700',
        default => 'bg-slate-100 text-slate-700',
    };
};

$tarjetaMovimientos = static function (string $titulo, string $icono, array $filas, string $campoHora, string $vacio) use ($esc, $colorEstado, $puedeVerReservas): void {
    ?>
    <div class="rounded-xl border border-primary/10 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-lg font-black uppercase tracking-tighter text-primary">
                <span class="material-symbols-outlined text-[22px]"><?php echo $icono; ?></span><?php echo $esc($titulo); ?>
            </h3>
            <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-black text-primary"><?php echo count($filas); ?></span>
        </div>
        <?php if ($filas === []): ?>
            <p class="rounded-lg border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-400"><?php echo $esc($vacio); ?></p>
        <?php else: ?>
            <div class="max-h-96 space-y-3 overflow-y-auto pr-1">
                <?php foreach ($filas as $fila): ?>
                    <div class="flex flex-wrap items-center gap-3 rounded-lg bg-slate-50 p-3">
                        <div class="min-w-[120px] flex-1">
                            <p class="text-sm font-black text-slate-800"><?php echo $esc($fila['huesped']); ?></p>
                            <p class="text-xs font-bold text-slate-400">
                                <?php echo $fila['habitaciones'] ? 'Hab. ' . $esc($fila['habitaciones']) : 'Sin habitación asignada'; ?>
                                · Reserva #<?php echo (int) $fila['cod_res']; ?>
                                · <?php echo $esc(date('H:i', strtotime((string) $fila[$campoHora]))); ?>
                            </p>
                        </div>
                        <span class="<?php echo $colorEstado((string) $fila['est_res']); ?> rounded-full px-3 py-1 text-[9px] font-black uppercase tracking-widest"><?php echo $esc($fila['est_res']); ?></span>
                        <?php if ($puedeVerReservas): ?>
                            <a href="index_rec.php?admin_seccion=reservas&amp;admin_reservas_busqueda=<?php echo rawurlencode((string) $fila['huesped']); ?>"
                               class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-widest text-primary hover:border-primary">Gestionar</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
};
?>
<section id="sec-dashboard" class="seccion-contenido">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-primary">Inicio de recepción</h2>
            <p class="mt-1 text-xs text-slate-400">Lo que está pasando hoy en el hotel.</p>
        </div>
        <?php if (usuario_tiene_permiso($conexion, 'reservas.crear')): ?>
            <button type="button" onclick="navegar('reservas', document.querySelector('.nav-item[onclick*=&quot;reservas&quot;]')); abrirModalReserva();"
                    class="rounded-lg bg-primary px-5 py-3 text-sm font-bold text-white shadow hover:brightness-110">
                <span class="material-symbols-outlined mr-1 align-middle text-sm">add_circle</span>
                Nueva reserva
            </button>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-4 mb-8">
        <div class="admin-card bg-white p-6 rounded-xl border-t-4 border-t-primary">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ocupación</p>
            <h3 class="text-4xl font-black text-heading"><?php echo (int) $resumenInicio['ocupacion']; ?>%</h3>
            <p class="mt-1 text-xs font-bold text-slate-400"><?php echo (int) $resumenInicio['habitaciones_ocupadas']; ?> de <?php echo (int) $resumenInicio['total_habitaciones']; ?> habitaciones ocupadas</p>
        </div>
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Llegadas hoy</p>
            <h3 class="text-4xl font-black text-heading"><?php echo count($llegadasHoy); ?></h3>
        </div>
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Salidas hoy</p>
            <h3 class="text-4xl font-black text-heading"><?php echo count($salidasHoy); ?></h3>
        </div>
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tareas pendientes</p>
            <h3 id="contador-tareas" class="text-4xl font-black text-heading text-amber-500">0</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <?php $tarjetaMovimientos('Llegadas de hoy', 'login', $llegadasHoy, 'fec_ent_res', 'No hay llegadas programadas para hoy.'); ?>
        <?php $tarjetaMovimientos('Salidas de hoy', 'logout', $salidasHoy, 'fec_sal_res', 'No hay salidas programadas para hoy.'); ?>
    </div>
</section>
