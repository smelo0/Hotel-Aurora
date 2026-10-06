<section id="sec-dashboard" class="seccion-contenido hidden">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <button type="button" data-dashboard-link="habitaciones" class="metric-card bg-[#fbfdfd] p-5 rounded-xl flex flex-col justify-between min-h-36 cursor-pointer shadow-md text-left">
            <div class="icon-box w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">bed</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ocupadas</p>
                <h3 id="dash-ocupadas" class="text-4xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </button>
        <button type="button" data-dashboard-link="habitaciones" class="metric-card bg-[#fbfdfd] p-5 rounded-xl flex flex-col justify-between min-h-36 cursor-pointer shadow-md text-left">
            <div class="icon-box w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">hotel</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hab. Disponibles</p>
                <h3 id="dash-disponibles" class="text-4xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </button>
        <button type="button" data-dashboard-link="huespedes" class="metric-card bg-[#fbfdfd] p-5 rounded-xl flex flex-col justify-between min-h-36 cursor-pointer shadow-md text-left">
            <div class="icon-box w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ocupación actual</p>
                <h3 id="dash-ocupacion" class="text-4xl font-black text-heading tracking-tighter">0%</h3>
            </div>
            </button>
    </div>

    <div class="bg-primary text-white p-7 rounded-xl shadow-2xl relative overflow-hidden mt-6">
        <div class="relative z-10 flex flex-col lg:flex-row justify-between gap-8">
            
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-2.5 h-2.5 rounded-full bg-accent"></span>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] opacity-80">Resumen operativo</h4>
                </div>
                <p class="text-sm text-white/75">Estado actual de las habitaciones registrado en el sistema.</p>
            </div>
            
            <div class="w-full lg:w-64 bg-white/5 p-6 rounded-xl backdrop-blur-md border border-white/10 flex flex-col justify-center">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-6 border-b border-white/10 pb-2">Ocupación del hotel</p>
                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest"><span>Ocupadas</span><span id="dash-ocupacion-porcentaje">0%</span></div>
                        <div class="w-full h-1 bg-white/10 rounded-full overflow-hidden"><div id="dash-ocupacion-barra" class="bg-accent h-full" style="width: 0%"></div></div>
                    </div>
                    <p class="text-[10px] text-white/60"><span id="dash-resumen-ocupadas">0</span> ocupadas de <span id="dash-total-habitaciones">0</span> habitaciones</p>
                    </div>
            </div>
        </div>
        <span class="material-symbols-outlined absolute -right-10 -bottom-10 text-[15rem] opacity-5">sensors</span>
    </div>

</section>
