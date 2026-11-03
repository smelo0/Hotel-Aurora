<section id="sec-operaciones" class="seccion-contenido hidden">

    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">

        <h3 class="text-2xl font-black text-primary tracking-tight mb-2">Control de HouseKeeping</h3>
        <p class="text-xs text-slate-400 mb-8">Estado actual de las habitaciones.</p>

        <div id="mensajeHousekeeping" class="hidden mb-5 rounded-lg px-4 py-3 text-sm font-bold" role="status" aria-live="polite"></div>
        <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-8 lg:grid-cols-10 gap-3 md:gap-4" id="gridHousekeeping" aria-live="polite">
            <p class="col-span-full py-8 text-center text-sm text-slate-400">Cargando habitaciones...</p>
        </div>

        <div class="mt-10 pt-6 border-t border-slate-100 flex flex-wrap gap-6">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Disponible</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-orange-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Ocupada</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-stone-600 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Sucia</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Mantenimiento</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-4 min-w-4 items-center justify-center rounded bg-sky-100 px-1 text-[9px] font-black text-sky-800">R</span>
                <span class="text-xs font-bold text-slate-500">Con reserva futura</span>
            </div>
        </div>

    </div>
</section>