<section id="sec-dashboard" class="seccion-contenido hidden">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div onclick="redirigirDesdeDash('habitaciones')" class="metric-card bg-[#fbfdfd] p-5 rounded-xl flex flex-col justify-between min-h-36 cursor-pointer shadow-md">
            <div class="icon-box w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">bed</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ocupadas</p>
                <h3 id="dash-ocupadas" class="text-4xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </div>
        <div onclick="redirigirDesdeDash('limpieza')" class="metric-card bg-[#fbfdfd] p-5 rounded-xl flex flex-col justify-between min-h-36 cursor-pointer shadow-md">
            <div class="icon-box w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">mop</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Por Limpiar</p>
                <h3 id="dash-limpieza" class="text-4xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </div>
        <div onclick="redirigirDesdeDash('huespedes')" class="metric-card bg-[#fbfdfd] p-5 rounded-xl flex flex-col justify-between min-h-36 cursor-pointer shadow-md">
            <div class="icon-box w-10 h-10 bg-accent rounded-xl flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Huéspedes</p>
                <h3 id="dash-huespedes" class="text-4xl font-black text-heading tracking-tighter">0</h3>
            </div>
        </div>
    </div>

    <div class="bg-primary text-white p-7 rounded-xl shadow-2xl relative overflow-hidden mt-6">
        <div class="relative z-10 flex flex-col lg:flex-row justify-between gap-8">
            
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-2.5 h-2.5 rounded-full bg-white pulse-white"></span>
                    <h4 class="text-xs font-black uppercase tracking-[0.2em] opacity-80">Monitor Actividad Live</h4>
                </div>
                <div class="bg-black/20 p-5 rounded-xl border border-white/10 font-mono text-[10px] space-y-3">
                    <div class="flex gap-4"><span class="text-accent font-bold">[08:12]</span> <span>Check-out procesado: Habitación 302</span></div>
                    <div class="flex gap-4 opacity-80"><span class="text-accent font-bold">[08:05]</span> <span>Limpieza finalizada: Habitación 408 (Marta G.)</span></div>
                    </div>
            </div>
            
            <div class="w-full lg:w-64 bg-white/5 p-6 rounded-xl backdrop-blur-md border border-white/10 flex flex-col justify-center">
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

    <div class="mt-6 bg-white rounded-xl p-6 border border-primary/10 shadow-sm max-w-2xl">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h4 class="font-black text-heading">Cuenta de acceso</h4>
                <p class="text-xs text-slate-500 mt-1">Cambia el correo asociado a tu cuenta de empleado.</p>
            </div>
        </div>

        <?php $correoActual = $_SESSION['emp_auth']['correo_usuario'] ?? ''; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] === 'correo_actualizado'): ?>
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">Correo actualizado correctamente.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'correo_duplicado'): ?>
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-600">Este correo ya está registrado en otra cuenta.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'correo_invalido'): ?>
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-600">Ingresa un correo válido.</div>
        <?php endif; ?>

        <form method="POST" action="../../controladores/actualizar_correo.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <label class="block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400 mb-2">Correo actual</label>
                <input type="text" value="<?php echo htmlspecialchars($correoActual, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700" disabled>
            </div>

            <div>
                <label for="nuevo_correo" class="block text-[10px] font-black uppercase tracking-[0.18em] text-slate-400 mb-2">Nuevo correo</label>
                <input id="nuevo_correo" name="nuevo_correo" type="email" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary" placeholder="nuevo@hotelaurora.com">
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-black text-white shadow hover:brightness-110 transition-all">
                Guardar correo
            </button>
        </form>
    </div>
</section>
