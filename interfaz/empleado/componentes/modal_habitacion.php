<div id="modalHabitacion" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex justify-center items-center z-50 opacity-0 pointer-events-none transition-all duration-300 ease-out">
    <div id="contenidoModalHabitacion" class="bg-white p-8 rounded-2xl w-96 shadow-2xl border border-slate-100 scale-95 translate-y-3 transition-all duration-300 ease-out">
        
        <div class="flex justify-between items-center mb-6">
            <h3 id="tituloModalHab" class="text-xl font-black text-heading">Gestionar Hab.</h3>
            <button onclick="cerrarModalHabitacion()" class="text-slate-400 hover:text-red-500 font-black text-xl transition-colors">&times;</button>
        </div>

        <form id="formGestionHabitacion">
            <input type="hidden" id="idHabitacionModal">
            
            <div class="mb-6 space-y-3">
                <!-- Modificación: Se corrigió el orden, 'Cambiar Estado A' es siempre visible y 'Prioridad' aparece solo al seleccionar Mantenimiento. -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Cambiar Estado A:</label>
                    <select id="estadoHabitacionModal" onchange="toggleDescripcionMantenimiento()" class="w-full p-4 rounded-xl border border-slate-200 text-slate-600 font-bold outline-none focus:border-primary transition-colors cursor-pointer appearance-none bg-slate-50">
                        <option value="Limpio">Limpio</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupada">Ocupada</option>
                        <option value="Sucia">Sucia</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
                </div>
                <!-- Modificación: Prioridad queda oculta por defecto y se muestra únicamente cuando el estado es Mantenimiento. -->
                <div id="areaPrioridadMantenimiento" class="hidden">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Prioridad:</label>
                    <select id="prioridadMantenimientoModal" onchange="actualizarPrioridadMantenimiento()" class="w-full p-4 rounded-xl border border-slate-200 text-slate-600 font-bold outline-none focus:border-primary transition-colors cursor-pointer appearance-none bg-slate-50">
                        <option value="Urgente" style="color:#475569;">&#9650; Urgente</option>
                        <option value="Importante" style="color:#475569;">&#9670; Importante</option>
                        <option value="No urgente" style="color:#475569;">&#9679; No urgente</option>
                    </select>
                </div>
            </div>

            <div id="areaDescripcionMantenimiento" class="mb-6 hidden">
                <textarea id="descripcionMantenimientoModal" oninput="toggleDescripcionMantenimiento()" class="w-full p-4 rounded-xl border border-slate-200 text-slate-600 font-bold outline-none focus:border-primary transition-colors bg-slate-50 resize-none" rows="4" placeholder="Escribe por qué la habitación está en mantenimiento"></textarea>
            </div>
            
            <button id="guardarCambiosHabitacion" type="submit" class="w-full bg-primary text-white font-black uppercase tracking-widest text-xs py-4 rounded-xl hover:bg-primary/90 transition-colors shadow-lg shadow-primary/30">
                Guardar Cambios
            </button>
        </form>
    </div>
</div>
