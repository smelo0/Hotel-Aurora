<!-- Cola de Tareas Desplegable del Administrador (idéntica al empleado) -->
<aside id="panelTareasDerechoAdmin" class="w-16 bg-[#f3f7f7] border-l border-primary/10 px-3 transition-all duration-300 ease-out flex flex-col h-screen shadow-[-8px_0_30px_rgba(44,94,94,0.05)]">
    
    <div id="cabeceraColaTareasAdmin" class="flex items-center justify-between mb-3 border-b border-primary/10 pb-1 pt-3 transition-all flex-col gap-0">
        <button id="botonColaTareasAdmin" onclick="toggleColaTareasAdmin()" class="flex items-center gap-3 text-left transition-all">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined text-primary text-2xl">list_alt</span>
                <span id="badgeNotificacionesAdmin" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full shadow-sm hidden transition-all">0</span>
            </div>
            <h3 id="tituloColaTareasAdmin" class="font-black text-xs text-primary tracking-widest uppercase whitespace-nowrap transition-all opacity-0 w-0 overflow-hidden">Cola de Tareas</h3>
        </button>
        <button id="flechaCerrarColaTareasAdmin" onclick="cerrarColaTareasAdmin(event)" class="cola-arrow-btn opacity-0 w-0 overflow-hidden pointer-events-none" aria-label="Cerrar cola de tareas" type="button">
            <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
        </button>
    </div>

    <div id="contenedorTareasAdmin" class="space-y-4 flex-1 overflow-y-auto no-scrollbar transition-all duration-300 opacity-0 pointer-events-none">
        <!-- Tarjetas inyectadas por JS -->
    </div>
</aside>
