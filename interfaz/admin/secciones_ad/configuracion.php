<section id="sec-configuracion" class="seccion-contenido hidden">
    
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm max-w-4xl">
        
        <h3 class="text-2xl font-black text-primary tracking-tight mb-2">Seguridad y Control de Accesos</h3>
        <p class="text-xs text-slate-400 mb-8">Administra los permisos específicos para cada rol del sistema.</p>
        
        <div class="space-y-6">
            
            <div class="flex items-center justify-between p-4 border border-slate-200 rounded-xl">
                
                <div>
                    <h4 class="font-black text-heading">Personal de Recepción</h4>
                    <p class="text-xs text-slate-500 mt-1">Acceso a reservas, check-in y visualización de habitaciones.</p>
                </div>
                
                <div class="flex gap-4">
                    
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" class="sr-only toggle-checkbox" checked>
                            
                            <div class="block bg-slate-200 w-10 h-6 rounded-full transition-colors toggle-label"></div>
                            
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform"></div>
                        </div>
                        <span class="ml-2 text-xs font-bold text-slate-500">Reservas</span>
                    </label>
                    
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" class="sr-only toggle-checkbox">
                            <div class="block bg-slate-200 w-10 h-6 rounded-full transition-colors toggle-label"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform"></div>
                        </div>
                        <span class="ml-2 text-xs font-bold text-slate-500">Finanzas</span>
                    </label>
                </div>
            </div>
            
            <div class="flex items-center justify-between p-4 border border-slate-200 rounded-xl bg-slate-50">
                <div>
                    <h4 class="font-black text-heading">HouseKeeping (Limpieza)</h4>
                    <p class="text-xs text-slate-500 mt-1">Acceso exclusivo a terminal operativa y cola de tareas.</p>
                </div>
                <div class="flex gap-4">
                    
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" class="sr-only toggle-checkbox" checked>
                            <div class="block bg-slate-200 w-10 h-6 rounded-full transition-colors toggle-label"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform"></div>
                        </div>
                        <span class="ml-2 text-xs font-bold text-slate-500">Cola Tareas</span>
                    </label>
                    
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" class="sr-only toggle-checkbox">
                            <div class="block bg-slate-200 w-10 h-6 rounded-full transition-colors toggle-label"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform"></div>
                        </div>
                        <span class="ml-2 text-xs font-bold text-slate-500">Ver Huéspedes</span>
                    </label>
                    
                </div>
            </div>

            <div class="p-6 border border-slate-200 rounded-xl bg-slate-50">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h4 class="font-black text-heading">Cuenta de acceso</h4>
                        <p class="text-xs text-slate-500 mt-1">Cambia el correo asociado a tu cuenta de administrador o empleado.</p>
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
            
        </div>
    </div>
</section>