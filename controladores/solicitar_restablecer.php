<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../configuracion/conexion.php';

// Autoload (PHPMailer y phpdotenv via Composer) si está disponible
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Cargar variables del archivo .env si se utiliza Dotenv
if (class_exists(\Dotenv\Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

function redirectBack(string $query = '')
{
    $dest = '../interfaz/loggins/recuperar_contrasena.php';
    if ($query !== '') {
        $dest .= '?' . $query;
    }
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

// Asegurar que exista la tabla para guardar los tokens
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
$expires = date('Y-m-d H:i:s', time() + 3600); // Válido por 1 hora

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

// El enlace usa una URL configurada por el servidor, nunca el encabezado Host de la solicitud.
$appEnvironment = strtolower(trim((string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'local'))));
$defaultBaseUrl = $appEnvironment === 'production' ? '' : 'http://localhost/Hotel-Aurora';
$baseUrl = trim((string) (getenv('APP_BASE_URL') ?: ($_ENV['APP_BASE_URL'] ?? $defaultBaseUrl)));
$baseParts = parse_url($baseUrl);
if (
    $baseParts === false
    || !in_array(strtolower((string) ($baseParts['scheme'] ?? '')), ['http', 'https'], true)
    || empty($baseParts['host'])
    || ($appEnvironment === 'production' && strtolower((string) $baseParts['scheme']) !== 'https')
    || isset($baseParts['user'])
    || isset($baseParts['pass'])
    || isset($baseParts['query'])
    || isset($baseParts['fragment'])
) {
    error_log('Password reset email was not sent because APP_BASE_URL is invalid.');
    redirectBack('exito=1&sent=0');
}
$baseUrl = rtrim($baseUrl, '/');
$resetUrl = $baseUrl . '/interfaz/loggins/restablecer_contrasena.php?token=' . urlencode($token);
$resetUrlHtml = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$subject = 'Restablece tu contraseña · Hotel Aurora';
$message = "<p>Hola,</p>\n<p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el siguiente enlace para establecer una nueva contraseña (válido 1 hora):</p>\n<p><a href=\"{$resetUrlHtml}\">Restablecer contraseña</a></p>\n<p>Si no solicitaste este cambio, puedes ignorar este correo.</p>\n<p>Saludos,<br>Hotel Aurora</p>";

$sent = false;

if (class_exists(PHPMailer::class)) {
    try {
        $mail = new PHPMailer(true);

        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? ($_SERVER['SMTP_HOST'] ?? 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        
        // Carga de credenciales
        $mail->Username   = $_ENV['SMTP_USER'] ?? ($_SERVER['SMTP_USER'] ?? '');
        $mail->Password   = $_ENV['SMTP_PASS'] ?? ($_SERVER['SMTP_PASS'] ?? '');
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? ($_SERVER['SMTP_PORT'] ?? 587));
        $mail->CharSet    = 'UTF-8';

        // Configuración de Remitente y Destinatario
        $fromEmail = $_ENV['FROM_EMAIL'] ?? ($_SERVER['FROM_EMAIL'] ?? $mail->Username);
        $fromName  = $_ENV['FROM_NAME']  ?? ($_SERVER['FROM_NAME']  ?? 'Hotel Aurora');

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        $sent = true;

    } catch (Exception $e) {
        error_log('Password reset email delivery failed: ' . $mail->ErrorInfo);
    }
}

// Si la ejecución concluye exitosamente, realiza la redirección indicando éxito:
$query = 'exito=1&sent=' . ($sent ? '1' : '0');
redirectBack($query);