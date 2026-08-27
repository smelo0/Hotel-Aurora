<?php
declare(strict_types=1);

session_start();

$panel = $_GET['panel'] ?? 'general';

if ($panel === 'emp') {
    unset($_SESSION['emp_auth']);

    if (empty($_SESSION)) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    header('Location: ../interfaz/loggins/index_ad_em.php?logout=ok');
    exit();
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

header('Location: ../interfaz_usu.php');
exit();
