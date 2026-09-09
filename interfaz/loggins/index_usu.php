<?php
require_once '../../includes/lang.php';

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Define la ruta hacia la raíz donde está el archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Huespedes | Hotel Aurora</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client?hl=<?=$idioma_actual?>" async defer></script>
    <link rel="stylesheet" href="../../assets/css/index_usu.css">
</head>
<body>
    <?php
    $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
    $vista = isset($_GET['vista']) ? (string) $_GET['vista'] : 'login';
    $exito = isset($_GET['exito']) ? (string) $_GET['exito'] : '';
    $recaptchaSiteKey = trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')));

    $mensajesError = [
        'rol' => 'Esta cuenta no pertenece al panel de huespedes.',
        'vacio' => 'Por favor completa todos los campos.',
        'email' => 'Ingresa un correo valido.',
        'credenciales' => 'Correo o contrasena incorrectos.',
        'captcha' => 'Debes marcar la casilla: No soy un robot.',
        'consentimiento' => 'Debes aceptar el tratamiento de tus datos personales para continuar.',
        'conexion_fallida' => 'No se pudo conectar con la base de datos.',
        'bd_preparacion' => 'No se pudo preparar la consulta. Intenta de nuevo.',
        'bd_insercion' => 'No se pudo preparar el registro. Intenta de nuevo.',
        'bd_ejecucion' => 'Ocurrio un error al procesar la solicitud.',
    ];
    ?>

    <div class="form-card">
        <span class="flex-logo">
            <img class="logo" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo" class="h-10 w-auto"> 
        </span>

        <div id="panel-login" class="fade-in" style="display: block;">
            <h1><?= $log['saludo'] ?></h1>

            <?php if ($vista !== 'registro' && $error !== '' && isset($mensajesError[$error])): ?>
                <p class="error-msg"><?php echo htmlspecialchars($mensajesError[$error], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($exito === 'registrado'): ?>
                <p class="success-msg"><?= $log['registroExitoso'] ?></p>
            <?php endif; ?>

            <form method="POST" action="../../controladores/validar_usuario.php">
                <label for="correo_login"><?= $log['correo'] ?></label>
                <input id="correo_login" type="email" name="correo" required placeholder="ejemplo@correo.com">

                <label for="password_login">Contrasena</label>
                <input id="password_login" type="password" name="password" required placeholder="********">
                
                <!-- Configuración e integración del botón -->
            
                <!-- Configuración del cliente -->
                <div id="g_id_onload"
                     data-client_id= <?= $_ENV['GOOGLE_CLIENT_ID'] ?>
                     data-login_uri="http://localhost/Hotel-Aurora/controladores/callBack.php"
                     data-auto_prompt="false">
                </div>
                <!-- Renderizado del botón -->
                <div class="g_id_signin"
                     data-type="standard"
                     data-size="large"
                     data-theme="outline"
                     data-text="sign_in_with"
                     data-shape="rectangular"
                     data-logo_alignment="left"
                     data-width="350">
                </div>

                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>
                    

                <!-- Configuración e integración del botón -->   

                <input type="submit" value="Iniciar sesion">

                <a class="link-switch" href="recuperar_contrasena.php" class="link-forgot">¿Has olvidado tu contraseña?</a>

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
                <label for="nom_usu">Nombre completo</label>
                <input id="nom_usu" type="text" name="nom_usu" required placeholder="Tu nombre">

                <label for="corr_usu">Correo</label>
                <input id="corr_usu" type="email" name="corr_usu" required placeholder="ejemplo@correo.com">

                <label for="psw_usu">Contrasena</label>
                <input id="psw_usu" type="password" name="psw_usu" required placeholder="********">

    
              <!-- Configuración e integración del botón -->
            
                <!-- Configuración del cliente -->
                <div id="g_id_onload"
                     data-client_id= <?= $_ENV['GOOGLE_CLIENT_ID'] ?>
                     data-login_uri="http://localhost/Hotel-Aurora/controladores/callBackRegistro.php"
                     data-auto_prompt="false">
                </div>
                <!-- Renderizado del botón -->
                <div class="g_id_signin"
                     data-type="standard"
                     data-size="large"
                     data-theme="outline"
                     data-text="sign_in_with"
                     data-shape="rectangular"
                     data-logo_alignment="left"
                     data-width="350">>
                </div>


                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>

                <label class="consent-label"style="display:flex; align-items:flex-start; gap:3px; margin: 5px 0 15px 0; text-transform:none; font-size:12px; line-height:1.5; letter-spacing:0px;">
                    <input type="checkbox" name="data_consent" value="1" required style=" width:18px; height:18px; margin-top:2px; accent-color:#2a7a5c;">
                    Acepto el tratamiento de mis datos personales para la gestión de mi reserva y atención del servicio, conforme a la política de privacidad del hotel.
                </label>

                <input type="submit" value="Crear cuenta">

                <a href="#" onclick="cambiarPanel('login'); return false;" class="link-switch">Ya tienes cuenta? Inicia sesion.</a>
            </form>

        </div>
    </div>

    <?php if ($recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>

    <script>
        function cambiarPanel(panelDestino) {
            const login = document.getElementById('panel-login');
            const registro = document.getElementById('panel-registro');

            if (panelDestino === 'registro') {
                login.style.display = 'none';
                registro.style.display = 'block';
                return;
            }

            registro.style.display = 'none';
            login.style.display = 'block';
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('vista') === 'registro') {
            cambiarPanel('registro');
        }
    </script>
    <?php
    $ayudaSistemaRol = 'usuario';
    require_once '../../includes/system_help.php';
    ?>
</body>
</html>