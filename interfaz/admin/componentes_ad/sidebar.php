<!-- Modificación: Se sincronizó el contenedor lateral del administrador con la estética del sidebar del panel empleado. -->
<aside id="adminSidebar" class="fixed left-0 top-0 h-full overflow-y-auto py-8 bg-white w-64 flex flex-col z-40 border-r border-slate-200 shadow-[8px_0_24px_rgba(15,23,42,0.05)]">
    <div class="admin-sidebar-toggle-wrap">
        <button id="adminSidebarToggle" type="button" onclick="alternarSidebarAdmin()" class="admin-sidebar-toggle" aria-label="Contraer menú" aria-expanded="true" title="Contraer menú">
            <span class="material-symbols-outlined">left_panel_close</span>
        </button>
    </div>
    <div class="admin-brand">
        <span class="admin-brand-mark">
            <img src="../../assets/images/logo.jpeg" alt="" aria-hidden="true">
        </span>
        <span class="admin-brand-copy">
            <h1>Hotel Aurora</h1>
            <p>Panel de control</p>
        </span>
    </div>

    <!-- Modificación: Se igualó la estructura de navegación con el panel empleado para que el active state funcione igual. -->
    <nav class="flex-1 flex flex-col">
        <!-- Modificación: Se reemplazó el botón tipo píldora por el estilo lineal activo del panel empleado. -->
        <button data-permiso="dashboard.ver" onclick="navegar('dashboard', this)" aria-label="Dashboard" title="Dashboard" class="nav-item active-nav relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">dashboard</span><span class="text-sm font-bold">Dashboard</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="reservas.ver" onclick="navegar('reservas', this)" aria-label="Reservas" title="Reservas" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">calendar_month</span><span class="text-sm font-bold">Reservas</span>
        </button>
        <!-- Nueva Sección: ítem de navegación para Experiencias -->
        <button data-permiso="experiencias.ver" onclick="navegar('experiencias', this)" aria-label="Experiencias" title="Experiencias" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">celebration</span><span class="text-sm font-bold">Experiencias</span>
        </button>
        <!-- Nueva Sección: gestión de habitaciones (añadir, editar, eliminar y describir) -->
        <button data-permiso="habitaciones.ver" onclick="navegar('habitaciones', this)" aria-label="Habitaciones" title="Habitaciones" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">meeting_room</span><span class="text-sm font-bold">Habitaciones</span>
        </button>
        <!-- Nueva Sección: ítem de navegación para Gestión de Roles y Permisos -->
        <button data-permiso="roles.ver" onclick="navegar('roles', this)" aria-label="Roles y Permisos" title="Roles y Permisos" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">admin_panel_settings</span><span class="text-sm font-bold">Roles y Permisos</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="operaciones.ver" onclick="navegar('operaciones', this)" aria-label="Operaciones" title="Operaciones" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">bed</span><span class="text-sm font-bold">Operaciones</span>
        </button>
        <!-- Modificación: Se aplicó el mismo estilo de item de navegación del panel empleado. -->
        <button data-permiso="finanzas.ver" onclick="navegar('finanzas', this)" aria-label="Finanzas" title="Finanzas" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">payments</span><span class="text-sm font-bold">Finanzas</span>
        </button>

        <button data-permiso="logs.ver" onclick="navegar('logs', this)" aria-label="Logs" title="Logs" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">history</span><span class="text-sm font-bold">Logs</span>
        </button>

        <!-- Modificación: Se ajustó el bloque de cierre de sesión al espaciado limpio del panel empleado. -->
        <div class="admin-sidebar-footer px-6 mt-auto pb-8 pt-10 border-t border-slate-100 space-y-3">
            <button type="button" onclick="abrirModal()" aria-label="Nueva Tarea" title="Nueva Tarea" class="w-full py-4 bg-primary text-white rounded-lg font-bold flex items-center justify-center gap-2 shadow-lg hover:brightness-110 transition-all">
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