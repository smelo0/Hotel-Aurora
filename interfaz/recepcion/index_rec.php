<?php
// ARCHIVO: interfaz/recepcion/index_rec.php
// Panel del recepcionista: mismo look del panel del gerente, pero solo con lo que se usa en el mostrador.

require_once __DIR__ . '/../../includes/sesion_seguridad.php';
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

const ROL_RECEPCIONISTA = 7;

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario']) ||
    (int) $_SESSION['emp_auth']['rol_usuario'] !== ROL_RECEPCIONISTA
) {
    header('Location: /Hotel-Aurora/interfaz_usu.php');
    exit();
}

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

$firma_actor_panel = hash_hmac(
    'sha256',
    ((int) $_SESSION['emp_auth']['id_usuario']) . '|' . ((int) $_SESSION['emp_auth']['rol_usuario']),
    'software_hotel_actor_panel_v1'
);

require_once '../../configuracion/conexion.php';
require_once '../../configuracion/permiso.php';
require_once '../../vendor/autoload.php';
require_once '../../src/RoomService/RoomServiceSchemaMigrator.php';
/** @var mysqli $conexion */
\App\Reserva\FinalizadorReservas::ejecutar($conexion);
/** @var mysqli $conexion */
\App\RoomService\RoomServiceSchemaMigrator::asegurar($conexion);
\App\Billing\FacturaHistorial::asegurarEsquema($conexion);

// Permisos reales del rol (los edita el gerente en "Roles y Permisos"); el menú y las acciones se ajustan solos.
$permisos_usuario = [];
$rol_sesion = (int) $_SESSION['emp_auth']['rol_usuario'];
$stmt_permisos = $conexion->prepare('SELECT cod_permiso FROM rol_permiso WHERE cod_rol = ?');
if ($stmt_permisos) {
    $stmt_permisos->bind_param('i', $rol_sesion);
    if ($stmt_permisos->execute()) {
        $resultado_permisos = $stmt_permisos->get_result();
        while ($permiso = $resultado_permisos->fetch_assoc()) {
            $permisos_usuario[] = $permiso['cod_permiso'];
        }
    }
    $stmt_permisos->close();
}

// Dirección de este panel: la usan la búsqueda, la paginación y el refresco en vivo de Reservas.
$panelReservasUrl = 'index_rec.php';

if (($_GET['reservas_fragmento'] ?? '') === '1') {
    if (!usuario_tiene_permiso($conexion, 'reservas.ver')) {
        http_response_code(403);
        exit();
    }
    require __DIR__ . '/../admin/secciones_ad/reservas.php';
    exit();
}

$puedeRoomService = usuario_tiene_permiso($conexion, 'roomservice.estado');
require_once 'componentes_rec/head.php';
?>

