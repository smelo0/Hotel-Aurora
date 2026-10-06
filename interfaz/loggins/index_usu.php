<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/sesion_seguridad.php';

use Dotenv\Dotenv;

// Define la ruta hacia la raíz donde está el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();
$googleClientId = trim((string) (getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? '')));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de huéspedes | Hotel Aurora</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <?php if (!empty($googleClientId)): ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
    <link rel="stylesheet" href="../../assets/css/index_usu.css?v=2">
</head>
<body>
    <?php
    $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
    $vista = isset($_GET['vista']) ? (string) $_GET['vista'] : 'login';
    $exito = isset($_GET['exito']) ? (string) $_GET['exito'] : '';
    $recaptchaSiteKey = trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')));
    $baseUrl = rtrim((string) (getenv('APP_BASE_URL') ?: ($_ENV['APP_BASE_URL'] ?? 'http://localhost/Hotel-Aurora')), '/');
    $googleLoginUrl = $baseUrl . '/controladores/callBack.php';

    $mensajesError = [
        'rol' => 'Esta cuenta no pertenece al panel de huéspedes.',
        'vacio' => 'Por favor, completa todos los campos.',
        'email' => 'Ingresa un correo válido.',
        'credenciales' => 'Correo o contraseña incorrectos.',
        'captcha' => 'Debes marcar la casilla: No soy un robot.',
        'consentimiento' => 'Debes aceptar el tratamiento de tus datos personales para continuar.',
        'conexion_fallida' => 'No se pudo conectar con la base de datos.',
        'bd_preparacion' => 'No se pudo preparar la consulta. Intenta de nuevo.',
        'bd_insercion' => 'No se pudo preparar el registro. Intenta de nuevo.',
        'bd_ejecucion' => 'Ocurrio un error al procesar la solicitud.',
        'contrasena' => 'La contraseña debe tener al menos 12 caracteres y no superar 72 bytes.',
        'google_registro' => 'Para crear una cuenta con Google, completa el registro y acepta el tratamiento de datos.',
        'flujo_obsoleto' => 'Este acceso ya no está disponible. Inicia sesión desde este formulario.',
        'registro_obsoleto' => 'Este registro ya no está disponible. Completa el formulario actualizado.',
    ];
    ?>

    <div class="form-card">
        <span class="flex-logo">
            <img class="logo" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo" class="h-10 w-auto"> 
        </span>

        <div id="panel-login" class="fade-in" style="display: block;">
            <h1>Sea bienvenido</h1>

            <?php if ($vista !== 'registro' && $error !== '' && isset($mensajesError[$error])): ?>
                <p class="error-msg"><?php echo htmlspecialchars($mensajesError[$error], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($exito === 'registrado'): ?>
                <p class="success-msg">Registro exitoso. Ya puedes iniciar sesion.</p>
            <?php endif; ?>

            <form method="POST" action="../../controladores/validar_usuario.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <label for="correo_login">Correo</label>
                <input id="correo_login" type="email" name="correo" autocomplete="email" required placeholder="nombre@correo.com">

                <label for="password_login">Contrasena</label>
                <input id="password_login" type="password" name="password" autocomplete="current-password" required placeholder="Ingresa tu contraseña">
                
                <!-- Configuración e integración del botón -->
            
                <!-- Configuración del cliente -->
                <?php if ($googleClientId !== ''): ?>
                    <div id="g_id_onload"
                         data-client_id="<?php echo htmlspecialchars($googleClientId, ENT_QUOTES, 'UTF-8'); ?>"
                         data-login_uri="<?php echo htmlspecialchars($googleLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin"
                         data-type="standard"
                         data-size="large"
                         data-theme="outline"
                         data-text="sign_in_with"
                         data-shape="rectangular"
                         data-logo_alignment="left"
                         data-width="350">
                    </div>
                <?php endif; ?>

                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>
                    

                <!-- Configuración e integración del botón -->   

                <input type="submit" value="Iniciar sesión">

                <a class="link-switch" href="recuperar_contrasena.php">¿Has olvidado tu contraseña?</a>

                <a href="#" onclick="cambiarPanel('registro'); return false;" class="link-switch">No tienes cuenta? Registrate aqui.</a>
            </form>
        </div>

        <div id="panel-registro" class="fade-in" style="display: none;">
            <h1>Unete a Aurora</h1>

            <?php if ($vista === 'registro' && $error !== '' && isset($mensajesError[$error])): ?>
                <p class="error-msg"><?php echo htmlspecialchars($mensajesError[$error], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($vista === 'registro' && $error === 'correo_duplicado'): ?>
                <p class="error-msg">Este correo ya esta registrado.</p>
            <?php endif; ?>

            <form method="POST" action="../../controladores/registrar_usuario.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <label for="nom_usu">Nombre completo</label>
                <input id="nom_usu" type="text" name="nom_usu" autocomplete="name" required placeholder="Tu nombre">

                <label for="corr_usu">Correo</label>
                <input id="corr_usu" type="email" name="corr_usu" autocomplete="email" required placeholder="nombre@correo.com">

                <label for="psw_usu">Contrasena</label>
                <input id="psw_usu" type="password" name="psw_usu" minlength="12" maxlength="72" autocomplete="new-password" required placeholder="Crea una contraseña">

    
              <!-- Configuración e integración del botón -->
            
                <!-- Configuración del cliente -->
                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>

                <label class="consent-label">
                    <input type="checkbox" name="data_consent" value="1" required>
                    Acepto el tratamiento de mis datos personales.  
                   <a href="#politica-privacidad" data-open-privacy-policy>Consulta nuestra Política de Tratamiento de Datos.</a>
                </label>
                

                <input type="submit" value="Crear cuenta">

                <a href="#" onclick="cambiarPanel('login'); return false;" class="link-switch">Ya tienes cuenta? Inicia sesion.</a>
            </form>

        </div>
    </div>

    <?php if ($recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>

    <script src="../../assets/js/login_usuario.js?v=1"></script>
    
    <?php include __DIR__ . '/../../includes/translate.php'; ?>
    <?php
    $privacyPolicyCookiesUrl = '../legal/politica_cookies.php';
    $privacyPolicyUrl = '../legal/politica_privacidad.php';
    include __DIR__ . '/../../includes/privacy_policy_modal.php';
    ?>

    
</body>
</html>