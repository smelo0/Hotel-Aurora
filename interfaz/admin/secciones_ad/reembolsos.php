<?php
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../configuracion/permiso.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'reembolsos.ver')) {
    return;
}

$reembolsosLista = [];
$reservasReembolsables = [];
$reembolsosResumen = [];
try {
    $repoReembolsosAdmin = new \App\Reembolso\ReembolsoRepository($conexion);
    $reembolsosLista = $repoReembolsosAdmin->listar();
    $reembolsosResumen = $repoReembolsosAdmin->resumen();
    $reservasReembolsables = $repoReembolsosAdmin->reservasReembolsables();
} catch (\Throwable $errorReembolsosAdmin) {
    error_log('No se pudo cargar el módulo de reembolsos: ' . $errorReembolsosAdmin->getMessage());
}
$puedeGestionarReembolsos = usuario_tiene_permiso($conexion, 'reembolsos.gestionar');
$referenciasReserva = [];
try {
    $referenciasReserva = $repoReembolsosAdmin->referenciasPorReservas(array_merge(
        array_column($reembolsosLista, 'cod_res_reemb'),
        array_column($reservasReembolsables, 'cod_res')
    ));
} catch (\Throwable $errorReferencias) {
    error_log('No se pudieron cargar las referencias de reserva: ' . $errorReferencias->getMessage());
}

