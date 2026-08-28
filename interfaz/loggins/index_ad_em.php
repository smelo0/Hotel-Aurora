<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Unificado - Hotel Aurora</title>
    <link rel="stylesheet" href="hotel_ad_em.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;700">
</head>
<body>

    <!-- Modificación: Este contenedor usa la clase login-panel reforzada en CSS para que la card del login sea visible. -->
    <div class="login-panel">
        <form id="form-acceso" action="../../controladores/validar_acceso.php" method="POST">
            <h2>Ingrese al Sistema</h2>

            <div class="input-box">
                <input type="email" name="correo_electronico" id="correo" required>
                <label for="correo">Correo Electronico</label>
            </div>

            <div class="input-box">
                <input type="password" name="contrasena" id="clave" required>
                <label for="clave">Contrasena</label>
            </div>

            <button type="submit" class="btn">INGRESAR</button>

            <div class="switch-text">
                <p>Problemas con el acceso? <a href="#">Contactar a soporte</a></p>
            </div>
        </form>
    </div>

    <?php
    $ayudaSistemaRol = 'admin';
    require_once '../../includes/system_help.php';
    ?>

</body>
</html>
