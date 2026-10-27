<?php
require_once __DIR__ . '/../../includes/sesion_seguridad.php';

require_once __DIR__ . '/../../configuracion/conexion.php';
require_once __DIR__ . '/../../src/Usuario/PortalRepository.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

$portalRepository = new \App\Usuario\PortalRepository();
$reservasUsuario = $portalRepository->fetchReservationHistory($conexion, $idUsuarioActual);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aurora | Mi Estancia</title>
    <link rel="stylesheet" href="../../assets/css/estilos_base.css">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800">
    <?php include __DIR__ . '/../../includes/translate.php'; ?>
    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b border-slate-200">
        <div class="text-xl font-bold text-teal-700 flex items-center gap-2">
            <span class="material-symbols-outlined">hotel</span>
            Hotel Aurora
        </div>
        <div class="flex items-center gap-4">
            <span class="font-medium text-slate-600">Hola, <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></span>
            <a href="../../interfaz_usu.php" class="text-red-500 hover:bg-red-50 px-4 py-2 rounded-xl transition flex items-center gap-2 font-bold text-sm">
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
                <div class="flex items-center gap-2">
                    <button type="button" onclick="abrirModalAsistencia()" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700 transition shadow-sm">
                        <span class="material-symbols-outlined text-sm">room_service</span>
                        Pedir Servicio a Habitación
                    </button>
                    <a href="../../interfaz_usu.php" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700 transition">
                        <span class="material-symbols-outlined text-sm">add_circle</span>
                        Nueva reserva
                    </a>
                </div>
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
                            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="verComprobanteReserva(<?php echo (int) $reserva['cod_res']; ?>)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                        <span class="material-symbols-outlined text-base">receipt_long</span>
                                        Comprobante / Factura
                                    </button>
                                    <?php if (in_array($estado, ['Confirmada', 'En Casa'], true)): ?>
                                        <button type="button" onclick="abrirModalAsistencia(<?php echo (int) $reserva['cod_res']; ?>)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 border border-teal-200 text-xs font-bold text-teal-700 hover:bg-teal-100 transition">
                                            <span class="material-symbols-outlined text-base">room_service</span>
                                            Pedir Servicio
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if (in_array($estado, ['Pendiente', 'Confirmada'], true)): ?>
                                    <button type="button" onclick="confirmarCancelarReserva(<?php echo (int) $reserva['cod_res']; ?>)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 transition">
                                        <span class="material-symbols-outlined text-base">cancel</span>
                                        Cancelar estancia
                                    </button>
                                <?php endif; ?>
                            </div>
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
    <?php require_once __DIR__ . '/../../includes/timeOut.php'; ?>
    <script src="../../assets/js/inactividad.js?v=3"></script>
    <!-- MODAL: SOLICITAR ASISTENCIA / SERVICIO A LA HABITACIÓN -->
    <div id="modalAsistencia" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 text-teal-700 font-bold">
                    <span class="material-symbols-outlined text-2xl">room_service</span>
                    <h3 class="text-lg font-black text-slate-800">Servicio a la Habitación</h3>
                </div>
                <button type="button" onclick="cerrarModalAsistencia()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>
            <form id="formAsistencia" onsubmit="enviarSolicitudAsistencia(event)" class="mt-4 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="accion" value="solicitar_asistencia">
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-1">¿Qué necesitas?</label>
                    <select name="tipo_servicio" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <option value="Toallas extra">Toallas limpias extra</option>
                        <option value="Limpieza de habitación">Limpieza / Retoque de habitación</option>
                        <option value="Almohadas y cobijas">Almohadas / Cobijas extra</option>
                        <option value="Mantenimiento / Avería">Mantenimiento / Avería (Luz, agua, baño)</option>
                        <option value="Asistencia técnica (TV/WiFi)">Asistencia TV / WiFi</option>
                        <option value="Otro servicio">Otro requerimiento especial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-1">Detalles o instrucciones adicionales</label>
                    <textarea name="detalles" rows="3" placeholder="Ej: Por favor dejar toallas en el baño..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-600"></textarea>
                </div>
                <div id="mensajeAsistencia" class="hidden text-xs font-bold p-3 rounded-xl"></div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModalAsistencia()" class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Cancelar</button>
                    <button type="submit" id="btnEnviarAsistencia" class="flex-1 py-3 rounded-xl bg-teal-600 text-white font-bold hover:bg-teal-700 shadow-sm">Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: VER COMPROBANTE / FACTURA -->
    <div id="modalComprobante" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xl w-full p-8 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 text-teal-700 font-bold">
                    <span class="material-symbols-outlined text-2xl">receipt_long</span>
                    <h3 class="text-lg font-black text-slate-800">Comprobante de Estancia</h3>
                </div>
                <button type="button" onclick="cerrarModalComprobante()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>
            <div id="contenidoComprobante" class="mt-5 space-y-4 text-sm text-slate-700">
                <!-- Se llena con JavaScript -->
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex gap-3">
                <button type="button" onclick="cerrarModalComprobante()" class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">Cerrar</button>
                <button type="button" onclick="window.print()" class="flex-1 py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 shadow-sm flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span> Imprimir
                </button>
            </div>
        </div>
    </div>

    <script>
        const CSRF_PORTAL = <?php echo json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function abrirModalAsistencia(codRes) {
            const modal = document.getElementById('modalAsistencia');
            modal.classList.remove('hidden');
            const msg = document.getElementById('mensajeAsistencia');
            msg.className = 'hidden';
            msg.textContent = '';
        }

        function cerrarModalAsistencia() {
            document.getElementById('modalAsistencia').classList.add('hidden');
        }

        async function enviarSolicitudAsistencia(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('btnEnviarAsistencia');
            const msg = document.getElementById('mensajeAsistencia');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const formData = new FormData(form);
                const resp = await fetch('../../controladores/portal_huesped.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (!resp.ok || data.status !== 'exito') {
                    throw new Error(data.mensaje || 'Error al enviar la solicitud.');
                }
                msg.className = 'text-xs font-bold p-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200';
                msg.textContent = data.mensaje;
                form.reset();
                setTimeout(() => cerrarModalAsistencia(), 2500);
            } catch (err) {
                msg.className = 'text-xs font-bold p-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200';
                msg.textContent = err.message;
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enviar Solicitud';
            }
        }

        async function confirmarCancelarReserva(codRes) {
            if (!confirm(`¿Estás seguro de que deseas cancelar la reserva #${codRes}? Esta acción liberará la habitación.`)) {
                return;
            }
            try {
                const formData = new FormData();
                formData.set('accion', 'cancelar_reserva');
                formData.set('cod_res', codRes);
                formData.set('csrf_token', CSRF_PORTAL);

                const resp = await fetch('../../controladores/portal_huesped.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (!resp.ok || data.status !== 'exito') {
                    throw new Error(data.mensaje || 'No se pudo cancelar la reserva.');
                }
                alert(data.mensaje);
                window.location.reload();
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        async function verComprobanteReserva(codRes) {
            const modal = document.getElementById('modalComprobante');
            const cont = document.getElementById('contenidoComprobante');
            cont.innerHTML = '<p class="text-center text-slate-400 py-4">Cargando comprobante...</p>';
            modal.classList.remove('hidden');

            try {
                const resp = await fetch(`../../controladores/portal_huesped.php?accion=obtener_factura&cod_res=${codRes}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (!resp.ok || data.status !== 'exito' || !data.factura) {
                    throw new Error(data.mensaje || 'No hay comprobante disponible.');
                }
                const fac = data.factura;
                const total = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(fac.tot_fac || 0);

                cont.innerHTML = `
                    <div class="border-b border-slate-100 pb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-black text-xl text-slate-800">Hotel Aurora</h4>
                                <p class="text-xs text-slate-500">Comprobante Oficial de Alojamiento</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block bg-teal-50 text-teal-800 border border-teal-200 px-3 py-1 rounded-full text-xs font-black">
                                    ${fac.folio || 'FAC-' + codRes}
                                </span>
                                <p class="text-[11px] text-slate-400 mt-1">${fac.creado_en || ''}</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-2xl">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Titular</span>
                            <p class="font-bold text-slate-800">${fac.nom_usu || ''}</p>
                            <p class="text-xs text-slate-500">${fac.corr_usu || ''}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Método de Pago</span>
                            <p class="font-bold text-slate-800">${fac.metodo_pago_fac || 'Recepción'}</p>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Aprobado / Pagado</span>
                        </div>
                    </div>
                    <div class="border-t border-b border-slate-100 py-3 flex justify-between items-center text-base font-bold">
                        <span>Total Pagado</span>
                        <span class="text-teal-700 font-black text-xl">${total}</span>
                    </div>
                    <p class="text-[11px] text-slate-400 text-center italic">Gracias por hospedarte en Hotel Aurora. Este documento es un comprobante de servicio.</p>
                `;
            } catch (err) {
                cont.innerHTML = `<div class="p-4 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold">
                    ${err.message}
                </div>`;
            }
        }

        function cerrarModalComprobante() {
            document.getElementById('modalComprobante').classList.add('hidden');
        }
    </script>
</body>
</html>
