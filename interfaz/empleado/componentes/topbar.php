<?php
// Garantizar que exista un valor por defecto si la sesión no está completamente poblada
$nombreEmpleado = $_SESSION['emp_auth']['nombre_usuario'] ?? 'Empleado';
?>
<!-- Componente: Topbar de Barra Superior -->
<header class="flex-1 min-w-full flex items-center justify-between px-8 md:px-12 h-20 bg-surface/90 backdrop-blur-md sticky top-0 z-30 border-b border-primary/10 shadow-sm">
    
    <!-- Saludo y Contexto de la Vista -->
    <div>
        <h2 class="font-headline font-black text-primary text-xl md:text-2xl tracking-tight">
            Hola, <?php echo htmlspecialchars($nombreEmpleado, ENT_QUOTES, 'UTF-8'); ?>
        </h2>
        <div class="flex items-center gap-2 mt-0.5">
            <p id="fechaHoy" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"></p>
            <span class="text-primary/20" aria-hidden="true">•</span>
            <p id="tituloCabecera" class="text-[10px] font-bold text-secondary uppercase tracking-widest">Vista Operativa</p>
        </div>
    </div>

    <!-- Perfil de Empleado -->
    <div class="flex items-center gap-4">
        <div class="flex flex-col items-end hidden sm:flex">
            <span class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($nombreEmpleado, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="text-[10px] text-slate-400 font-semibold uppercase">Recepción</span>
        </div>
        
        <div class="h-11 w-11 rounded-full border-2 border-primary/30 p-0.5 shadow-inner bg-primary/5 flex items-center justify-center">
            <!-- Icono vectorial / Avatar por defecto para evitar dependencia de Unsplash -->
            <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">account_circle</span>
        </div>
    </div>
</header>