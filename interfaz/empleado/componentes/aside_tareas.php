<!-- Modificación: Se mantiene el panel lateral de tareas con la estética limpia del panel empleado. -->
<aside id="panelTareasDerecho" class="w-16 bg-[#f3f7f7] border-l border-primary/10 px-3 transition-all duration-300 ease-out h-screen flex flex-col shadow-[-8px_0_30px_rgba(44,94,94,0.05)]">
    
    <!-- Modificación: Se redujo el margen y padding de la cabecera para que el icono y la línea queden alineados al minimizar. -->
    <!-- Modificación: Se redujo aún más el padding inferior y el gap para unir visualmente el icono con la barra de la cola de tareas. -->
    <div id="cabeceraColaTareas" class="flex items-center justify-between mb-3 border-b border-primary/10 pb-1 pt-3 transition-all flex-col gap-0">
        <button id="botonColaTareas" onclick="abrirColaTareas()" class="flex items-center gap-3 text-left transition-all">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined text-primary text-2xl">list_alt</span>
                <span id="badgeNotificaciones" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full shadow-sm hidden transition-all">0</span>
            </div>
            <h3 id="tituloColaTareas" class="font-black text-xs text-primary tracking-widest uppercase whitespace-nowrap transition-all opacity-0 w-0 overflow-hidden">Cola de Tareas</h3>
        </button>
        <button id="flechaCerrarColaTareas" onclick="cerrarColaTareas(event)" class="cola-arrow-btn opacity-0 w-0 overflow-hidden pointer-events-none" aria-label="Cerrar cola de tareas" type="button">
            <span class="material-symbols-outlined text-[20px]">keyboard_double_arrow_right</span>
        </button>
    </div>

    <div id="contenedorTareas" class="space-y-4 flex-1 overflow-y-auto no-scrollbar transition-all duration-300 opacity-0 pointer-events-none">
        </div>
</aside>
