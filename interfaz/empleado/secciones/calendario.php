<!-- Sección: Calendario de Reservas -->
<section id="sec-calendario" class="seccion-contenido hidden" aria-labelledby="tituloCalendario">
    <h2 id="tituloCalendario" class="sr-only">Calendario y Planificación de Reservas</h2>
    
    <!-- Contenedor principal para el calendario interactivo / Gantt de ocupación -->
    <div class="bg-surface border border-slate-200/80 rounded-2xl p-6 shadow-sm min-h-[500px]" id="contenedorCalendario">
        <!-- Skeleton de carga inicial mientras se hidratan las reservas -->
        <div class="animate-pulse space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="h-8 bg-slate-200/80 rounded-lg w-48"></div>
                <div class="flex gap-2">
                    <div class="h-8 bg-slate-200/60 rounded-lg w-20"></div>
                    <div class="h-8 bg-slate-200/60 rounded-lg w-20"></div>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-2 pt-2">
                <div class="h-6 bg-slate-200/60 rounded"></div>
                <div class="h-6 bg-slate-200/60 rounded"></div>
                <div class="h-6 bg-slate-200/60 rounded"></div>
                <div class="h-6 bg-slate-200/60 rounded"></div>
                <div class="h-6 bg-slate-200/60 rounded"></div>
                <div class="h-6 bg-slate-200/60 rounded"></div>
                <div class="h-6 bg-slate-200/60 rounded"></div>
            </div>
            <div class="grid grid-cols-7 gap-3 h-80 pt-2">
                <div class="bg-slate-100/80 rounded-xl"></div>
                <div class="bg-slate-100/80 rounded-xl"></div>
                <div class="bg-slate-100/80 rounded-xl"></div>
                <div class="bg-slate-100/80 rounded-xl"></div>
                <div class="bg-slate-100/80 rounded-xl"></div>
                <div class="bg-slate-100/80 rounded-xl"></div>
                <div class="bg-slate-100/80 rounded-xl"></div>
            </div>
        </div>
    </div>
</section>