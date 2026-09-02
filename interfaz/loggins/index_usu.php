<?php
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
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #c8e6d4;
            font-family: 'DM Sans', sans-serif;
            padding: 20px;
        }

        .form-card {
            background: #fffdf7;
            border-radius: 28px;
            padding: 32px 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 48px rgba(50, 120, 90, 0.13);
        }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 30px;
            color: #1a3d30;
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            font-size: 11px;
            font-weight: 500;
            color: #6a9a82;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"], input[type="email"], input[type="password"] {
            display: block;
            width: 100%;
            padding: 13px 16px;
            margin-bottom: 18px;
            border: 1.5px solid #c8e6d4;
            border-radius: 14px;
            background: #f4fbf7;
            outline: none;
        }

        input[type="submit"] {
            width: 100%;
            padding: 15px;
            background: #2a7a5c;
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        input[type="submit"]:hover { background: #3a9e78; }

        .link-switch {
            display: block;
            text-align: center;
            font-size: 13px;
            color: #5bb89a;
            text-decoration: none;
            margin-top: 15px;
        }

        .error-msg {
            color: #ef4444;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .success-msg {
            color: #10b981;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .captcha-wrap {
            display: flex;
            justify-content: center;
            margin: 0 0 18px;
        }

        .captcha-box {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #295f49;
            margin: 0 0 18px;
            cursor: pointer;
        }

        .captcha-box input {
            width: 18px;
            height: 18px;
            accent-color: #2a7a5c;
            margin: 0;
        }

        .fade-in { animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .g_id_signin {
        margin-bottom: 20px;          /* Espaciado abajo */
        padding: 15px;
        display: flex;
        flex-direction: row;
        justify-content: center;
        }


    </style>
</head>
<body>
    <?php
    $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
    $vista = isset($_GET['vista']) ? (string) $_GET['vista'] : 'login';
    $exito = isset($_GET['exito']) ? (string) $_GET['exito'] : '';
    $recaptchaSiteKey = trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? '')));

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
        <span class="logo">
                <img src="img/WhatsApp Image 2026-07-31 at 7.32.43 AM.jpeg" alt="Hotel Aurora Logo" class="h-10 w-auto">
                
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
                <label for="correo_login">Correo</label>
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
                     data-width="350">>
                </div>

                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="captcha-wrap">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                <?php endif; ?>
                    

                <!-- Configuración e integración del botón -->   

                <input type="submit" value="Iniciar sesion">

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

                <label style="display:flex; align-items:flex-start; gap:10px; margin: 0 0 18px; text-transform:none; font-size:13px; line-height:1.5; color:#355b4a; letter-spacing:0;">
                    <input type="checkbox" name="data_consent" value="1" required style="width:18px; height:18px; margin-top:2px; accent-color:#2a7a5c;">
                    Acepto el tratamiento de mis datos personales para la gestión de mi reserva y atención del servicio, conforme a la política de privacidad del hotel.
                </label>

              <!-- Configuración e integración del botón -->
            
                <!-- Configuración del cliente -->
                <div id="g_id_onload"
                     data-client_id= <?= $_ENV['GOOGLE_CLIENT_ID'] ?>
                     data-login_uri="http://localhost/software_hotel v2.0/controladores/callBackRegistro.php"
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