$escReemb = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$copReemb = static fn($v): string => '$' . number_format((float) $v, 0, ',', '.');
$clasesReemb = [
    'Solicitado' => 'bg-amber-100 text-amber-700',
    'Aprobado' => 'bg-sky-100 text-sky-700',
    'Pagado' => 'bg-emerald-100 text-emerald-700',
    'Rechazado' => 'bg-rose-100 text-rose-700',
];
?>
<section id="sec-reembolsos" class="seccion-contenido hidden">
    <div class="space-y-8">
        <div class="rounded-xl border border-primary/10 bg-white p-8 shadow-sm">
            <div class="mb-6">
                <h3 class="text-2xl font-black tracking-tight text-primary">Reembolsos</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Política vigente: <?php echo $escReemb(\App\Reembolso\PoliticaReembolso::descripcion()); ?>.
                    Solo aplica al alojamiento. La devolución del dinero se hace por fuera (transferencia, reverso o efectivo) y aquí se registra su referencia.
                </p>
            </div>

            <?php if ($puedeGestionarReembolsos): ?>
                <div class="mb-8 rounded-xl border border-primary/10 bg-slate-50 p-5">
                    <h4 class="text-lg font-black text-slate-800">Registrar un reembolso</h4>
                    <p class="mb-4 text-xs text-slate-500">Cancela la reserva y deja el reembolso aprobado. Si ya devolviste el dinero, escribe la referencia y quedará como pagado.</p>
                    <?php if ($reservasReembolsables === []): ?>
                        <p class="text-sm text-slate-500">No hay reservas pagadas que se puedan reembolsar.</p>
                    <?php else: ?>
                        <div class="grid gap-3 md:grid-cols-6">
                            <select id="rbReserva" class="rounded-lg border border-slate-200 px-3 py-2 text-sm md:col-span-3">
                                <?php foreach ($reservasReembolsables as $rr): ?>
                                    <option value="<?php echo (int) $rr['cod_res']; ?>" data-referencias="<?php echo $escReemb(implode(', ', $referenciasReserva[(int) $rr['cod_res']] ?? [])); ?>">#<?php echo (int) $rr['cod_res']; ?> · <?php echo $escReemb($rr['nom_usu']); ?> · <?php echo $escReemb($rr['est_res']); ?> · pagado <?php echo $copReemb($rr['pagado']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input id="rbPorcentaje" type="number" min="1" max="100" value="100" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" aria-label="Porcentaje a reembolsar" title="Porcentaje a reembolsar">
                            <input id="rbReferencia" type="text" maxlength="100" placeholder="Referencia (si ya pagaste)" class="rounded-lg border border-slate-200 px-3 py-2 text-sm md:col-span-2">
                            <input id="rbMotivo" type="text" maxlength="500" placeholder="Motivo" class="rounded-lg border border-slate-200 px-3 py-2 text-sm md:col-span-5">
                            <button type="button" id="rbCrear" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white">Reembolsar</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8 grid gap-4 md:grid-cols-4">
                <?php foreach (['Solicitado', 'Aprobado', 'Pagado', 'Rechazado'] as $estadoResumen): ?>
                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider <?php echo $clasesReemb[$estadoResumen]; ?>"><?php echo $estadoResumen; ?></span>
                        <p class="mt-3 text-2xl font-black text-primary"><?php echo (int) ($reembolsosResumen[$estadoResumen]['total'] ?? 0); ?></p>
                        <p class="text-xs text-slate-500"><?php echo $copReemb($reembolsosResumen[$estadoResumen]['monto'] ?? 0); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($reembolsosLista === []): ?>
                <p class="rounded-lg bg-slate-50 p-5 text-sm text-slate-500">Todavía no hay solicitudes de reembolso.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left">
                        <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Huésped</th>
                                <th class="px-4 py-3">Reserva</th>
                                <th class="px-4 py-3">Pagado</th>
                                <th class="px-4 py-3">A devolver</th>
                                <th class="px-4 py-3">Motivo / notas</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php foreach ($reembolsosLista as $rb): ?>
                                <tr>
                                    <td class="px-4 py-3 font-black text-slate-500">#<?php echo (int) $rb['id_reembolso']; ?><br><span class="text-[11px] font-normal"><?php echo $escReemb(substr((string) $rb['creado_en'], 0, 10)); ?></span></td>
                                    <td class="px-4 py-3"><p class="font-bold text-slate-800"><?php echo $escReemb($rb['nom_usu'] ?? 'Huésped'); ?></p><p class="text-xs text-slate-500"><?php echo $escReemb($rb['corr_usu'] ?? ''); ?></p></td>
                                    <td class="px-4 py-3">#<?php echo (int) $rb['cod_res_reemb']; ?><br><span class="text-xs text-slate-500"><?php echo $escReemb(substr((string) ($rb['fec_ent_res'] ?? ''), 0, 10)); ?> → <?php echo $escReemb(substr((string) ($rb['fec_sal_res'] ?? ''), 0, 10)); ?></span><br><span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase text-slate-600"><?php echo $escReemb($rb['est_res_actual'] ?? 'Sin estado'); ?></span></td>
                                    <td class="px-4 py-3"><?php echo $copReemb($rb['monto_pagado']); ?></td>
                                    <td class="px-4 py-3 font-black text-primary"><?php echo $copReemb($rb['monto_reembolso']); ?><br><span class="text-xs font-normal text-slate-500"><?php echo (int) $rb['porcentaje']; ?>%</span></td>
                                    <td class="max-w-xs px-4 py-3 text-xs text-slate-600">
                                        <?php if (!empty($rb['motivo'])): ?><p><b>Huésped:</b> <?php echo $escReemb($rb['motivo']); ?></p><?php endif; ?>
                                        <?php if (!empty($rb['nota_admin'])): ?><p><b>Hotel:</b> <?php echo $escReemb($rb['nota_admin']); ?></p><?php endif; ?>
                                        <?php if (!empty($rb['referencia_devolucion'])): ?><p><b>Ref.:</b> <?php echo $escReemb($rb['referencia_devolucion']); ?></p><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider <?php echo $clasesReemb[$rb['estado']] ?? ''; ?>"><?php echo $escReemb($rb['estado']); ?></span></td>
                                    <td class="px-4 py-3">
                                        <?php if ($puedeGestionarReembolsos): ?>
                                            <div class="flex flex-wrap gap-2">
                                                <?php if ($rb['estado'] === 'Solicitado'): ?>
                                                    <button type="button" data-reembolso-accion="aprobar" data-id="<?php echo (int) $rb['id_reembolso']; ?>" class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white">Aprobar</button>
                                                    <button type="button" data-reembolso-accion="rechazar" data-id="<?php echo (int) $rb['id_reembolso']; ?>" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-600">Rechazar</button>
                                                <?php elseif ($rb['estado'] === 'Aprobado'): ?>
                                                    <button type="button" data-reembolso-accion="pagar" data-id="<?php echo (int) $rb['id_reembolso']; ?>" data-referencias="<?php echo $escReemb(implode(', ', $referenciasReserva[(int) $rb['cod_res_reemb']] ?? [])); ?>" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Marcar pagado</button>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400">—</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
