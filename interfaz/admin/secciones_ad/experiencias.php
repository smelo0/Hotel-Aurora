<?php
require_once '../../configuracion/conexion.php';
require_once '../../configuracion/permiso.php';
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'experiencias.ver')) { return; }
?>
<section id="sec-experiencias" class="seccion-contenido hidden fade-in">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div><h3 class="text-2xl font-black text-primary tracking-tight">Experiencias</h3><p class="text-xs text-primary/60 mt-1">Administra las tarjetas que ven los huéspedes en la sección "Experiencias" del sitio.</p></div>
            <button id="btnNuevaExperiencia" type="button" onclick="abrirModalNuevaExperiencia()" class="hidden bg-primary text-white px-6 py-3 rounded-lg font-bold text-sm shadow hover:brightness-110"><span class="material-symbols-outlined text-sm align-middle mr-1">add</span> Nueva Experiencia</button>
        </div>
        <div id="mensajeExperiencias" class="hidden mb-5 rounded-lg px-4 py-3 text-sm font-bold"></div>
        <div class="border border-primary/10 rounded-xl overflow-x-auto"><table class="w-full min-w-[700px] text-left"><thead class="bg-[#f4f8f6] text-[10px] font-black uppercase tracking-wider text-primary/70"><tr><th class="px-6 py-4">Categoría</th><th class="px-6 py-4">Nombre</th><th class="px-6 py-4">Descripción</th><th class="px-6 py-4 text-right">Acciones</th></tr></thead><tbody id="tablaExperiencias" class="divide-y divide-primary/10 text-sm"></tbody></table></div>
    </div>
