<?php
require_once __DIR__ . '/../../configuracion/conexion.php';
require_once 'includes/lang.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$success = '';

if ($token === '') {
    $error = $log['err_token_invalido'] ?? 'Token inválido.';
} else {
    // Validar token
    $stmt = $conexion->prepare('SELECT id, email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = $log['err_token_no_encontrado'] ?? 'Token no encontrado o ya utilizado.';
        } else {
            $expires = strtotime($row['expires_at']);
            if ($expires < time()) {
                $error = $log['err_token_expirado'] ?? 'El token ha expirado.';
            } else {
                $email = $row['email'];

                // Si es método POST para actualizar la contraseña
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                    $new = (string) ($_POST['password'] ?? '');
                    $confirm = (string) ($_POST['password_confirm'] ?? '');
                    if ($new === '' || $new !== $confirm) {
                        $error = $log['err_coincidencia_pass'] ?? 'Las contraseñas no coinciden o están vacías.';
                    } else {
                        $hash = password_hash($new, PASSWORD_BCRYPT);
                        $ustmt = $conexion->prepare('UPDATE usuario SET psw_usu = ? WHERE corr_usu = ? LIMIT 1');
                        if ($ustmt) {
                            $ustmt->bind_param('ss', $hash, $email);
                            if ($ustmt->execute()) {
                                // Eliminar tokens usados
                                $dstmt = $conexion->prepare('DELETE FROM password_resets WHERE email = ?');
                                if ($dstmt) {
                                    $dstmt->bind_param('s', $email);
                                    $dstmt->execute();
                                    $dstmt->close();
                                }
                                $success = $log['exito_actualizacion_pass'] ?? 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
                            } else {
                                $error = $log['err_actualizar_pass'] ?? 'No se pudo actualizar la contraseña.';
                            }
                            $ustmt->close();
                        } else {
                            $error = $log['err_bd'] ?? 'Error en la base de datos.';
                        }
                    }
                }
            }
        }
    } else {
        $error = $log['err_validar_token'] ?? 'Error al validar token.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($idioma_actual, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $log['restablecerTitulo'] ?? 'Restablecer contraseña' ?> | Hotel Aurora</title>
    <link rel="stylesheet" href="../../assets/css/index_usu.css">
</head>
<body>
    <div class="form-card">
        <!-- Selector rápido de idioma -->
        <div class="language-selector text-right mb-4">
            <a href="?token=<?= urlencode($token) ?>&lang=es" class="<?= ($idioma_actual === 'es') ? 'active font-bold' : '' ?>">ES</a>
            <span>/</span>
            <a href="?token=<?= urlencode($token) ?>&lang=en" class="<?= ($idioma_actual === 'en') ? 'active font-bold' : '' ?>">EN</a>
        </div>

        <span class="flex-logo">
            <img class="logo" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo">
        </span>

        <h1><?= $log['restablecerTitulo'] ?? 'Restablecer contraseña' ?></h1>

        <?php if ($error !== ''): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p class="success-msg"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><a href="index_usu.php?lang=<?= htmlspecialchars($idioma_actual, ENT_QUOTES, 'UTF-8'); ?>" class="link-switch"><?= $log['volverLogin'] ?? 'Volver al inicio de sesión' ?></a></p>
        <?php elseif ($error === ''): ?>
            <form method="POST" action="restablecer_contrasena.php?lang=<?= htmlspecialchars($idioma_actual, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <label for="password"><?= $log['nuevaContrasena'] ?? 'Nueva contraseña' ?></label>
                <input id="password" name="password" type="password" required placeholder="Nueva contraseña">

                <label for="password_confirm"><?= $log['confirmarContrasena'] ?? 'Confirmar contraseña' ?></label>
                <input id="password_confirm" name="password_confirm" type="password" required placeholder="Confirmar contraseña">

                <input type="submit" value="<?= $log['actualizarContrasena'] ?? 'Actualizar contraseña' ?>">
            </form>
        <?php endif; ?>

    </div>
</body>
</html>