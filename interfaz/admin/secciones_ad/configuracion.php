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
            
        </div>
    </div>
</section>