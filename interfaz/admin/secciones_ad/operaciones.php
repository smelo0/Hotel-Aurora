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

<script>
// Solo se define si el archivo principal NO trae ya un renderHousekeeping.
if (typeof window.renderHousekeeping !== 'function') {
    window.renderHousekeeping = async function() {
        const grid = document.getElementById('gridHousekeeping');
        if (!grid) return;

        grid.innerHTML = '<p class="col-span-full text-xs text-slate-400">Cargando habitaciones...</p>';

        try {
            const respuesta = await fetch('../../controladores/gestionar_habitaciones.php?accion=listar', {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            });
            const datos = await respuesta.json();
            if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron cargar las habitaciones.');

            const habitaciones = datos.habitaciones || [];
            if (habitaciones.length === 0) {
                grid.innerHTML = '<p class="col-span-full text-xs text-slate-400">Aún no hay habitaciones registradas.</p>';
                return;
            }

            grid.innerHTML = habitaciones.map(h => {
                const estado = String(h.estado || 'Disponible').trim();
                const clases = {
                    'Disponible':    'bg-green-500 hover:bg-green-600',
                    'Ocupada':       'bg-orange-500 hover:bg-orange-600',
                    'Sucia':         'bg-stone-600 hover:bg-stone-700',
                    'Mantenimiento': 'bg-red-500 hover:bg-red-600'
                }[estado] || 'bg-slate-400 hover:bg-slate-500';

                return `<button type="button"
                                class="aspect-square rounded-lg ${clases} text-white font-black text-sm shadow-sm transition-transform hover:scale-105"
                                title="Habitación ${Number(h.numero)} · ${estado}"
                                data-cod-hab="${Number(h.id)}"
                                data-estado="${estado}">
                            ${Number(h.numero)}
                        </button>`;
            }).join('');
        } catch (error) {
            grid.innerHTML = `<p class="col-span-full text-xs text-red-500">${error.message}</p>`;
        }
    };
}
</script>