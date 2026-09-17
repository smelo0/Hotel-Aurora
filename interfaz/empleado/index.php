<?php
// interfaz/empleado/index.php

require_once __DIR__ . '/../../includes/sesion_seguridad.php';
header('Content-Type: text/html; charset=utf-8');

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario']) ||
    !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [3, 4, 5], true)
) {
    header("Location: ../loggins/inndex_usu.php");
    exit();
}

$nombre_empleado = $_SESSION['emp_auth']['nombre_usuario']; 
$firma_actor_panel = hash_hmac(
    'sha256',
    ((int) $_SESSION['emp_auth']['id_usuario']) . '|' . ((int) $_SESSION['emp_auth']['rol_usuario']),
    'software_hotel_actor_panel_v1'
);

require_once 'componentes/head.php';
?>

<body class="bg-surface font-body text-heading antialiased flex min-h-screen overflow-hidden">

    <?php 
    require_once 'componentes/modal_habitacion.php';
    require_once 'componentes/modal_tarea.php'; 
    require_once 'componentes/sidebar.php'; 
    ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto no-scrollbar">
        <?php require_once 'componentes/topbar.php'; ?>

        <div class="p-10 space-y-10">
            <?php
            require_once 'secciones/inicio.php';
            require_once 'secciones/habitaciones.php';
            require_once 'secciones/limpieza.php';
            require_once 'secciones/huespedes.php';
            ?>
        </div>
    </main>

    <?php require_once 'componentes/aside_tareas.php'; ?>

    <?php
    $ayudaSistemaRol = 'empleado';
    require_once '../../includes/system_help.php';
    ?>

    <script>
    const ROL_USUARIO = <?php echo (int) $_SESSION['emp_auth']['rol_usuario']; ?>;
    const ID_USUARIO_ACTIVO = <?php echo (int) $_SESSION['emp_auth']['id_usuario']; ?>;
    const FIRMA_USUARIO_ACTIVO = "<?php echo htmlspecialchars($firma_actor_panel, ENT_QUOTES, 'UTF-8'); ?>";
    </script>
    <script src="js/panel.js?v=22"></script>
<?php if (!empty($_SESSION['emp_auth'])): ?>
    <?php require_once __DIR__ . '/../../includes/timeOut.php'; ?>
    <script src="../../assets/js/inactividad.js"></script>
<?php endif; ?>
</body>
</html>
