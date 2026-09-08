<!-- Sección: Dashboard Operativo del Hotel -->
<section id="sec-dashboard" class="seccion-contenido hidden" aria-labelledby="tituloDashboard">
    <h2 id="tituloDashboard" class="sr-only">Panel Principal del Día</h2>
    
    <!-- Rejilla de Tarjetas Métricas (3 Contadores) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Tarjeta 1: Habitaciones Ocupadas -->
        <div onclick="redirigirDesdeDash('habitaciones')" 
             class="metric-card bg-surface p-6 md:p-8 rounded-2xl flex flex-col justify-between h-44 cursor-pointer shadow-sm border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all group">
            <div class="icon-box w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                <span class="material-symbols-outlined" aria-hidden="true">bed</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hab. Ocupadas</p>
                <h3 id="dash-ocupadas" class="text-4xl md:text-5xl font-black text-slate-800 tracking-tighter">0</h3>
            </div>
        </div>

        <!-- Tarjeta 2: Habitaciones Disponibles -->
        <div onclick="redirigirDesdeDash('habitaciones')" 
             class="metric-card bg-surface p-6 md:p-8 rounded-2xl flex flex-col justify-between h-44 cursor-pointer shadow-sm border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all group">
            <div class="icon-box w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                <span class="material-symbols-outlined" aria-hidden="true">meeting_room</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hab. Disponibles</p>
                <h3 id="dash-disponibles" class="text-4xl md:text-5xl font-black text-slate-800 tracking-tighter">0</h3>
            </div>
        </div>

        <!-- Tarjeta 3: Total Huéspedes -->
        <div onclick="redirigirDesdeDash('huespedes')" 
             class="metric-card bg-surface p-6 md:p-8 rounded-2xl flex flex-col justify-between h-44 cursor-pointer shadow-sm border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all group">
            <div class="icon-box w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                <span class="material-symbols-outlined" aria-hidden="true">group</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Huéspedes Total</p>
                <h3 id="dash-huespedes" class="text-4xl md:text-5xl font-black text-slate-800 tracking-tighter">0</h3>
            </div>
        </div>
    </div>

    <!-- Panel de Operaciones Diarias: Check-ins y Check-outs del Día -->
    <div class="bg-primary text-white p-8 md:p-10 rounded-2xl shadow-2xl relative overflow-hidden mt-8">
        <div class="relative z-10 flex flex-col lg:flex-row justify-between gap-8 md:gap-12">
            
            <!-- Resumen de Entradas y Salidas Programadas -->
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] text-white/80">Flujo de Recepción Hoy</h4>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Caja de Check-ins -->
                    <div class="bg-black/20 p-5 rounded-xl border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">login</span> Check-ins
                            </span>
                            <span id="dash-count-checkin" class="text-lg font-black">0</span>
                        </div>
                        <p class="text-[11px] text-white/60">Llegadas pendientes de confirmar</p>
                    </div>

                    <!-- Caja de Check-outs -->
                    <div class="bg-black/20 p-5 rounded-xl border border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">logout</span> Check-outs
                            </span>
                            <span id="dash-count-checkout" class="text-lg font-black">0</span>
                        </div>
                        <p class="text-[11px] text-white/60">Salidas programadas para hoy</p>
                    </div>
                </div>
            </div>
            
            <!-- Barra de Progreso de Turno -->
            <div class="w-full lg:w-72 bg-white/5 p-8 rounded-xl backdrop-blur-md border border-white/10 flex flex-col justify-center">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-6 border-b border-white/10 pb-2">Ocupación General</p>
                <div class="space-y-5">
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest">
                            <span>Capacidad</span>
                            <span id="dash-porcentaje-ocupacion">0%</span>
                        </div>
                        <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                            <div id="dash-barra-ocupacion" class="bg-emerald-400 h-full w-[0%] transition-all duration-500"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <span class="material-symbols-outlined absolute -right-10 -bottom-10 text-[15rem] opacity-5 pointer-events-none" aria-hidden="true">hotel</span>
    </div>
</section>