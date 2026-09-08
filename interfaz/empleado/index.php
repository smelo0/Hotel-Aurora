<?php
// interfaz/empleado/index.php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Constantes globales de configuración (Recomendado mover a config.php)
if (!defined('PANEL_SECRET_KEY')) {
    define('PANEL_SECRET_KEY', 'software_hotel_actor_panel_v1');
}

// Guardia de Autenticación
$auth = $_SESSION['emp_auth'] ?? null;
if (!$auth || !isset($auth['id_usuario'], $auth['rol_usuario']) || !in_array((int)$auth['rol_usuario'], [3, 4, 5], true)) {
    header("Location: ../loggins/index_usu.php");
    exit();
}

// Datos de sesión sanitizados
$id_usuario = (int) $auth['id_usuario'];
$rol_usuario = (int) $auth['rol_usuario'];
$nombre_empleado = $auth['nombre_usuario']; 

// Generación de Firma
$firma_actor_panel = hash_hmac('sha256', "{$id_usuario}|{$rol_usuario}", PANEL_SECRET_KEY);

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
            require_once 'secciones/dashboard.php';
            require_once 'secciones/habitaciones.php';
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
    const ROL_USUARIO = <?= $rol_usuario ?>;
    const ID_USUARIO_ACTIVO = <?= $id_usuario ?>;
    const FIRMA_USUARIO_ACTIVO = "<?= htmlspecialchars($firma_actor_panel, ENT_QUOTES, 'UTF-8') ?>";
    </script>
    <script src="js/panel.js?v=22"></script>
</body>
</html>