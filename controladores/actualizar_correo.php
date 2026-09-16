<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';

function redirigir_panel(string $estado, string $mensaje = ''): never
{
    $rol = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);

    if ($rol === 1 || $rol === 2) {
        $destino = '../interfaz/admin/index_ad.php';
    } elseif ($rol === 3 || $rol === 4 || $rol === 5) {
        $destino = '../interfaz/empleado/index.php';
    } else {
        header('Location: ../interfaz/loggins/index_ad_em.php');
        exit();
    }

    if ($estado === 'ok') {
        $destino .= '?success=correo_actualizado';
    } else {
        $destino .= '?error=' . urlencode($mensaje !== '' ? $mensaje : 'correo_invalido');
    }

    header('Location: ' . $destino);
    exit();
}

if (!isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario'])) {
    header('Location: ../interfaz/loggins/index_ad_em.php');
    exit();
}

$rol = (int) $_SESSION['emp_auth']['rol_usuario'];
if (!in_array($rol, [1, 2, 3, 4, 5], true)) {
    header('Location: ../interfaz/loggins/index_ad_em.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir_panel('error', 'correo_invalido');
}

$correoNuevo = trim((string) ($_POST['nuevo_correo'] ?? ''));
if ($correoNuevo === '' || !filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {
    redirigir_panel('error', 'correo_invalido');
}

$idUsuario = (int) $_SESSION['emp_auth']['id_usuario'];
$correoActual = trim((string) ($_SESSION['emp_auth']['correo_usuario'] ?? ''));

if (strcasecmp($correoNuevo, $correoActual) === 0) {
    redirigir_panel('ok');
}

$sqlExiste = 'SELECT id_usu FROM usuario WHERE corr_usu = ? AND id_usu != ? LIMIT 1';
/**@var mysqli $conexion */
$stmtExiste = $conexion->prepare($sqlExiste);
if (!$stmtExiste) {
    redirigir_panel('error', 'correo_invalido');
}

$stmtExiste->bind_param('si', $correoNuevo, $idUsuario);
$stmtExiste->execute();
$stmtExiste->store_result();

if ($stmtExiste->num_rows > 0) {
    $stmtExiste->close();
    redirigir_panel('error', 'correo_duplicado');
}

$stmtExiste->close();

$sqlActualizar = 'UPDATE usuario SET corr_usu = ? WHERE id_usu = ?';
$stmtActualizar = $conexion->prepare($sqlActualizar);
if (!$stmtActualizar) {
    redirigir_panel('error', 'correo_invalido');
}

$stmtActualizar->bind_param('si', $correoNuevo, $idUsuario);
if (!$stmtActualizar->execute()) {
    $stmtActualizar->close();
    redirigir_panel('error', 'correo_invalido');
}

$stmtActualizar->close();
$_SESSION['emp_auth']['correo_usuario'] = $correoNuevo;
redirigir_panel('ok');