</section>
<div id="modalExperiencia" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-500"><div class="bg-white p-8 rounded-xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl"><div class="flex items-start justify-between gap-4 mb-6"><div><h2 id="tituloModalExperiencia" class="text-2xl font-black text-primary">Nueva Experiencia</h2><p class="text-xs text-primary/60 mt-1">Así se mostrará en la sección de Experiencias del sitio público.</p></div><button type="button" onclick="cerrarModalExperiencia()" class="text-primary/60 hover:text-primary"><span class="material-symbols-outlined">close</span></button></div><form id="formExperiencia" class="space-y-5"><input type="hidden" id="idExperiencia"><div><label for="categoriaExperiencia" class="block text-[10px] font-black uppercase text-primary/70 mb-1">Categoría</label><input type="text" id="categoriaExperiencia" maxlength="50" required placeholder="Ej. Sabores exclusivos" class="w-full border-primary/10 bg-[#f4f8f6] rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></div><div><label for="nombreExperiencia" class="block text-[10px] font-black uppercase text-primary/70 mb-1">Nombre</label><input type="text" id="nombreExperiencia" maxlength="150" required placeholder="Ej. Gastronomía" class="w-full border-primary/10 bg-[#f4f8f6] rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></div><div><label for="descripcionExperiencia" class="block text-[10px] font-black uppercase text-primary/70 mb-1">Descripción</label><textarea id="descripcionExperiencia" required class="w-full border-primary/10 bg-[#f4f8f6] rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary" rows="3" placeholder="Rituales de relajación, masajes premium..."></textarea></div><div class="flex gap-4 pt-2"><button type="button" onclick="cerrarModalExperiencia()" class="flex-1 py-3 text-primary/70 font-bold hover:bg-[#eef6f2] rounded-lg">Cancelar</button><button type="submit" class="flex-1 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Guardar</button></div></form></div></div>
<script>
const ENDPOINT_EXPERIENCIAS = '../../controladores/gestionar_experiencias.php';
let datosExperiencias = { experiencias: [], puede_gestionar: false };
function escaparExperiencias(valor) { return String(valor ?? '').replace(/[&<>"']/g, caracter => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[caracter])); }
function mostrarMensajeExperiencias(texto, error = false) { const mensaje = document.getElementById('mensajeExperiencias'); mensaje.textContent = texto; mensaje.className = `mb-5 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`; mensaje.classList.remove('hidden'); }
async function cargarExperiencias() { try { const respuesta = await fetch(`${ENDPOINT_EXPERIENCIAS}?accion=listar`, { headers: { Accept: 'application/json' } }); const datos = await respuesta.json(); if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron cargar las experiencias'); datosExperiencias = datos; document.getElementById('btnNuevaExperiencia').classList.toggle('hidden', !datos.puede_gestionar); renderizarExperiencias(); } catch (error) { mostrarMensajeExperiencias(error.message, true); } }
function renderizarExperiencias() {
    document.getElementById('tablaExperiencias').innerHTML = datosExperiencias.experiencias.map(experiencia => {
        const acciones = datosExperiencias.puede_gestionar
            ? `<button type="button" class="text-primary/60 hover:text-primary mx-1" title="Editar experiencia" onclick="abrirEditarExperiencia(${experiencia.id})"><span class="material-symbols-outlined text-lg">edit</span></button><button type="button" class="text-primary/60 hover:text-red-600 mx-1" title="Eliminar experiencia" onclick="confirmarEliminacionExperiencia(${experiencia.id}, '${escaparExperiencias(experiencia.nombre)}')"><span class="material-symbols-outlined text-lg">delete</span></button>`
            : '<span class="text-primary/50">Solo lectura</span>';
        return `<tr class="hover:bg-[#f4f8f6]/80"><td class="px-6 py-4"><span class="bg-accent/40 text-primary px-2 py-1 rounded text-xs font-bold">${escaparExperiencias(experiencia.categoria)}</span></td><td class="px-6 py-4 font-black text-heading">${escaparExperiencias(experiencia.nombre)}</td><td class="px-6 py-4 text-primary/70 text-xs max-w-sm">${escaparExperiencias(experiencia.descripcion)}</td><td class="px-6 py-4 text-right">${acciones}</td></tr>`;
    }).join('') || '<tr><td colspan="4" class="px-6 py-8 text-center text-primary/60 text-sm">Aún no hay experiencias registradas.</td></tr>';
}
function abrirModalNuevaExperiencia() { abrirModalExperiencia(null); }
function abrirEditarExperiencia(id) { abrirModalExperiencia(datosExperiencias.experiencias.find(experiencia => experiencia.id === id)); }
function abrirModalExperiencia(experiencia) {
    document.getElementById('formExperiencia').reset();
    document.getElementById('idExperiencia').value = experiencia?.id || '';
    document.getElementById('categoriaExperiencia').value = experiencia?.categoria || '';
    document.getElementById('nombreExperiencia').value = experiencia?.nombre || '';
    document.getElementById('descripcionExperiencia').value = experiencia?.descripcion || '';
    document.getElementById('tituloModalExperiencia').textContent = experiencia ? `Editar: ${experiencia.nombre}` : 'Nueva Experiencia';
    const modal = document.getElementById('modalExperiencia');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
}
function cerrarModalExperiencia() { const modal = document.getElementById('modalExperiencia'); modal.classList.add('modal-oculto'); modal.classList.remove('modal-visible'); }
async function confirmarEliminacionExperiencia(id, nombre) {
    if (!confirm(`¿Eliminar la experiencia "${nombre}"?`)) return;
    const respuesta = await fetch(ENDPOINT_EXPERIENCIAS, { method: 'POST', headers: { Accept: 'application/json' }, body: new URLSearchParams({ accion: 'eliminar', id, csrf_token: CSRF_TOKEN }) });
    const datos = await respuesta.json();
    if (!respuesta.ok) return mostrarMensajeExperiencias(datos.mensaje, true);
    mostrarMensajeExperiencias(datos.mensaje);
    cargarExperiencias();
}
document.getElementById('formExperiencia').addEventListener('submit', async evento => {
    evento.preventDefault();
    const cuerpo = new URLSearchParams({
        accion: 'guardar',
        csrf_token: CSRF_TOKEN,
        id: document.getElementById('idExperiencia').value,
        categoria: document.getElementById('categoriaExperiencia').value.trim(),
        nombre: document.getElementById('nombreExperiencia').value.trim(),
        descripcion: document.getElementById('descripcionExperiencia').value.trim(),
    });
    const respuesta = await fetch(ENDPOINT_EXPERIENCIAS, { method: 'POST', headers: { Accept: 'application/json' }, body: cuerpo });
    const datos = await respuesta.json();
    if (!respuesta.ok) return mostrarMensajeExperiencias(datos.mensaje, true);
    cerrarModalExperiencia();
    mostrarMensajeExperiencias(datos.mensaje);
    cargarExperiencias();
});
cargarExperiencias();
</script>