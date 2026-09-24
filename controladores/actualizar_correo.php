Este archivo maneja la actualización del correo electrónico de un empleado o administrador. Es un proceso crítico de auditoría y gestión de datos, por lo que es perfecto para registrar tanto los éxitos como los fallos (como intentos con correos duplicados o errores de base de datos).

Aquí tienes tu código con los logs insertados estratégicamente en los puntos clave:

PHP
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';
// 1. Asegúrate de incluir el autoloader y usar la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

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

exigir_csrf();

$correoNuevo = trim((string) ($_POST['nuevo_correo'] ?? ''));
if ($correoNuevo === '' || !filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {
    // LOG: Advertencia si intentan enviar un formato de correo inválido
    Logger::registrarLog('WARN', 'Intento de actualizar correo con formato inválido', [
        'id_usuario' => $_SESSION['emp_auth']['id_usuario']
    ]);
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
    Logger::registrarLog('ERROR', 'Fallo al preparar consulta SQL para verificar correo duplicado', [
        'id_usuario' => $idUsuario,
        'error_db' => $conexion->error
    ]);
    redirigir_panel('error', 'correo_invalido');
}

$stmtExiste->bind_param('si', $correoNuevo, $idUsuario);
$stmtExiste->execute();
$stmtExiste->store_result();

if ($stmtExiste->num_rows > 0) {
    $stmtExiste->close();
    
    // LOG: Advertencia por intento de usar un correo que ya le pertenece a otro usuario
    Logger::registrarLog('WARN', 'Intento de actualizar a un correo ya registrado', [
        'id_usuario' => $idUsuario,
        'correo_duplicado' => $correoNuevo
    ]);
    
    redirigir_panel('error', 'correo_duplicado');
}

$stmtExiste->close();

$sqlActualizar = 'UPDATE usuario SET corr_usu = ? WHERE id_usu = ?';
$stmtActualizar = $conexion->prepare($sqlActualizar);
if (!$stmtActualizar) {
    Logger::registrarLog('ERROR', 'Fallo al preparar consulta SQL para actualizar correo', [
        'id_usuario' => $idUsuario,
        'error_db' => $conexion->error
    ]);
    redirigir_panel('error', 'correo_invalido');
}

$stmtActualizar->bind_param('si', $correoNuevo, $idUsuario);
if (!$stmtActualizar->execute()) {
    $stmtActualizar->close();
    
    Logger::registrarLog('ERROR', 'Fallo al ejecutar la actualización del correo en base de datos', [
        'id_usuario' => $idUsuario,
        'error_db' => $stmtActualizar->error
    ]);
    
    redirigir_panel('error', 'correo_invalido');
}

$stmtActualizar->close();
$_SESSION['emp_auth']['correo_usuario'] = $correoNuevo;

// LOG DE ÉXITO: El correo se actualizó correctamente
Logger::registrarLog('INFO', 'Correo electrónico actualizado con éxito', [
    'id_usuario' => $idUsuario
]);

redirigir_panel('ok');