<?php
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Cargar variables del archivo .env desde la raíz del proyecto
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        header('Location: ../vistas/recuperar_contrasena.php?error=invalid_email');
        exit;
    }

    // 1. Verificar si el correo existe en la base de datos
    $stmt = $conexion->prepare('SELECT id FROM usuario WHERE corr_usu = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->fetch_assoc()) {
        // 2. Generar token único y fecha de expiración (30 minutos)
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Limpiar tokens anteriores asignados a este correo
        $delStmt = $conexion->prepare('DELETE FROM password_resets WHERE email = ?');
        $delStmt->bind_param('s', $email);
        $delStmt->execute();

        // Guardar el nuevo token
        $insStmt = $conexion->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
        $insStmt->bind_param('sss', $email, $token, $expires_at);
        $insStmt->execute();

        // 3. Configurar y enviar correo con PHPMailer leyendo las variables de entorno
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS']; // Lee la clave desde el .env
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $_ENV['SMTP_PORT'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($email);

            $baseUrl = rtrim($_ENV['APP_URL'], '/');
            $link = "{$baseUrl}/vistas/restablecer_contrasena.php?token=" . urlencode($token);

            $mail->isHTML(true);
            $mail->Subject = 'Restablecer contraseña - Hotel Aurora';
            $mail->Body    = "
                <h2>Recuperación de Contraseña</h2>
                <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
                <p><a href='{$link}'>{$link}</a></p>
                <p><small>Este enlace expira en 30 minutos.</small></p>
            ";

            $mail->send();
            header('Location: ../vistas/recuperar_contrasena.php?exito=1&sent=1');
            exit;
        } catch (Exception $e) {
            header('Location: ../vistas/recuperar_contrasena.php?exito=1&sent=0');
            exit;
        }
    } else {
        // Respuesta genérica por seguridad para evitar el escaneo de correos existentes
        header('Location: ../vistas/recuperar_contrasena.php?exito=1&sent=1');
        exit;
    }
}