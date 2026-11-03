<?php
// ARCHIVO: interfaz/admin/index_ad.php

require_once __DIR__ . '/../../includes/sesion_seguridad.php';
header('Content-Type: text/html; charset=utf-8');

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario']) ||
    (int) $_SESSION['emp_auth']['rol_usuario'] !== 1
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
require_once '../../src/RoomService/RoomServiceSchemaMigrator.php';
/** @var mysqli $conexion */
// Room Service: crea tablas y permisos la primera vez (no hace nada si ya existen).
\App\RoomService\RoomServiceSchemaMigrator::asegurar($conexion);
require_once 'componentes_ad/head.php';

$permisos_usuario = [];
$rol_sesion = (int) $_SESSION['emp_auth']['rol_usuario'];
$sincronizarPermisosAdmin = $conexion->query(
    'INSERT IGNORE INTO rol_permiso (cod_rol, cod_permiso) SELECT 1, cod_permiso FROM permiso'
);
if (!$sincronizarPermisosAdmin) {
    \App\Logger::registrarLog('ERROR', 'No se pudieron sincronizar todos los permisos del gerente general', [
        'id_usuario' => (int) $_SESSION['emp_auth']['id_usuario'],
        'error_db' => $conexion->error,
    ]);
}

$consultaPermisos = $rol_sesion === 1
    ? 'SELECT cod_permiso FROM permiso'
    : 'SELECT rp.cod_permiso FROM rol_permiso rp WHERE rp.cod_rol = ?';
$stmt_permisos = $conexion->prepare($consultaPermisos);
if ($stmt_permisos) {
    if ($rol_sesion !== 1) {
        $stmt_permisos->bind_param('i', $rol_sesion);
    }
    if ($stmt_permisos->execute()) {
        $resultado_permisos = $stmt_permisos->get_result();
    } else {
        $resultado_permisos = false;
        \App\Logger::registrarLog('ERROR', 'No se pudieron cargar los permisos del usuario administrador', [
            'id_usuario' => (int) $_SESSION['emp_auth']['id_usuario'],
            'error_db' => $stmt_permisos->error,
        ]);
    }
    if ($resultado_permisos instanceof mysqli_result) {
        while ($permiso = $resultado_permisos->fetch_assoc()) {
            $permisos_usuario[] = $permiso['cod_permiso'];
        }
    }
    $stmt_permisos->close();
} else {
    \App\Logger::registrarLog('ERROR', 'No se pudo preparar la carga de permisos del panel administrador', [
        'id_usuario' => (int) $_SESSION['emp_auth']['id_usuario'],
        'error_db' => $conexion->error,
    ]);
}

if ($permisos_usuario === []) {
    $permisos_usuario = ['dashboard.ver', 'reservas.ver', 'roles.ver', 'operaciones.ver', 'finanzas.ver', 'configuracion.ver', 'experiencias.ver', 'experiencias.gestionar', 'habitaciones.ver', 'habitaciones.gestionar', 'roomservice.ver', 'roomservice.gestionar'];
}

if (($_GET['reservas_fragmento'] ?? '') === '1') {
    if (!usuario_tiene_permiso($conexion, 'reservas.ver')) {
        http_response_code(403);
        exit();
    }
    require 'secciones_ad/reservas.php';
    exit();
}

?>

<body class="bg-surface font-body text-heading antialiased flex min-h-screen overflow-hidden">

    <?php 
    require_once 'componentes_ad/sidebar.php'; 
    require_once 'componentes_ad/modal_tarea.php';
    ?>

    <main id="adminMain" tabindex="-1" class="ml-64 flex-1 h-screen overflow-y-auto">
        
        <?php require_once 'componentes_ad/topbar.php'; ?>

        <div class="p-10 space-y-10">
            <?php
            require_once 'secciones_ad/dashboard.php';
            require_once 'secciones_ad/reservas.php';
            require_once 'secciones_ad/roles.php';
            require_once 'secciones_ad/experiencias.php';
            require_once 'secciones_ad/room_service.php';
            require_once 'secciones_ad/habitaciones.php';
            require_once 'secciones_ad/operaciones.php';
            require_once 'secciones_ad/finanzas.php';
            require_once 'secciones_ad/panel_logs.php';
            ?>
        </div>
    </main>

    <?php require_once 'componentes_ad/aside_tareas_admin.php'; ?>

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

    <div id="modalMantenimientoHousekeeping" onclick="cerrarModalMantenimiento(event)" class="maintenance-modal-overlay fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[90] flex justify-center items-center p-4">
        <div id="cardMantenimientoHousekeeping" onclick="event.stopPropagation()" class="maintenance-modal-card bg-white p-8 rounded-2xl w-full max-w-md shadow-2xl border border-slate-100">
            <div class="flex justify-between items-start gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-primary mb-2">Housekeeping</p>
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

    <?php
    $ayudaSistemaRol = 'admin';
    require_once __DIR__ . '/../../includes/system_help.php';
    ?>

    <script>
        const ROL_USUARIO = <?php echo isset($_SESSION['emp_auth']['rol_usuario']) ? (int) $_SESSION['emp_auth']['rol_usuario'] : 1; ?>;
        const PERMISOS_USUARIO = <?php echo json_encode($permisos_usuario, JSON_UNESCAPED_UNICODE); ?>;
        const ID_USUARIO_ACTIVO = <?php echo isset($_SESSION['emp_auth']['id_usuario']) ? (int) $_SESSION['emp_auth']['id_usuario'] : 0; ?>;
        const FIRMA_USUARIO_ACTIVO = "<?php echo htmlspecialchars($firma_actor_panel, ENT_QUOTES, 'UTF-8'); ?>";
        const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
    </script>

 <script src="js_ad/admin.js?v=23" onerror="this.onerror=null; this.src='../../assets/js/admin.js?v=23';"></script>
    <script src="js_ad/scroll_admin.js?v=1"></script>

<?php if (!empty($_SESSION['emp_auth'])): ?>
    <?php require_once __DIR__ . '/../../includes/timeOut.php'; ?>
    <script src="../../assets/js/inactividad.js?v=10" onerror="this.onerror=null; this.src='/Hotel-Aurora/assets/js/inactividad.js?v=10';"></script>
<?php endif; ?>
</body>
</html>