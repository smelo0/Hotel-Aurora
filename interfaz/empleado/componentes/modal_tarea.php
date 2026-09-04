<!-- Componente: Modal Emergente para Creación de Nueva Tarea -->
<div id="modalTarea" 
     class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 ease-out p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="tituloModalTarea">

    <div id="modalTareaContenido" class="bg-white p-8 sm:p-10 rounded-2xl w-full max-w-md shadow-2xl border border-slate-100 scale-95 translate-y-3 transition-all duration-300 ease-out">
        
        <!-- Cabecera del Modal -->
        <div class="flex justify-between items-center mb-6">
            <h2 id="tituloModalTarea" class="text-2xl font-black text-primary">Nueva Tarea</h2>
            <button id="btnCerrarModalTarea" 
                    data-action="cerrar-modal-tarea" 
                    type="button" 
                    class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400"
                    aria-label="Cerrar ventana modal">
                <span class="material-symbols-outlined text-2xl" aria-hidden="true">close</span>
            </button>
        </div>

        <!-- Formulario de Registro de Tarea -->
        <form id="formTarea" class="space-y-4" novalidate>
            <!-- Campo: Título -->
            <div>
                <label for="tituloTarea" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Título de la Tarea</label>
                <input type="text" 
                       id="tituloTarea" 
                       name="titulo"
                       required 
                       maxlength="100"
                       class="w-full border border-slate-200 bg-slate-50 rounded-xl p-4 text-slate-700 font-bold focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:font-normal" 
                       placeholder="¿Qué hay que hacer?">
            </div>

            <!-- Campo: Categoría -->
            <div>
                <label for="categoriaTarea" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Categoría</label>
                <select id="categoriaTarea" 
                        name="categoria"
                        class="w-full border border-slate-200 bg-slate-50 rounded-xl p-4 text-xs font-bold text-slate-600 uppercase focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all cursor-pointer">
                    <option value="URGENTE">Urgente</option>
                    <option value="ALTA">Alta</option>
                    <option value="GENERAL">General</option>
                </select>
            </div>

            <!-- Campo: Descripción -->
            <div>
                <label for="descTarea" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Detalles / Instrucciones</label>
                <textarea id="descTarea" 
                          name="descripcion"
                          required 
                          rows="3" 
                          maxlength="500"
                          class="w-full border border-slate-200 bg-slate-50 rounded-xl p-4 text-slate-700 font-medium focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all resize-none placeholder:font-normal" 
                          placeholder="Escribe los detalles de la tarea..."></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="flex gap-3 pt-4">
                <button type="button" 
                        data-action="cerrar-modal-tarea" 
                        class="flex-1 py-4 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-slate-300">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 py-4 bg-primary text-white font-bold rounded-xl shadow-lg shadow-primary/20 hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 transition-all cursor-pointer">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>