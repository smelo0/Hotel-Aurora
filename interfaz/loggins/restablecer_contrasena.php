<?php
require_once __DIR__ . '/../../configuracion/conexion.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$success = '';

if ($token === '') {
    $error = 'Token inválido.';
} else {
    // validate token
    $stmt = $conexion->prepare('SELECT id, email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = 'Token no encontrado o ya utilizado.';
        } else {
            $expires = strtotime($row['expires_at']);
            if ($expires < time()) {
                $error = 'El token ha expirado.';
            } else {
                $email = $row['email'];

                // If POST with new password
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                    $new = (string) ($_POST['password'] ?? '');
                    $confirm = (string) ($_POST['password_confirm'] ?? '');
                    if ($new === '' || $new !== $confirm) {
                        $error = 'Las contraseñas no coinciden o están vacías.';
                    } else {
                        $hash = password_hash($new, PASSWORD_BCRYPT);
                        $ustmt = $conexion->prepare('UPDATE usuario SET psw_usu = ? WHERE corr_usu = ? LIMIT 1');
                        if ($ustmt) {
                            $ustmt->bind_param('ss', $hash, $email);
                            if ($ustmt->execute()) {
                                // remove all tokens for this email
                                $dstmt = $conexion->prepare('DELETE FROM password_resets WHERE email = ?');
                                if ($dstmt) {
                                    $dstmt->bind_param('s', $email);
                                    $dstmt->execute();
                                    $dstmt->close();
                                }
                                $success = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
                            } else {
                                $error = 'No se pudo actualizar la contraseña.';
                            }
                            $ustmt->close();
                        } else {
                            $error = 'Error en la base de datos.';
                        }
                    }
                }
            }
        }
    } else {
        $error = 'Error al validar token.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña | Hotel Aurora</title>
    <link rel="stylesheet" href="../../assets/css/index_usu.css">
</head>
<body>
    <div class="form-card">
        <span class="flex-logo">
            <img class="logo" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo">
        </span>

        <h1>Restablecer contraseña</h1>

        <?php if ($error !== ''): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p class="success-msg"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a href="index_usu.php" class="link-switch">Volver al inicio de sesión</a></p>
        <?php elseif ($error === ''): ?>
            <form method="POST" action="restablecer_contrasena.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <label for="password">Nueva contraseña</label>
                <input id="password" name="password" type="password" required placeholder="Nueva contraseña">

                <label for="password_confirm">Confirmar contraseña</label>
                <input id="password_confirm" name="password_confirm" type="password" required placeholder="Confirmar contraseña">

                <input type="submit" value="Actualizar contraseña">
            </form>
        <?php endif; ?>
        <?php require_once __DIR__ . "../../../includes/system_help.php" ?>
    </div>
</body>
</html>
