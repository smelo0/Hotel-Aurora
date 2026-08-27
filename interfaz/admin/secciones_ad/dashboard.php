<section id="sec-dashboard" class="seccion-contenido">
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        <div class="admin-card bg-white p-6 rounded-xl border-t-4 border-t-primary">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ocupación</p>
            <h3 class="text-4xl font-black text-heading">84%</h3>
            </div>
        
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Check-ins</p>
            <h3 class="text-4xl font-black text-heading">12</h3>
        </div>
        
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ingresos Hoy</p>
            <h3 class="text-4xl font-black text-heading">$4,250</h3>
        </div>
        
        <div onclick="mostrarVista('sec-tareas')" class="admin-card bg-white p-6 rounded-xl cursor-pointer hover:ring-2 hover:ring-amber-400 transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tareas Pendientes</p>
            <h3 id="contador-tareas" class="text-4xl font-black text-heading text-amber-500">0</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-red-50 border border-red-100 rounded-xl p-6">
                <h3 class="text-sm font-black text-red-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-xl">warning</span> Alertas Críticas
                </h3>
                
                <div class="space-y-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500">
                        <h4 class="font-bold text-xs text-heading">Mantenimiento Urgente</h4>
                        <p class="text-[10px] text-slate-500 mt-1">Fuga de agua detectada en tubería principal de Habitación 510.</p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-amber-500">
                        <h4 class="font-bold text-xs text-heading">Solicitud Especial</h4>
                        <p class="text-[10px] text-slate-500 mt-1">Huésped VIP Hab 405 solicita cuna extra y botellas de agua antes del arribo.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-2 bg-white rounded-xl p-8 border border-primary/10">
            <h3 class="text-xl font-black text-primary uppercase tracking-tighter mb-6">Monitor Operativo Live</h3>
            
            <div class="space-y-4">
                <div class="flex gap-4 items-center p-3 hover:bg-slate-50 rounded-lg">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <p class="text-xs text-slate-600"><strong>[08:12]</strong> Check-out procesado: Habitación 302</p>
                </div>
                
                <div class="flex gap-4 items-center p-3 hover:bg-slate-50 rounded-lg">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <p class="text-xs text-slate-600"><strong>[08:05]</strong> Limpieza finalizada: Habitación 408</p>
                </div>
                
                <div class="flex gap-4 items-center p-3 hover:bg-slate-50 rounded-lg">
                    <span class="w-2 h-2 bg-primary rounded-full"></span>
                    <p class="text-xs text-slate-600"><strong>[07:42]</strong> Check-in exitoso: Julianne Vance (Hab 405)</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        function actualizarContadorTareas() {
            // Ajusta los ../ dependiendo de qué tan profundo esté este archivo en tus carpetas
            fetch('../../controladores/api_tareas_pendientes.php')
                .then(response => response.text())
                .then(data => {
                    const contador = document.getElementById('contador-tareas');
                    if(contador) {
                        // Actualiza el número de la tarjeta mágicamente
                        contador.innerText = data;
                    }
                })
                .catch(error => console.error('Error al actualizar tareas:', error));
        }

        // Arranca inmediatamente y luego consulta la BD cada 5 segundos
        actualizarContadorTareas();
        setInterval(actualizarContadorTareas, 5000); 
    </script>
</section>