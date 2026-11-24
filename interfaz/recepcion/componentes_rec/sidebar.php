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
            <p>Recepción</p>
        </span>
    </div>

    <nav class="flex-1 flex flex-col">
        <button data-permiso="dashboard.ver" onclick="navegar('dashboard', this)" aria-label="Inicio" title="Inicio" class="nav-item active-nav relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">dashboard</span><span class="text-sm font-bold">Inicio</span>
        </button>
        <button data-permiso="reservas.ver" onclick="navegar('reservas', this)" aria-label="Reservas" title="Reservas" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">calendar_month</span><span class="text-sm font-bold">Reservas</span>
        </button>
        <button data-permiso="operaciones.ver" onclick="navegar('operaciones', this)" aria-label="Habitaciones" title="Habitaciones" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">bed</span><span class="text-sm font-bold">Habitaciones</span>
        </button>
        <button data-permiso="roomservice.estado" onclick="navegar('rsrecepcion', this)" aria-label="Room Service" title="Room Service" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">room_service</span><span class="text-sm font-bold">Room Service</span>
            <span id="rsEmpleadoBadge" class="hidden ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white">0</span>
        </button>

        <button data-permiso="facturas.ver" onclick="navegar('facturas', this)" aria-label="Facturas" title="Facturas" class="nav-item relative flex items-center gap-4 px-8 py-4 text-slate-400 hover:text-primary transition-all">
            <span class="material-symbols-outlined">receipt_long</span><span class="text-sm font-bold">Facturas</span>
        </button>

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