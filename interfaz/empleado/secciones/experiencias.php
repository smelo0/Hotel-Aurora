<section id="sec-experiencias" class="seccion-contenido hidden">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-primary">Experiencias</h2>
            <p class="mt-1 text-xs text-slate-400">Grupos de huéspedes que realizarán cada experiencia, por día y hora.</p>
        </div>
        <div class="flex items-center gap-3">
            <span id="expEmpActualizado" class="text-[11px] font-bold text-slate-400"></span>
            <button type="button" id="expEmpRefrescar" class="flex items-center gap-1 rounded-lg border border-primary/20 bg-white px-3 py-2 text-xs font-bold text-primary hover:bg-primary/5">
                <span class="material-symbols-outlined text-sm">refresh</span> Actualizar
            </button>
        </div>
    </div>
    <div class="mb-5 flex flex-wrap gap-2" id="expEmpFiltros" role="tablist" aria-label="Filtrar experiencias">
        <button type="button" data-exp-filtro="hoy" class="exp-filtro rounded-full border px-4 py-1.5 text-[11px] font-black uppercase tracking-wide">Hoy</button>
        <button type="button" data-exp-filtro="proximos" class="exp-filtro rounded-full border px-4 py-1.5 text-[11px] font-black uppercase tracking-wide">Próximos 7 días</button>
        <button type="button" data-exp-filtro="todos" class="exp-filtro rounded-full border px-4 py-1.5 text-[11px] font-black uppercase tracking-wide">Todas</button>
    </div>
    <div id="expEmpMensaje" class="hidden mb-4 rounded-lg px-4 py-3 text-sm font-bold" role="alert" aria-live="polite"></div>
    <div id="expEmpContenedor" class="space-y-8"></div>
</section>