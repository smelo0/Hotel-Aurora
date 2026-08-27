<section id="sec-operaciones" class="seccion-contenido hidden">
    
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        
        <h3 class="text-2xl font-black text-primary tracking-tight mb-2">Control de HouseKeeping</h3>
        <p class="text-xs text-slate-400 mb-8">Estado actual de las habitaciones.</p>
        
        <div class="grid grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-4" id="gridHousekeeping">
        </div>

        <!-- Modificación: Se sincronizó la leyenda de Housekeeping con la paleta de estados del Panel del Empleado. -->
        <div class="mt-10 pt-6 border-t border-slate-100 flex flex-wrap gap-6">
            
            <div class="flex items-center gap-2">
                <!-- Modificación: Disponible usa verde. -->
                <div class="w-4 h-4 bg-green-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Disponible</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Modificación: Limpio usa azul claro/cian. -->
                <div class="w-4 h-4 bg-cyan-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Limpio</span>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Modificación: Ocupada usa naranja. -->
                <div class="w-4 h-4 bg-orange-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Ocupada</span>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Modificación: Sucia usa gris oscuro/café. -->
                <div class="w-4 h-4 bg-stone-600 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Sucia (Requiere Limpieza)</span>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Modificación: Mantenimiento usa rojo. -->
                <div class="w-4 h-4 bg-red-500 rounded-md"></div>
                <span class="text-xs font-bold text-slate-500">Mantenimiento</span>
            </div>
            
        </div>
        
    </div>
</section>