<div id="modalTarea" class="fixed inset-0 bg-primary/10 z-[100] flex items-center justify-center modal-oculto transition-all duration-500">
    <div id="modalTareaContenido" class="bg-white p-10 rounded-xl w-[450px] shadow-2xl">
        <h2 class="text-2xl font-black text-primary mb-6">Nueva Tarea</h2>
        <form id="formTarea" class="space-y-4">
            <input type="text" id="tituloTarea" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-4 focus:ring-1 focus:ring-primary outline-none" placeholder="¿Qué hay que hacer?">
            <select id="categoriaTarea" class="w-full border-slate-200 bg-slate-50 rounded-lg p-4 text-xs font-bold text-slate-500 uppercase">
                <option value="LIMPIEZA">Limpieza</option>
                <option value="URGENTE">Urgente</option>
                <option value="GENERAL">General</option>
            </select>
            <textarea id="descTarea" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-4 focus:ring-1 focus:ring-primary outline-none" rows="3" placeholder="Detalles..."></textarea>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="cerrarModal()" class="flex-1 py-4 text-slate-400 font-bold hover:bg-slate-100 rounded-lg transition-all">Cancelar</button>
                <button type="submit" class="flex-1 py-4 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Confirmar</button>
            </div>
        </form>
    </div>
</div>