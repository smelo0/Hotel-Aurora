<?php
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../configuracion/permiso.php';
require_once __DIR__ . '/../../../includes/experiencias.php';

/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'finanzas.ver')) {
    return;
}

$pagosFinanzas = [];
$totalesFinanzas = [];
$resultadoPagos = $conexion->query(
    "SELECT p.id_pago, p.cod_res_pago, p.monto, p.metodo_pago, p.estado_pago, p.fecha_pago,
            r.id_usu_res, r.fec_ent_res, r.fec_sal_res,
            u.nom_usu, u.corr_usu, COALESCE(habitaciones.descripcion, 'Sin habitación registrada') AS habitaciones
     FROM pagos p
     INNER JOIN reservas r ON r.cod_res = p.cod_res_pago
     LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
     LEFT JOIN (
         SELECT d.cod_res_det,
                GROUP_CONCAT(DISTINCT CONCAT('Habitación ', h.num_hab, ' · ', h.tipo_hab) SEPARATOR ', ') AS descripcion
         FROM detalle d
         INNER JOIN habitacion h ON h.cod_hab = d.cod_hab_det
         GROUP BY d.cod_res_det
     ) habitaciones ON habitaciones.cod_res_det = r.cod_res
     ORDER BY p.fecha_pago DESC, p.id_pago DESC"
);
while ($pago = $resultadoPagos->fetch_assoc()) {
    $pagosFinanzas[] = $pago;
    $idUsuario = (int) ($pago['id_usu_res'] ?? 0);
    $identificadorCliente = $idUsuario > 0
        ? 'usuario:' . $idUsuario
        : 'sin-cuenta:' . mb_strtolower(trim((string) ($pago['corr_usu'] ?? $pago['nom_usu'] ?? 'desconocido')), 'UTF-8');
    if (!isset($totalesFinanzas[$identificadorCliente])) {
        $totalesFinanzas[$identificadorCliente] = [
            'nombre' => (string) ($pago['nom_usu'] ?? 'Huésped sin cuenta asociada'),
            'correo' => (string) ($pago['corr_usu'] ?? ''),
            'total_pagado' => 0.0,
            'pagos_aprobados' => 0,
        ];
    }
    if ($pago['estado_pago'] === 'Aprobado') {
        $totalesFinanzas[$identificadorCliente]['total_pagado'] += (float) $pago['monto'];
        $totalesFinanzas[$identificadorCliente]['pagos_aprobados']++;
    }
}
asegurar_esquema_agenda_experiencias($conexion);
$historialExperienciasFinanzas = obtener_historial_experiencias($conexion);
$puedeActualizarPagosExperiencia = usuario_tiene_permiso($conexion, 'experiencias.gestionar');
$movimientosExperienciasFinanzas = [];
foreach ($historialExperienciasFinanzas as $solicitudExperiencia) {
    $idUsuario = (int) ($solicitudExperiencia['id_usu_agenda'] ?? 0);
    $identificadorCliente = $idUsuario > 0
        ? 'usuario:' . $idUsuario
        : 'sin-cuenta:' . mb_strtolower(trim((string) ($solicitudExperiencia['correo_cliente'] ?? $solicitudExperiencia['nombre_cliente'] ?? 'desconocido')), 'UTF-8');
    if (!isset($totalesFinanzas[$identificadorCliente])) {
        $totalesFinanzas[$identificadorCliente] = [
            'nombre' => (string) ($solicitudExperiencia['nombre_cliente'] ?? 'Huésped sin cuenta asociada'),
            'correo' => (string) ($solicitudExperiencia['correo_cliente'] ?? ''),
            'total_pagado' => 0.0,
            'pagos_aprobados' => 0,
        ];
    }
    $solicitudExperiencia['estado_pago_experiencia'] = (string) ($solicitudExperiencia['estado_pago_experiencia'] ?? 'Pendiente');
    if ($solicitudExperiencia['estado_pago_experiencia'] === 'Pagada' && $solicitudExperiencia['monto_experiencia'] !== null) {
        $totalesFinanzas[$identificadorCliente]['total_pagado'] += (float) $solicitudExperiencia['monto_experiencia'];
        $totalesFinanzas[$identificadorCliente]['pagos_aprobados']++;
    }
    $movimientosExperienciasFinanzas[] = $solicitudExperiencia;
}
uasort($totalesFinanzas, static fn(array $a, array $b): int => $b['total_pagado'] <=> $a['total_pagado']);

