<header class="empleado-topbar flex-none w-full flex items-center justify-between gap-6 px-6 h-20 sticky top-0 z-30">
    <div>
        <h2 class="font-headline font-black text-primary text-xl tracking-tight">
            Hola, <?php echo htmlspecialchars($_SESSION['emp_auth']['nombre_usuario']); ?>
        </h2>
        <div class="flex items-center gap-2 mt-0.5">
            <p id="fechaHoy" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"></p>
            <span class="text-primary/20">•</span>
            <p id="tituloCabecera" class="text-[10px] font-bold text-secondary uppercase tracking-widest">Vista Operativa</p>
        </div>
    </div>

    <div class="login-layouts">
        <?php include __DIR__ . '/../../../includes/translate.php';?>
    </div>

    <!-- Modificación: Se eliminó el botón "Salir del Turno" del header para unificar el cierre de sesión en el sidebar. -->
    <div class="flex items-center gap-4">
        <div class="h-10 w-10 rounded-full border-2 border-primary/30 p-0.5 shadow-inner">
            <img class="w-full h-full rounded-full object-cover shadow-sm" src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=100" alt="Perfil empleado" />
        </div>
    </div>
    
</header>
