<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../configuracion/conexion.php';
// Autoload (PHPMailer via Composer) if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function redirectBack(string $query = '')
{
    $dest = '../interfaz/loggins/recuperar_contrasena.php';
    if ($query !== '') $dest .= '?' . $query;
    header('Location: ' . $dest);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirectBack();
}

$email = trim((string) ($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectBack('error=invalid_email');
}

// Ensure table exists
$create = "CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$conexion->query($create);

$token = bin2hex(random_bytes(24));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora

$stmt = $conexion->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
if (!$stmt) {
    redirectBack('error=bd');
}
$stmt->bind_param('sss', $email, $token, $expires);
if (!$stmt->execute()) {
    $stmt->close();
    redirectBack('error=bd');
}
$stmt->close();

// Build absolute URL to reset page
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $protocol . '://' . $host;
$resetUrl = $base . '/Hotel-Aurora/interfaz/loggins/restablecer_contrasena.php?token=' . urlencode($token);

$subject = 'Restablece tu contraseña · Hotel Aurora';
$message = "<p>Hola,</p>\n<p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el siguiente enlace para establecer una nueva contraseña (válido 1 hora):</p>\n<p><a href=\"{$resetUrl}\">Restablecer contraseña</a></p>\n<p>Si no solicitaste este cambio, puedes ignorar este correo.</p>\n<p>Saludos,<br>Hotel Aurora</p>";

// Prefer SMTP via PHPMailer if available, otherwise fall back to mail()
$sent = false;
$fallbackSent = false;
$debugModeUsed = false;

if (class_exists(PHPMailer::class)) {
    try {
        $mail = new PHPMailer(true);

        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? ($_SERVER['SMTP_HOST'] ?? 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? ($_SERVER['SMTP_USER'] ?? 'tu_correo@gmail.com');
        $mail->Password   = $_ENV['SMTP_PASS'] ?? ($_SERVER['SMTP_PASS'] ?? 'tu_contraseña_de_aplicacion');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? ($_SERVER['SMTP_PORT'] ?? 587));
        $mail->CharSet    = 'UTF-8';

        // From address
        $fromEmail = $_ENV['FROM_EMAIL'] ?? ($_SERVER['FROM_EMAIL'] ?? ($mail->Username ?? 'no-reply@hotelaurora.com'));
        $fromName = $_ENV['FROM_NAME'] ?? ($_SERVER['FROM_NAME'] ?? 'Hotel Aurora');

        // Destinatarios y Contenido
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message . "<p><a href='{$resetUrl}'>{$resetUrl}</a></p>";

        $mail->send();
        $sent = true;
    } catch (Exception $e) {
        $sent = false;
    }
}

// Respaldo por si falla PHPMailer o para modo debug
if (!$sent) {
    $mailDebugEnv = trim((string)(getenv('MAIL_DEBUG') ?: ($_ENV['MAIL_DEBUG'] ?? '')));
    $mailDebug = in_array(strtolower($mailDebugEnv), ['1', 'true', 'on'], true);

    if ($mailDebug) {
        $storageDir = __DIR__ . '/../storage';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        $logFile = $storageDir . '/reset-links.log';
        $logLine = date('c') . ' | ' . $email . ' | ' . $resetUrl . PHP_EOL;
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        $sent = true;
    }
}

$finalSent = $sent || $fallbackSent;
$query = 'exito=1&sent=' . ($finalSent ? '1' : '0');
if (!empty($debugModeUsed)) {
    $query .= '&debug=1';
}
redirectBack($query);
