<?php
// includes/lang.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si el usuario hace clic en el toggle en cualquier página, se actualiza la sesión
if (isset($_GET['lang'])) {
    if ($_GET['lang'] === 'en' || $_GET['lang'] === 'es') {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

// Definir idioma por defecto (español)
$idioma_actual = $_SESSION['lang'] ?? 'es';

// Cargar las traducciones correspondientes
$lang = [];
$log = [];

if ($idioma_actual === 'en') {
    // Landing Page
    $lang['title'] = 'Hotel Aurora | Bookings & Experiences';
    $lang['hotel'] = 'Hotel Aurora';
    $lang['rooms'] = 'Rooms';
    $lang['experiences'] = 'Experiences';
    $lang['booking'] = 'Bookings';
    $lang['greating'] = 'Hello, ';
    $lang['logOut'] = 'Log Out';
    $lang['logIn'] = 'Log In';
    $lang['signIn'] = 'Sign Up';
    $lang['premiunBookingInFrontOfSea'] = 'Premium oceanfront booking';
    $lang['bookingYourNextStayWithAFlawlessVisualExperience'] = 'Book your next stay with a flawless visual experience.';
    $lang['chooseYourDates'] = 'Choose your dates, adjust your guests and find available rooms instantly with a truly functional search.';
    $lang['bookNow'] = 'Book now';
    $lang['exploreExperiences'] = 'Explore experiences';
    $lang['dates'] = 'Dates';

    // Autenticación, Login y Registro ($log)
    $log['titulo'] = 'Guest Access';
    $log['saludo'] = 'Welcome!';
    $log['correo'] = 'Email';
    $log['contrasena'] = 'Password';
    $log['iniciarSesion'] = 'Sign In';
    $log['olvidoContrasena'] = 'Forgot your password?';
    $log['noTienesCuenta'] = "Don't have an account? Sign up here.";
    $log['yaTienesCuenta'] = 'Already have an account? Sign in.';
    $log['registroTitulo'] = 'Join Aurora';
    $log['nombreCompleto'] = 'Full Name';
    $log['crearCuenta'] = 'Create Account';
    $log['registroExitoso'] = 'Registration successful. You can now log in.';
    $log['consentimientoDatos'] = 'I accept the processing of my personal data for managing my booking and service provision, according to the hotel privacy policy.';

    // Recuperar / Restablecer Contraseña
    $log['recuperarTitulo'] = 'Recover Password';
    $log['restablecerTitulo'] = 'Reset Password';
    $log['nuevaContrasena'] = 'New Password';
    $log['confirmarContrasena'] = 'Confirm Password';
    $log['enviarInstrucciones'] = 'Send Instructions';
    $log['actualizarContrasena'] = 'Update Password';
    $log['volverLogin'] = 'Back to Sign In';

    // Mensajes de error del login/registro
    $log['err_rol'] = 'This account does not belong to the guest panel.';
    $log['err_vacio'] = 'Please fill in all fields.';
    $log['err_email'] = 'Enter a valid email address.';
    $log['err_credenciales'] = 'Incorrect email or password.';
    $log['err_captcha'] = 'You must check the box: I am not a robot.';
    $log['err_consentimiento'] = 'You must accept the personal data treatment to continue.';
    $log['err_conexion'] = 'Could not connect to the database.';
    $log['err_bd'] = 'An internal error occurred while processing the request.';
    $log['err_correo_duplicado'] = 'This email is already registered.';
    $log['exitoEnvioCorreo'] = 'Email sent successfully. Check your inbox (and spam folder).';
    $log['errorEnvioCorreo'] = 'An attempt was made to send the email, but it could not be completed. Contact support if you do not receive instructions.';
    $log['err_token_invalido'] = 'Invalid token.';
    $log['err_token_no_encontrado'] = 'Token not found or already used.';
    $log['err_token_expirado'] = 'Token has expired.';
    $log['err_coincidencia_pass'] = 'Passwords do not match or are empty.';
    $log['exito_actualizacion_pass'] = 'Password updated successfully. You can now log in.';
    $log['err_actualizar_pass'] = 'Could not update password.';
    $log['err_validar_token'] = 'Error validating token.';

} else {
    // Landing Page
    $lang['title'] = 'Hotel Aurora | Reservas & Experiencias';
    $lang['hotel'] = 'Hotel Aurora';
    $lang['rooms'] = 'Habitaciones';
    $lang['experiences'] = 'Experiencias';
    $lang['booking'] = 'Agenda';
    $lang['greating'] = 'Hola, ';
    $lang['logOut'] = 'Cerrar Sesión';
    $lang['logIn'] = 'Iniciar Sesión';
    $lang['signIn'] = 'Registrarse';
    $lang['premiunBookingInFrontOfSea'] = 'Reserva premium frente al mar';
    $lang['bookingYourNextStayWithAFlawlessVisualExperience'] = 'Reserva tu próxima estancia con una experiencia visual impecable.';
    $lang['chooseYourDates'] = 'Elige tus fechas, ajusta tus huéspedes y encuentra habitaciones disponibles al instante con una búsqueda realmente funcional.';
    $lang['bookNow'] = 'Reservar ahora';
    $lang['exploreExperiences'] = 'Explorar experiencias';
    $lang['dates'] = 'Fechas';

    // Autenticación, Login y Registro ($log)
    $log['titulo'] = 'Acceso Huéspedes';
    $log['saludo'] = 'Sea Bienvenido';
    $log['correo'] = 'Correo';
    $log['contrasena'] = 'Contraseña';
    $log['iniciarSesion'] = 'Iniciar sesión';
    $log['olvidoContrasena'] = '¿Has olvidado tu contraseña?';
    $log['noTienesCuenta'] = '¿No tienes cuenta? Regístrate aquí.';
    $log['yaTienesCuenta'] = '¿Ya tienes cuenta? Inicia sesión.';
    $log['registroTitulo'] = 'Únete a Aurora';
    $log['nombreCompleto'] = 'Nombre completo';
    $log['crearCuenta'] = 'Crear cuenta';
    $log['registroExitoso'] = 'Registro exitoso. Ya puedes iniciar sesión.';
    $log['consentimientoDatos'] = 'Acepto el tratamiento de mis datos personales para la gestión de mi reserva y atención del servicio, conforme a la política de privacidad del hotel.';

    // Recuperar / Restablecer Contraseña
    $log['recuperarTitulo'] = 'Recuperar contraseña';
    $log['restablecerTitulo'] = 'Restablecer contraseña';
    $log['nuevaContrasena'] = 'Nueva contraseña';
    $log['confirmarContrasena'] = 'Confirmar contraseña';
    $log['enviarInstrucciones'] = 'Enviar instrucciones';
    $log['actualizarContrasena'] = 'Actualizar contraseña';
    $log['volverLogin'] = 'Volver al inicio de sesión';

    // Mensajes de error del login/registro
    $log['err_rol'] = 'Esta cuenta no pertenece al panel de huéspedes.';
    $log['err_vacio'] = 'Por favor completa todos los campos.';
    $log['err_email'] = 'Ingresa un correo válido.';
    $log['err_credenciales'] = 'Correo o contraseña incorrectos.';
    $log['err_captcha'] = 'Debes marcar la casilla: No soy un robot.';
    $log['err_consentimiento'] = 'Debes aceptar el tratamiento de tus datos personales para continuar.';
    $log['err_conexion'] = 'No se pudo conectar con la base de datos.';
    $log['err_bd'] = 'Ocurrió un error al procesar la solicitud.';
    $log['err_correo_duplicado'] = 'Este correo ya está registrado.';
    $log['exitoEnvioCorreo'] = 'Correo enviado correctamente. Revisa tu bandeja de entrada (y la carpeta de spam).';
    $log['errorEnvioCorreo'] = 'Se intentó enviar el correo pero no fue posible completar el envío. Por favor contacta soporte si no recibes instrucciones.';
    $log['err_token_invalido'] = 'Token inválido.';
    $log['err_token_no_encontrado'] = 'Token no encontrado o ya utilizado.';
    $log['err_token_expirado'] = 'El token ha expirado.';
    $log['err_coincidencia_pass'] = 'Las contraseñas no coinciden o están vacías.';
    $log['exito_actualizacion_pass'] = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
    $log['err_actualizar_pass'] = 'No se pudo actualizar la contraseña.';
    $log['err_validar_token'] = 'Error al validar token.';
}