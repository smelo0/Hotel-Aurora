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
    $lang['booking'] = 'Booking';
    $lang['greating'] = 'Hello, ';
    $lang['logOut'] = 'Log Out';
    $lang['logIn'] = 'Log In';
    $lang['signIn'] = 'Sign Up';
    $lang['premiunBookingInFrontOfSea'] = 'Premium beachfront booking';
    $lang['bookingYourNextStayWithAFlawlessVisualExperience'] = 'Book your next stay with a flawless visual experience.';
    $lang['chooseYourDates'] = 'Choose your dates, adjust your guests, and find available rooms instantly with a truly functional search.';
    $lang['bookNow'] = 'Book now';
    $lang['exploreExperiences'] = 'Explore experiences';
    $lang['dates'] = 'Dates';
    $lang['selectCheckin&Checkout'] = "Select check-in and check-out";
    $lang['guess'] = "Guests";
    $lang['adults&children'] = "2 adults, 0 children";
    $lang['adults'] = "Adults";
    $lang['greatherThan12'] = "Over 12 years old";
    $lang['children'] = "Children";
    $lang['0to12yo'] = "From 0 to 12 years old";
    $lang['apply'] = "Apply";
    $lang['searchdisponibility'] = "Search Availability";
    $lang['destacatedRooms'] = "Featured rooms";
    $lang['findYourNextStay'] = "Discover stays ready for your next booking.";
    $lang['availabilityInRealTime'] = "Real-time availability";
    $lang['all'] = "All";
    $lang['suite'] = "Suite";
    $lang['double'] = "Double";
    $lang['simple'] = "Single";
    $lang['onlineBook'] = "Online Booking";
    $lang['bookbtn'] = "Book";
    $lang['noAvailableRooms'] = "No rooms available for those dates.";
    $lang['changeDatesOrSearchLater'] = "Change the date range or check back in a few seconds.";
    $lang['curatedAurora'] = "Aurora Curation";
    $lang['spa&Wellness'] = "Spa & Wellness";
    $lang['relaxationMassage'] = "Relaxation rituals, premium massages, and private circuits to renew body and mind.";
    $lang['exclusiveFlavors'] = "Exclusive Flavors";
    $lang['fineCuise'] = "Fine Dining";
    $lang['costalCousin'] = "Coastal menu, signature cuisine, and elegant pairings for an unforgettable evening.";
    $lang['bookYourStay'] = "Book your stay";
    $lang['askForAExperience'] = "Request an experience before you arrive.";
    $lang['bookASpaSession'] = "Schedule a spa session, a special dinner, or a private activity for our team to prepare in advance.";
    $lang['privateSpace'] = "Private Spa";
    $lang['authorDinner'] = "Signature Dinner";
    $lang['nauticalTour'] = "Coastal Boat Tour";
    $lang['dateExperiences'] = "Date";
    $lang['hourExperience'] = "Time";
    $lang['nameExperiences'] = "Name";
    $lang['emailExperiences'] = "Email";
    $lang['emailExperiencesPlaceHolder'] = "example@correo.com";
    $lang['bookAExperience'] = "Schedule Experience";
    $lang['myHistory'] = "My History";
    $lang['doneBookings'] = "Completed Bookings";
    $lang['registers'] = "records";
    $lang['noBookingsYet'] = "You don't have any bookings registered yet.";
    $lang['whenBookingItAppearsHistory'] = "When you book a stay, the complete history will appear here.";
    $lang['bookingsHistory'] = "Bookings";
    $lang['roomHistory'] = "Room";
    $lang['checkinHistory'] = "Check-in";
    $lang['checkoutHistory'] = "Check-out";
    $lang['state'] = "Status";
    $lang['footer'] = "Exclusivity, calm, and beachfront service.";

    // Help Center
    $lang['helpCenter'] = "Help Center";
    $lang['howQuestionHelp'] = "How can we help you?";
    $lang['howSearchHelp'] = "How do I search for a room?";
    $lang['explanationSearchHelp'] = "Open “Dates”, select your check-in and check-out, adjust the guests, and click “Search Availability”";
    $lang['WhatNeededHelp'] = "What do I need to book?";
    $lang['explanationNeedHelp'] = "You must log in, choose valid dates, and select an available room. Then check the total and payment method.";
    $lang['canPayHelp'] = "Can I pay only a portion?";
    $lang['explanationPayHelp'] = "Yes. In the confirmation, you can choose full payment or an initial 50% deposit. The remaining balance is paid at the front desk.";
    $lang['howAskExperienceHelp'] = "How do I request an experience?";
    $lang['explanationAskExperienceHelp'] = "Under “Book your stay”, choose the experience, date, time, and your contact info. The team will confirm the request.";
    $lang['howEmailHelp'] = "Need more help? Write to us from your email at";
    $lang['emailHelp'] = "reservas@hotelaurora.com";
    $lang['helpbtn'] = "Help";

    // Payments Module
    $lang['confirmation&Payment'] = "Confirmation and Payment";
    $lang['selectedRoom'] = "Selected Room";
    $lang['checkBillBeforePayment'] = "Review the financial breakdown and confirm your transaction.";
    $lang['checkInPayment'] = "Check-in";
    $lang['check-outPayment'] = "Check-out";
    $lang['Guesses'] = "Guests";
    $lang['staySubtotal'] = "Stay Subtotal";
    $lang['taxesIva'] = "Taxes (VAT 19%):";
    $lang['totalPaymentStay'] = "Amount to pay now:";
    $lang['totalToBePaidPayment'] = "Amount to pay now:";
    $lang['chargeProcess'] = "Payment Process";
    $lang['paymentDetails'] = "Payment Details";
    $lang['paymentMethod'] = "Payment Method";
    $lang['totalPayment100%'] = "Full Payment (100%)";
    $lang['payNow'] = "Pay the full amount now";
    $lang['payHalf'] = "Initial Deposit (50%)";
    $lang['paytheRemainingInReception'] = "Pay the rest at the front desk";
    $lang['paymentCoso'] = "Transfer / PSE · Wompi";
    $lang['payInReception'] = "Pay at Front Desk";
    $lang['wompiSafePayment'] = "Secure payment processed by Wompi";
    $lang['cardNumber'] = "Card Number";
    $lang['cardExperation'] = "Expiration";
    $lang['bankData'] = "Bank details for wire transfer:";
    $lang['savingAc'] = "Savings Account:";
    $lang['accountHolder'] = "Account Holder: ";
    $lang['confirm&Pay'] = "Confirm and Pay";

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
    $lang['selectCheckin&Checkout'] = "Selecciona check-in y check-out";
    $lang['guess'] = "Huéspedes";
    $lang['adults&children'] = "2 adultos, 0 niños";
    $lang['adults'] = "Adultos";
    $lang['greatherThan12'] = "Mayores de 12 años";
    $lang['children'] = "Niños";
    $lang['0to12yo'] = "Desde 0 hasta 12 años";
    $lang['apply'] = "Aplicar";
    $lang['searchdisponibility'] = "Buscar Disponibilidad";
    $lang['destacatedRooms'] = "Habitaciones destacadas";
    $lang['findYourNextStay'] = "Descubre las estancias listas para tu próxima reserva.";
    $lang['availabilityInRealTime'] = "Disponibilidad en tiempo real";
    $lang['all'] = "Todas";
    $lang['suite'] = "Suite";
    $lang['double'] = "Doble";
    $lang['simple'] = "Sencillas";
    $lang['onlineBook'] = "Reserva Online";
    $lang['bookbtn'] = "Reserva";
    $lang['noAvailableRooms'] = "No hay habitaciones disponibles para esas fechas.";
    $lang['changeDatesOrSearchLater'] = "Cambia el rango de fechas o vuelve a consultar en unos segundos.";
    $lang['curatedAurora'] = "Curaduría Aurora";
    $lang['spa&Wellness'] = "Spa y bienestar";
    $lang['relaxationMassage'] = "Rituales de relajación, masajes premium y circuitos privados para renovar cuerpo y mente.";
    $lang['exclusiveFlavors'] = "Sabores exclusivos";
    $lang['fineCuise'] = "Alta gastronomía";
    $lang['costalCousin'] = "Menú costero, cocina de autor y maridajes elegantes para una velada inolvidable.";
    $lang['bookYourStay'] = "Agenda tu estancia";
    $lang['askForAExperience'] = "Solicita una experiencia antes de llegar.";
    $lang['bookASpaSession'] = "Programa una sesión de spa, una cena especial o una actividad privada para que nuestro equipo la prepare con anticipación.";
    $lang['privateSpace'] = "Spa privado";
    $lang['authorDinner'] = "Cena de autor";
    $lang['nauticalTour'] = "Paseo náutico";
    $lang['dateExperiences'] = "Fecha";
    $lang['hourExperience'] = "Hora";
    $lang['nameExperiences'] = "Nombre";
    $lang['emailExperiences'] = "Correo";
    $lang['emailExperiencesPlaceHolder'] = "example@corre.com";
    $lang['bookAExperience'] = "Programar experiencia";
    $lang['myHistory'] = "Mi historial";
    $lang['doneBookings'] = "Reservas realizadas";
    $lang['registers'] = "registros";
    $lang['noBookingsYet'] = "Aún no tienes reservas registradas.";
    $lang['whenBookingItAppearsHere'] = "Cuando reserves una estancia, aparecerá aquí el historial completo.";
    $lang['bookingsHistory'] = "Reservas";
    $lang['roomHistory'] = "Habitación";
    $lang['checkinHistory'] = "Check-in";
    $lang['checkoutHistory'] = "Check-out";
    $lang['state'] = "EstadoS";
    $lang['footer'] = "Exclusividad, calma y servicio frente al mar.";

    // centro de ayuda
    $lang['helpCenter'] = "Centro de ayuda";
    $lang['howQuestionHelp'] = "¿Cómo podemos ayudarte?";
    $lang['howSearchHelp'] = "¿Cómo busco una habitación?";
    $lang['explanationSearchHelp'] = "Abre “Fechas”, selecciona tu check-in y check-out, ajusta los huéspedes y pulsa “Buscar disponibilidad”";
    $lang['WhatNeededHelp'] = "¿Qué necesito para reservar?";
    $lang['explanationNeedHelp'] = "Debes iniciar sesión, elegir fechas válidas y seleccionar una habitación disponible. Después revisa el total y el método de pago.";
    $lang['canPayHelp'] = "¿Puedo pagar solo una parte?";
    $lang['explanationPayHelp'] = "Sí. En la confirmación puedes elegir pago total o un abono inicial del 50%. El saldo del abono se paga en recepción.";
    $lang['howAskExperienceHelp'] = "¿Cómo solicito una experiencia?";
    $lang['explanationAskExperienceHelp'] = "En “Agenda tu estancia”, elige la experiencia, fecha, hora y tus datos de contacto. El equipo confirmará la solicitud.";
    $lang['howEmailHelp'] = "¿Necesitas más ayuda? Escríbenos desde tu correo a";
    $lang['emailHelp'] = "reservas@hotelaurora.com";
    $lang['helpbtn'] = "Ayuda";

    // Modulo pagos
    $lang['confirmation&Payment'] = "Confirmación y Cobro";
    $lang['selectedRoom'] = "Habitación seleccionada";
    $lang['checkBillBeforePayment'] = "Revisa el desglose financiero y confirma tu transacción.";
    $lang['checkInPayment'] = "Check-in";
    $lang['check-outPayment'] = "Check-out";
    $lang['Guesses'] = "Huéspedes";
    $lang['staySubtotal'] = "Subtotal estadía";
    $lang['taxesIva'] = "Impuestos (IVA 19%):";
    $lang['totalPaymentStay'] = "Monto a pagar ahora:";
    $lang['totalToBePaidPayment'] = "Monto a pagar ahora:";
    $lang['chargeProcess'] = "Proceso de Cobro";
    $lang['paymentDetails'] = "Detalles de Pago";
    $lang['paymentMethod'] = "Modalidad de Cobro";
    $lang['totalPayment100%'] = "Pago Total (100%)";
    $lang['payNow'] = "Liquida el valor completo ahora";
    $lang['payHalf'] = "Abono inicial (50%)";
    $lang['paytheRemainingInReception'] = "Paga el resto en recepción";
    $lang['paymentCoso'] = "Transferencia / PSE · Wompi";
    $lang['payInReception'] = "Pago en Recepción";
    $lang['wompiSafePayment'] = "Pago seguro procesado por Wompi";
    $lang['cardNumber'] = "Número de Tarjeta";
    $lang['cardExperation'] = "Expiración";
    $lang['bankData'] = "Datos bancarios para consignación:";
    $lang['savingAc'] = "Cuenta de Ahorros:";
    $lang['accountHolder'] = "Titular: ";
    $lang['confirm&Pay'] = "Confirmar y Pagar";

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