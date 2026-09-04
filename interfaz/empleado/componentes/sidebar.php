<!-- Componente: Sidebar de Navegación Principal y Modal de Logout -->
<aside class="fixed left-0 top-0 h-full py-8 bg-surface w-64 flex flex-col z-40 border-r border-primary/15 shadow-[8px_0_30px_rgba(11,70,43,0.06)]" aria-label="Navegación principal">
    
    <!-- Identificador del Hotel -->
    <div class="mb-8 px-8">
        <h1 class="text-xl font-black tracking-tighter text-primary">HOTEL AURORA</h1>
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Panel Operativo</p>
    </div>
    
    <!-- Contenedor Principal de Navegación -->
    <nav class="flex-1 flex flex-col justify-between">
        
        <!-- Bloque Superior: Secciones Operativas -->
        <div class="space-y-1">
            <!-- 1. Dashboard -->
            <button data-nav="dashboard" class="nav-item relative flex items-center gap-4 px-8 py-3 text-slate-400 hover:text-primary transition-all text-left w-full">
                <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                <span class="text-sm font-bold">Dashboard</span>
            </button>

            <!-- 2. Calendario / Recepción Principal -->
            <button data-nav="calendario" class="nav-item active-nav relative flex items-center gap-4 px-8 py-3 text-slate-400 hover:text-primary transition-all text-left w-full">
                <span class="material-symbols-outlined" aria-hidden="true">calendar_month</span>
                <span class="text-sm font-bold">Calendario</span>
            </button>

            <!-- 3. Habitaciones -->
            <button data-nav="habitaciones" class="nav-item relative flex items-center gap-4 px-8 py-3 text-slate-400 hover:text-primary transition-all text-left w-full">
                <span class="material-symbols-outlined" aria-hidden="true">meeting_room</span>
                <span class="text-sm font-bold">Habitaciones</span>
            </button>

            <!-- 4. Caja y Ventas -->
            <button data-nav="caja" class="nav-item relative flex items-center gap-4 px-8 py-3 text-slate-400 hover:text-primary transition-all text-left w-full">
                <span class="material-symbols-outlined" aria-hidden="true">point_of_sale</span>
                <span class="text-sm font-bold">Caja y Ventas</span>
            </button>

            <!-- 5. Huéspedes -->
            <button data-nav="huespedes" class="nav-item relative flex items-center gap-4 px-8 py-3 text-slate-400 hover:text-primary transition-all text-left w-full">
                <span class="material-symbols-outlined" aria-hidden="true">group</span>
                <span class="text-sm font-bold">Huéspedes</span>
            </button>
        </div>

        <!-- Bloque Inferior: Configuración + Acciones Operativas -->
        <div class="mt-auto pt-4 border-t border-slate-100 px-6 space-y-3">
            
            <!-- 6. Configuración (Separado de la operación diaria) -->
            <button data-nav="configuracion" class="nav-item relative flex items-center gap-4 px-2 py-2 text-slate-400 hover:text-primary transition-all text-left w-full rounded-xl">
                <span class="material-symbols-outlined" aria-hidden="true">settings</span>
                <span class="text-sm font-bold">Configuración</span>
            </button>

            <!-- Botón: Nueva Tarea -->
            <button type="button" 
                    data-action="abrir-modal-tarea" 
                    class="w-full py-3.5 bg-primary text-white rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg shadow-primary/20 hover:bg-primary-hover transition-all cursor-pointer">
                <span class="text-xs uppercase tracking-widest">Nueva Tarea</span>
                <span class="material-symbols-outlined text-sm" aria-hidden="true">add</span>
            </button>

            <!-- Botón: Cerrar Sesión -->
            <button type="button" 
                    data-action="abrir-modal-logout" 
                    class="w-full flex items-center justify-center gap-3 text-red-500 font-bold px-4 py-2.5 rounded-xl hover:bg-red-50 transition-colors cursor-pointer">
                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                <span class="text-sm">Cerrar Sesión</span>
            </button>
        </div>
    </nav>
</aside>

<!-- Modal de Confirmación para Cerrar Sesión -->
<div id="modalLogoutEmpleado" 
     class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-4 transition-all"
     role="dialog"
     aria-modal="true"
     aria-labelledby="tituloModalLogout">
     
    <div id="cajaLogoutEmpleado" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-4xl" aria-hidden="true">logout</span>
            </div>
            <h4 id="tituloModalLogout" class="text-xl font-black text-slate-800 mb-2">Cerrar Sesión</h4>
            <p class="text-sm text-slate-500">Estás a punto de salir del sistema. Asegúrate de haber guardado todos tus cambios.</p>
        </div>
        
        <div class="flex border-t border-slate-100">
            <button type="button" 
                    data-action="cerrar-modal-logout" 
                    class="flex-1 px-6 py-4 text-sm font-bold text-slate-400 hover:bg-slate-50 transition-colors border-r border-slate-100 cursor-pointer">
                Seguir trabajando
            </button>
            <a href="../../controladores/logout.php" 
                class="flex-1 px-6 py-4 text-sm font-black text-red-500 hover:bg-red-50 transition-colors text-center">
                Salir
            </a>
        </div>
    </div>
</div>