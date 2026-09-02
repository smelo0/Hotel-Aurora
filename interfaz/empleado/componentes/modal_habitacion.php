<!-- Componente: Modal Emergente de Gestión de Habitación -->
<div id="modalHabitacion" 
     class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex justify-center items-center z-50 opacity-0 pointer-events-none transition-all duration-300 ease-out"
     role="dialog"
     aria-modal="true"
     aria-labelledby="tituloModalHab">

    <div id="contenidoModalHabitacion" class="bg-white p-8 rounded-2xl w-96 shadow-2xl border border-slate-100 scale-95 translate-y-3 transition-all duration-300 ease-out">
        
        <!-- Cabecera del Modal -->
        <div class="flex justify-between items-center mb-6">
            <h3 id="tituloModalHab" class="text-xl font-black text-heading">Gestionar Hab.</h3>
            <button id="btnCerrarModalHabitacion" 
                    data-action="cerrar-modal-hab" 
                    type="button" 
                    class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400"
                    aria-label="Cerrar modal">
                <span class="material-symbols-outlined text-2xl" aria-hidden="true">close</span>
            </button>
        </div>

        <!-- Formulario de Actualización de Estado -->
        <form id="formGestionHabitacion" novalidate>
            <!-- ID Oculto de la Habitación -->
            <input type="hidden" id="idHabitacionModal" name="id_habitacion">
            
            <div class="mb-6 space-y-4">
                <!-- Selector de Estado Principal -->
                <div>
                    <label for="estadoHabitacionModal" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Cambiar Estado A:</label>
                    <select id="estadoHabitacionModal" 
                            name="estado"
                            class="w-full p-4 rounded-xl border border-slate-200 text-slate-700 font-bold outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors cursor-pointer bg-slate-50">
                        <option value="Limpio">Limpio</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupada">Ocupada</option>
                        <option value="Sucia">Sucia</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
                </div>

                <!-- Campo Condicional: Prioridad de Mantenimiento -->
                <div id="areaPrioridadMantenimiento" class="hidden">
                    <label for="prioridadMantenimientoModal" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Prioridad:</label>
                    <select id="prioridadMantenimientoModal" 
                            name="prioridad"
                            class="w-full p-4 rounded-xl border border-slate-200 text-slate-700 font-bold outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors cursor-pointer bg-slate-50">
                        <option value="Urgente" class="text-red-600 font-bold">▲ Urgente</option>
                        <option value="Importante" class="text-amber-600 font-bold">◆ Importante</option>
                        <option value="No urgente" class="text-slate-600 font-bold">● No urgente</option>
                    </select>
                </div>
            </div>

            <!-- Campo Condicional: Descripción de Mantenimiento -->
            <div id="areaDescripcionMantenimiento" class="mb-6 hidden">
                <label for="descripcionMantenimientoModal" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Motivo del Mantenimiento:</label>
                <textarea id="descripcionMantenimientoModal" 
                          name="descripcion_mantenimiento"
                          rows="4" 
                          maxlength="255"
                          class="w-full p-4 rounded-xl border border-slate-200 text-slate-700 font-bold outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors bg-slate-50 resize-none" 
                          placeholder="Escribe por qué la habitación entra en mantenimiento..."></textarea>
            </div>
            
            <!-- Botón de Confirmación -->
            <button id="guardarCambiosHabitacion" 
                    type="submit" 
                    class="w-full bg-primary text-white font-black uppercase tracking-widest text-xs py-4 rounded-xl hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 transition-all shadow-lg shadow-primary/20 cursor-pointer">
                Guardar Cambios
            </button>
        </form>
    </div>
</div>