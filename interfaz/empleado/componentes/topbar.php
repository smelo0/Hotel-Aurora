<header class="flex-1 min-w-full flex items-center justify-between px-12 h-24 bg-[#f8fbfb]/90 backdrop-blur-md sticky top-0 z-30 border-b border-primary/10 shadow-sm">
    <div>
        <h2 class="font-headline font-black text-primary text-2xl tracking-tight">
            Hola, <?php echo htmlspecialchars($_SESSION['emp_auth']['nombre_usuario']); ?>
        </h2>
        <div class="flex items-center gap-2 mt-0.5">
            <p id="fechaHoy" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"></p>
            <span class="text-primary/20">•</span>
            <p id="tituloCabecera" class="text-[10px] font-bold text-secondary uppercase tracking-widest">Vista Operativa</p>
        </div>
    </div>

    <!-- Modificación: Se eliminó el botón "Salir del Turno" del header para unificar el cierre de sesión en el sidebar. -->
    <div class="flex items-center gap-6">
        <div class="h-12 w-12 rounded-full border-2 border-primary/30 p-0.5 shadow-inner">
            <img class="w-full h-full rounded-full object-cover shadow-sm" src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=100" alt="Perfil empleado" />
        </div>
    </div>
</header>
