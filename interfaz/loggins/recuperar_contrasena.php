<?php
// Página de recuperación de contraseña
$error = '';
$message = '';
$exito = isset($_GET['exito']) ? (string) $_GET['exito'] : '';
$sent = isset($_GET['sent']) ? (string) $_GET['sent'] : '';

if ($exito === '1') {
    if ($sent === '1') {
        $message = 'Correo enviado correctamente. Revisa tu bandeja de entrada (y la carpeta de spam).';
    } else {
        $message = 'Se intentó enviar el correo pero no fue posible completar el envío. Por favor contacta soporte si no recibes instrucciones.';
    }
}
if (isset($_GET['error'])) {
    $err = $_GET['error'];
    if ($err === 'invalid_email') $error = 'Introduce un correo válido.';
    elseif ($err === 'bd') $error = 'Error en el servidor. Intenta más tarde.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña | Hotel Aurora</title>
    <link rel="stylesheet" href="../../assets/css/index_usu.css">
</head>
<body>
    <div class="form-card">
        <span class="flex-logo">
            <img class="logo" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo">
        </span>

        <h1>Recuperar contraseña</h1>

        <?php if ($error !== ''): ?>
            <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <p class="success-msg"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
            <form method="POST" action="../../controladores/solicitar_restablecer.php">
                <label for="email_rec">Correo</label>
                <input id="email_rec" type="email" name="email" required placeholder="ejemplo@correo.com">

                <input type="submit" value="Enviar instrucciones">
            </form>
        <?php endif; ?>

        <p class="mt-4">
            <a href="index_usu.php" class="link-switch">Volver al inicio de sesión</a>
        </p>
    </div>
</body>
</html>
