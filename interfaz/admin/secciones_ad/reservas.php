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
                    <th class="p-4">Experiencias y cobros</th>
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

                // CONSULTA DIRECTA Y ADAPTABLE DE ROOM SERVICE POR RESERVA Y NÚMERO DE HABITACIÓN
                $roomServicePorReserva = [];
                if (!empty($reservasAdmin)) {
                    foreach ($reservasAdmin as $reserva) {
                        $idRes = (int) ($reserva['cod_res'] ?? 0);
                        if ($idRes <= 0) continue;

                        // Extraer los números de habitación de la cadena devuelta por el repositorio
                        $numsHabitaciones = [];
                        foreach (explode('||', (string) ($reserva['habitaciones'] ?? '')) as $hItem) {
                            $hItem = trim($hItem);
                            if ($hItem === '') continue;
                            $partesH = explode(' · ', $hItem, 2);
                            $numH = trim($partesH[0]);
                            if ($numH !== '') {
                                $numsHabitaciones[] = $conexion->real_escape_string($numH);
                            }
                        }

                        // Construir filtros flexibles para la tabla de room service
                        $condiciones = ["p.cod_res = $idRes"];
                        if (!empty($numsHabitaciones)) {
                            $listaHabStr = "'" . implode("','", $numsHabitaciones) . "'";
                            $condiciones[] = "p.num_hab IN ($listaHabStr)";
                        }
                        $whereClausula = implode(' OR ', $condiciones);

                        $sqlRs = "SELECT p.*, GROUP_CONCAT(CONCAT(i.cantidad, 'x ', item.nombre) SEPARATOR ', ') as resumen_items 
                                  FROM pedidos_room_service p 
                                  LEFT JOIN items_pedido_room_service i ON p.id_pedido = i.id_pedido
                                  LEFT JOIN catalogoroomservice item ON i.id_item = item.id
                                  WHERE ($whereClausula)
                                  GROUP BY p.id_pedido 
                                  ORDER BY p.id_pedido DESC";
                        try {
                            $resRs = $conexion->query($sqlRs);
                            if ($resRs) {
                                while ($rowRs = $resRs->fetch_assoc()) {
                                    $roomServicePorReserva[$idRes][] = [
                                        'id_pedido' => $rowRs['id_pedido'] ?? '',
                                        'resumen'   => $rowRs['resumen_items'] ?? 'Consumo Room Service',
                                        'monto'     => (float) ($rowRs['total'] ?? 0),
                                        'estado'    => $rowRs['estado'] ?? 'Entregado',
                                        'pago'      => $rowRs['estado_pago'] ?? 'Pendiente'
                                    ];
                                }
                            }
                        } catch (\Throwable $e) {
                            // En caso de discrepancia menor en la estructura
                        }
                    }
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
                        if ($experienciasTabla === '') {$experienciasTabla = '<span class="text-slate-300 italic">Sin experiencias vinculadas</span>';
                        }

                        $nombreUsuario = htmlspecialchars($reserva['nom_usu'] ?? 'Huésped desconocido', ENT_QUOTES, 'UTF-8');

                        // Inyección segura del JSON con room_service mapeado
                        $detallesJson = htmlspecialchars(json_encode([
                            'id' => $id_reserva,
                            'huesped' => $nombreUsuario,
                            'saldo_alojamiento' => (float) ($reserva['saldo_pendiente'] ?? 0),
                            'experiencias' => $experienciasPorReserva[$id_reserva] ?? [],
                            'room_service' => $roomServicePorReserva[$id_reserva] ?? []
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

                <!-- BLOQUES DESGLOSADOS SEPARADOS -->
                <div class="space-y-3">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest">Desglose de Consumos y Saldos</label>
                    
                    <!-- 1. Alojamiento -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-slate-700 flex items-center gap-1.5"><span class="material-symbols-outlined text-sm text-primary">hotel</span> Alojamiento</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Saldo pendiente del hospedaje</p>
                        </div>
                        <span id="editDesgloseAlojamientoMonto" class="text-sm font-bold text-slate-800">$0</span>
                    </div>

                    <!-- 2. Experiencias -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-black text-slate-700 flex items-center gap-1.5 mb-2"><span class="material-symbols-outlined text-sm text-primary">celebration</span> Experiencias</p>
                        <div id="editDesgloseExperienciasContainer" class="space-y-1 text-xs text-slate-500">
                            <span class="italic">Cargando experiencias...</span>
                        </div>
                    </div>

                    <!-- 3. Room Service -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-black text-slate-700 flex items-center gap-1.5 mb-2"><span class="material-symbols-outlined text-sm text-primary">room_service</span> Room Service</p>
                        <div id="editDesgloseRoomServiceContainer" class="space-y-1 text-xs text-slate-500">
                            <span class="italic">Cargando pedidos de room service...</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Peticiones Especiales</label>
                    <textarea name="notas" id="edit_notas" rows="2" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-bold text-slate-600 outline-none resize-none"></textarea>
                </div>

                <!-- CONTROLES DE PAGO GLOBAL -->
                <div id="editPagoControles" class="rounded-2xl border border-emerald-500/20 bg-emerald-50/50 p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Total Global Pendiente</p>
                            <p class="text-2xl font-black text-emerald-900" id="editMontoCobro">$0</p>
                        </div>
                        <div>
                            <label for="edit_metodo_pago" class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Método de pago</label>
                            <select name="metodo_pago" id="edit_metodo_pago" class="reserva-field rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">Si el cliente ya pagó la reserva desde su casa, el sistema descuenta los abonos previos y solo calcula el remanente pendiente.</p>
                </div>

                <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="cerrarModalEditar()" class="ui-action flex-1 bg-white border border-slate-200 text-slate-500 font-bold py-3 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button type="submit" name="accion" value="guardar" class="ui-action flex-1 bg-slate-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-slate-700">Actualizar datos</button>
                    <button type="submit" id="editAccionCobrar" name="accion" value="cobrar" class="ui-action flex-1 bg-emerald-600 text-white font-bold py-3 rounded-xl shadow-md hover:bg-emerald-700 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">payments</span> Cobrar Todo
                    </button>
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

        function abrirEdicion(boton) {
            const id = boton.getAttribute('data-id');
            const estado = boton.getAttribute('data-estado');
            const habitacion = boton.getAttribute('data-habitacion');
            const notas = boton.getAttribute('data-notas');
            const saldoPendiente = Number(boton.getAttribute('data-saldo-pendiente') || 0);

            let detalles = {};
            try {
                detalles = JSON.parse(boton.getAttribute('data-detalles-json') || '{}');
            } catch (e) {
                detalles = {};
            }

            document.getElementById('edit_cod_res').value = id;
            document.getElementById('edit_estado').value = estado;
            document.getElementById('edit_habitacion').textContent = (habitacion === 'Sin asignar') ? 'Sin habitaciones asignadas' : habitacion;
            document.getElementById('edit_notas').value = (notas === 'Ninguna' ? '' : notas);
            
            document.getElementById('editMontoCobro').textContent = formatoMonedaReserva.format(saldoPendiente);
            document.getElementById('editDesgloseAlojamientoMonto').textContent = formatoMonedaReserva.format(saldoPendiente);

            // 1. RENDERIZAR EXPERIENCIAS
            const containerExp = document.getElementById('editDesgloseExperienciasContainer');
            if (containerExp) {
                let htmlExp = '';
                if (detalles.experiencias && detalles.experiencias.length > 0) {
                    detalles.experiencias.forEach(exp => {
                        const montoExp = exp.monto_experiencia ? formatoMonedaReserva.format(exp.monto_experiencia) : 'Sin precio';
                        const estadoPago = exp.estado_pago_experiencia || 'Pendiente';
                        const clasePago = estadoPago === 'Pagado' ? 'text-emerald-600' : 'text-amber-600';
                        
                        htmlExp += `
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-100 last:border-0">
                                <div>
                                    <p class="font-bold text-slate-700">${exp.actividad || 'Experiencia'}</p>
                                    <p class="text-[10px] text-slate-400">${exp.fecha_agenda || ''} · <span class="${clasePago} font-bold">${estadoPago}</span></p>
                                </div>
                                <span class="font-bold text-slate-700">${montoExp}</span>
                            </div>`;
                    });
                } else {
                    htmlExp = '<p class="italic text-slate-400 text-xs py-1">Sin experiencias registradas.</p>';
                }
                containerExp.innerHTML = htmlExp;
            }

            // 2. RENDERIZAR ROOM SERVICE
            const containerRs = document.getElementById('editDesgloseRoomServiceContainer');
            if (containerRs) {
                let htmlRs = '';
                if (detalles.room_service && detalles.room_service.length > 0) {
                    detalles.room_service.forEach(pedido => {
                        const montoVal = Number(pedido.monto || 0);
                        const totalPedido = formatoMonedaReserva.format(montoVal);
                        const estadoPago = pedido.pago || 'Pendiente';
                        const clasePago = (estadoPago === 'Pagado' || estadoPago === 'Pagada') ? 'text-emerald-600' : 'text-amber-600';
                        const resumenPedido = pedido.resumen || `Pedido #${pedido.id_pedido || ''}`;
                        const estadoEntrega = pedido.estado || 'Entregado';

                        htmlRs += `
                            <div class="flex items-center justify-between py-1.5 border-b border-slate-100 last:border-0">
                                <div>
                                    <p class="font-bold text-slate-700">Pedido #${pedido.id_pedido} · ${resumenPedido}</p>
                                    <p class="text-[10px] text-slate-400">Estado: <span class="font-semibold text-slate-600">${estadoEntrega}</span> · Pago: <span class="${clasePago} font-bold">${estadoPago}</span></p>
                                </div>
                                <span class="font-bold text-slate-700">${totalPedido}</span>
                            </div>`;
                    });
                } else {
                    htmlRs = '<p class="italic text-slate-400 text-xs py-1">Sin pedidos de room service registrados.</p>';
                }
                containerRs.innerHTML = htmlRs;
            }

            const permiteCobrar = estado !== 'Cancelada' && saldoPendiente > 0;
            document.getElementById('editPagoControles').classList.toggle('hidden', !permiteCobrar);
            document.getElementById('editAccionCobrar').classList.toggle('hidden', !permiteCobrar);

            const modal = document.getElementById('modalEditarReserva');
            modal.classList.remove('hidden');
            requestAnimationFrame(() => modal.classList.add('modal-editar-reserva-visible'));
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