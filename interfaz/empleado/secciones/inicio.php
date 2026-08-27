<section id="sec-dashboard" class="seccion-contenido hidden">
    <div class="grid grid-cols-3 gap-6">
        <div onclick="redirigirDesdeDash('habitaciones')" class="metric-card bg-[#fbfdfd] p-8 rounded-xl flex flex-col justify-between h-44 cursor-pointer shadow-md">
            <div class="icon-box w-12 h-12 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">bed</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ocupadas</p>
                <h3 id="dash-ocupadas" class="text-5xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </div>
        <div onclick="redirigirDesdeDash('limpieza')" class="metric-card bg-[#fbfdfd] p-8 rounded-xl flex flex-col justify-between h-44 cursor-pointer shadow-md">
            <div class="icon-box w-12 h-12 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">mop</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Por Limpiar</p>
                <h3 id="dash-limpieza" class="text-5xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </div>
        <div onclick="redirigirDesdeDash('huespedes')" class="metric-card bg-[#fbfdfd] p-8 rounded-xl flex flex-col justify-between h-44 cursor-pointer shadow-md">
            <div class="icon-box w-12 h-12 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Huéspedes</p>
                <h3 id="dash-huespedes" class="text-5xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </div>
    </div>

    <div class="bg-primary text-white p-10 rounded-xl shadow-2xl relative overflow-hidden mt-8">
        <div class="relative z-10 flex flex-col lg:flex-row justify-between gap-12">
            
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-2.5 h-2.5 rounded-full bg-white pulse-white"></span>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] opacity-80">Monitor Actividad Live</h4>
                </div>
                <div class="bg-black/20 p-6 rounded-xl border border-white/10 font-mono text-[11px] space-y-3">
                    <div class="flex gap-4"><span class="text-accent font-bold">[08:12]</span> <span>Check-out procesado: Habitación 302</span></div>
                    <div class="flex gap-4 opacity-80"><span class="text-accent font-bold">[08:05]</span> <span>Limpieza finalizada: Habitación 408 (Marta G.)</span></div>
                    </div>
            </div>
            
            <div class="w-full lg:w-72 bg-white/5 p-8 rounded-xl backdrop-blur-md border border-white/10 flex flex-col justify-center">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-6 border-b border-white/10 pb-2">Estado de Turno</p>
                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest"><span>Check-ins</span><span>85%</span></div>
                        <div class="w-full h-1 bg-white/10 rounded-full overflow-hidden"><div class="bg-accent h-full w-[85%]"></div></div>
                    </div>
                    </div>
            </div>
        </div>
        <span class="material-symbols-outlined absolute -right-10 -bottom-10 text-[15rem] opacity-5">sensors</span>
    </div>
</section>