(() => {
    const selector = document.getElementById('rbReserva');
    const campo = document.getElementById('rbReferencia');
    if (!selector || !campo) return;
    const actualizar = () => {
        const validas = selector.options[selector.selectedIndex]?.dataset.referencias;
        campo.placeholder = validas ? 'Referencia si ya pagaste (' + validas + ')' : 'Referencia si ya pagaste';
        campo.title = validas ? 'Referencias válidas de esta reserva: ' + validas : '';
    };
    selector.addEventListener('change', actualizar);
    actualizar();
})();

document.getElementById('rbCrear')?.addEventListener('click', async evento => {
    const boton = evento.currentTarget;
    const porcentaje = document.getElementById('rbPorcentaje').value;
    const reserva = document.getElementById('rbReserva');
    if (!confirm(`¿Cancelar la reserva ${reserva.options[reserva.selectedIndex].text.split(' · ')[0]} y reembolsar el ${porcentaje}%?`)) return;
    const cuerpo = new URLSearchParams({
        accion: 'crear', id_reserva: reserva.value, porcentaje,
        motivo: document.getElementById('rbMotivo').value,
        referencia: document.getElementById('rbReferencia').value,
        csrf_token: CSRF_TOKEN
    });
    boton.disabled = true;
    try {
        const respuesta = await fetch('../../controladores/reembolsos.php', {
            method: 'POST', body: cuerpo, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const datos = await respuesta.json();
        alert(datos.mensaje || (respuesta.ok ? 'Listo.' : 'No se pudo completar la acción.'));
        if (respuesta.ok) {
            try { sessionStorage.setItem('hotel_admin_seccion', 'reembolsos'); } catch (_) {}
            window.location.reload();
        }
    } catch (error) {
        alert('Error de conexión. Intenta de nuevo.');
    } finally {
        boton.disabled = false;
    }
});

document.addEventListener('click', async evento => {
    const boton = evento.target.closest('[data-reembolso-accion]');
    if (!boton || boton.disabled) return;
    const accion = boton.dataset.reembolsoAccion;
    const cuerpo = new URLSearchParams({ accion, id_reembolso: boton.dataset.id, csrf_token: CSRF_TOKEN });

    if (accion === 'aprobar') {
        if (!confirm('¿Aprobar este reembolso?')) return;
    } else if (accion === 'rechazar') {
        const nota = prompt('Motivo del rechazo (el huésped lo verá). La reserva se restaurará:');
        if (nota === null) return;
        cuerpo.set('nota', nota);
    } else {
        const validas = boton.dataset.referencias;
        const referencia = prompt('Referencia asociada a la reserva' + (validas ? ' (válidas: ' + validas + ')' : '') + ':');
        if (referencia === null) return;
        cuerpo.set('referencia', referencia);
    }

    boton.disabled = true;
    try {
        const respuesta = await fetch('../../controladores/reembolsos.php', {
            method: 'POST', body: cuerpo, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const datos = await respuesta.json();
        alert(datos.mensaje || (respuesta.ok ? 'Listo.' : 'No se pudo completar la acción.'));
        if (respuesta.ok) {
            try { sessionStorage.setItem('hotel_admin_seccion', 'reembolsos'); } catch (_) {}
            window.location.reload();
        }
    } catch (error) {
        alert('Error de conexión. Intenta de nuevo.');
    } finally {
        boton.disabled = false;
    }
});
</script>