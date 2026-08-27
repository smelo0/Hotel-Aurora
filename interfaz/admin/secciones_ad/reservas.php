<?php
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
?>
<section id="sec-reservas" class="seccion-contenido hidden">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h3 class="text-2xl font-black text-primary tracking-tight">Gestion de Reservas</h3>
                <p class="text-xs text-slate-400 mt-1">Directorio de ingresos y salidas.</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative w-72">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" id="buscadorReservas" onkeyup="filtrarReservas()" placeholder="Filtrar por nombre..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-12 pr-4 py-3 text-xs font-bold outline-none focus:ring-2 focus:ring-primary/20">
                </div>

                <button type="button" onclick="abrirModalReserva()" class="ui-action bg-primary text-white px-5 py-3 rounded-lg text-xs font-black uppercase tracking-widest hover:bg-heading focus-visible:ring-4 focus-visible:ring-primary/20 flex items-center gap-2 shadow-sm hover:shadow-md active:shadow-sm">
                    <span class="material-symbols-outlined text-sm">add_circle</span> Nuevo
                </button>
            </div>
        </div>

        <table class="w-full text-left">
            <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200">
                <tr>
                    <th class="p-4">Huésped</th>
                    <th class="p-4">Habitacion</th>
                    <th class="p-4">Fechas</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4">Peticiones</th>
                    <th class="p-4 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody id="tablaReservas" class="divide-y divide-slate-100 text-sm font-semibold">
                <?php
                require_once __DIR__ . '/../../../configuracion/conexion.php';

                $sql_reservas = "SELECT r.cod_res, u.nom_usu, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res, d.cod_hab_det
                                 FROM reservas r
                                 INNER JOIN usuario u ON r.id_usu_res = u.id_usu
                                 LEFT JOIN detalle d ON r.cod_res = d.cod_res_det
                                 ORDER BY r.cod_res DESC";

                $resultado = $conexion->query($sql_reservas);

                if ($resultado && $resultado->num_rows > 0) {
                    while ($reserva = $resultado->fetch_assoc()) {
                        $fecha_in = formatear_fecha_reserva_admin($reserva['fec_ent_res']);
                        $fecha_out = formatear_fecha_reserva_admin($reserva['fec_sal_res']);

                        $estado = $reserva['est_res'];
                        $color_clase = 'bg-slate-100 text-slate-700';
                        if ($estado === 'Confirmada') $color_clase = 'bg-green-100 text-green-700';
                        if ($estado === 'Pendiente') $color_clase = 'bg-amber-100 text-amber-700';
                        if ($estado === 'En Casa') $color_clase = 'bg-blue-100 text-blue-700';
                        if ($estado === 'Cancelada') $color_clase = 'bg-red-100 text-red-700';

                        $habitacion = !empty($reserva['cod_hab_det']) ? $reserva['cod_hab_det'] : 'Sin asignar';
                        $notas_db = $reserva['not_res'] ?? '';
                        $notas_separadas = separar_notas_reserva_admin($notas_db);
                        $huespedes_tabla = !empty($notas_separadas['huespedes'])
                            ? '<p class="text-xs font-black text-slate-700">' . htmlspecialchars($notas_separadas['huespedes'], ENT_QUOTES, 'UTF-8') . '</p>'
                            : '';
                        $peticion_tabla = !empty($notas_separadas['peticion'])
                            ? '<p class="text-xs italic text-slate-400 mt-1">' . htmlspecialchars($notas_separadas['peticion'], ENT_QUOTES, 'UTF-8') . '</p>'
                            : '';
                        $notas_tabla = ($huespedes_tabla || $peticion_tabla) ? $huespedes_tabla . $peticion_tabla : '<span class="text-slate-300 italic">Ninguna</span>';

                        $id_reserva = (int) $reserva['cod_res'];
                        $notas_seguras = htmlspecialchars($notas_db, ENT_QUOTES, 'UTF-8');
                        $hab_segura = htmlspecialchars($habitacion, ENT_QUOTES, 'UTF-8');

                        echo '<tr class="hover:bg-slate-50 transition-colors" data-reserva-id="' . $id_reserva . '">';
                        echo '<td class="py-5 px-4 align-middle font-semibold text-slate-800">' . htmlspecialchars($reserva['nom_usu']) . '</td>';
                        echo '<td class="py-5 px-4 align-middle text-slate-600 font-bold">' . htmlspecialchars($habitacion) . '</td>';
                        echo '<td class="py-5 px-4 align-middle text-slate-500"><div class="flex items-center gap-2 text-xs font-bold"><span>' . htmlspecialchars($fecha_in) . '</span><span class="material-symbols-outlined text-[15px] text-slate-300">arrow_forward</span><span>' . htmlspecialchars($fecha_out) . '</span></div></td>';
                        echo '<td class="py-5 px-4 align-middle"><span class="' . $color_clase . ' px-3 py-1 rounded-full text-[9px] uppercase tracking-widest">' . htmlspecialchars($estado) . '</span></td>';
                        echo '<td class="py-5 px-4 align-middle max-w-xs">' . $notas_tabla . '</td>';
                        echo '<td class="py-5 px-4 align-middle"><div class="flex justify-center gap-2">';
                        echo "<button onclick=\"abrirEdicion(this)\" class=\"ui-action bg-amber-400 text-white px-5 py-2 rounded-lg hover:bg-amber-500 hover:shadow-lg hover:opacity-95 shadow-sm btn-editar-reserva\" title=\"Editar Reserva\" data-id=\"$id_reserva\" data-estado=\"" . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . "\" data-habitacion=\"$hab_segura\" data-notas=\"$notas_seguras\"><span class=\"material-symbols-outlined text-sm\">edit</span></button>";
                        echo "<button onclick=\"confirmarEliminacion($id_reserva)\" class=\"ui-action bg-red-500 text-white px-5 py-2 rounded-lg hover:bg-red-600 hover:shadow-lg hover:opacity-95 shadow-sm\" title=\"Eliminar Reserva\"><span class=\"material-symbols-outlined text-sm\">delete</span></button>";
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="p-4 text-center text-slate-400">No hay reservas registradas en el sistema.</td></tr>';
                }

                // Mejora: listado para searchable select.
                $sql_huespedes_reserva = 'SELECT id_usu, nom_usu, corr_usu FROM usuario ORDER BY nom_usu ASC';
                $resultado_huespedes_reserva = $conexion->query($sql_huespedes_reserva);
                $huespedes_reserva = [];
                if ($resultado_huespedes_reserva && $resultado_huespedes_reserva->num_rows > 0) {
                    while ($huesped_reserva = $resultado_huespedes_reserva->fetch_assoc()) {
                        $huespedes_reserva[] = $huesped_reserva;
                    }
                }

                // Mejora: selector de habitaciones solo disponibles.
                $sql_habitaciones_disponibles = "SELECT cod_hab, num_hab, tipo_hab FROM habitacion WHERE est_hab = 'Disponible' ORDER BY num_hab ASC";
                $resultado_hab_disponibles = $conexion->query($sql_habitaciones_disponibles);
                $habitaciones_disponibles = [];
                if ($resultado_hab_disponibles && $resultado_hab_disponibles->num_rows > 0) {
                    while ($hab = $resultado_hab_disponibles->fetch_assoc()) {
                        $habitaciones_disponibles[] = $hab;
                    }
                }
                ?>
            </tbody>
        </table>

        <div id="noResultados" class="hidden text-center py-10 text-slate-400 text-sm font-bold">
            No se encontraron huéspedes con ese nombre.
        </div>
    </div>

    <div id="modalReserva" class="modal-reserva-shell hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="modal-reserva-card bg-white rounded-2xl shadow-2xl w-full max-w-[680px] max-h-[92vh] overflow-y-auto border border-slate-100">
            <div class="px-7 pt-7 pb-2">
                <h4 class="font-black text-heading text-xl">Registrar Reserva</h4>
                <p class="text-[12px] text-slate-400 font-semibold mt-1">Completa el flujo en tres grupos para registrar la estancia.</p>
            </div>

            <form id="formCrearReserva" action="../../controladores/guardar_reserva.php" method="POST" class="px-7 pb-7 space-y-6">
                <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-3">
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide">Huésped</label>
                    <div id="contenedorBuscadorHuespedReserva" class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">person_search</span>
                        <input type="search" id="buscadorHuespedReserva" oninput="filtrarHuespedesReservaPremium()" placeholder="Buscar por nombre o correo" class="reserva-field w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm font-semibold outline-none">
                    </div>
                    <input type="hidden" id="tipo_huesped" name="tipo_huesped" required>
                    <input type="hidden" id="nuevo_nombre_hidden" name="nuevo_nombre" value="">
                    <input type="hidden" id="nuevo_correo_hidden" name="nuevo_correo" value="">
                    <div id="huespedSeleccionadoReserva" class="hidden items-center justify-between gap-3 rounded-xl border border-primary/10 bg-primary/5 px-4 py-3">
                        <div>
                            <p id="huespedSeleccionadoNombre" class="text-sm font-black text-heading leading-tight"></p>
                            <p id="huespedSeleccionadoCorreo" class="text-[11px] font-semibold text-slate-400 mt-0.5"></p>
                        </div>
                        <button type="button" onclick="limpiarHuespedSeleccionado()" class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 transition-colors font-black">×</button>
                    </div>
                    <div id="listaHuespedesReserva" class="max-h-44 overflow-y-auto rounded-xl border border-slate-200 bg-white divide-y divide-slate-100"></div>
                    <div class="flex justify-end">
                        <button type="button" onclick="abrirSubModalNuevoHuesped()" class="ui-action text-primary text-xs font-black tracking-wide px-3 py-2 rounded-lg hover:bg-primary/10">+ Nuevo Huésped</button>
                    </div>
                </div>

                <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-4">
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide">Estancia</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-2">Check-in</label>
                            <input type="date" name="fecha_in" required class="reserva-field w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-2">Check-out</label>
                            <input type="date" name="fecha_out" required class="reserva-field w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none">
                        </div>
                    </div>
                    <div class="relative">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-2">Cantidad de Huéspedes</label>
                        <input type="hidden" id="cantAdultosReserva" name="cant_adultos" value="1">
                        <input type="hidden" id="cantNinosReserva" name="cant_ninos" value="0">
                        <button type="button" onclick="togglePopoverHuespedesAdmin()" class="reserva-field w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none flex items-center justify-between">
                            <span id="resumenHuespedesAdmin">1 Adulto, 0 Niños</span>
                            <span class="material-symbols-outlined text-slate-400 text-[18px]">expand_more</span>
                        </button>
                        <div id="popoverHuespedesAdmin" class="hidden absolute left-0 right-0 top-[72px] z-[75] bg-white border border-slate-200 rounded-xl shadow-2xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-700">Adultos</span>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="cambiarHuespedesAdmin('adultos', -1)" class="w-8 h-8 rounded-full border border-slate-200 font-black">-</button>
                                    <span id="contadorAdultosAdmin" class="w-6 text-center font-black">1</span>
                                    <button type="button" onclick="cambiarHuespedesAdmin('adultos', 1)" class="w-8 h-8 rounded-full bg-primary text-white font-black">+</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-700">Niños</span>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="cambiarHuespedesAdmin('ninos', -1)" class="w-8 h-8 rounded-full border border-slate-200 font-black">-</button>
                                    <span id="contadorNinosAdmin" class="w-6 text-center font-black">0</span>
                                    <button type="button" onclick="cambiarHuespedesAdmin('ninos', 1)" class="w-8 h-8 rounded-full bg-primary text-white font-black">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-4">
                    <label class="block text-[11px] font-semibold text-slate-500 tracking-wide">Habitacion</label>
                    <select name="id_habitacion" id="selectorHabitacionDisponible" required class="reserva-field w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none">
                        <option value="" selected disabled>Seleccionar habitacion disponible...</option>
                        <?php foreach ($habitaciones_disponibles as $hab): ?>
                            <option value="<?php echo (int) $hab['cod_hab']; ?>">
                                Habitacion <?php echo (int) $hab['num_hab']; ?> - <?php echo htmlspecialchars($hab['tipo_hab'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-2">Peticiones Especiales (Opcional)</label>
                        <textarea name="notas_reserva" rows="3" placeholder="Ej: alergias, cuna, piso alto..." class="reserva-field w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none resize-none"></textarea>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-1">
                    <button type="button" onclick="cerrarModalReserva()" class="ui-action flex-1 text-slate-500 font-semibold py-3 rounded-xl hover:bg-slate-100">Cancelar</button>
                    <button type="submit" class="ui-action flex-1 bg-primary text-white font-bold py-3 rounded-xl shadow-md hover:shadow-lg">Guardar Reserva</button>
                </div>
            </form>
        </div>
    </div>

    <div id="subModalNuevoHuesped" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-[8px]">
        <div class="bg-white w-full max-w-sm rounded-2xl border border-slate-100 shadow-2xl p-5">
            <h5 class="text-base font-black text-heading mb-1">Nuevo Huésped</h5>
            <p class="text-xs text-slate-400 font-semibold mb-4">Registra nombre y correo para continuar.</p>
            <div class="space-y-3">
                <input type="text" id="nuevo_nombre" name="nuevo_nombre" placeholder="Nombre completo" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none">
                <input type="email" id="nuevo_correo" name="nuevo_correo" placeholder="Correo electronico" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="cerrarSubModalNuevoHuesped()" class="ui-action text-slate-500 font-semibold px-3 py-2 rounded-lg hover:bg-slate-100">Cancelar</button>
                <button type="button" onclick="confirmarNuevoHuespedEnModal()" class="ui-action bg-primary text-white font-bold px-4 py-2 rounded-lg shadow-sm hover:shadow-md">Usar en reserva</button>
            </div>
        </div>
    </div>

    <div id="modalEditarReserva" class="modal-editar-reserva-shell hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="modal-editar-reserva-card bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="bg-slate-50 px-6 py-5 border-b border-slate-100 flex justify-between items-center">
                <h4 class="font-black text-heading">Editar Reserva</h4>
            </div>

            <form id="formEditarReserva" action="../../controladores/editar_reserva.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="cod_res" id="edit_cod_res">

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Asignar Habitacion</label>
                    <input type="text" name="habitacion" id="edit_habitacion" placeholder="Ej: 5 (Codigo de BD)" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm font-bold text-slate-600 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Estado</label>
                    <select name="estado" id="edit_estado" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm font-bold text-slate-600 outline-none">
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="En Casa">En Casa</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Peticiones Especiales</label>
                    <textarea name="notas" id="edit_notas" rows="3" class="reserva-field w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm font-bold text-slate-600 outline-none resize-none"></textarea>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-100 mt-6">
                    <button type="button" onclick="cerrarModalEditar()" class="ui-action flex-1 bg-white border border-slate-200 text-slate-500 font-bold py-3 rounded-lg hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="ui-action flex-1 bg-amber-400 text-white font-bold py-3 rounded-lg shadow-md hover:bg-amber-500 hover:shadow-lg">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const HUESPEDES_RESERVA = <?php echo json_encode($huespedes_reserva, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const huespedesAdminState = { adultos: 1, ninos: 0 };

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
                btn.innerHTML = `<p class="text-sm font-semibold text-slate-700">${h.nom_usu}</p><p class="text-[11px] text-slate-400">${h.corr_usu}</p>`;
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

            document.getElementById('edit_cod_res').value = id;
            document.getElementById('edit_estado').value = estado;
            document.getElementById('edit_habitacion').value = (habitacion === 'Sin asignar') ? '' : habitacion;
            document.getElementById('edit_notas').value = (notas === 'Ninguna' ? '' : notas);

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
