<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function redirectPasswordReset(string $query = ''): never
{
    $destination = '../interfaz/loggins/restablecer_contrasena.php';
    if ($query !== '') {
        $destination .= '?' . $query;
    }

    header('Location: ' . $destination);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirectPasswordReset();
}

exigir_csrf();

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmation = $_POST['password_confirm'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{48}$/', $token)
    || !is_string($password) || !is_string($confirmation)) {
    redirectPasswordReset('error=token');
}

if ($password !== $confirmation || mb_strlen($password) < 12 || strlen($password) > 72) {
    redirectPasswordReset('error=contrasena');
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
if ($passwordHash === false) {
    error_log('Password reset failed because password_hash returned false.');
    redirectPasswordReset('error=servidor');
}

$tokenHash = hash('sha256', $token);
if (!$conexion->begin_transaction()) {
    error_log('Password reset transaction could not start.');
    redirectPasswordReset('error=servidor');
}
try {
    $tokenStatement = $conexion->prepare(
        'SELECT email FROM password_resets
         WHERE token IN (?, ?) AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $tokenStatement->bind_param('ss', $tokenHash, $token);
    $tokenStatement->execute();
    $tokenStatement->bind_result($email);
    $tokenValid = $tokenStatement->fetch();
    $tokenStatement->close();

    if (!$tokenValid) {
        $conexion->rollback();
        redirectPasswordReset('error=token');
    }

    $userStatement = $conexion->prepare('UPDATE usuario SET psw_usu = ? WHERE corr_usu = ? LIMIT 1');
    $userStatement->bind_param('ss', $passwordHash, $email);
    $userStatement->execute();
    if ($userStatement->affected_rows !== 1) {
        $userStatement->close();
        throw new RuntimeException('password_reset_user_not_found');
    }
    $userStatement->close();

    $deleteStatement = $conexion->prepare('DELETE FROM password_resets WHERE email = ?');
    $deleteStatement->bind_param('s', $email);
    $deleteStatement->execute();
    $deleteStatement->close();

    $conexion->commit();
} catch (Throwable $error) {
    try {
        $conexion->rollback();
    } catch (Throwable $rollbackError) {
        error_log('Password reset transaction rollback failed: ' . $rollbackError->getMessage());
    }
    error_log('Password reset transaction failed: ' . $error->getMessage());
    redirectPasswordReset('error=servidor');
}

redirectPasswordReset('success=1');
