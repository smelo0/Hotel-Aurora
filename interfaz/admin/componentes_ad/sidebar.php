<!-- Modificación: Se sincronizó el contenedor lateral del administrador con la estética del sidebar del panel empleado. -->
<aside class="fixed left-0 top-0 h-full py-8 bg-[#f7fafa] w-64 flex flex-col z-40 border-r border-primary/15 shadow-[8px_0_30px_rgba(44,94,94,0.06)]">
    <!-- Modificación: Se ajustó el espaciado del encabezado para coincidir con el panel empleado. -->
    <div class="mb-12 px-8">
        <h1 class="text-xl font-black tracking-tighter text-primary">HOTEL AURORA</h1>
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Panel de Control</p>
    </div>

    <!-- Modificación: Se igualó la estructura de navegación con el panel empleado para que el active state funcione igual. -->
    <nav class="flex-1 flex flex-col">
        <!-- Modificación: Se reemplazó el botón tipo píldora por el estilo lineal activo del panel empleado. -->
        <button data-permiso="dashboard.ver" onclick="navegar('dashboard', this)" class="nav-item active-nav relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">dashboard</span><span class="text-sm font-bold">Dashboard</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="reservas.ver" onclick="navegar('reservas', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">calendar_month</span><span class="text-sm font-bold">Reservas</span>
        </button>
        <!-- Nueva Sección: ítem de navegación para Gestión de Roles y Permisos -->
        <button data-permiso="roles.ver" onclick="navegar('roles', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">admin_panel_settings</span><span class="text-sm font-bold">Roles y Permisos</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="operaciones.ver" onclick="navegar('operaciones', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">bed</span><span class="text-sm font-bold">Operaciones</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="finanzas.ver" onclick="navegar('finanzas', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">payments</span><span class="text-sm font-bold">Finanzas</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="configuracion.ver" onclick="navegar('configuracion', this)" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">security</span><span class="text-sm font-bold">Seguridad</span>
        </button>

        <!-- Modificación: Se ajustó el bloque de cierre de sesión al espaciado limpio del panel empleado. -->
        <div class="px-6 mt-auto pb-8 pt-10 border-t border-slate-100 space-y-3">
            <button type="button" onclick="abrirModal()" class="w-full py-4 bg-primary text-white rounded-lg font-bold flex items-center justify-center gap-2 shadow-lg hover:brightness-110 transition-all">
                <span class="text-xs uppercase tracking-widest">Nueva Tarea</span>
                <span class="material-symbols-outlined text-sm">add</span>
            </button>

            <a href="#" onclick="abrirModalLogout(event)" class="flex items-center justify-center gap-3 text-red-400 font-bold px-4 py-3 rounded-xl hover:bg-red-50 transition-colors">
                <span class="material-symbols-outlined">logout</span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</aside>
