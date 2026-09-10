<?php
<<<<<<< HEAD

=======
require_once '../../includes/lang.php'; // Carga las variables $idioma_actual y $log
>>>>>>> 798807e29ff3ba6c352065bed7ddc1d3f5598d09
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Forzar la carga limpia del archivo de idiomas asegurando la ruta absoluta
require_once __DIR__ . '/../../includes/lang.php';
?>
<!DOCTYPE html>
<html lang="<?= $idioma_actual ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title><?= $log['title'] ?></title>
=======
    <title><?= $log['titulo'] ?? 'Acceso Huéspedes' ?> | Hotel Aurora</title>
>>>>>>> 798807e29ff3ba6c352065bed7ddc1d3f5598d09
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    
    <!-- Carga dinámica del SDK de Google según el idioma seleccionado -->
    <script src="https://accounts.google.com/gsi/client?hl=<?= $idioma_actual ?>" async defer></script>
    <link rel="stylesheet" href="../../assets/css/index_usu.css">
</head>
<body>
    <?php
    $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
    $vista = isset($_GET['vista']) ? (string) $_GET['vista'] : 'login';
    $exito = isset($_GET['exito']) ? (string) $_GET['exito'] : '';
    $recaptchaSiteKey = trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')));
<<<<<<< HEAD
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
=======

    // Diccionario de mensajes formateados dinámicamente según el diccionario cargado
    $mensajesError = [
        'rol' => $log['err_rol'] ?? 'Esta cuenta no pertenece al panel de huéspedes.',
        'vacio' => $log['err_vacio'] ?? 'Por favor completa todos los campos.',
        'email' => $log['err_email'] ?? 'Ingresa un correo válido.',
        'credenciales' => $log['err_credenciales'] ?? 'Correo o contraseña incorrectos.',
        'captcha' => $log['err_captcha'] ?? 'Debes marcar la casilla: No soy un robot.',
        'consentimiento' => $log['err_consentimiento'] ?? 'Debes aceptar el tratamiento de tus datos personales.',
        'conexion_fallida' => $log['err_conexion'] ?? 'No se pudo conectar con la base de datos.',
        'bd_preparacion' => $log['err_bd'] ?? 'Error interno al procesar la consulta.',
        'bd_insercion' => $log['err_bd'] ?? 'Error al registrar la información.',
        'bd_ejecucion' => $log['err_bd'] ?? 'Ocurrió un error al procesar la solicitud.',
    ];
>>>>>>> 798807e29ff3ba6c352065bed7ddc1d3f5598d09
    ?>

    <div class="form-card">
        <!-- Selector rápido de idioma dentro de la vista de inicio de sesión -->
        <div class="language-selector text-right mb-4">
            <a href="?lang=es<?= ($vista === 'registro') ? '&vista=registro' : '' ?>" class="<?= ($idioma_actual === 'es') ? 'active font-bold' : '' ?>">ES</a>
            <span>/</span>
            <a href="?lang=en<?= ($vista === 'registro') ? '&vista=registro' : '' ?>" class="<?= ($idioma_actual === 'en') ? 'active font-bold' : '' ?>">EN</a>
        </div>

        <span class="flex-logo">
            <img class="logo h-10 w-auto" src="../../assets/images/logo.jpeg" alt="Hotel Aurora Logo"> 
        </span>

<<<<<<< HEAD
        <div id="panel-login" class="fade-in" style="display: block;">
            <h1><?= $log['greeting'] ?></h1>
=======
        <!-- Panel de Login -->
        <div id="panel-login" class="fade-in" style="display: <?= ($vista !== 'registro') ? 'block' : 'none'; ?>;">
            <h1><?= $log['saludo'] ?? 'Sea Bienvenido' ?></h1>
