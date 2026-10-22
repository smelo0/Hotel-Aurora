<?php
if (
    realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== false
    && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)
) {
    require_once __DIR__ . '/../../../includes/sesion_seguridad.php';
    require_once __DIR__ . '/../../../configuracion/conexion.php';
    require_once __DIR__ . '/../../../configuracion/permiso.php';

    $rolUsuario = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
    if (
        !isset($_SESSION['emp_auth']['id_usuario'])
        || !in_array($rolUsuario, [1, 2], true)
    ) {
        header('Location: /Hotel-Aurora/interfaz_usu.php');
        exit();
    }

    exigir_permiso($conexion, 'reservas.ver');
}

if (!usuario_tiene_permiso($conexion, 'reservas.ver')) {
    return;
}

if (!function_exists('formatear_fecha_reserva_admin')) {
    function formatear_fecha_reserva_admin($fecha) {
        if (empty($fecha)) return '';
        return date('d M Y', strtotime($fecha));
    }
}

if (!function_exists('separar_notas_reserva_admin')) {
    function separar_notas_reserva_admin($notas) {
        $notas = trim((string) $notas);
        $resultado = [
            'huespedes' => '',
            'peticion' => $notas
        ];

        if (preg_match('/^Adultos:\s*(\d+)\s*\|\s*Ni(?:ñ|n)os:\s*(\d+)\s*(.*)$/isu', $notas, $coincidencias)) {
            $adultos = (int) $coincidencias[1];
            $ninos = (int) $coincidencias[2];
            $resto = trim($coincidencias[3] ?? '');
            $resultado['huespedes'] = $adultos . ' Adulto' . ($adultos === 1 ? '' : 's') . ', ' . $ninos . ' Niño' . ($ninos === 1 ? '' : 's');
            $resultado['peticion'] = trim(preg_replace('/^\R+/', '', $resto));
        }

        return $resultado;
    }
}
$paginaSolicitadaReservas = filter_input(INPUT_GET, 'admin_reservas_page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$busquedaReservas = filter_input(INPUT_GET, 'admin_reservas_busqueda', FILTER_UNSAFE_RAW);
$busquedaReservas = is_string($busquedaReservas) ? trim(mb_substr($busquedaReservas, 0, 100, 'UTF-8')) : '';
?>
<section id="sec-reservas" class="seccion-contenido hidden">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h3 class="text-2xl font-black text-primary tracking-tight">Gestión de Reservas</h3>
                <p class="text-xs text-slate-400 mt-1">Directorio de ingresos y salidas.</p>
            </div>

            <div class="flex items-center gap-4">
                <form method="get" action="index_ad.php" class="flex items-center gap-2">
                    <input type="hidden" name="admin_seccion" value="reservas">
                    <div class="relative w-72">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="search" id="buscadorReservas" name="admin_reservas_busqueda" value="<?php echo htmlspecialchars($busquedaReservas, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" placeholder="Buscar huésped..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-12 pr-4 py-3 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <button type="submit" class="rounded-lg border border-slate-200 px-4 py-3 text-xs font-bold text-slate-600 hover:bg-slate-50">Buscar</button>
                </form>

                <button type="button" onclick="abrirModalReserva()" class="ui-action bg-primary text-white px-5 py-3 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-heading focus-visible:ring-4 focus-visible:ring-primary/20 flex items-center gap-2 shadow-sm hover:shadow-md active:shadow-sm">
                    <span class="material-symbols-outlined text-sm">add_circle</span> Nuevo
                </button>
            </div>
        </div>

        <table class="w-full text-left">
            <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                <tr>
                    <th class="p-4">Huésped</th>
                    <th class="p-4">Habitación</th>
                    <th class="p-4">Fechas</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4">Peticiones</th>
                    <th class="p-4">Experiencias y room service</th>
                    <th class="p-4 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody id="tablaReservas" class="divide-y divide-slate-100 text-sm font-semibold">
                <?php
                require_once __DIR__ . '/../../../vendor/autoload.php';
                $datosReservasAdmin = (new \App\Admin\AdminReservationRepository($conexion))->obtenerDatos(
                    is_int($paginaSolicitadaReservas) ? $paginaSolicitadaReservas : 1,
                    $busquedaReservas
                );
                $experienciasPorReserva = $datosReservasAdmin['experiencias_por_reserva'] ?? [];
                $reservasAdmin = $datosReservasAdmin['reservas'] ?? [];

                // Room service por reserva (tablas room_service_pedidos / room_service_pedido_items)
                $roomServicePorReserva = [];
                try {
                    require_once __DIR__ . '/../../../src/RoomService/RoomServiceRepository.php';
                    $resumenRoomService = (new \App\RoomService\RoomServiceRepository($conexion))->resumenPorReservas(
                        array_map(static fn($filaReserva): int => (int) ($filaReserva['cod_res'] ?? 0), $reservasAdmin)
                    );
                    foreach ($resumenRoomService as $codigoReservaRs => $datosReservaRs) {
                        $roomServicePorReserva[(int) $codigoReservaRs] = $datosReservaRs['pedidos'] ?? [];
                    }
                } catch (\Throwable $errorRoomService) {
                    error_log('No se pudo cargar el room service de las reservas: ' . $errorRoomService->getMessage());
                }

                $puedeCobrarExperienciasReserva = usuario_tiene_permiso($conexion, 'finanzas.ver')
                    && usuario_tiene_permiso($conexion, 'experiencias.gestionar');
                
                $paginacionReservas = $datosReservasAdmin['paginacion'] ?? [
                    'pagina' => 1,
                    'paginas' => 1,
                    'por_pagina' => 25,
                    'total' => 0,
                    'desde' => 0,
                    'hasta' => 0,
                    'busqueda' => '',
                ];
                $consultaPaginacionReservas = static function (int $pagina) use ($busquedaReservas): string {
                    return http_build_query(array_filter([
                        'admin_seccion' => 'reservas',
                        'admin_reservas_page' => $pagina,
                        'admin_reservas_busqueda' => $busquedaReservas,
                    ], static fn($valor): bool => $valor !== ''));
                };

                if ($reservasAdmin !== []) {
                    foreach ($reservasAdmin as $reserva) {
                        $fecha_in = formatear_fecha_reserva_admin($reserva['fec_ent_res'] ?? '');
                        $fecha_out = formatear_fecha_reserva_admin($reserva['fec_sal_res'] ?? '');

                        $estado = $reserva['est_res'] ?? 'Pendiente';
                        $color_clase = 'bg-slate-100 text-slate-700';
                        if ($estado === 'Confirmada') $color_clase = 'bg-green-100 text-green-700';
                        if ($estado === 'Pendiente') $color_clase = 'bg-amber-100 text-amber-700';
                        if ($estado === 'En Casa') $color_clase = 'bg-blue-100 text-blue-700';
                        if ($estado === 'Cancelada') $color_clase = 'bg-red-100 text-red-700';

                        $habitacionesAsignadas = [];
                        foreach (explode('||', (string) ($reserva['habitaciones'] ?? '')) as $habitacionItem) {
                            $habitacionItem = trim($habitacionItem);
                            if ($habitacionItem === '') continue;
                            $partesHabitacion = explode(' · ', $habitacionItem, 2);
                            $habitacionesAsignadas[] = [
                                'numero' => trim($partesHabitacion[0]),
                                'tipo' => trim($partesHabitacion[1] ?? ''),
                            ];
                        }
                        $habitacionesHtml = '';
                        foreach ($habitacionesAsignadas as $h) {
                            $habitacionesHtml .= '<div class="leading-tight"><span class="block">Habitación '
                                . htmlspecialchars($h['numero'], ENT_QUOTES, 'UTF-8') . '</span>'
                                . ($h['tipo'] !== '' ? '<span class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">'
                                    . htmlspecialchars($h['tipo'], ENT_QUOTES, 'UTF-8') . '</span>' : '')
                                . '</div>';
                        }
                        if ($habitacionesHtml === '') {
                            $habitacionesHtml = '<span>Sin asignar</span>';
                        }

                        $notas_db = $reserva['not_res'] ?? '';
                        $notas_separadas = separar_notas_reserva_admin($notas_db);
                        $huespedes_tabla = !empty($notas_separadas['huespedes'])
                            ? '<p class="text-xs font-black text-slate-700">' . htmlspecialchars($notas_separadas['huespedes'], ENT_QUOTES, 'UTF-8') . '</p>'
                            : '';
                        $peticion_tabla = !empty($notas_separadas['peticion'])
                            ? '<p class="text-xs italic text-slate-400 mt-1">' . htmlspecialchars($notas_separadas['peticion'], ENT_QUOTES, 'UTF-8') . '</p>'
                            : '';
                        $notas_tabla = ($huespedes_tabla || $peticion_tabla) ? $huespedes_tabla . $peticion_tabla : '<span class="text-slate-300 italic">Ninguna</span>';

                        $id_reserva = (int) ($reserva['cod_res'] ?? 0);
                        $notas_seguras = htmlspecialchars($notas_db, ENT_QUOTES, 'UTF-8');
                        $hab_segura = htmlspecialchars(is_array($habitacionesAsignadas) ? implode(', ', array_column($habitacionesAsignadas, 'numero')) : '', ENT_QUOTES, 'UTF-8');
                        $saldo_pendiente = number_format((float) ($reserva['saldo_pendiente'] ?? 0), 2, '.', '');
                        $experienciasTabla = '';

                        foreach ($experienciasPorReserva[$id_reserva] ?? [] as $experienciaReserva) {
                            $idAgenda = (int) ($experienciaReserva['id_agenda'] ?? 0);
                            $nombreExperiencia = htmlspecialchars((string) ($experienciaReserva['actividad'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $fechaExperiencia = htmlspecialchars(
                                date('d M Y', strtotime((string) ($experienciaReserva['fecha_agenda'] ?? 'now'))) . ' · ' . substr((string) ($experienciaReserva['hora_agenda'] ?? '00:00'), 0, 5),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            $estadoExperiencia = htmlspecialchars((string) (($experienciaReserva['estado_agenda'] ?? '') ?: 'Pendiente'), ENT_QUOTES, 'UTF-8');
                            $estadoPagoExperiencia = (string) ($experienciaReserva['estado_pago_experiencia'] ?? 'Pendiente');
                            $reservaVinculadaExperiencia = (int) ($experienciaReserva['cod_res_agenda'] ?? 0);
                            $notaVinculoExperiencia = $reservaVinculadaExperiencia !== $id_reserva
                                ? '<p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">'
                                    . ($reservaVinculadaExperiencia > 0 ? 'Vinculada a la reserva #' . $reservaVinculadaExperiencia : 'Sin reserva vinculada')
                                    . '</p>'
                                : '';
                            $montoExperiencia = ($experienciaReserva['monto_experiencia'] ?? null) === null
                                ? 'Sin precio'
                                : '$' . number_format((float) $experienciaReserva['monto_experiencia'], 0, ',', '.');
                            $experienciasTabla .= '<div class="mb-3 rounded-lg border border-slate-100 bg-slate-50 p-3 last:mb-0">'
                                . '<p class="font-black text-slate-700">' . $nombreExperiencia . '</p>'
                                . '<p class="mt-1 text-xs text-slate-500">' . $fechaExperiencia . ' · ' . $estadoExperiencia . '</p>'
                                . $notaVinculoExperiencia
                                . '<p class="mt-1 text-xs font-bold text-slate-600">Experiencia: ' . htmlspecialchars($montoExperiencia, ENT_QUOTES, 'UTF-8')
                                . ' · Pago: ' . htmlspecialchars($estadoPagoExperiencia, ENT_QUOTES, 'UTF-8') . '</p>';
                            if ($puedeCobrarExperienciasReserva && $estadoPagoExperiencia === 'Pendiente'
                                && ($experienciaReserva['monto_experiencia'] ?? null) !== null
                                && (float) $experienciaReserva['monto_experiencia'] > 0
                                && $estadoExperiencia !== 'Cancelada') {$experienciasTabla .= '<div class="mt-2 flex flex-wrap items-center gap-2">'
                                    . '<select data-metodo-pago-experiencia class="rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-semibold">'
                                    . '<option value="Efectivo">Efectivo</option><option value="Tarjeta">Tarjeta</option><option value="Transferencia">Transferencia</option></select>'
                                    . '<button type="button" data-cobrar-experiencia-reserva data-id-agenda="' . $idAgenda . '" class="ui-action rounded-md bg-primary px-3 py-1 text-xs font-bold text-white hover:bg-heading">Cobrar y facturar</button>'
                                    . '</div><p data-error-cobro-experiencia class="mt-1 hidden text-xs text-rose-600"></p>';
                            }
                            $experienciasTabla .= '</div>';
                        }
                        foreach ($roomServicePorReserva[$id_reserva] ?? [] as $pedidoRoomService) {
                            if (($pedidoRoomService['estado'] ?? '') === 'Cancelado' || (float) ($pedidoRoomService['total'] ?? 0) <= 0) {
                                continue;
                            }
                            $pedidoPagado = ($pedidoRoomService['estado_pago'] ?? '') === 'Pagado';
                            $experienciasTabla .= '<div class="mb-3 rounded-lg border border-slate-100 bg-slate-50 p-3 last:mb-0">'
                                . '<p class="font-black text-slate-700">Room service #' . (int) $pedidoRoomService['id_pedido'] . '</p>'
                                . '<p class="mt-1 text-xs text-slate-500">' . htmlspecialchars((string) ($pedidoRoomService['resumen'] ?? ''), ENT_QUOTES, 'UTF-8')
                                . ' · ' . htmlspecialchars((string) ($pedidoRoomService['estado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
                                . '<p class="mt-1 text-xs font-bold text-slate-600">$' . number_format((float) $pedidoRoomService['total'], 0, ',', '.')
                                . ' · Pago: <span class="' . ($pedidoPagado ? 'text-emerald-600' : 'text-amber-600') . '">' . ($pedidoPagado ? 'Pagado' : 'Pendiente') . '</span></p>'
                                . '</div>';
                        }
                        if ($experienciasTabla === '') {
                            $experienciasTabla = '<span class="text-slate-300 italic">Sin experiencias ni room service</span>';
                        }

                        $nombreUsuario = htmlspecialchars($reserva['nom_usu'] ?? 'Huésped desconocido', ENT_QUOTES, 'UTF-8');

                        // Datos mínimos para abrir el modal; la cuenta completa se consulta en cuenta_reserva.php.
                        $detallesJson = htmlspecialchars(json_encode([
                            'id' => $id_reserva,
                            'huesped' => $nombreUsuario,
                        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                        echo '<tr class="hover:bg-slate-50 transition-colors" data-reserva-id="' . $id_reserva . '">';
                        echo '<td class="py-5 px-4 align-middle font-semibold text-slate-800">' . $nombreUsuario . '</td>';
                        echo '<td class="py-5 px-4 align-middle text-slate-600 font-bold">'
                            . '<div class="flex items-start gap-2"><span class="material-symbols-outlined text-[17px] text-primary">hotel</span>'
                            . '<div class="flex flex-col gap-2">' . $habitacionesHtml . '</div></div>'
                            . '</td>';
                        echo '<td class="py-5 px-4 align-middle text-slate-500"><div class="flex items-center gap-2 text-xs font-bold"><span>' . htmlspecialchars($fecha_in, ENT_QUOTES, 'UTF-8') . '</span><span class="material-symbols-outlined text-[15px] text-slate-300">arrow_forward</span><span>' . htmlspecialchars($fecha_out, ENT_QUOTES, 'UTF-8') . '</span></div></td>';
                        echo '<td class="py-5 px-4 align-middle"><span class="' . $color_clase . ' px-3 py-1 rounded-full text-[9px] uppercase tracking-widest">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
                        echo '<td class="py-5 px-4 align-middle max-w-xs">' . $notas_tabla . '</td>';
                        echo '<td class="py-5 px-4 align-middle min-w-64">' . $experienciasTabla . '</td>';
                        echo '<td class="py-5 px-4 align-middle"><div class="flex justify-center gap-2">';
                        echo "<button onclick=\"abrirEdicion(this)\" class=\"ui-action bg-amber-400 text-white px-5 py-2 rounded-lg hover:bg-amber-500 hover:shadow-lg hover:opacity-95 shadow-sm btn-editar-reserva\" title=\"Editar Reserva\" data-id=\"$id_reserva\" data-estado=\"" . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . "\" data-habitacion=\"$hab_segura\" data-notas=\"$notas_seguras\" data-saldo-pendiente=\"$saldo_pendiente\" data-detalles-json=\"$detallesJson\"><span class=\"material-symbols-outlined text-sm\">edit</span></button>";
                        echo "<button onclick=\"confirmarEliminacion($id_reserva)\" class=\"ui-action bg-red-500 text-white px-5 py-2 rounded-lg hover:bg-red-600 hover:shadow-lg hover:opacity-95 shadow-sm\" title=\"Eliminar Reserva\"><span class=\"material-symbols-outlined text-sm\">delete</span></button>";
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7" class="p-4 text-center text-slate-400">No hay reservas registradas en el sistema.</td></tr>';
                }

                $huespedes_reserva =$datosReservasAdmin['huespedes'] ?? [];
                $habitaciones_disponibles =$datosReservasAdmin['habitaciones_disponibles'] ?? [];
                ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div id="reservasPaginacion" class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <p class="text-xs text-slate-500">
                Mostrando <?php echo (int) $paginacionReservas['desde']; ?>-<?php echo (int)$paginacionReservas['hasta']; ?>
                de <?php echo (int) $paginacionReservas['total']; ?> reservas
            </p>
            <nav class="flex items-center gap-2" aria-label="Paginación de reservas">
                <?php if ($paginacionReservas['pagina'] > 1): ?>
                    <a href="index_ad.php?<?php echo htmlspecialchars($consultaPaginacionReservas($paginacionReservas['pagina'] - 1), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Anterior</a>
                <?php endif; ?>
                <span class="px-2 text-xs font-bold text-slate-500">Página <?php echo (int) $paginacionReservas['pagina']; ?> de <?php echo (int)$paginacionReservas['paginas']; ?></span>
                <?php if ($paginacionReservas['pagina'] <$paginacionReservas['paginas']): ?>
                    <a href="index_ad.php?<?php echo htmlspecialchars($consultaPaginacionReservas($paginacionReservas['pagina'] + 1), ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Siguiente</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- MODAL DE EDICIÓN Y COBRO UNIFICADO -->
    <div id="modalEditarReserva" class="modal-editar-reserva-shell hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="modal-editar-reserva-card bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-100 flex flex-col max-h-[90vh]">
            
            <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600">Gestión Financiera de Reserva</p>
                    <h4 class="font-black text-heading text-xl">Editar Reserva</h4>
                </div>
                <button type="button" onclick="cerrarModalEditar()" class="text-slate-400 hover:text-slate-600 text-2xl font-black">&times;</button>
            </div>

            <form id="formEditarReserva" action="../../controladores/editar_reserva.php" method="POST" class="p-6 space-y-5 overflow-y-auto flex-1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="cod_res" id="edit_cod_res">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Habitaciones asignadas</label>
                        <div id="edit_habitacion" class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600">
                            Cargando...
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Estado de la reserva</label>
                        <select name="estado" id="edit_estado" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-600 outline-none">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                            <option value="En Casa">En Casa</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>

                <!-- CUENTA DE LA RESERVA: alojamiento + experiencias + room service -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest">Desglose de Consumos y Saldos</label>
                        <button type="button" onclick="cargarCuentaReserva()" class="text-[11px] font-bold text-primary underline">Actualizar cuenta</button>
                    </div>
                    <div id="editCuentaLineas" class="space-y-3">
                        <span class="text-xs italic text-slate-400">Cargando cuenta...</span>
                    </div>
                    <div id="editCuentaTotales" class="hidden grid grid-cols-3 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
                        <div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total</p><p id="editCuentaTotal" class="text-sm font-black text-slate-700">$0</p></div>
                        <div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pagado</p><p id="editCuentaPagado" class="text-sm font-black text-emerald-600">$0</p></div>
                        <div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Por cobrar</p><p id="editCuentaPendiente" class="text-sm font-black text-amber-600">$0</p></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Peticiones Especiales</label>
                    <textarea name="notas" id="edit_notas" rows="2" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-600 outline-none resize-none"></textarea>
                </div>

                <!-- COBRO: selección, división de la cuenta o todo -->
                <div id="editCuentaCobro" class="hidden rounded-2xl border border-emerald-500/20 bg-emerald-50/50 p-5 space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">A cobrar ahora (selección)</p>
                            <p class="text-2xl font-black text-emerald-900" id="editCuentaSeleccion">$0</p>
                        </div>
                        <div>
                            <label for="editCuentaMetodo" class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Método de pago</label>
                            <select id="editCuentaMetodo" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">Para dividir la cuenta marca solo lo que se paga ahora, cambia el monto del alojamiento o elige un método distinto en cada línea. Lo que el cliente ya pagó desde su casa aparece como pagado y no se vuelve a cobrar.</p>
                    <p id="editCuentaError" class="hidden text-xs font-bold text-rose-600"></p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" id="btnCobrarSeleccion" onclick="cobrarSeleccionCuenta()" class="ui-action flex-1 bg-emerald-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-emerald-700 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">checklist</span> Cobrar selección
                        </button>
                        <button type="button" id="btnCobrarTodo" onclick="cobrarTodoCuenta()" class="ui-action flex-1 bg-primary text-white font-bold py-3 rounded-xl shadow-md hover:bg-heading flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">payments</span> Cobrar todo
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalEditar()" class="ui-action flex-1 bg-white border border-slate-200 text-slate-500 font-bold py-3 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button type="submit" name="accion" value="guardar" class="ui-action flex-1 bg-slate-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-slate-700">Actualizar datos</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT JAVASCRIPT -->
    <script>
        const HUESPEDES_RESERVA = <?php echo json_encode($huespedes_reserva, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const huespedesAdminState = { adultos: 1, ninos: 0 };
        const formatoMonedaReserva = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

        function actualizarTotalReservaPreview() {
            const form = document.getElementById('formCrearReserva');
            const habitacion = document.getElementById('selectorHabitacionDisponible');
            const entrada = form?.elements.namedItem('fecha_in')?.value;
            const salida = form?.elements.namedItem('fecha_out')?.value;
            const precio = Number(habitacion?.selectedOptions[0]?.dataset.price || 0);
            const totalElement = document.getElementById('totalReservaPreview');
            const detalleElement = document.getElementById('detalleTotalReservaPreview');
            if (!totalElement || !detalleElement) return;

            if (!entrada || !salida || !habitacion?.value || !Number.isFinite(precio) || precio <= 0) {
                totalElement.textContent = formatoMonedaReserva.format(0);
                detalleElement.textContent = 'Selecciona una habitación y las fechas para calcular el total con IVA incluido.';
                return;
            }

            const noches = Math.round((Date.parse(`${salida}T00:00:00`) - Date.parse(`${entrada}T00:00:00`)) / 86400000);
            if (noches <= 0) {
                totalElement.textContent = formatoMonedaReserva.format(0);
                detalleElement.textContent = 'La fecha de salida debe ser posterior al check-in.';
                return;
            }

            const total = Math.round(precio * noches * 1.19 * 100) / 100;
            totalElement.textContent = formatoMonedaReserva.format(total);
            detalleElement.textContent = `${noches} noche(s) · tarifa por noche ${formatoMonedaReserva.format(precio)} · IVA incluido. El total definitivo lo calcula el servidor.`;
        }

        document.getElementById('formCrearReserva')?.querySelectorAll('[name="fecha_in"], [name="fecha_out"], #selectorHabitacionDisponible')
            .forEach(campo => campo.addEventListener('change', actualizarTotalReservaPreview));

        function confirmarEliminacion(idReserva) {
            if (typeof confirmarEliminacionReservaPremium === 'function') {
                confirmarEliminacionReservaPremium(idReserva);
            }
        }

        function renderListaHuespedesReserva(termino = '') {
            const lista = document.getElementById('listaHuespedesReserva');
            const hiddenTipo = document.getElementById('tipo_huesped');
            if (!lista || !hiddenTipo) return;

            const texto = String(termino || '').toLowerCase().trim();
            const filtrados = HUESPEDES_RESERVA.filter(h => {
                const base = `${h.nom_usu || ''} ${h.corr_usu || ''}`.toLowerCase();
                return !texto || base.includes(texto);
            });

            lista.innerHTML = '';
            filtrados.forEach(h => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 hover:bg-slate-50 transition-colors';
                const nombre = document.createElement('p');
                nombre.className = 'text-sm font-semibold text-slate-700';
                nombre.textContent = h.nom_usu || '';
                const correo = document.createElement('p');
                correo.className = 'text-[11px] text-slate-400';
                correo.textContent = h.corr_usu || '';
                btn.append(nombre, correo);
                btn.onclick = () => {
                    seleccionarHuespedReserva(h.id_usu, h.nom_usu, h.corr_usu);
                };
                lista.appendChild(btn);
            });

            if (!filtrados.length) {
                lista.innerHTML = '<p class="px-3 py-3 text-xs font-bold text-slate-400">No hay coincidencias.</p>';
            }
        }

        function filtrarHuespedesReservaPremium() {
            const buscador = document.getElementById('buscadorHuespedReserva');
            document.getElementById('tipo_huesped').value = '';
            document.getElementById('huespedSeleccionadoReserva')?.classList.add('hidden');
            renderListaHuespedesReserva(buscador ? buscador.value : '');
        }

        function seleccionarHuespedReserva(id, nombre, correo) {
            document.getElementById('tipo_huesped').value = id;
            document.getElementById('huespedSeleccionadoNombre').textContent = nombre;
            document.getElementById('huespedSeleccionadoCorreo').textContent = correo;
            document.getElementById('huespedSeleccionadoReserva').classList.remove('hidden');
            document.getElementById('huespedSeleccionadoReserva').classList.add('flex');
            document.getElementById('contenedorBuscadorHuespedReserva').style.display = 'none';
            document.getElementById('listaHuespedesReserva').innerHTML = '';
        }

        function limpiarHuespedSeleccionado() {
            document.getElementById('tipo_huesped').value = '';
            document.getElementById('nuevo_nombre_hidden').value = '';
            document.getElementById('nuevo_correo_hidden').value = '';
            document.getElementById('huespedSeleccionadoReserva').classList.add('hidden');
            document.getElementById('huespedSeleccionadoReserva').classList.remove('flex');
            document.getElementById('contenedorBuscadorHuespedReserva').style.display = '';
            document.getElementById('buscadorHuespedReserva').value = '';
            renderListaHuespedesReserva('');
        }

        function togglePopoverHuespedesAdmin() {
            document.getElementById('popoverHuespedesAdmin').classList.toggle('hidden');
        }

        function cambiarHuespedesAdmin(tipo, delta) {
            const minimo = tipo === 'adultos' ? 1 : 0;
            huespedesAdminState[tipo] = Math.max(minimo, huespedesAdminState[tipo] + delta);
            document.getElementById('contadorAdultosAdmin').innerText = huespedesAdminState.adultos;
            document.getElementById('contadorNinosAdmin').innerText = huespedesAdminState.ninos;
            document.getElementById('cantAdultosReserva').value = huespedesAdminState.adultos;
            document.getElementById('cantNinosReserva').value = huespedesAdminState.ninos;
            document.getElementById('resumenHuespedesAdmin').innerText = `${huespedesAdminState.adultos} Adulto${huespedesAdminState.adultos === 1 ? '' : 's'}, ${huespedesAdminState.ninos} Niño${huespedesAdminState.ninos === 1 ? '' : 's'}`;
        }

        function abrirSubModalNuevoHuesped() {
            document.getElementById('subModalNuevoHuesped').classList.remove('hidden');
        }

        function cerrarSubModalNuevoHuesped() {
            document.getElementById('subModalNuevoHuesped').classList.add('hidden');
        }

        function confirmarNuevoHuespedEnModal() {
            const nombre = document.getElementById('nuevo_nombre').value.trim();
            const correo = document.getElementById('nuevo_correo').value.trim();
            if (!nombre || !correo) {
                alert('Completa nombre y correo del huésped.');
                return;
            }
            document.getElementById('tipo_huesped').value = 'nuevo';
            document.getElementById('nuevo_nombre_hidden').value = nombre;
            document.getElementById('nuevo_correo_hidden').value = correo;
            seleccionarHuespedReserva('nuevo', nombre, correo);
            cerrarSubModalNuevoHuesped();
        }

        let cuentaReservaActual = null;

        function escaparCuenta(valor) {
            return String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function abrirEdicion(boton) {
            const id = boton.getAttribute('data-id');
            const estado = boton.getAttribute('data-estado');
            const habitacion = boton.getAttribute('data-habitacion');
            const notas = boton.getAttribute('data-notas');

            document.getElementById('edit_cod_res').value = id;
            document.getElementById('edit_estado').value = estado;
            document.getElementById('edit_habitacion').textContent = (habitacion === 'Sin asignar') ? 'Sin habitaciones asignadas' : habitacion;
            document.getElementById('edit_notas').value = (notas === 'Ninguna' ? '' : notas);

            cuentaReservaActual = null;
            document.getElementById('editCuentaLineas').innerHTML = '<span class="text-xs italic text-slate-400">Cargando cuenta...</span>';
            document.getElementById('editCuentaTotales').classList.add('hidden');
            document.getElementById('editCuentaCobro').classList.add('hidden');
            document.getElementById('editCuentaError').classList.add('hidden');
            cargarCuentaReserva();

            const modal = document.getElementById('modalEditarReserva');
            modal.classList.remove('hidden');
            requestAnimationFrame(() => modal.classList.add('modal-editar-reserva-visible'));
        }

        async function cargarCuentaReserva() {
            const codRes = document.getElementById('edit_cod_res').value;
            if (!codRes) return;
            try {
                const respuesta = await fetch(`../../controladores/cuenta_reserva.php?cod_res=${encodeURIComponent(codRes)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const datos = await respuesta.json();
                if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudo cargar la cuenta');
                pintarCuentaReserva(datos.cuenta);
            } catch (e) {
                document.getElementById('editCuentaLineas').innerHTML = `<span class="text-xs font-bold text-rose-600">${escaparCuenta(e.message)}</span>`;
            }
        }

        function pintarCuentaReserva(cuenta) {
            cuentaReservaActual = cuenta;
            const moneda = valor => formatoMonedaReserva.format(Number(valor) || 0);
            const secciones = [
                ['alojamiento', 'hotel', 'Alojamiento'],
                ['experiencia', 'celebration', 'Experiencias'],
                ['room_service', 'room_service', 'Room Service'],
            ];
            const metodos = '<option value="">Método general</option><option value="Efectivo">Efectivo</option><option value="Tarjeta">Tarjeta</option><option value="Transferencia">Transferencia</option>';
            let html = '';
            secciones.forEach(([tipo, icono, titulo]) => {
                const lineas = cuenta.lineas.filter(l => l.tipo === tipo);
                let cuerpo = '';
                if (!lineas.length) {
                    cuerpo = `<p class="italic text-slate-400 text-xs py-1">${tipo === 'experiencia' ? 'Sin experiencias registradas.' : 'Sin pedidos de room service registrados.'}</p>`;
                }
                lineas.forEach(l => {
                    const clave = `${l.tipo}|${l.id}`;
                    const pagada = l.pendiente <= 0 && l.total > 0;
                    const parcial = l.pagado > 0 && l.pendiente > 0;
                    const etiqueta = pagada
                        ? '<span class="font-bold text-emerald-600">Pagado</span>'
                        : (parcial
                            ? `<span class="font-bold text-amber-600">Pagado ${moneda(l.pagado)} · Pendiente ${moneda(l.pendiente)}</span>`
                            : (l.total > 0 ? '<span class="font-bold text-amber-600">Pendiente</span>' : '<span class="font-bold text-slate-400">Sin cobro</span>'));
                    const controles = l.cobrable ? `
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-600">
                                <input type="checkbox" data-cuenta-check="${escaparCuenta(clave)}" checked class="rounded border-slate-300"> Cobrar ahora
                            </label>
                            ${l.tipo === 'alojamiento' ? `<input type="number" data-cuenta-monto="${escaparCuenta(clave)}" min="1" max="${l.pendiente}" step="any" value="${l.pendiente}"
                                class="w-32 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-700" title="Monto a cobrar (puedes cobrar solo una parte)">` : ''}
                            <select data-cuenta-metodo="${escaparCuenta(clave)}" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600">${metodos}</select>
                        </div>` : '';
                    cuerpo += `
                        <div class="py-2 border-b border-slate-100 last:border-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-700">${escaparCuenta(l.concepto)}</p>
                                    <p class="text-[10px] text-slate-400">${escaparCuenta(l.detalle)}</p>
                                    <p class="text-[11px] mt-0.5">${etiqueta}${l.nota ? ` <span class="text-slate-400">· ${escaparCuenta(l.nota)}</span>` : ''}</p>
                                </div>
                                <span class="font-bold text-slate-700 whitespace-nowrap">${l.total > 0 ? moneda(l.total) : 'Sin precio'}</span>
                            </div>${controles}
                        </div>`;
                });
                html += `
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-black text-slate-700 flex items-center gap-1.5 mb-2"><span class="material-symbols-outlined text-sm text-primary">${icono}</span> ${titulo}</p>
                        <div class="text-xs text-slate-500">${cuerpo}</div>
                    </div>`;
            });
            document.getElementById('editCuentaLineas').innerHTML = html;
            document.getElementById('editCuentaTotal').textContent = moneda(cuenta.totales.total);
            document.getElementById('editCuentaPagado').textContent = moneda(cuenta.totales.pagado);
            document.getElementById('editCuentaPendiente').textContent = moneda(cuenta.totales.pendiente);
            document.getElementById('editCuentaTotales').classList.remove('hidden');
            document.getElementById('edit_estado').value = cuenta.estado;

            const hayCobrables = cuenta.lineas.some(l => l.cobrable);
            document.getElementById('editCuentaCobro').classList.toggle('hidden', !hayCobrables);
            document.querySelectorAll('#editCuentaLineas [data-cuenta-check], #editCuentaLineas [data-cuenta-monto]').forEach(campo => {
                campo.addEventListener('input', actualizarSeleccionCuenta);
                campo.addEventListener('change', actualizarSeleccionCuenta);
                campo.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            });
            actualizarSeleccionCuenta();
        }

        function montoLineaCuenta(linea) {
            const campo = document.querySelector(`#editCuentaLineas [data-cuenta-monto="${CSS.escape(linea.tipo + '|' + linea.id)}"]`);
            if (!campo) return linea.pendiente;
            const valor = Number(campo.value);
            return Number.isFinite(valor) ? Math.min(Math.max(valor, 0), linea.pendiente) : 0;
        }

        function lineasSeleccionadasCuenta() {
            if (!cuentaReservaActual) return [];
            return cuentaReservaActual.lineas
                .filter(l => l.cobrable)
                .filter(l => document.querySelector(`#editCuentaLineas [data-cuenta-check="${CSS.escape(l.tipo + '|' + l.id)}"]`)?.checked)
                .map(l => {
                    const metodo = document.querySelector(`#editCuentaLineas [data-cuenta-metodo="${CSS.escape(l.tipo + '|' + l.id)}"]`)?.value || '';
                    const linea = { tipo: l.tipo, id: l.id, metodo };
                    if (l.tipo === 'alojamiento') linea.monto = montoLineaCuenta(l);
                    return linea;
                });
        }

        function actualizarSeleccionCuenta() {
            if (!cuentaReservaActual) return;
            let total = 0;
            lineasSeleccionadasCuenta().forEach(sel => {
                const l = cuentaReservaActual.lineas.find(x => x.tipo === sel.tipo && x.id === sel.id);
                total += sel.tipo === 'alojamiento' ? sel.monto : l.pendiente;
            });
            document.getElementById('editCuentaSeleccion').textContent = formatoMonedaReserva.format(total);
        }

        function cobrarSeleccionCuenta() {
            cobrarCuentaReserva(lineasSeleccionadasCuenta());
        }

        function cobrarTodoCuenta() {
            if (!cuentaReservaActual) return;
            cobrarCuentaReserva(cuentaReservaActual.lineas.filter(l => l.cobrable).map(l => ({ tipo: l.tipo, id: l.id, metodo: '' })));
        }

        async function cobrarCuentaReserva(lineas) {
            const error = document.getElementById('editCuentaError');
            error.classList.add('hidden');
            error.classList.remove('text-amber-700');
            error.classList.add('text-rose-600');
            if (!lineas.length) {
                error.textContent = 'Selecciona al menos un concepto para cobrar.';
                error.classList.remove('hidden');
                return;
            }
            const montoInvalido = lineas.find(l => l.tipo === 'alojamiento' && !(l.monto > 0));
            if (montoInvalido) {
                error.textContent = 'Indica un monto válido para el alojamiento.';
                error.classList.remove('hidden');
                return;
            }

            // La ventana se abre dentro del clic para que el navegador no bloquee la factura.
            const ventana = window.open('', '_blank');
            if (!ventana) {
                error.textContent = 'Permite las ventanas emergentes para poder imprimir la factura.';
                error.classList.remove('hidden');
                return;
            }

            const botones = [document.getElementById('btnCobrarSeleccion'), document.getElementById('btnCobrarTodo')];
            botones.forEach(b => { b.disabled = true; });
            try {
                const respuesta = await fetch('../../controladores/cuenta_reserva.php', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({
                        cod_res: document.getElementById('edit_cod_res').value,
                        metodo_pago: document.getElementById('editCuentaMetodo').value,
                        lineas: JSON.stringify(lineas),
                        csrf_token: document.querySelector('#formEditarReserva [name="csrf_token"]').value,
                    })
                });
                const datos = await respuesta.json();
                if (datos.cuenta) pintarCuentaReserva(datos.cuenta);
                if (!datos.facturas || !datos.facturas.length) {
                    ventana.close();
                    throw new Error(datos.mensaje || 'No se pudo registrar el cobro');
                }
                if (datos.facturas.length === 1 && typeof imprimirFacturaReserva === 'function') {
                    imprimirFacturaReserva(datos.facturas[0], ventana);
                } else {
                    imprimirComprobanteCuenta(datos.facturas, datos.cuenta, ventana);
                }
                if (datos.status === 'parcial') {
                    error.textContent = datos.mensaje;
                    error.classList.remove('text-rose-600');
                    error.classList.add('text-amber-700');
                    error.classList.remove('hidden');
                }
                if (typeof refrescarTablaReservas === 'function') await refrescarTablaReservas();
                if (typeof notificarCambioReservas === 'function') notificarCambioReservas();
                window.dispatchEvent(new CustomEvent('roomservice:actualizar'));
            } catch (e) {
                error.textContent = e.message;
                error.classList.remove('hidden');
            } finally {
                botones.forEach(b => { b.disabled = false; });
            }
        }

        // Comprobante único cuando un mismo cobro incluye varios conceptos (cada uno queda además con su factura).
        function imprimirComprobanteCuenta(facturas, cuenta, ventana) {
            const moneda = valor => formatoMonedaReserva.format(Number(valor) || 0);
            const concepto = f => f.tipo === 'experiencia'
                ? `Experiencia: ${f.experiencia}`
                : (f.tipo === 'room_service'
                    ? `Room service ${(f.pedidos || []).map(id => '#' + id).join(', ')}`
                    : `Alojamiento (${f.noches} noche(s), IVA incluido)`);
            const filas = facturas.map(f => `<tr><td>${escaparCuenta(f.folio)}</td><td>${escaparCuenta(concepto(f))}</td><td>${escaparCuenta(f.metodo_pago)}</td><td>${moneda(f.monto)}</td></tr>`).join('');
            const total = facturas.reduce((suma, f) => suma + (Number(f.monto) || 0), 0);
            ventana.document.open();
            ventana.document.write(`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Comprobante de pago</title>
                <style>body{font-family:Arial,sans-serif;color:#172b3d;margin:0;padding:36px}main{max-width:760px;margin:0 auto;border:1px solid #d8e0e8;padding:36px}
                header{border-bottom:2px solid #17354f;padding-bottom:20px}h1{margin:0 0 8px;font-size:26px}p{margin:6px 0;color:#475569}
                table{width:100%;border-collapse:collapse;margin-top:28px}th,td{text-align:left;padding:12px;border-bottom:1px solid #e2e8f0}
                th:last-child,td:last-child{text-align:right}.total{font-size:19px;font-weight:bold}footer{margin-top:32px;text-align:center;color:#64748b;font-size:12px}
                @media print{body{padding:0}main{border:0;padding:12mm}}</style></head><body><main>
                <header><h1>Hotel Aurora</h1><p>Comprobante de pago · Reserva #${escaparCuenta(cuenta ? cuenta.cod_res : '')}</p></header>
                <p><strong>${escaparCuenta(cuenta ? cuenta.huesped : '')}</strong></p><p>${escaparCuenta(cuenta ? cuenta.correo : '')}</p>
                <table><thead><tr><th>Factura</th><th>Concepto</th><th>Pago</th><th>Total</th></tr></thead><tbody>${filas}</tbody>
                <tfoot><tr><td colspan="3" class="total">Total pagado</td><td class="total">${moneda(total)}</td></tr></tfoot></table>
                <footer>Cada concepto queda registrado con su propia factura. Gracias por elegir Hotel Aurora.</footer></main>
                <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));<\/script></body></html>`);
            ventana.document.close();
        }

        function cerrarModalEditar() {
            const modal = document.getElementById('modalEditarReserva');
            modal.classList.remove('modal-editar-reserva-visible');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderListaHuespedesReserva('');
        });
    </script>
</section>