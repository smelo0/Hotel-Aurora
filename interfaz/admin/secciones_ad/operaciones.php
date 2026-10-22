<section id="sec-operaciones" class="seccion-contenido hidden">

    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">

        <h3 class="text-2xl font-black text-primary tracking-tight mb-2">Control de HouseKeeping</h3>
        <p class="text-xs text-slate-400 mb-8">Estado actual de las habitaciones.</p>

        <div class="grid grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-4" id="gridHousekeeping">
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
                <div class="w-4 h-4 bg-sky-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Reservada</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-stone-600 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Sucia</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Mantenimiento</span>
            </div>
        </div>

    </div>
</section>