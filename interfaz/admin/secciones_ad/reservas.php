<?php
if (
    realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== false
    && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)
) {
    require_once __DIR__ . '/../../../includes/sesion_seguridad.php';
    require_once __DIR__ . '/../../../configuracion/conexion.php';
    require_once __DIR__ . '/../../../configuracion/permiso.php';
    require_once __DIR__ . '/../../../src/Reserva/RoomRateSettings.php';

    $rolUsuario = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
    if (
        !isset($_SESSION['emp_auth']['id_usuario'])
        || $rolUsuario !== 1
    ) {
        header('Location: /Hotel-Aurora/interfaz_usu.php');
        exit();
    }

    exigir_permiso($conexion, 'reservas.ver');
}

if (!usuario_tiene_permiso($conexion, 'reservas.ver')) {
    return;
}
require_once __DIR__ . '/../../../src/Reserva/RoomRateSettings.php';
$suplementoNinoAdicional = \App\Reserva\RoomRateSettings::additionalChildSupplement($conexion);

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
            'hospedaje' => '',
            'peticion' => $notas
        ];

        if (preg_match('/(?:^|\R)Hospedaje a nombre de:\s*([^\r\n]+)/u', $notas, $coincidencias)) {
            $resultado['hospedaje'] = trim($coincidencias[1]);
            $notas = trim(preg_replace('/(?:^|\R)Hospedaje a nombre de:\s*[^\r\n]*/u', '', $notas) ?? $notas);
            $resultado['peticion'] = $notas;
        }

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

                // Botón que despliega las experiencias o el room service de una reserva (los cobros se hacen en el modal de la reserva).
                $botonDesplegableReserva = static function (int $idReserva, string $tipo, string $icono, string $titulo, int $total, int $porCobrar): string {
                    if ($total === 0) {
                        return '<span class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-slate-200 px-3 py-2 text-xs font-bold text-slate-300">'
                            . '<span class="material-symbols-outlined text-[16px]">' . $icono . '</span>' . $titulo . ' · 0</span>';
                    }
                    return '<button type="button" data-desplegable-toggle="' . $idReserva . '|' . $tipo . '" aria-expanded="false" '
                        . 'class="ui-action inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:border-primary hover:text-primary">'
                        . '<span class="material-symbols-outlined text-[16px] text-primary">' . $icono . '</span>' . $titulo
                        . '<span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-black text-primary">' . $total . '</span>'
                        . ($porCobrar > 0 ? '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-700">' . $porCobrar . ' por cobrar</span>' : '')
                        . '<span data-desplegable-flecha class="material-symbols-outlined text-[16px] text-slate-400 transition-transform">expand_more</span></button>';
                };
                $panelDesplegableReserva = static function (int $idReserva, string $tipo, string $tarjetas, int $porCobrar): string {
                    if ($tarjetas === '') {
                        return '';
                    }
                    return '<div data-desplegable-panel="' . $idReserva . '|' . $tipo . '" class="hidden mt-3 max-h-80 overflow-y-auto pr-1">' . $tarjetas
                        . ($porCobrar > 0 ? '<p class="mt-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Los cobros se hacen desde Editar reserva</p>' : '')
                        . '</div>';
                };
                
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
                        $hospedaje_tabla = !empty($notas_separadas['hospedaje'])
                            ? '<p class="text-xs font-black text-slate-700">Hospedaje a nombre de: ' . htmlspecialchars($notas_separadas['hospedaje'], ENT_QUOTES, 'UTF-8') . '</p>'
                            : '';
                        $peticion_tabla = !empty($notas_separadas['peticion'])
                            ? '<p class="text-xs italic text-slate-400 mt-1">' . htmlspecialchars($notas_separadas['peticion'], ENT_QUOTES, 'UTF-8') . '</p>'
                            : '';
                        $notas_tabla = ($huespedes_tabla || $hospedaje_tabla || $peticion_tabla)
                            ? $huespedes_tabla . $hospedaje_tabla . $peticion_tabla
                            : '<span class="text-slate-300 italic">Ninguna</span>';

                        $id_reserva = (int) ($reserva['cod_res'] ?? 0);
                        $notas_seguras = htmlspecialchars($notas_db, ENT_QUOTES, 'UTF-8');
                        $hab_segura = htmlspecialchars(is_array($habitacionesAsignadas) ? implode(', ', array_column($habitacionesAsignadas, 'numero')) : '', ENT_QUOTES, 'UTF-8');
                        $saldo_pendiente = number_format((float) ($reserva['saldo_pendiente'] ?? 0), 2, '.', '');
                        $experienciasTabla = '';
                        $tarjetasExperiencias = '';
                        $totalExperienciasReserva = 0;
                        $experienciasPorCobrar = 0;
                        $tarjetasRoomService = '';
                        $totalRoomServiceReserva = 0;
                        $roomServicePorCobrar = 0;

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
                            $totalExperienciasReserva++;
                            if ($estadoPagoExperiencia === 'Pendiente'
                                && ($experienciaReserva['monto_experiencia'] ?? null) !== null
                                && (float) $experienciaReserva['monto_experiencia'] > 0
                                && $estadoExperiencia !== 'Cancelada') {
                                $experienciasPorCobrar++;
                            }
                            $tarjetasExperiencias .= '<div class="mb-3 rounded-lg border border-slate-100 bg-slate-50 p-3 last:mb-0">'
                                . '<p class="font-black text-slate-700">' . $nombreExperiencia . '</p>'
                                . '<p class="mt-1 text-xs text-slate-500">' . $fechaExperiencia . ' · ' . $estadoExperiencia . '</p>'
                                . $notaVinculoExperiencia
                                . '<p class="mt-1 text-xs font-bold text-slate-600">Experiencia: ' . htmlspecialchars($montoExperiencia, ENT_QUOTES, 'UTF-8')
                                . ' · Pago: <span class="' . ($estadoPagoExperiencia === 'Pagada' ? 'text-emerald-600' : 'text-amber-600') . '">'
                                . htmlspecialchars($estadoPagoExperiencia, ENT_QUOTES, 'UTF-8') . '</span></p>'
                                . '</div>';
                        }
                        foreach ($roomServicePorReserva[$id_reserva] ?? [] as $pedidoRoomService) {
                            if (($pedidoRoomService['estado'] ?? '') === 'Cancelado' || (float) ($pedidoRoomService['total'] ?? 0) <= 0) {
                                continue;
                            }
                            $pedidoPagado = ($pedidoRoomService['estado_pago'] ?? '') === 'Pagado';
                            $totalRoomServiceReserva++;
                            if (!$pedidoPagado) {
                                $roomServicePorCobrar++;
                            }
                            $tarjetasRoomService .= '<div class="mb-3 rounded-lg border border-slate-100 bg-slate-50 p-3 last:mb-0">'
                                . '<p class="font-black text-slate-700">Room service #' . (int) $pedidoRoomService['id_pedido'] . '</p>'
                                . '<p class="mt-1 text-xs text-slate-500">' . htmlspecialchars((string) ($pedidoRoomService['resumen'] ?? ''), ENT_QUOTES, 'UTF-8')
                                . ' · ' . htmlspecialchars((string) ($pedidoRoomService['estado'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
                                . '<p class="mt-1 text-xs font-bold text-slate-600">$' . number_format((float) $pedidoRoomService['total'], 0, ',', '.')
                                . ' · Pago: <span class="' . ($pedidoPagado ? 'text-emerald-600' : 'text-amber-600') . '">' . ($pedidoPagado ? 'Pagado' : 'Pendiente') . '</span></p>'
                                . '</div>';
                        }
                        if ($totalExperienciasReserva === 0 && $totalRoomServiceReserva === 0) {
                            $experienciasTabla = '<span class="text-slate-300 italic">Sin experiencias ni room service</span>';
                        } else {
                            $experienciasTabla = '<div class="flex flex-wrap gap-2">'
                                . $botonDesplegableReserva($id_reserva, 'experiencias', 'celebration', 'Experiencias', $totalExperienciasReserva, $experienciasPorCobrar)
                                . $botonDesplegableReserva($id_reserva, 'room_service', 'room_service', 'Room service', $totalRoomServiceReserva, $roomServicePorCobrar)
                                . '</div>'
                                . $panelDesplegableReserva($id_reserva, 'experiencias', $tarjetasExperiencias, $experienciasPorCobrar)
                                . $panelDesplegableReserva($id_reserva, 'room_service', $tarjetasRoomService, $roomServicePorCobrar);
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

    <div id="modalReserva" class="modal-reserva-shell hidden fixed inset-0 z-[80] flex items-center justify-center p-4">
        <div class="modal-reserva-card flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 bg-slate-50 px-6 py-5">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600">Gestión de reservas</p>
                    <h2 class="text-xl font-black text-heading">Nueva reserva</h2>
                </div>
                <button type="button" onclick="cerrarModalReserva()" aria-label="Cerrar" class="text-2xl font-black text-slate-400 hover:text-slate-700">&times;</button>
            </div>
            <form id="formCrearReserva" action="../../controladores/guardar_reserva.php" method="POST" class="flex-1 space-y-5 overflow-y-auto p-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="tipo_huesped" id="tipo_huesped" value="">
                <input type="hidden" name="nuevo_nombre" id="nuevo_nombre_hidden" value="">
                <input type="hidden" name="nuevo_correo" id="nuevo_correo_hidden" value="">
                <input type="hidden" name="nuevo_tipo_documento" id="nuevo_tipo_documento_hidden" value="">
                <input type="hidden" name="nuevo_documento" id="nuevo_documento_hidden" value="">
                <input type="hidden" name="nuevo_telefono" id="nuevo_telefono_hidden" value="">
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="buscadorHuespedReserva" class="block text-xs font-black uppercase tracking-wider text-slate-500">Huésped</label>
                        <button type="button" onclick="abrirSubModalNuevoHuesped()" class="text-xs font-bold text-primary underline">Crear huésped nuevo</button>
                    </div>
                    <div id="contenedorBuscadorHuespedReserva">
                        <input id="buscadorHuespedReserva" type="search" autocomplete="off" oninput="filtrarHuespedesReservaPremium()" placeholder="Buscar por nombre o correo..." class="reserva-field w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <div id="listaHuespedesReserva" class="mt-2 max-h-40 overflow-y-auto rounded-xl border border-slate-200 bg-white"></div>
                    </div>
                    <div id="huespedSeleccionadoReserva" class="hidden items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                        <div class="min-w-0">
                            <p id="huespedSeleccionadoNombre" class="truncate text-sm font-bold text-slate-700"></p>
                            <p id="huespedSeleccionadoCorreo" class="truncate text-xs text-slate-500"></p>
                        </div>
                        <button type="button" onclick="limpiarHuespedSeleccionado()" class="shrink-0 text-xs font-bold text-primary underline">Cambiar</button>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Fecha de entrada
                        <input type="date" name="fecha_in" required min="<?php echo htmlspecialchars((new DateTimeImmutable('today', new DateTimeZone('America/Bogota')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case">
                    </label>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Fecha de salida
                        <input type="date" name="fecha_out" required min="<?php echo htmlspecialchars((new DateTimeImmutable('today', new DateTimeZone('America/Bogota')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case">
                    </label>
                </div>
                <div>
                    <label for="selectorHabitacionDisponible" class="block text-xs font-black uppercase tracking-wider text-slate-500">Habitación</label>
                    <select id="selectorHabitacionDisponible" name="id_habitacion" required disabled class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case">
                        <option value="">Selecciona primero las fechas</option>
                    </select>
                    <p id="mensajeDisponibilidadHabitacionesReserva" class="mt-2 text-xs text-slate-500" role="status" aria-live="polite">Selecciona entrada y salida para consultar las habitaciones disponibles.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Adultos
                        <input type="number" id="cantAdultosReserva" name="cant_adultos" min="1" max="4" value="1" required class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case">
                    </label>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Niños
                        <input type="number" id="cantNinosReserva" name="cant_ninos" min="0" max="4" value="0" required class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case">
                    </label>
                </div>
                <div id="edadesNinosReservaAdmin" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">Edad de cada niño</p>
                    <div id="edadesNinosReservaAdminLista" class="mt-3 grid gap-3 sm:grid-cols-2"></div>
                    <p id="descripcionCargoNinoAdmin" class="mt-2 text-xs text-slate-500">La tarifa estándar cubre hasta 4 huéspedes (máximo 3 en habitaciones sencillas). Los niños de 0 a 2 años no tienen costo; los de 3 a 12 años suman $<?php echo number_format($suplementoNinoAdicional, 0, ',', '.'); ?> por noche al superar los 4 huéspedes incluidos.</p>
                </div>
                <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Peticiones especiales
                    <textarea name="notas_reserva" rows="2" maxlength="500" class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium normal-case" placeholder="Opcional"></textarea>
                </label>
                <label class="block text-xs font-black uppercase tracking-wider text-slate-500">Método de pago (si cobras ahora)
                    <select name="metodo_pago" class="reserva-field mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold normal-case">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </label>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Total estimado</p>
                        <p id="totalReservaPreview" class="text-lg font-black text-primary">$0</p>
                    </div>
                    <p id="detalleTotalReservaPreview" class="mt-1 text-xs text-slate-400">Selecciona una habitación y las fechas para calcular tarifa, subtotal e IVA del 19%.</p>
                </div>
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                    <button type="button" onclick="cerrarModalReserva()" class="ui-action flex-1 rounded-xl border border-slate-200 bg-white py-3 text-sm font-bold text-slate-500 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" name="accion" value="guardar" class="ui-action flex-1 rounded-xl bg-primary py-3 text-sm font-bold text-white hover:bg-heading">Guardar reserva</button>
                    <button type="submit" name="accion" value="cobrar" class="ui-action flex-1 rounded-xl bg-emerald-600 py-3 text-sm font-bold text-white hover:bg-emerald-700">Crear y cobrar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="subModalNuevoHuesped" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4">
        <div class="max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-black text-heading">Nuevo huésped</h3>
            <p class="text-xs text-slate-500">Nombre y documento son obligatorios. El teléfono y correo son opcionales.</p>
            <label class="block text-xs font-bold text-slate-600">Nombre <span class="text-rose-500">*</span>
                <input id="nuevo_nombre" type="text" maxlength="100" autocomplete="name" required class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
            </label>
            <label class="block text-xs font-bold text-slate-600">Tipo de documento <span class="text-rose-500">*</span>
                <select id="nuevo_tipo_documento" required class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
                    <option value="">Selecciona un tipo</option>
                    <option value="CC">Cédula de ciudadanía (CC)</option>
                    <option value="CE">Cédula de extranjería (CE)</option>
                    <option value="PAS">Pasaporte</option>
                    <option value="PPT">Permiso por protección temporal (PPT)</option>
                    <option value="PEP">Permiso especial de permanencia (PEP)</option>
                </select>
            </label>
            <label class="block text-xs font-bold text-slate-600">Número de documento <span class="text-rose-500">*</span>
                <input id="nuevo_documento" type="text" inputmode="numeric" maxlength="12" pattern="[0-9]{3,12}" title="Ingresa solo números, entre 3 y 12 dígitos." autocomplete="off" placeholder="Obligatorio · 3 a 12 números" required class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
            </label>
            <label class="block text-xs font-bold text-slate-600">Número de teléfono
                <input id="nuevo_telefono" type="tel" maxlength="20" autocomplete="tel" placeholder="Opcional" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
            </label>
            <label class="block text-xs font-bold text-slate-600">Correo electrónico
                <input id="nuevo_correo" type="email" maxlength="100" autocomplete="email" placeholder="Opcional" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
            </label>
            <div class="flex gap-3">
                <button type="button" onclick="cerrarSubModalNuevoHuesped()" class="flex-1 rounded-lg border border-slate-200 py-3 text-sm font-bold text-slate-500">Cancelar</button>
                <button type="button" onclick="confirmarNuevoHuespedEnModal()" class="flex-1 rounded-lg bg-primary py-3 text-sm font-bold text-white">Usar huésped</button>
            </div>
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
        const POLITICA_TARIFA_RESERVA = {
            ocupacionEstandar: <?php echo \App\Reserva\RoomRateCalculator::STANDARD_OCCUPANCY; ?>,
            suplementoNinoAdicional: <?php echo $suplementoNinoAdicional; ?>,
            edadMaximaSinCosto: <?php echo \App\Reserva\RoomRateCalculator::FREE_CHILD_MAX_AGE; ?>,
            edadMaximaNino: <?php echo \App\Reserva\RoomRateCalculator::MAX_CHILD_AGE; ?>
        };
        const huespedesAdminState = { adultos: 1, ninos: 0 };
        const edadesNinosAdmin = [];
        const formatoMonedaReserva = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

        let solicitudDisponibilidadHabitacionesAdmin = 0;
        async function actualizarHabitacionesDisponiblesAdmin() {
                const form = document.getElementById('formCrearReserva');
                const selector = document.getElementById('selectorHabitacionDisponible');
                const mensaje = document.getElementById('mensajeDisponibilidadHabitacionesReserva');
                const entrada = form?.elements.namedItem('fecha_in')?.value || '';
                const salida = form?.elements.namedItem('fecha_out')?.value || '';
                const adultos = Number(document.getElementById('cantAdultosReserva')?.value || 1);
                const ninos = Number(document.getElementById('cantNinosReserva')?.value || 0);
                const solicitud = ++solicitudDisponibilidadHabitacionesAdmin;
                selector.replaceChildren(new Option('Selecciona primero las fechas', ''));
                selector.disabled = true;
                if (!entrada || !salida || salida <= entrada) {
                    if (mensaje) mensaje.textContent = 'Selecciona entrada y una salida posterior para consultar disponibilidad.';
                    actualizarTotalReservaPreview();
                    return;
                }
                if (mensaje) mensaje.textContent = 'Consultando habitaciones disponibles...';
                try {
                    const parametros = new URLSearchParams({ checkin: entrada, checkout: salida, adultos: String(adultos), ninos: String(ninos) });
                    const respuesta = await fetch(`../../controladores/disponibilidad_reserva_admin.php?${parametros}`, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin'
                    });
                    const datos = await respuesta.json();
                    if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudo consultar la disponibilidad.');
                    if (solicitud !== solicitudDisponibilidadHabitacionesAdmin) return;
                    selector.replaceChildren(new Option('Selecciona una habitación', ''));
                    datos.habitaciones.forEach(habitacion => {
                        const capacidad = habitacion.capacidad;
                        const opcion = new Option(
                            `Hab. ${habitacion.numero} · ${habitacion.tipo} · ${new Intl.NumberFormat('es-CO').format(habitacion.precio)} COP/noche · máximo ${capacidad.personas} personas`,
                            String(habitacion.id)
                        );
                        opcion.dataset.price = String(habitacion.precio);
                        opcion.dataset.singlePersonPrice = habitacion.precio_una_persona === null ? '' : String(habitacion.precio_una_persona);
                        opcion.dataset.roomType = habitacion.tipo;
                        opcion.dataset.maxPeople = String(capacidad.personas);
                        opcion.dataset.maxAdults = String(capacidad.adultos);
                        opcion.dataset.maxChildren = String(capacidad.ninos);
                        selector.add(opcion);
                    });
                    selector.disabled = datos.habitaciones.length === 0;
                    if (mensaje) mensaje.textContent = datos.mensaje;
                    if (datos.habitaciones.length > 0) actualizarLimitesHabitacionAdmin();
                    actualizarTotalReservaPreview();
                } catch (error) {
                    if (solicitud !== solicitudDisponibilidadHabitacionesAdmin) return;
                    selector.replaceChildren(new Option('No se pudo consultar disponibilidad', ''));
                    if (mensaje) mensaje.textContent = error.message;
                    actualizarTotalReservaPreview();
                }
            }

        function actualizarLimitesHabitacionAdmin() {
                const selector = document.getElementById('selectorHabitacionDisponible');
                const opcion = selector?.selectedOptions[0];
                const adultos = document.getElementById('cantAdultosReserva');
                const ninos = document.getElementById('cantNinosReserva');
                if (!opcion?.value || !adultos || !ninos) return;
                adultos.max = opcion.dataset.maxAdults || '4';
                ninos.max = opcion.dataset.maxChildren || '4';
                if (Number(adultos.value) > Number(adultos.max)) adultos.value = adultos.max;
                if (Number(ninos.value) > Number(ninos.max)) ninos.value = ninos.max;
            }

        function actualizarTotalReservaPreview() {
            const form = document.getElementById('formCrearReserva');
            const habitacion = document.getElementById('selectorHabitacionDisponible');
            const entrada = form?.elements.namedItem('fecha_in')?.value;
            const salida = form?.elements.namedItem('fecha_out')?.value;
            const precioBase = Number(habitacion?.selectedOptions[0]?.dataset.price || 0);
            const adultos = Number(document.getElementById('cantAdultosReserva')?.value || 1);
            const edades = Array.from(document.querySelectorAll('[data-edad-nino-admin]')).map(select => select.value);
            const totalElement = document.getElementById('totalReservaPreview');
            const detalleElement = document.getElementById('detalleTotalReservaPreview');
            if (!totalElement || !detalleElement) return;

            if (!entrada || !salida || !habitacion?.value || !Number.isFinite(precioBase) || precioBase <= 0) {
                totalElement.textContent = formatoMonedaReserva.format(0);
                detalleElement.textContent = 'Selecciona una habitación y las fechas para calcular tarifa, subtotal e IVA del 19%.';
                return;
            }

            const noches = Math.round((Date.parse(`${salida}T00:00:00`) - Date.parse(`${entrada}T00:00:00`)) / 86400000);
            if (noches <= 0) {
                totalElement.textContent = formatoMonedaReserva.format(0);
                detalleElement.textContent = 'La fecha de salida debe ser posterior al check-in.';
                return;
            }

            const ninosIncluidos = Math.max(0, POLITICA_TARIFA_RESERVA.ocupacionEstandar - adultos);
            const ninosAdicionalesConCosto = edades.slice(ninosIncluidos)
                .filter(edad => Number(edad) > POLITICA_TARIFA_RESERVA.edadMaximaSinCosto).length;
            const precioUnaPersona = Number(habitacion?.selectedOptions[0]?.dataset.singlePersonPrice || 0);
            const tarifaNoche = adultos + edades.length === 1 && precioUnaPersona > 0
                ? precioUnaPersona
                : precioBase + ninosAdicionalesConCosto * POLITICA_TARIFA_RESERVA.suplementoNinoAdicional;
            const subtotal = tarifaNoche * noches;
            const iva = Math.round(subtotal * 0.19 * 100) / 100;
            const total = subtotal + iva;
            totalElement.textContent = formatoMonedaReserva.format(total);
            const faltanEdades = edades.some(edad => edad === '');
            detalleElement.textContent = `${noches} noche(s) · tarifa ${formatoMonedaReserva.format(tarifaNoche)}/noche · subtotal ${formatoMonedaReserva.format(subtotal)} + IVA 19% ${formatoMonedaReserva.format(iva)}.${faltanEdades ? ' Completa las edades para precisar la tarifa.' : ''}`;
        }

        function actualizarEdadesNinosAdmin() {
            const box = document.getElementById('edadesNinosReservaAdmin');
            const list = document.getElementById('edadesNinosReservaAdminLista');
            const cantidad = Math.max(0, Number(document.getElementById('cantNinosReserva')?.value || 0));
            if (!box || !list) return;
            while (edadesNinosAdmin.length < cantidad) edadesNinosAdmin.push('');
            edadesNinosAdmin.length = cantidad;
            box.classList.toggle('hidden', cantidad === 0);
            list.innerHTML = edadesNinosAdmin.map((edad, indice) => `
                <label class="block text-xs font-bold text-slate-600">Edad del niño ${indice + 1}
                    <select name="edades_ninos[]" data-edad-nino-admin required class="mt-1 w-full rounded-lg border-slate-200 bg-white p-3 text-sm">
                        <option value="" ${edad === '' ? 'selected' : ''}>Selecciona edad</option>
                        ${Array.from({ length: POLITICA_TARIFA_RESERVA.edadMaximaNino + 1 }, (_, edadOpcion) => `<option value="${edadOpcion}" ${edad !== '' && Number(edad) === edadOpcion ? 'selected' : ''}>${edadOpcion} ${edadOpcion === 1 ? 'año' : 'años'}</option>`).join('')}
                    </select>
                </label>`).join('');
            actualizarTotalReservaPreview();
        }

        function sincronizarHuespedesAdmin() {
            const adultos = document.getElementById('cantAdultosReserva');
            const ninos = document.getElementById('cantNinosReserva');
            huespedesAdminState.adultos = Math.max(1, Number(adultos?.value || 1));
            huespedesAdminState.ninos = Math.max(0, Number(ninos?.value || 0));
            if (adultos) adultos.value = huespedesAdminState.adultos;
            if (ninos) ninos.value = huespedesAdminState.ninos;
            const contadorAdultos = document.getElementById('contadorAdultosAdmin');
            const contadorNinos = document.getElementById('contadorNinosAdmin');
            const resumen = document.getElementById('resumenHuespedesAdmin');
            if (contadorAdultos) contadorAdultos.innerText = huespedesAdminState.adultos;
            if (contadorNinos) contadorNinos.innerText = huespedesAdminState.ninos;
            if (resumen) resumen.innerText = `${huespedesAdminState.adultos} Adulto${huespedesAdminState.adultos === 1 ? '' : 's'}, ${huespedesAdminState.ninos} Niño${huespedesAdminState.ninos === 1 ? '' : 's'}`;
            actualizarEdadesNinosAdmin();
        }

        document.getElementById('formCrearReserva')?.addEventListener('input', evento => {
            if (evento.target.matches('#cantAdultosReserva, #cantNinosReserva')) {
                sincronizarHuespedesAdmin();
                actualizarHabitacionesDisponiblesAdmin();
            } else {
                actualizarTotalReservaPreview();
            }
        });
        document.getElementById('formCrearReserva')?.addEventListener('change', evento => {
            if (evento.target.matches('[name="fecha_in"], [name="fecha_out"]')) {
                if (evento.target.name === 'fecha_in') {
                    const checkout = document.querySelector('#formCrearReserva [name="fecha_out"]');
                    if (checkout) checkout.min = evento.target.value || checkout.min;
                    if (checkout?.value && checkout.value <= evento.target.value) checkout.value = '';
                }
                actualizarHabitacionesDisponiblesAdmin();
                return;
            }
            if (evento.target.matches('#selectorHabitacionDisponible')) actualizarLimitesHabitacionAdmin();
            actualizarTotalReservaPreview();
        });

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
            document.getElementById('nuevo_tipo_documento_hidden').value = '';
            document.getElementById('nuevo_documento_hidden').value = '';
            document.getElementById('nuevo_telefono_hidden').value = '';
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
            const contadorAdultos = document.getElementById('contadorAdultosAdmin');
            const contadorNinos = document.getElementById('contadorNinosAdmin');
            const resumen = document.getElementById('resumenHuespedesAdmin');
            if (contadorAdultos) contadorAdultos.innerText = huespedesAdminState.adultos;
            if (contadorNinos) contadorNinos.innerText = huespedesAdminState.ninos;
            document.getElementById('cantAdultosReserva').value = huespedesAdminState.adultos;
            document.getElementById('cantNinosReserva').value = huespedesAdminState.ninos;
            if (resumen) resumen.innerText = `${huespedesAdminState.adultos} Adulto${huespedesAdminState.adultos === 1 ? '' : 's'}, ${huespedesAdminState.ninos} Niño${huespedesAdminState.ninos === 1 ? '' : 's'}`;
            actualizarEdadesNinosAdmin();
        }

        function abrirSubModalNuevoHuesped() {
            ['nuevo_nombre', 'nuevo_tipo_documento', 'nuevo_documento', 'nuevo_telefono', 'nuevo_correo'].forEach(id => {
                const field = document.getElementById(id);
                if (field) field.value = '';
            });
            document.getElementById('subModalNuevoHuesped').classList.remove('hidden');
        }

        function cerrarSubModalNuevoHuesped() {
            document.getElementById('subModalNuevoHuesped').classList.add('hidden');
        }

        function confirmarNuevoHuespedEnModal() {
            const nombre = document.getElementById('nuevo_nombre').value.trim();
            const correo = document.getElementById('nuevo_correo').value.trim();
            const tipoDocumento = document.getElementById('nuevo_tipo_documento').value;
            const documento = document.getElementById('nuevo_documento').value.trim();
            const telefono = document.getElementById('nuevo_telefono').value.trim();
            if (!nombre) {
                alert('El nombre del huésped es obligatorio.');
                document.getElementById('nuevo_nombre').focus();
                return;
            }
            if (!tipoDocumento) {
                alert('Debes seleccionar un tipo de documento.');
                document.getElementById('nuevo_tipo_documento').focus();
                return;
            }
            if (!documento) {
                alert('El número de documento es obligatorio.');
                document.getElementById('nuevo_documento').focus();
                return;
            }
            if (correo && !document.getElementById('nuevo_correo').checkValidity()) {
                document.getElementById('nuevo_correo').reportValidity();
                return;
            }
            if (!/^[0-9]{3,12}$/.test(documento)) {
                alert('El número de documento debe tener solo números y entre 3 y 12 dígitos.');
                return;
            }
            if (telefono && !/^\+?[0-9 ]{7,20}$/.test(telefono)) {
                alert('Ingresa un teléfono válido de 7 a 20 caracteres.');
                return;
            }
            document.getElementById('tipo_huesped').value = 'nuevo';
            document.getElementById('nuevo_nombre_hidden').value = nombre;
            document.getElementById('nuevo_correo_hidden').value = correo;
            document.getElementById('nuevo_tipo_documento_hidden').value = tipoDocumento;
            document.getElementById('nuevo_documento_hidden').value = documento;
            document.getElementById('nuevo_telefono_hidden').value = telefono;
            seleccionarHuespedReserva('nuevo', nombre || 'Huésped de recepción', correo || 'Sin correo');
            cerrarSubModalNuevoHuesped();
        }

        // Desplegables de experiencias / room service en la tabla. La tabla se refresca sola, así que se recuerda qué está abierto.
        const desplegablesReservasAbiertos = new Set();

        function aplicarDesplegablesReservas() {
            document.querySelectorAll('[data-desplegable-toggle]').forEach(boton => {
                const abierto = desplegablesReservasAbiertos.has(boton.dataset.desplegableToggle);
                boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                boton.querySelector('[data-desplegable-flecha]')?.classList.toggle('rotate-180', abierto);
            });
            document.querySelectorAll('[data-desplegable-panel]').forEach(panel => {
                panel.classList.toggle('hidden', !desplegablesReservasAbiertos.has(panel.dataset.desplegablePanel));
            });
        }

        document.addEventListener('click', evento => {
            const boton = evento.target.closest('[data-desplegable-toggle]');
            if (!boton) return;
            const clave = boton.dataset.desplegableToggle;
            if (desplegablesReservasAbiertos.has(clave)) desplegablesReservasAbiertos.delete(clave);
            else desplegablesReservasAbiertos.add(clave);
            aplicarDesplegablesReservas();
        });

        const tablaReservasDesplegables = document.getElementById('tablaReservas');
        if (tablaReservasDesplegables) {
            new MutationObserver(aplicarDesplegablesReservas).observe(tablaReservasDesplegables, { childList: true });
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

        // Lee la respuesta como JSON; si el servidor devolvió HTML (404, error de PHP) explica qué pasó.
        async function leerRespuestaCuenta(respuesta) {
            const texto = await respuesta.text();
            try {
                return JSON.parse(texto);
            } catch (e) {
                const detalle = respuesta.status === 404
                    ? 'No se encontró controladores/cuenta_reserva.php. Copia ese archivo a la carpeta "controladores" del proyecto (junto a editar_reserva.php).'
                    : 'El servidor no devolvió datos válidos (HTTP ' + respuesta.status + '). Revisa el log de errores de PHP (xampp/php/logs o logs/ del proyecto).';
                throw new Error(detalle);
            }
        }

        async function cargarCuentaReserva() {
            const codRes = document.getElementById('edit_cod_res').value;
            if (!codRes) return;
            try {
                const respuesta = await fetch(`../../controladores/cuenta_reserva.php?cod_res=${encodeURIComponent(codRes)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const datos = await leerRespuestaCuenta(respuesta);
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
                            ${l.tipo === 'alojamiento' ? `<input type="text" inputmode="numeric" maxlength="13" data-cuenta-monto="${escaparCuenta(clave)}" value="${formatearMontoCuenta(l.pendiente)}"
                                class="w-32 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-700" title="Monto a cobrar en pesos (puedes cobrar solo una parte)">` : ''}
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
            document.querySelectorAll('#editCuentaLineas [data-cuenta-monto]').forEach(campo => {
                campo.addEventListener('input', evento => {
                    const entrada = evento.currentTarget;
                    const posicionDesdeFinal = entrada.value.length - entrada.selectionStart;
                    entrada.value = formatearMontoCuenta(entrada.value);
                    const posicion = Math.max(0, entrada.value.length - posicionDesdeFinal);
                    entrada.setSelectionRange(posicion, posicion);
                });
            });
            document.querySelectorAll('#editCuentaLineas [data-cuenta-check], #editCuentaLineas [data-cuenta-monto]').forEach(campo => {
                campo.addEventListener('input', actualizarSeleccionCuenta);
                campo.addEventListener('change', actualizarSeleccionCuenta);
                campo.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            });
            actualizarSeleccionCuenta();
        }

        function formatearMontoCuenta(valor) {
            const digitos = (typeof valor === 'number'
                ? String(Math.round(valor))
                : String(valor ?? '').replace(/\D/g, '')
            ).slice(0, 10);
            return digitos ? new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(Number(digitos)) : '';
        }

        function montoLineaCuenta(linea) {
            const campo = document.querySelector(`#editCuentaLineas [data-cuenta-monto="${CSS.escape(linea.tipo + '|' + linea.id)}"]`);
            if (!campo) return linea.pendiente;
            const digitos = campo.value.replace(/\D/g, '');
            const valor = digitos === '' ? 0 : Number(digitos);
            if (valor === Math.round(linea.pendiente)) return linea.pendiente;
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
            cobrarCuentaReserva(cuentaReservaActual.lineas.filter(l => l.cobrable).map(l => ({
                tipo: l.tipo,
                id: l.id,
                metodo: '',
                ...(l.tipo === 'alojamiento' ? { monto: l.pendiente } : {}),
            })));
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
                const datos = await leerRespuestaCuenta(respuesta);
                if (datos.cuenta) pintarCuentaReserva(datos.cuenta);
                if (!datos.facturas || !datos.facturas.length) {
                    if (ventana && !ventana.closed) ventana.close();
                    throw new Error(datos.mensaje || 'No se pudo registrar el cobro');
                }
                if (ventana) {
                    if (datos.facturas.length === 1 && typeof imprimirFacturaReserva === 'function') {
                        imprimirFacturaReserva(datos.facturas[0], ventana);
                    } else {
                        imprimirComprobanteCuenta(datos.facturas, datos.cuenta, ventana);
                    }
                }
                if (datos.status === 'parcial') {
                    error.textContent = datos.mensaje;
                    error.classList.remove('text-rose-600');
                    error.classList.add('text-amber-700');
                    error.classList.remove('hidden');
                } else if (!ventana) {
                    error.textContent = 'Cobro registrado. Permite ventanas emergentes para imprimir la factura.';
                    error.classList.remove('text-rose-600');
                    error.classList.add('text-amber-700');
                    error.classList.remove('hidden');
                }
                if (typeof refrescarTablaReservas === 'function') await refrescarTablaReservas();
                if (typeof notificarCambioReservas === 'function') notificarCambioReservas();
                window.dispatchEvent(new CustomEvent('roomservice:actualizar'));
            } catch (e) {
                if (ventana && !ventana.closed) ventana.close();
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