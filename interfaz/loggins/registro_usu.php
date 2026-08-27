<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aurora | Registrar Usuario</title>
    <link rel="stylesheet" href="../../estetica/estilos_usu.css">
</head>
<body>
    <?php $error = isset($_GET['error']) ? (string) $_GET['error'] : ''; ?>

    <div class="container">
        <form method="POST" action="../../controladores/guardar_registro.php">
            <h1>Registro de huesped</h1>

            <h4 class="mensaje" style="color: #ef4444; font-weight: bold;">
                <?php
                if ($error === 'vacio') {
                    echo 'Por favor, completa todos los campos.';
                } elseif ($error === 'correo_duplicado') {
                    echo 'Este correo ya esta registrado.';
                } elseif ($error === 'email') {
                    echo 'Ingresa un correo valido.';
                } elseif (in_array($error, ['conexion_fallida', 'bd_preparacion', 'bd_insercion', 'bd_ejecucion'], true)) {
                    echo 'No se pudo completar el registro. Intenta de nuevo.';
                }
                ?>
            </h4>

            <h4>Tipo de documento</h4>
            <select name="tipo" required>
                <option value="" disabled selected>Seleccione el tipo</option>
                <option value="TI">Tarjeta de identidad</option>
                <option value="CC">Cedula de ciudadania</option>
                <option value="CE">Cedula de extranjeria</option>
            </select>

            <h4>Numero de documento</h4>
            <input type="number" name="num_documento" placeholder="Numero de documento" required>

            <h4>Nombre completo</h4>
            <input type="text" name="nombre" placeholder="Nombre completo" required>

            <h4>Numero de telefono</h4>
            <input type="number" name="telefono" placeholder="Numero de telefono" required>

            <h4>Correo electronico</h4>
            <input type="email" name="correo" placeholder="ejemplo@correo.com" required>

            <h4>Contrasena</h4>
            <input type="password" name="contrasena" placeholder="********" required>

            <a href="index_usu.php">Ya tienes cuenta? Inicia sesion aqui.</a>

            <input type="submit" value="Registrar">
        </form>
    </div>

    <script>
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('wheel', event => event.preventDefault());
        });
    </script>
</body>
</html>
