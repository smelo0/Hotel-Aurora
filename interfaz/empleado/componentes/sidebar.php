<!-- // Corrección: Se sincronizó el layout y la posición de los botones con el diseño del Panel de Administrador según referencia visual. -->
<aside class="empleado-sidebar fixed left-0 top-0 h-full py-8 w-64 flex flex-col z-40">
    <div class="empleado-brand mb-12 px-8">
        <img src="../../assets/images/logo.jpeg" alt="Logo Hotel Aurora" class="empleado-brand-logo">
        <h1 class="text-xl font-black tracking-tighter text-primary">HOTEL AURORA</h1>
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Administración</p>
    </div>
    
    <nav class="flex-1 flex flex-col">
        <button onclick="navegar('dashboard', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">dashboard</span><span class="text-sm font-bold">Panel Hoy</span>
        </button>
        <button onclick="navegar('habitaciones', this)" class="nav-item active-nav relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">hotel</span><span class="text-sm font-bold">Habitaciones</span>
        </button>
        <button onclick="navegar('limpieza', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">cleaning_services</span><span class="text-sm font-bold">Limpieza</span>
        </button>
        <button onclick="navegar('huespedes', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">group</span><span class="text-sm font-bold">Huéspedes</span>
        </button>

        <!-- Modificación: Nueva Tarea y Cerrar Sesión quedan juntos en la sección inferior, igual que en el sidebar del administrador. -->
        <div class="px-6 mt-auto pb-8 pt-10 border-t border-slate-100 space-y-3">
            <button type="button" onclick="abrirModal()" class="w-full py-4 bg-primary text-white rounded-lg font-bold flex items-center justify-center gap-2 shadow-lg hover:brightness-110 transition-all">
                <span class="text-xs uppercase tracking-widest">Nueva Tarea</span>
                <span class="material-symbols-outlined text-sm">add</span>
            </button>

            <!-- // Modificación: Se implementó el modal de confirmación de salida idéntico al del Panel de Administrador. -->
            <a href="#" onclick="abrirModalLogoutEmpleado(event)" class="flex items-center justify-center gap-3 text-red-400 font-bold px-4 py-3 rounded-xl hover:bg-red-50 transition-colors">
                <span class="material-symbols-outlined">logout</span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</aside>

<!-- // Modificación: Se implementó el modal de confirmación de salida idéntico al del Panel de Administrador. -->
<div id="modalLogoutEmpleado" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="cajaLogoutEmpleado">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl">logout</span>
            </div>
            <h4 class="text-xl font-black text-slate-800 mb-2">Cerrar Sesión</h4>
            <p class="text-sm text-slate-500">Estás a punto de salir del sistema. Asegúrate de haber guardado todos tus cambios.</p>
        </div>
        
        <div class="flex border-t border-slate-100">
            <button onclick="cerrarModalLogoutEmpleado()" class="flex-1 px-6 py-4 text-sm font-bold text-slate-400 hover:bg-slate-50 transition-colors border-r border-slate-100" type="button">
                Seguir trabajando
            </button>
            <a href="../../controladores/logout.php" class="flex-1 px-6 py-4 text-sm font-black text-red-500 hover:bg-red-50 transition-colors text-center">
                Salir
            </a>
        </div>
    </div>
</div>

<script>
    // Modificación: Se implementó el modal de confirmación de salida idéntico al del Panel de Administrador.
    function abrirModalLogoutEmpleado(event) {
        if (event) event.preventDefault();

        const modal = document.getElementById('modalLogoutEmpleado');
        const caja = document.getElementById('cajaLogoutEmpleado');

        modal.classList.remove('hidden');
        setTimeout(() => {
            caja.classList.remove('scale-95', 'opacity-0');
            caja.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function cerrarModalLogoutEmpleado() {
        const modal = document.getElementById('modalLogoutEmpleado');
        const caja = document.getElementById('cajaLogoutEmpleado');

        caja.classList.remove('scale-100', 'opacity-100');
        caja.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }
</script>