$escaparFinanzas = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$formatearMontoFinanzas = static fn($valor): string => '$' . number_format((float) $valor, 0, ',', '.');
$clasesEstadoPago = [
    'Aprobado' => 'bg-emerald-100 text-emerald-700',
    'Pendiente' => 'bg-amber-100 text-amber-700',
    'Fallido' => 'bg-rose-100 text-rose-700',
    'Pagada' => 'bg-emerald-100 text-emerald-700',
    'Cancelada' => 'bg-rose-100 text-rose-700',
];
?>
<section id="sec-finanzas" class="seccion-contenido hidden">
    <div class="space-y-8">
        <div class="rounded-xl border border-primary/10 bg-white p-8 shadow-sm">
            <div class="mb-8">
                <h3 class="text-2xl font-black tracking-tight text-primary">Historial financiero</h3>
                <p class="mt-1 text-sm text-slate-500">El total por cliente suma pagos aprobados de reservas y experiencias registradas como pagadas. Las experiencias se administran manualmente.</p>
            </div>

            <h4 class="mb-4 text-lg font-black text-slate-800">Total pagado por cliente</h4>
            <?php if ($totalesFinanzas === []): ?>
                <p class="rounded-lg bg-slate-50 p-5 text-sm text-slate-500">Todavía no hay pagos registrados.</p>
            <?php else: ?>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($totalesFinanzas as $totalCliente): ?>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <p class="font-black text-slate-800"><?php echo $escaparFinanzas($totalCliente['nombre']); ?></p>
                            <?php if ($totalCliente['correo'] !== ''): ?>
                                <p class="mt-1 text-xs text-slate-500"><?php echo $escaparFinanzas($totalCliente['correo']); ?></p>
                            <?php endif; ?>
                            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Total pagado</p>
                            <p class="mt-1 text-2xl font-black text-primary"><?php echo $formatearMontoFinanzas($totalCliente['total_pagado']); ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?php echo (int) $totalCliente['pagos_aprobados']; ?> pagos pagados o aprobados</p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 class="mb-4 mt-10 text-lg font-black text-slate-800">Movimientos de pago</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Fecha del movimiento</th>
                            <th class="px-5 py-4">Cliente</th>
                            <th class="px-5 py-4">Reserva / experiencia</th>
                            <th class="px-5 py-4">Método</th>
                            <th class="px-5 py-4">Estado</th>
                            <th class="px-5 py-4 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if ($pagosFinanzas === [] && $movimientosExperienciasFinanzas === []): ?>
                            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No hay movimientos de pago.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pagosFinanzas as $pago): ?>
                                <?php $estadoPago = (string) ($pago['estado_pago'] ?? 'Pendiente'); ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4 text-slate-600"><?php echo $escaparFinanzas(date('d/m/Y H:i', strtotime((string) $pago['fecha_pago']))); ?></td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-800"><?php echo $escaparFinanzas($pago['nom_usu'] ?? 'Huésped sin cuenta asociada'); ?></p>
                                        <p class="mt-1 text-xs text-slate-500"><?php echo $escaparFinanzas($pago['corr_usu'] ?? ''); ?></p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-700">Reserva #<?php echo (int) $pago['cod_res_pago']; ?></p>
                                        <p class="mt-1 text-xs text-slate-500"><?php echo $escaparFinanzas($pago['habitaciones']); ?></p>
                                        <p class="mt-1 text-xs text-slate-500"><?php echo $escaparFinanzas(date('d/m/Y', strtotime((string) $pago['fec_ent_res']))); ?> – <?php echo $escaparFinanzas(date('d/m/Y', strtotime((string) $pago['fec_sal_res']))); ?></p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600"><?php echo $escaparFinanzas($pago['metodo_pago']); ?></td>
                                    <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-[10px] font-black uppercase <?php echo $clasesEstadoPago[$estadoPago] ?? 'bg-slate-100 text-slate-600'; ?>"><?php echo $escaparFinanzas($estadoPago); ?></span></td>
                                    <td class="px-5 py-4 text-right font-black <?php echo $estadoPago === 'Aprobado' ? 'text-primary' : 'text-slate-400'; ?>"><?php echo $formatearMontoFinanzas($pago['monto']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($movimientosExperienciasFinanzas as $solicitud): ?>
                                <?php
                                $estadoPagoExperiencia = $solicitud['estado_pago_experiencia'];
                                $estadoAgendaExperiencia = (string) ($solicitud['estado_agenda'] ?? 'Pendiente');
                                $montoExperiencia = $solicitud['monto_experiencia'];
                                $fechaMovimientoExperiencia = $estadoPagoExperiencia === 'Pagada' && !empty($solicitud['fecha_pago_experiencia'])
                                    ? $solicitud['fecha_pago_experiencia']
                                    : $solicitud['creado_en'];
                                ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4 text-slate-600"><?php echo $escaparFinanzas(date('d/m/Y H:i', strtotime((string) $fechaMovimientoExperiencia))); ?></td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-800"><?php echo $escaparFinanzas($solicitud['nombre_cliente']); ?></p>
                                        <p class="mt-1 text-xs text-slate-500"><?php echo $escaparFinanzas($solicitud['correo_cliente']); ?></p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-slate-700"><?php echo $escaparFinanzas($solicitud['actividad']); ?></p>
                                        <?php foreach ($solicitud['selecciones_personas'] as $indicePersona => $opcionPersona): ?>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Persona <?php echo $indicePersona + 1; ?>: <?php echo $escaparFinanzas($opcionPersona); ?>
                                                <?php if (isset($solicitud['precios_personas'][$indicePersona]) && $solicitud['precios_personas'][$indicePersona] !== null): ?>
                                                    · <?php echo $formatearMontoFinanzas($solicitud['precios_personas'][$indicePersona]); ?>
                                                <?php else: ?>
                                                    · Sin precio
                                                <?php endif; ?>
                                            </p>
                                        <?php endforeach; ?>
                                        <p class="mt-1 text-xs text-slate-500">Programada: <?php echo $escaparFinanzas(date('d/m/Y', strtotime((string) $solicitud['fecha_agenda']))); ?> · <?php echo $escaparFinanzas(substr((string) $solicitud['hora_agenda'], 0, 5)); ?></p>
                                        <p class="mt-1 text-xs font-bold text-slate-500"><?php echo !empty($solicitud['cod_res_agenda']) ? 'Reserva vinculada: #' . (int) $solicitud['cod_res_agenda'] : 'Sin reserva vinculada'; ?></p>
                                        <p class="mt-1 text-xs text-slate-500">Experiencia: <?php echo $escaparFinanzas($estadoAgendaExperiencia); ?></p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <?php if ($estadoPagoExperiencia === 'Pagada'): ?>
                                            <?php echo $escaparFinanzas($solicitud['metodo_pago_experiencia'] ?? 'Pago registrado'); ?>
                                        <?php elseif ($puedeActualizarPagosExperiencia && $estadoPagoExperiencia === 'Pendiente' && $montoExperiencia !== null && (float) $montoExperiencia > 0 && $estadoAgendaExperiencia !== 'Cancelada'): ?>
                                            <label class="sr-only" for="metodo-pago-experiencia-<?php echo (int) $solicitud['id_agenda']; ?>">Método de pago para <?php echo $escaparFinanzas($solicitud['actividad']); ?></label>
                                            <select id="metodo-pago-experiencia-<?php echo (int) $solicitud['id_agenda']; ?>" class="mb-2 rounded-lg border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700" data-metodo-pago-experiencia>
                                                <?php foreach (['Efectivo', 'Tarjeta', 'Transferencia'] as $metodoPagoExperiencia): ?>
                                                    <option value="<?php echo $metodoPagoExperiencia; ?>"><?php echo $metodoPagoExperiencia; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white hover:brightness-110 disabled:cursor-wait disabled:opacity-60" data-cobrar-experiencia data-id-agenda="<?php echo (int) $solicitud['id_agenda']; ?>">
                                                <span class="material-symbols-outlined text-sm">point_of_sale</span>
                                                Cobrar e imprimir factura
                                            </button>
                                            <p class="mt-1 hidden text-xs text-rose-600" data-error-cobro-experiencia></p>
                                        <?php elseif ($montoExperiencia === null): ?>
                                            <span class="text-xs text-slate-400">Sin precio para cobrar</span>
                                        <?php elseif ((float) $montoExperiencia <= 0): ?>
                                            <span class="text-xs text-slate-400">Experiencia sin costo</span>
                                        <?php elseif ($estadoAgendaExperiencia === 'Cancelada'): ?>
                                            <span class="text-xs text-slate-400">Experiencia cancelada</span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-500">No disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?php if ($estadoPagoExperiencia === 'Pagada'): ?>
                                            <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase <?php echo $clasesEstadoPago[$estadoPagoExperiencia]; ?>"><?php echo $escaparFinanzas($estadoPagoExperiencia); ?></span>
                                        <?php elseif ($puedeActualizarPagosExperiencia): ?>
                                            <label class="sr-only" for="estado-pago-experiencia-<?php echo (int) $solicitud['id_agenda']; ?>">Estado de pago para <?php echo $escaparFinanzas($solicitud['actividad']); ?></label>
                                            <select
                                                id="estado-pago-experiencia-<?php echo (int) $solicitud['id_agenda']; ?>"
                                                class="rounded-lg border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700"
                                                data-estado-pago-experiencia
                                                data-id-agenda="<?php echo (int) $solicitud['id_agenda']; ?>"
                                                data-estado-original="<?php echo $escaparFinanzas($estadoPagoExperiencia); ?>"
                                            >
                                                <?php foreach (['Pendiente', 'Cancelada'] as $opcionEstadoPago): ?>
                                                    <option value="<?php echo $opcionEstadoPago; ?>" <?php echo $estadoPagoExperiencia === $opcionEstadoPago ? 'selected' : ''; ?>><?php echo $opcionEstadoPago; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($montoExperiencia === null): ?>
                                                <p class="mt-1 text-[10px] text-slate-400">Define el precio antes de registrar el pago.</p>
                                            <?php endif; ?>
                                            <p class="mt-1 hidden text-xs text-rose-600" data-error-pago-experiencia></p>
                                        <?php else: ?>
                                            <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase <?php echo $clasesEstadoPago[$estadoPagoExperiencia] ?? 'bg-slate-100 text-slate-600'; ?>"><?php echo $escaparFinanzas($estadoPagoExperiencia); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 text-right font-black <?php echo $estadoPagoExperiencia === 'Pagada' ? 'text-primary' : 'text-slate-400'; ?>"><?php echo $montoExperiencia === null ? 'Sin precio' : $formatearMontoFinanzas($montoExperiencia); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
<?php if ($puedeActualizarPagosExperiencia && $movimientosExperienciasFinanzas !== []): ?>
    <script>
        document.querySelectorAll('[data-estado-pago-experiencia]').forEach(select => {
            select.addEventListener('change', async () => {
                const estadoAnterior = select.dataset.estadoOriginal;
                const error = select.parentElement.querySelector('[data-error-pago-experiencia]');
                select.disabled = true;
                error.classList.add('hidden');
                try {
                    const respuesta = await fetch('../../controladores/gestionar_experiencias.php', {
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                        body: new URLSearchParams({
                            accion: 'actualizar_pago_experiencia',
                            id_agenda: select.dataset.idAgenda,
                            estado_pago: select.value,
                            csrf_token: CSRF_TOKEN
                        })
                    });
                    const datos = await respuesta.json();
                    if (!respuesta.ok || datos.status !== 'exito') {
                        throw new Error(datos.mensaje || 'No se pudo actualizar el pago');
                    }
                    window.location.reload();
                } catch (errorPeticion) {
                    select.value = estadoAnterior;
                    error.textContent = errorPeticion.message;
                    error.classList.remove('hidden');
                    select.disabled = false;
                }
            });
        });

        document.querySelectorAll('[data-cobrar-experiencia]').forEach(boton => {
            boton.addEventListener('click', async () => {
                const fila = boton.closest('tr');
                const selectorMetodo = fila.querySelector('[data-metodo-pago-experiencia]');
                const error = fila.querySelector('[data-error-cobro-experiencia]');
                const ventanaFactura = window.open('', '_blank');
                if (!ventanaFactura) {
                    error.textContent = 'Permite las ventanas emergentes para imprimir la factura.';
                    error.classList.remove('hidden');
                    return;
                }

                boton.disabled = true;
                error.classList.add('hidden');
                try {
                    const respuesta = await fetch('../../controladores/gestionar_experiencias.php', {
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                        body: new URLSearchParams({
                            accion: 'cobrar_experiencia',
                            id_agenda: boton.dataset.idAgenda,
                            metodo_pago: selectorMetodo.value,
                            csrf_token: CSRF_TOKEN
                        })
                    });
                    const datos = await respuesta.json();
                    if (!respuesta.ok || datos.status !== 'exito' || !datos.factura) {
                        throw new Error(datos.mensaje || 'No se pudo cobrar la experiencia');
                    }
                    imprimirFacturaReserva(datos.factura, ventanaFactura);
                    window.location.reload();
                } catch (errorPeticion) {
                    ventanaFactura.close();
                    error.textContent = errorPeticion.message;
                    error.classList.remove('hidden');
                    boton.disabled = false;
                }
            });
        });
    </script>
<?php endif; ?>
