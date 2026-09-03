<?php
// ARCHIVO: interfaz/usuario/panel_usu.php
// MISIÃ“N: Mostrar el panel de control privado del huÃ©sped.

session_start();

// EL GUARDIA DE SEGURIDAD (RBAC) PARA HUÃ‰SPEDES
// Verificamos si hay sesiÃ³n iniciada y si el rol es exactamente 3 (HuÃ©sped)
$usuario = $_SESSION['user_auth'] ?? [];
if (!isset($usuario['id_usuario']) || (int) ($usuario['rol_usuario'] ?? 0) !== 6) {
    // Si es un intruso, lo mandamos al login de usuarios
    header("Location: ../loggins/index_usu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Aurora | Mi Estancia</title>
    <script src=""></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800">
    
    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b border-slate-200">
        <div class="text-xl font-bold text-teal-700 flex items-center gap-2">
            <span class="material-symbols-outlined">hotel</span>
            Hotel Aurora
        </div>
        <div class="flex items-center gap-4">
            <span class="font-medium text-slate-600">Hola, <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></span>
            
           
                <span class="material-symbols-outlined text-sm">logout</span> Salir
                <a href="../../interfaz_usu.php" class="text-red-500 hover:bg-red-50 px-4 py-2 rounded-xl transition flex items-center gap-2 font-bold text-sm">
            </a>
        </div>
    </nav>

    <main class="w-full pb-20 md:px-6">
        <div class="bg-white rounded-3xl shadow-sm p-10 border border-slate-100 text-center">
            <div class="w-20 h-20 bg-teal-50 text-teal-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-4xl">waving_hand</span>
            </div>
            <h1 class="text-3xl font-black text-slate-800 mb-4">Bienvenido a tu panel, <?php echo htmlspecialchars($usuario['nombre_usuario']); ?>!</h1>
            <p class="text-slate-500 max-w-xl mx-auto">
                Este es tu espacio privado. Muy pronto podrÃ¡s gestionar tus reservas, revisar tus facturas y solicitar servicios a la habitaciÃ³n directamente desde aquÃ­.
            </p>
        </div>
    </main>

    <?php
    $ayudaSistemaRol = 'usuario';
    require_once '../../includes/system_help.php';
    ?>

</body>
</html>
