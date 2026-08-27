<?php
session_start();

require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$clientID = $_ENV['GOOGLE_CLIENT_ID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    
    $id_token = $_POST['credential'];

    // Tolerancia por si el reloj local tiene un leve desfase
    \Firebase\JWT\JWT::$leeway = 300;

    $client = new Google_Client(['client_id' => $clientID]);
    $payload = $client->verifyIdToken($id_token);

    if ($payload) {
        // --- AQUÍ RECEPCIONAS LOS DATOS EN VARIABLES ---
        $google_id = $payload['sub'];            // ID único e inmutable del usuario
        $email     = $payload['email'];          // Correo electrónico
        $nombre    = $payload['name'];           // Nombre completo
        $foto      = $payload['picture'];        // URL de la foto de perfil
        $primer_nombre = $payload['given_name'];  // Primer nombre
        $apellido  = $payload['family_name'];    // Apellido

        // Guardar en variables de sesión
        $_SESSION['id_usuario']     = $google_id;
        $_SESSION['usuario_email']  = $email;
        $_SESSION['nombre_usuario'] = $nombre;
        $_SESSION['usuario_foto']   = $foto;

        // Opcional: Aquí podrías hacer un INSERT o SELECT en tu base de datos (MySQL)
        // para verificar si el correo ya existe en tu sistema.

        // 2. Redirigir al panel principal de tu hotel
        header('Location: ../interfaz_usu.php'); // Ajusta la ruta a tu página de inicio
        exit();

    } else {
        // Si por alguna razón falla la validación
        header('Location: ../login.php?error=invalid_token');
        exit();
    }

} else {
    // Si intentan entrar al callBack.php directamente desde la URL
    header('Location: ../login.php');
    exit();
}