<?php
// ARCHIVO: interfaz/admin/index_ad.php

require_once __DIR__ . '/../../includes/sesion_seguridad.php';
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario']) ||
    !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2], true)
) {
    header("Location: interfaz/admin/interfaz_usu.php");
    exit();
}

$firma_actor_panel = hash_hmac(
    'sha256',
    ((int) $_SESSION['emp_auth']['id_usuario']) . '|' . ((int) $_SESSION['emp_auth']['rol_usuario']),
    'software_hotel_actor_panel_v1'
);

require_once '../../configuracion/conexion.php';
require_once '../../configuracion/permiso.php';
/** @var mysqli $conexion */
require_once 'componentes_ad/head.php';

$permisos_usuario = [];
$stmt_permisos = $conexion->prepare('SELECT rp.cod_permiso FROM rol_permiso rp WHERE rp.cod_rol = ?');
$rol_sesion = (int) $_SESSION['emp_auth']['rol_usuario'];
if ($stmt_permisos) {
    $stmt_permisos->bind_param('i', $rol_sesion);
    $stmt_permisos->execute();
    $resultado_permisos = $stmt_permisos->get_result();
    while ($permiso = $resultado_permisos->fetch_assoc()) {
        $permisos_usuario[] = $permiso['cod_permiso'];
    }
}

// Mantiene visible el panel mientras se instala la migración de permisos.
if ($permisos_usuario === []) {
    $permisos_usuario = $rol_sesion === 1
        ? ['dashboard.ver', 'reservas.ver', 'roles.ver', 'operaciones.ver', 'finanzas.ver', 'configuracion.ver', 'experiencias.ver', 'experiencias.gestionar']
        : ['dashboard.ver', 'reservas.ver', 'operaciones.ver', 'finanzas.ver', 'experiencias.ver', 'experiencias.gestionar'];
}

?>

<body class="bg-surface font-body text-heading antialiased flex min-h-screen overflow-hidden">

    <?php 
    require_once 'componentes_ad/sidebar.php'; 
    require_once 'componentes_ad/modal_tarea.php';
    ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto no-scrollbar">
        
        <?php require_once 'componentes_ad/topbar.php'; ?>

        <div class="p-10 space-y-10">
            <?php
            require_once 'secciones_ad/dashboard.php';
            require_once 'secciones_ad/reservas.php';
            require_once 'secciones_ad/roles.php';
            require_once 'secciones_ad/experiencias.php';
            require_once 'secciones_ad/operaciones.php';
            require_once 'secciones_ad/finanzas.php';
            require_once 'secciones_ad/configuracion.php';
            require_once 'secciones_ad/panel_logs.php'
            ?>
        </div>
    </main>

    <?php require_once 'componentes_ad/aside_tareas_admin.php'; ?>

    <div id="modalLogout" class="hidden fixed inset-0 bg-[#03271B]/60 backdrop-blur-md z-[100] flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="cajaLogout">
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl">logout</span>
                </div>
                <h4 class="text-xl font-black text-heading mb-2">¿Cerrar Sesión?</h4>
                <p class="text-sm text-primary/70">Estás a punto de salir del sistema. Asegúrate de haber guardado todos tus cambios.</p>
            </div>
            
            <div class="flex border-t border-primary/10">
                <button onclick="cerrarModalLogout()" class="flex-1 px-6 py-4 text-sm font-bold text-primary/60 hover:bg-[#f4f8f6] transition-colors border-r border-primary/10">
                    Seguir trabajando
                </button>
                <a href="../../controladores/logout.php" class="flex-1 px-6 py-4 text-sm font-black text-red-500 hover:bg-red-50 transition-colors text-center">
                    Sí, salir ahora
                </a>
            </div>
        </div>
    </div>

    <div id="modalMantenimientoHousekeeping" onclick="cerrarModalMantenimiento(event)" class="maintenance-modal-overlay fixed inset-0 bg-[#03271B]/40 backdrop-blur-sm z-[90] flex justify-center items-center p-4">
        <div id="cardMantenimientoHousekeeping" onclick="event.stopPropagation()" class="maintenance-modal-card bg-white p-8 rounded-2xl w-full max-w-md shadow-2xl border border-primary/10">
            <div class="flex justify-between items-start gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-400 mb-2">Mantenimiento</p>
                    <h3 id="tituloMantenimientoHousekeeping" class="text-xl font-black text-heading">Habitación</h3>
                </div>
                <button onclick="cerrarModalMantenimiento()" class="text-primary/60 hover:text-red-500 font-black text-xl transition-colors" type="button">&times;</button>
            </div>

            <div id="prioridadMantenimientoHousekeeping" class="inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest mb-5"></div>

            <div class="bg-[#f4f8f6] border border-primary/10 rounded-xl p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-primary/60 mb-2">Motivo registrado</p>
                <p id="motivoMantenimientoHousekeeping" class="text-sm font-bold text-primary/80 leading-relaxed whitespace-pre-line"></p>
            </div>
        </div>
    </div>

    <?php
    $ayudaSistemaRol = 'admin';
    // borre el system-help.php
    ?>
    

    <script>
        const ROL_USUARIO = <?php echo isset($_SESSION['emp_auth']['rol_usuario']) ? (int) $_SESSION['emp_auth']['rol_usuario'] : 1; ?>;
        const PERMISOS_USUARIO = <?php echo json_encode($permisos_usuario, JSON_UNESCAPED_UNICODE); ?>;
        const ID_USUARIO_ACTIVO = <?php echo isset($_SESSION['emp_auth']['id_usuario']) ? (int) $_SESSION['emp_auth']['id_usuario'] : 0; ?>;
        const FIRMA_USUARIO_ACTIVO = "<?php echo htmlspecialchars($firma_actor_panel, ENT_QUOTES, 'UTF-8'); ?>";
        const CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
    </script>

    <script src="js_ad/admin.js?v=2026-10-01-admin-theme"></script>
<?php if (!empty($_SESSION['emp_auth'])): ?>
    <?php require_once __DIR__ . '/../../includes/timeOut.php'; ?>
    <script src="../../assets/js/inactividad.js?v=2"></script>
<?php endif; ?>
</body>
</html>