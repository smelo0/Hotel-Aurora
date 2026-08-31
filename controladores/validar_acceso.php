<?php
session_start();
require_once '../configuracion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../interfaz/loggins/index_ad_em.php");
    exit();
}

$correo = $_POST['correo_electronico'] ?? '';
$password_ingresada = $_POST['contrasena'] ?? '';

$sql = "SELECT id_usu, nom_usu, corr_usu, cod_rol_usu, psw_usu FROM usuario WHERE corr_usu = ?";
/**@var mysqli $conexion */
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    if (password_verify($password_ingresada, $usuario['psw_usu'])) {
        // Seguridad: Espacio de nombres de sesión único para evitar conflicto con panel de Usuario.
        $_SESSION['emp_auth'] = [
            'id_usuario' => (int) $usuario['id_usu'],
            'nombre_usuario' => $usuario['nom_usu'],
            'rol_usuario' => (int) $usuario['cod_rol_usu'],
            'correo_usuario' => (string) $usuario['corr_usu']
        ];

        switch ((int) $usuario['cod_rol_usu']) {
            case 1:
            case 2:
                header("Location: ../interfaz/admin/index_ad.php");
                break;

            case 3:
            case 4:
            case 5:
                header("Location: ../interfaz/empleado/index.php");
                break;

            case 6:
                header("Location: ../interfaz_usu.php");
                break;

            default:
                header("Location: ../interfaz/loggins/index_ad_em.php?error=rol");
                break;
        }
        exit();
    }

    header("Location: ../interfaz/loggins/index_ad_em.php?error=clave");
    exit();
}

header("Location: ../interfaz/loggins/index_ad_em.php?error=usuario");
exit();
?>
