<!-- Modificación: Se agregó efecto blur al fondo del modal con overlay oscuro y backdrop-filter. -->
<div id="modalTarea" class="fixed inset-0 bg-black/50 backdrop-blur-[5px] z-[100] flex items-center justify-center modal-oculto transition-all duration-500">
    <div id="modalTareaContenido" class="bg-white p-10 rounded-xl w-full max-w-[450px] shadow-2xl border border-primary/10">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-secondary mb-1">Operacion interna</p>
                <h2 class="text-2xl font-black text-primary">Nueva Tarea</h2>
            </div>
            <button type="button" onclick="cerrarModal()" class="text-slate-300 hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="formTarea" class="space-y-4">
            <input type="text" id="tituloTarea" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-4 text-sm font-bold text-slate-700 focus:ring-1 focus:ring-primary outline-none" placeholder="Que hay que hacer?">

            <select id="categoriaTarea" class="w-full border-slate-200 bg-slate-50 rounded-lg p-4 text-xs font-bold text-slate-500 uppercase focus:ring-1 focus:ring-primary outline-none">
                <option value="LIMPIEZA">Limpieza</option>
                <option value="URGENTE">Urgente</option>
                <option value="GENERAL">General</option>
            </select>

            <textarea id="descTarea" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-4 text-sm font-bold text-slate-700 focus:ring-1 focus:ring-primary outline-none resize-none" rows="3" placeholder="Detalles..."></textarea>

            <div class="flex gap-4 pt-4">
                <button type="button" onclick="cerrarModal()" class="flex-1 py-4 text-slate-400 font-bold hover:bg-slate-100 rounded-lg transition-all">Cancelar</button>
                <button type="submit" class="flex-1 py-4 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110 transition-all">Confirmar</button>
            </div>
        </form>
    </div>
</div>
