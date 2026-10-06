<?php
require_once __DIR__ . '/../../includes/sesion_seguridad.php';
if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario'])
    || !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2, 3, 4, 5], true)
) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../configuracion/conexion.php';
require_once __DIR__ . '/../../configuracion/permiso.php';
$puedeEditarHabitaciones = usuario_tiene_permiso($conexion, 'operaciones.editar');
$conexion->close();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/componentes/head.php'; ?>
</head>
<body class="empleado-body text-slate-800 flex h-screen overflow-hidden">

    <!-- Sidebar lateral -->
    <?php include __DIR__ . '/componentes/sidebar.php'; ?>

    <!-- Panel lateral de tareas, antes del área desplazable para mantener su barra al extremo derecho. -->
    <?php include __DIR__ . '/componentes/aside_tareas.php'; ?>

    <!-- Contenedor central -->
    <div id="empleadoMain" class="empleado-main flex-1 flex flex-col h-full overflow-hidden min-w-0">
        
        <!-- Topbar -->
        <?php include __DIR__ . '/componentes/topbar.php'; ?>

        <!-- Área de contenidos -->
        <main class="flex-1 overflow-y-auto px-5 py-5 md:px-7 md:py-6 relative">
            
            <?php include __DIR__ . '/secciones/inicio.php'; ?>
            <?php include __DIR__ . '/secciones/habitaciones.php'; ?>
            <?php include __DIR__ . '/secciones/huespedes.php'; ?>

        </main>
    </div>

    <!-- Modales -->
    <?php include __DIR__ . '/componentes/modal_tarea.php'; ?>
    <?php include __DIR__ . '/componentes/modal_habitacion.php'; ?>

    <!-- Variables globales -->
    <script>
        const PUEDE_EDITAR_HABITACIONES = <?php echo $puedeEditarHabitaciones ? 'true' : 'false'; ?>;
        const CSRF_TOKEN = <?php echo json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <script src="js/panel.js?v=4"></script>
    <?php
    $ayudaSistemaRol = 'empleado';
    require_once __DIR__ . '/../../includes/system_help.php';
    ?>
    <?php require_once __DIR__ . '/../../includes/timeOut.php'; ?>
    <script src="../../assets/js/inactividad.js?v=3"></script>
</body>
</html>