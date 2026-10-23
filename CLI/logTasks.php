<?php
require __DIR__ . '/security/conexion.php';

echo "" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "=====Bienvenido al panel de tareas del administrador=====" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "" . PHP_EOL;

// Inicializamos la variable de control fuera del bucle
$sessionLoged = false;

do {

    echo "Inicie sesión para continuar" . PHP_EOL;
    $userEmailTyped = (string) trim(readline("Ingrese su correo: "));
    $passwordTyped = (string) trim(readline("Ingrese su contraseña: "));

    // consulta base de datos
    try {
        $sql = "SELECT id_usu, nom_usu, corr_usu, psw_usu, cod_rol_usu FROM usuario WHERE corr_usu = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$userEmailTyped]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (($usuario && password_verify($passwordTyped, $usuario['psw_usu'])) && ($usuario['cod_rol_usu'] == "1")){
            echo "\n[¡ACCESO PERMITIDO!] Bienvenid@, " . $usuario['nom_usu'] . "\n";
            

            $sessionData = [
                'id_usu' => $usuario['id_usu'],
                'corr_usu' => $usuario['corr_usu'],
                'cod_rol_usu' => $usuario['cod_rol_usu']
            ];

            file_put_contents(__DIR__ . '/session.json', json_encode($sessionData));

            require __DIR__ . "/options/selectTasks.php";
            $sessionLoged = true;
            break;
        
        } else {
            echo "[¡ACCESO DENEGADO!] Clave o usuario incorrecto. " . PHP_EOL;
            echo "" . PHP_EOL;
        };
    } catch (PDOException $e) {
        echo "Error en la consulta: " . $e->getMessage() . "\n";
    }

} while ($sessionLoged != true);