<body class="bg-surface font-body text-heading antialiased flex min-h-screen overflow-hidden">

    <?php
    require_once 'componentes_rec/sidebar.php';
    require_once __DIR__ . '/../admin/componentes_ad/modal_tarea.php';
    ?>

    <main id="adminMain" tabindex="-1" class="ml-64 flex-1 h-screen overflow-y-auto">

        <?php require_once 'componentes_rec/topbar.php'; ?>

        <div class="p-10 space-y-10">
            <?php
            require_once 'secciones_rec/inicio.php';
            require_once __DIR__ . '/../admin/secciones_ad/reservas.php';
            require_once __DIR__ . '/../admin/secciones_ad/operaciones.php';
            require_once 'secciones_rec/room_service.php';
            require_once 'secciones_rec/facturas.php';
            ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/../admin/componentes_ad/aside_tareas_admin.php'; ?>

    <div id="modalLogout" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="cajaLogout">
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl">logout</span>
                </div>
                <h4 class="text-xl font-black text-slate-800 mb-2">¿Cerrar Sesión?</h4>
                <p class="text-sm text-slate-500">Estás a punto de salir del sistema. Asegúrate de haber guardado todos tus cambios.</p>
            </div>

            <div class="flex border-t border-slate-100">
                <button onclick="cerrarModalLogout()" class="flex-1 px-6 py-4 text-sm font-bold text-slate-400 hover:bg-slate-50 transition-colors border-r border-slate-100">
                    Seguir trabajando
                </button>
                <a href="../../controladores/logout.php" class="flex-1 px-6 py-4 text-sm font-black text-red-500 hover:bg-red-50 transition-colors text-center">
                    Sí, salir ahora
                </a>
            </div>
        </div>
    </div>

    <!-- Estado de la habitación (misma ventana del panel del gerente) -->
    <div id="modalMantenimientoHousekeeping" onclick="cerrarModalMantenimiento(event)" class="maintenance-modal-overlay fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[90] flex justify-center items-center p-4">
        <div id="cardMantenimientoHousekeeping" onclick="event.stopPropagation()" class="maintenance-modal-card bg-white p-8 rounded-2xl w-full max-w-md shadow-2xl border border-slate-100">
            <div class="flex justify-between items-start gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-primary mb-2">Habitación</p>
                    <h3 id="tituloMantenimientoHousekeeping" class="text-xl font-black text-heading">Habitación</h3>
                </div>
                <button onclick="cerrarModalMantenimiento()" class="text-slate-400 hover:text-red-500 font-black text-xl transition-colors" type="button">&times;</button>
            </div>

            <form id="formHousekeeping" class="space-y-4">
                <input type="hidden" id="idHabitacionHousekeeping">
                <div id="reservaHabitacionHousekeeping" class="hidden rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800"></div>
                <div>
                    <p id="estadoActualHousekeeping" class="mb-1 text-xs font-bold text-slate-500"></p>
                    <label for="estadoHousekeeping" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Estado de la habitación</label>
                    <select id="estadoHousekeeping" name="estado" required class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm font-bold text-slate-700 focus:ring-1 focus:ring-primary">
                        <option value="Limpio">Limpio</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupada">Ocupada</option>
                        <option value="Sucia">Sucia</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
                </div>
                <div id="camposMantenimientoHousekeeping" class="hidden space-y-4">
                    <div>
                        <label for="prioridadHousekeeping" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Prioridad</label>
                        <select id="prioridadHousekeeping" name="prioridad_mantenimiento" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm font-bold text-slate-700 focus:ring-1 focus:ring-primary">
                            <option value="Urgente">Urgente</option>
                            <option value="Importante">Importante</option>
                            <option value="No urgente">No urgente</option>
                        </select>
                    </div>
                    <div>
                        <label for="motivoHousekeeping" class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-500">Motivo del mantenimiento</label>
                        <textarea id="motivoHousekeeping" name="descripcion_mantenimiento" rows="3" maxlength="255" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 focus:ring-1 focus:ring-primary" placeholder="Describe qué requiere atención"></textarea>
                    </div>
                </div>
                <p id="errorHousekeeping" class="hidden text-sm font-bold text-red-600" role="alert"></p>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModalMantenimiento()" class="flex-1 rounded-lg py-3 font-bold text-slate-500 hover:bg-slate-100">Cancelar</button>
                    <button id="guardarHousekeeping" type="submit" class="flex-1 rounded-lg bg-primary py-3 font-bold text-white shadow hover:brightness-110">Guardar estado</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const ROL_USUARIO = <?php echo $rol_sesion; ?>;
        const PERMISOS_USUARIO = <?php echo json_encode($permisos_usuario, JSON_UNESCAPED_UNICODE); ?>;
        const ID_USUARIO_ACTIVO = <?php echo (int) $_SESSION['emp_auth']['id_usuario']; ?>;
        const FIRMA_USUARIO_ACTIVO = "<?php echo htmlspecialchars($firma_actor_panel, ENT_QUOTES, 'UTF-8'); ?>";
        const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
        // admin.js refresca la tabla de Reservas contra esta dirección.
        window.PANEL_URL = 'index_rec.php';
        // Si en este navegador quedó guardada una sección del panel del gerente, se descarta.
        try {
            if (!['dashboard', 'reservas', 'operaciones', 'rsrecepcion', 'facturas'].includes(sessionStorage.getItem('hotel_admin_seccion'))) {
                sessionStorage.removeItem('hotel_admin_seccion');
            }
        } catch (_) {}
    </script>

    <script src="../admin/js_ad/admin.js?v=25"></script>
    <script src="../admin/js_ad/scroll_admin.js?v=1"></script>
    <?php if ($puedeRoomService): ?>
    <script src="../empleado/js/room_service.js?v=1"></script>
    <?php endif; ?>

    <?php
    $ayudaSistemaRol = 'recepcion';
    require_once __DIR__ . '/../../includes/system_help.php';
    ?>

    <?php require_once __DIR__ . '/../../includes/timeOut.php'; ?>
    <script src="../../assets/js/inactividad.js?v=10" onerror="this.onerror=null; this.src='/Hotel-Aurora/assets/js/inactividad.js?v=10';"></script>
</body>
</html>