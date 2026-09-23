<?php
// 1. Importas tu archivo de conexión existente
require_once 'conexion.php'; // Cambia 'conexion.php' por el nombre real de tu archivo de conexión

// 2. Procesar el formulario cuando el administrador guarda una nueva opción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_experiencia'])) {
    $categoria = $_POST['categoria'];
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);

    if (!empty($categoria) && !empty($nombre) && !empty($descripcion)) {
        // Ajusta $conexion si tu variable se llama $conn o $db
        /**@var mysqli $conexion */
        $stmt = mysqli_prepare($conexion, "INSERT INTO experiencias (categoria, nombre, descripcion) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $categoria, $nombre, $descripcion);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: admin_experiencias.php?status=success");
        exit;
    }
}

// 3. Procesar eliminación de opciones
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
     /**@var mysqli $conexion */
    $stmt = mysqli_prepare($conexion, "DELETE FROM experiencias WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: admin_experiencias.php?status=deleted");
    exit;
}

// 4. Obtener todas las experiencias registradas usando tu conexión
$sql = "SELECT * FROM experiencias ORDER BY categoria, id DESC";
 /**@var mysqli $conexion */
$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Experiencias</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-950 text-white min-h-screen p-6 md:p-10">
    <div class="max-w-4xl mx-auto bg-gray-900 border border-gray-800 p-6 shadow-2xl">
        
        <h2 class="text-2xl font-black uppercase tracking-wider text-blue-400 mb-6 border-b border-gray-800 pb-3">
            Gestión de Experiencias y Opciones
        </h2>

        <!-- Formulario para agregar una nueva opción -->
        <form action="" method="POST" class="space-y-4 mb-8 bg-gray-800/50 p-5 border border-gray-700">
            <h3 class="font-bold text-lg text-white">Agregar Nueva Opción</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase text-gray-400 mb-1">Categoría</label>
                    <select name="categoria" required class="w-full bg-gray-900 border border-gray-700 p-2.5 text-white text-sm focus:border-blue-500 outline-none">
                        <option value="">-- Seleccionar Categoría --</option>
                        <option value="planes">Planes Especiales</option>
                        <option value="actividades">Actividades</option>
                        <option value="gastronomia">Gastronomía</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase text-gray-400 mb-1">Nombre de la opción</label>
                    <input type="text" name="nombre" placeholder="Ej. Caminata al Anochecer" required class="w-full bg-gray-900 border border-gray-700 p-2.5 text-white text-sm focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs uppercase text-gray-400 mb-1">Descripción corta</label>
                <textarea name="descripcion" rows="2" placeholder="Detalles o resumen del servicio..." required class="w-full bg-gray-900 border border-gray-700 p-2.5 text-white text-sm focus:border-blue-500 outline-none"></textarea>
            </div>

            <button type="submit" name="crear_experiencia" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 uppercase tracking-wider text-xs transition">
                Guardar Opción
            </button>
        </form>

        <!-- Tabla de opciones existentes -->
        <h3 class="font-bold text-lg text-white mb-3">Opciones Registradas</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse border border-gray-800">
                <thead>
                    <tr class="bg-gray-800 text-xs uppercase text-gray-400 border-b border-gray-700">
                        <th class="p-3 border-r border-gray-700">Categoría</th>
                        <th class="p-3 border-r border-gray-700">Nombre</th>
                        <th class="p-3 border-r border-gray-700">Descripción</th>
                        <th class="p-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 text-sm">
                    <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                            <tr class="hover:bg-gray-800/40">
                                <td class="p-3 border-r border-gray-800 font-bold uppercase text-xs text-blue-400">
                                    <?= htmlspecialchars($row['categoria']); ?>
                                </td>
                                <td class="p-3 border-r border-gray-800 font-medium text-white">
                                    <?= htmlspecialchars($row['nombre']); ?>
                                </td>
                                <td class="p-3 border-r border-gray-800 text-gray-300 text-xs">
                                    <?= htmlspecialchars($row['descripcion']); ?>
                                </td>
                                <td class="p-3 text-center">
                                    <a href="?eliminar=<?= $row['id']; ?>" onclick="return confirm('¿Estás seguro de eliminar esta opción?')" class="text-red-500 hover:text-red-400 font-bold text-xs uppercase">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-500 text-xs italic">
                                Aún no has registrado opciones.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>