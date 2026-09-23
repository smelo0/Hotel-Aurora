<?php
session_start();
if (!isset($_SESSION['emp_auth'])) {
    header("Location: ../../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include __DIR__ . '/componentes/head.php'; ?>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex h-screen overflow-hidden">

    <!-- Sidebar lateral -->
    <?php include __DIR__ . '/componentes/sidebar.php'; ?>

    <!-- Contenedor central -->
    <div class="flex-1 flex flex-col h-full overflow-hidden min-w-0">
        
        <!-- Topbar -->
        <?php include __DIR__ . '/componentes/topbar.php'; ?>

        <!-- Área de contenidos -->
        <main class="flex-1 overflow-y-auto p-6 md:p-8 relative">
            
            <?php include __DIR__ . '/secciones/inicio.php'; ?>
            <?php include __DIR__ . '/secciones/habitaciones.php'; ?>
            <?php include __DIR__ . '/secciones/limpieza.php'; ?>
            <?php include __DIR__ . '/secciones/huespedes.php'; ?>

        </main>
    </div>

    <!-- Panel lateral derecho de tareas -->
    <?php include __DIR__ . '/componentes/aside_tareas.php'; ?>

    <!-- Modales -->
    <?php include __DIR__ . '/componentes/modal_tarea.php'; ?>
    <?php include __DIR__ . '/componentes/modal_habitacion.php'; ?>

    <!-- Variables globales -->
    <script>
        const ID_USUARIO_ACTIVO = <?php echo (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0); ?>;
        const ROL_USUARIO = <?php echo (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 3); ?>;
        const FIRMA_USUARIO_ACTIVO = <?php echo json_encode(hash_hmac(
            'sha256',
            ((int) ($_SESSION['emp_auth']['id_usuario'] ?? 0)) . '|' . ((int) ($_SESSION['emp_auth']['rol_usuario'] ?? 3)),
            'software_hotel_actor_panel_v1'
        )); ?>;
    </script>

    <script src="js/panel.js"></script>
</body>
</html>