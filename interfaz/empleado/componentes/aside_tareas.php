<!-- Componente: Panel Lateral Colapsable de Cola de Tareas -->
<aside id="panelTareasDerecho" 
    data-empleado-task-panel="true"
    class="empleado-task-panel w-16 px-3 transition-all duration-300 ease-out h-screen flex flex-col relative z-20"
       aria-label="Panel lateral de tareas">
    
    <!-- Cabecera de la Cola de Tareas -->
    <div id="cabeceraColaTareas" class="flex items-center justify-between mb-3 border-b border-primary/10 pb-1 pt-3 transition-all flex-col gap-0">
        
        <!-- Activador de Apertura -->
        <button id="botonColaTareas" 
                data-action="abrir-cola" 
                type="button" 
                class="flex items-center gap-3 text-left transition-all focus:outline-none focus:ring-2 focus:ring-primary/40 rounded-lg p-1"
                title="Abrir cola de tareas">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">list_alt</span>
                <span id="badgeNotificaciones" 
                      class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full shadow-sm hidden transition-all"
                      role="status" 
                      aria-live="polite">0</span>
            </div>
            <h3 id="tituloColaTareas" class="font-black text-xs text-primary tracking-widest uppercase whitespace-nowrap transition-all opacity-0 w-0 overflow-hidden">
                Cola de Tareas
            </h3>
        </button>

        <!-- Botón de Cierre -->
        <button id="flechaCerrarColaTareas" 
                data-action="cerrar-cola" 
                type="button" 
                class="cola-arrow-btn opacity-0 w-0 overflow-hidden pointer-events-none focus:outline-none focus:ring-2 focus:ring-primary/40" 
                aria-label="Cerrar cola de tareas">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">keyboard_double_arrow_right</span>
        </button>
    </div>

    <!-- Contenedor Dinámico para Inyección de Tareas Vía JS -->
    <div id="contenedorTareas" 
         class="space-y-4 flex-1 overflow-y-auto no-scrollbar transition-all duration-300 opacity-0 pointer-events-none"
         aria-live="polite">
        <!-- Renderizado dinámico vía JavaScript -->
    </div>
</aside>