<?php if (!empty($puedeRoomServiceEmpleado)): ?>
<section id="sec-roomservice" class="seccion-contenido hidden">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-primary">Room Service</h2>
            <p class="mt-1 text-xs text-slate-400">Pedidos de los huéspedes. Marca cada uno como pendiente, en preparación o entregado.</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="rsEmpleadoActualizado" class="text-[11px] font-bold text-slate-400"></span>
            <button type="button" id="rsEmpleadoRefrescar" class="flex items-center gap-1 rounded-lg border border-primary/20 bg-white px-3 py-2 text-xs font-bold text-primary hover:bg-primary/5">
                <span class="material-symbols-outlined text-sm">refresh</span> Actualizar
            </button>
        </div>
    </div>
    <div id="rsEmpleadoMensaje" class="hidden mb-4 rounded-lg px-4 py-3 text-sm font-bold" role="alert" aria-live="polite"></div>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3" id="rsEmpleadoColumnas">
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-widest text-amber-700">Pendientes</h3>
                <span id="rsContPendiente" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-black text-amber-700">0</span>
            </div>
            <div id="rsColPendiente" class="space-y-3"></div>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-widest text-sky-700">En preparación</h3>
                <span id="rsContPreparacion" class="rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] font-black text-sky-700">0</span>
            </div>
            <div id="rsColPreparacion" class="space-y-3"></div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-widest text-emerald-700">Entregados (24 h)</h3>
                <span id="rsContEntregado" class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-700">0</span>
            </div>
            <div id="rsColEntregado" class="space-y-3"></div>
        </div>
    </div>
</section>
<?php endif; ?>