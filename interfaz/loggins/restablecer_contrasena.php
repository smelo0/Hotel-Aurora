<?php
require_once __DIR__ . '/../../includes/sesion_seguridad.php';

$token = $_GET['token'] ?? '';
$token = is_string($token) ? $token : '';
$errorCode = $_GET['error'] ?? '';
$errorCode = is_string($errorCode) ? $errorCode : '';
$success = ($_GET['success'] ?? '') === '1';
$errorMessages = [
    'token' => 'El enlace no es válido, ya fue utilizado o ha expirado. Solicita uno nuevo.',
    'contrasena' => 'Las contraseñas deben coincidir, tener al menos 12 caracteres y no superar 72 bytes.',
    'servidor' => 'No se pudo actualizar la contraseña. Inténtalo de nuevo más tarde.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña | Hotel Aurora</title>
    <link rel="stylesheet" href="../../assets/css/index_usu.css?v=2">
</head>
<body>
    <div class="form-card">
        <span class="flex-logo">
            <img class="logo" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo">
        </span>

        <h1>Restablecer contraseña</h1>

        <?php if ($success): ?>
            <p class="success-msg">Contraseña actualizada correctamente. Ya puedes iniciar sesión.</p>
            <p><a href="index_usu.php" class="link-switch">Volver al inicio de sesión</a></p>
        <?php elseif ($token === ''): ?>
            <p class="error-msg">El enlace no es válido o ha expirado. Solicita uno nuevo.</p>
            <p><a href="recuperar_contrasena.php" class="link-switch">Solicitar otro enlace</a></p>
        <?php else: ?>
            <?php if (isset($errorMessages[$errorCode])): ?>
                <p class="error-msg"><?php echo htmlspecialchars($errorMessages[$errorCode], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <form method="POST" action="../../controladores/restablecer_contrasena.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <label for="password">Nueva contraseña</label>
                <input id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required placeholder="Nueva contraseña">

                <label for="password_confirm">Confirmar contraseña</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="12" maxlength="72" autocomplete="new-password" required placeholder="Confirmar contraseña">

                <input type="submit" value="Actualizar contraseña">
            </form>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/../../includes/translate.php'; ?>
    <?php require_once __DIR__ . '/../../includes/system_help.php'; ?>
</body>
</html>
