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

if ($idioma_actual === 'en') {
    // landingPage
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

    // Registro
    $log['greeting'] = "Welcome!";
} else {
    $lang['title'] = 'Hotel Aurora | Reservas & Experiencias';
    $lang['hotel'] = 'Hotel Aurora';
    $lang['rooms'] = 'Habitaciones';
    $lang['experiences'] = 'Experiencias';
    $lang['booking'] = 'Agenda';
    $lang['greating'] = 'Hola, ';
    $lang['logOut'] = 'Cerrar Sesión';
    $lang['logIn'] = 'Iniciar Sesión';
    $lang['signIn'] = 'Registrarse';
    $lang['premiunBookingInFrontOfSea'] = 'Reserva premiun frente al mar';
    $lang['bookingYourNextStayWithAFlawlessVisualExperience'] = 'Reserva tu próxima estancia con una experiencia visual impecable.';
    $lang['chooseYourDates'] = 'Elige tus fechas, ajusta tus huéspedes y encuentra habitaciones disponibles al instante con una búsqueda realmente funcional.';
    $lang['bookNow'] = 'Reservar ahora';
    $lang['exploreExperiences'] = 'Explorar experiencias';
    $lang['dates'] = 'Fechas';

    // Registros
    
    $log['saludo'] = "Sea Bienvenido";
    $log['registroExitoso'] = "Registro exitoso. Ya puedes iniciar sesion.";
    $log['correo'] = 'Correo';
    
}