>>>>>>> 798807e29ff3ba6c352065bed7ddc1d3f5598d09

            <?php if ($vista !== 'registro' && $error !== '' && isset($mensajesError[$error])): ?>
                <p class="error-msg"><?= htmlspecialchars($mensajesError[$error], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($exito === 'registrado'): ?>
<<<<<<< HEAD
                <p class="success-msg"><?= $log['successfulSignUp'] ?></p>
            <?php endif; ?>

            <form method="POST" action="../../controladores/validar_usuario.php">
                <label for="correo_login"><?= $log['email'] ?></label>
=======
                <p class="success-msg"><?= $log['registroExitoso'] ?? '¡Registro exitoso! Ya puedes iniciar sesión.' ?></p>
            <?php endif; ?>

            <form method="POST" action="../../controladores/validar_usuario.php">
                <label for="correo_login"><?= $log['correo'] ?? 'Correo' ?></label>
>>>>>>> 798807e29ff3ba6c352065bed7ddc1d3f5598d09
                <input id="correo_login" type="email" name="correo" required placeholder="ejemplo@correo.com">

                <label for="password_login"><?= $log['contrasena'] ?? 'Contraseña' ?></label>
                <input id="password_login" type="password" name="password" required placeholder="********">
                
                <div id="g_id_onload"
                     data-client_id="<?= $_ENV['GOOGLE_CLIENT_ID'] ?? '' ?>"
                     data-login_uri="http://localhost/Hotel-Aurora/controladores/callBack.php"
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

                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>

                <input type="submit" value="<?= $log['iniciarSesion'] ?? 'Iniciar sesión' ?>">

                <a class="link-switch" href="recuperar_contrasena.php?lang=<?= $idioma_actual ?>">
                    <?= $log['olvidoContrasena'] ?? '¿Has olvidado tu contraseña?' ?>
                </a>

                <a href="#" onclick="cambiarPanel('registro'); return false;" class="link-switch">
                    <?= $log['noTienesCuenta'] ?? '¿No tienes cuenta? Regístrate aquí.' ?>
                </a>
            </form>
        </div>

        <!-- Panel de Registro -->
        <div id="panel-registro" class="fade-in" style="display: <?= ($vista === 'registro') ? 'block' : 'none'; ?>;">
            <h1><?= $log['registroTitulo'] ?? 'Únete a Aurora' ?></h1>

            <?php if ($vista === 'registro' && $error !== '' && isset($mensajesError[$error])): ?>
                <p class="error-msg"><?= htmlspecialchars($mensajesError[$error], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($vista === 'registro' && $error === 'correo_duplicado'): ?>
                <p class="error-msg"><?= $log['err_correo_duplicado'] ?? 'Este correo ya está registrado.' ?></p>
            <?php endif; ?>

            <form method="POST" action="../../controladores/registrar_usuario.php">
                <label for="nom_usu"><?= $log['nombreCompleto'] ?? 'Nombre completo' ?></label>
                <input id="nom_usu" type="text" name="nom_usu" required placeholder="Tu nombre">

                <label for="corr_usu"><?= $log['correo'] ?? 'Correo' ?></label>
                <input id="corr_usu" type="email" name="corr_usu" required placeholder="ejemplo@correo.com">

                <label for="psw_usu"><?= $log['contrasena'] ?? 'Contraseña' ?></label>
                <input id="psw_usu" type="password" name="psw_usu" required placeholder="********">

                <div id="g_id_onload"
                     data-client_id="<?= $_ENV['GOOGLE_CLIENT_ID'] ?? '' ?>"
                     data-login_uri="http://localhost/Hotel-Aurora/controladores/callBackRegistro.php"
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

                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>

                <label class="consent-label" style="display:flex; align-items:flex-start; gap:3px; margin: 5px 0 15px 0; text-transform:none; font-size:12px; line-height:1.5; letter-spacing:0px;">
                    <input type="checkbox" name="data_consent" value="1" required style="width:18px; height:18px; margin-top:2px; accent-color:#2a7a5c;">
                    <?= $log['consentimientoDatos'] ?? 'Acepto el tratamiento de mis datos personales para la gestión de mi reserva y atención del servicio, conforme a la política de privacidad del hotel.' ?>
                </label>

                <input type="submit" value="<?= $log['crearCuenta'] ?? 'Crear cuenta' ?>">

                <a href="#" onclick="cambiarPanel('login'); return false;" class="link-switch">
                    <?= $log['yaTienesCuenta'] ?? '¿Ya tienes cuenta? Inicia sesión.' ?>
                </a>
            </form>
        </div>
    </div>

    <?php if ($recaptchaSiteKey !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js?hl=<?= $idioma_actual ?>" async defer></script>
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