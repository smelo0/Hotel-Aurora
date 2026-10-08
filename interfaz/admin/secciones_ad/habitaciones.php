<?php
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../configuracion/permiso.php';
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'habitaciones.ver')) { return; }
?>
<section id="sec-habitaciones" class="seccion-contenido hidden fade-in">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <h3 class="text-2xl font-black text-primary tracking-tight">Habitaciones</h3>
                <p class="text-xs text-slate-400 mt-1">Todo lo que edites aquí (foto, comodidades, descripción, tipo y precio) es lo que ven los huéspedes en el sitio público.</p>
            </div>
            <button id="btnNuevaHabitacion" type="button" onclick="abrirModalNuevaHabitacion()" class="hidden bg-primary text-white px-6 py-3 rounded-lg font-bold text-sm shadow hover:brightness-110">
                <span class="material-symbols-outlined text-sm align-middle mr-1">add</span> Nueva Habitación
            </button>
        </div>
        <div id="mensajeHabitaciones" class="hidden mb-5 rounded-lg px-4 py-3 text-sm font-bold" role="status" aria-live="polite"></div>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <label for="filtroEstadoHabitaciones" class="text-[10px] font-black uppercase text-slate-500">Mostrar</label>
            <select id="filtroEstadoHabitaciones" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-600 outline-none focus:ring-1 focus:ring-primary">
                <option value="todas">Todas</option>
                <option value="disponibles">Disponibles</option>
                <option value="no_disponibles">Reservadas u ocupadas</option>
            </select>
            <span id="resumenHabitaciones" class="text-xs font-bold text-slate-400" aria-live="polite"></span>
        </div>
        <div class="border border-slate-200 rounded-xl overflow-x-auto">
            <table class="w-full min-w-[1150px] text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Foto</th>
                        <th class="px-6 py-4">N.º</th>
                        <th class="px-6 py-4">Tipo</th>
                        <th class="px-6 py-4">Precio / noche</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Reservas</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaHabitaciones" class="divide-y divide-slate-100 text-sm"></tbody>
            </table>
        </div>
    </div>
</section>

<div id="modalHabitacion" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-500">
    <div class="bg-white p-8 rounded-xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h2 id="tituloModalHabitacion" class="text-2xl font-black text-primary">Nueva Habitación</h2>
                <p class="text-xs text-slate-400 mt-1">El estado (disponible, sucia, mantenimiento…) se sigue manejando desde Operaciones.</p>
            </div>
            <button type="button" onclick="cerrarModalHabitacion()" class="text-slate-400 hover:text-primary" aria-label="Cerrar"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formHabitacion" class="space-y-5" enctype="multipart/form-data" novalidate>
            <div id="mensajeFormularioHabitacion" class="hidden rounded-lg px-4 py-3 text-sm font-bold" role="alert" aria-live="polite"></div>
            <input type="hidden" id="idHabitacion">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="numeroHabitacion" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Número</label>
                    <input type="number" id="numeroHabitacion" min="1" max="99999" step="1" inputmode="numeric" required placeholder="Ej. 411" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label for="tipoHabitacion" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Tipo</label>
                    <input type="text" id="tipoHabitacion" list="tiposHabitacionSugeridos" maxlength="50" required placeholder="Ej. Sencilla, Doble, Suite" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary">
                    <datalist id="tiposHabitacionSugeridos">
                        <option value="Sencilla"></option>
                        <option value="Doble"></option>
                        <option value="Suite"></option>
                        <option value="Suite Premium"></option>
                    </datalist>
                </div>
            </div>
            <div>
                <label for="precioHabitacion" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Precio por noche (COP)</label>
                <input type="number" id="precioHabitacion" min="1" max="99999999" step="1" inputmode="numeric" required placeholder="Ej. 85000" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary">
            </div>
            <div>
                <label for="imagenHabitacion" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Foto (JPG, PNG o WebP; máximo 5 MB)</label>
                <input type="file" id="imagenHabitacion" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                <input type="hidden" id="quitarImagenHabitacion" value="0">
                <p class="mt-1 text-[11px] text-slate-400">Si no subes foto, el sitio usa una imagen por defecto según el tipo.</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <img id="vistaPreviaImagenHabitacion" class="hidden h-36 w-full rounded-lg object-cover" alt="Vista previa de la habitación">
                    <button id="eliminarImagenHabitacion" type="button" class="hidden rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Quitar foto</button>
                </div>
            </div>
            <div>
                <label for="comodidadesHabitacion" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Comodidades (una por línea, máximo 8)</label>
                <textarea id="comodidadesHabitacion" rows="4" placeholder="Cama queen&#10;Smart TV&#10;Wi-Fi&#10;Caja fuerte" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></textarea>
                <p class="mt-1 text-[11px] text-slate-400">Si lo dejas vacío, el sitio usa las comodidades por defecto según el tipo.</p>
            </div>
            <div>
                <label for="descripcionHabitacion" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Descripción</label>
                <textarea id="descripcionHabitacion" rows="4" maxlength="500" placeholder="Describe la habitación: vista, camas, comodidades…" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></textarea>
                <p class="mt-1 text-right text-[10px] font-bold text-slate-400"><span id="contadorDescripcionHabitacion">0</span>/500</p>
            </div>
            <div class="flex gap-4 pt-2">
                <button type="button" onclick="cerrarModalHabitacion()" class="flex-1 py-3 text-slate-400 font-bold hover:bg-slate-100 rounded-lg">Cancelar</button>
                <button type="submit" class="flex-1 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const ENDPOINT_HABITACIONES = '../../controladores/gestionar_habitaciones.php';
let datosHabitaciones = { habitaciones: [], puede_gestionar: false };

function escaparHabitaciones(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
}
function precioHabitacionLegible(precio) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(precio) || 0);
}
function claseEstadoHabitacion(estado) {
    return {
        'Disponible': 'bg-green-100 text-green-700',
        'Ocupada': 'bg-orange-100 text-orange-700',
        'Reservada': 'bg-sky-100 text-sky-700',
        'Sucia': 'bg-stone-200 text-stone-700',
        'Mantenimiento': 'bg-red-100 text-red-700'
    }[estado] || 'bg-slate-100 text-slate-600';
}
// El estado físico (est_hab) lo maneja Operaciones; aquí se le suma lo que dicen las reservas.
function estadoVisibleHabitacion(h) {
    if (h.estado === 'Mantenimiento' || h.estado === 'Sucia') return h.estado;
    if (h.estado === 'Ocupada' || h.ocupacion === 'en_curso') return 'Ocupada';
    if (h.ocupacion === 'reservada') return 'Reservada';
    return h.estado;
}
function fechaCortaHabitacion(valor) {
    const [anio, mes, dia] = String(valor || '').slice(0, 10).split('-');
    return anio && mes && dia ? `${dia}/${mes}/${anio}` : '';
}
function celdaReservasHabitacion(h) {
    const reservas = h.reservas || [];
    if (!reservas.length) return '<span class="text-xs text-slate-300">Sin reservas</span>';
    const visibles = reservas.slice(0, 3).map(r => `<div class="rounded-lg border ${r.en_curso ? 'border-orange-200 bg-orange-50' : 'border-sky-200 bg-sky-50'} px-2 py-1.5 text-[11px] leading-4 text-slate-600">
            <span class="font-black ${r.en_curso ? 'text-orange-700' : 'text-sky-700'}">${r.en_curso ? 'En casa' : 'Reservada'}</span>
            · Reserva #${Number(r.cod_res)} (${escaparHabitaciones(r.estado)})<br>
            ${escaparHabitaciones(fechaCortaHabitacion(r.entrada))} → ${escaparHabitaciones(fechaCortaHabitacion(r.salida))}${r.huesped ? ' · ' + escaparHabitaciones(r.huesped) : ''}
        </div>`).join('');
    const extra = reservas.length > 3 ? `<p class="text-[10px] font-bold text-slate-400">+${reservas.length - 3} reserva(s) más</p>` : '';
    return `<div class="max-w-[260px] space-y-1">${visibles}${extra}</div>`;
}
function habitacionesFiltradas() {
    const filtro = document.getElementById('filtroEstadoHabitaciones').value;
    return datosHabitaciones.habitaciones.filter(h => {
        const estado = estadoVisibleHabitacion(h);
        if (filtro === 'disponibles') return estado === 'Disponible';
        if (filtro === 'no_disponibles') return estado === 'Ocupada' || estado === 'Reservada';
        return true;
    });
}
function mostrarMensajeHabitaciones(texto, error = false) {
    const mensaje = document.getElementById('mensajeHabitaciones');
    mensaje.textContent = texto;
    mensaje.className = `mb-5 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`;
}
function mostrarErrorFormularioHabitacion(texto) {
    const mensaje = document.getElementById('mensajeFormularioHabitacion');
    mensaje.textContent = texto;
    mensaje.className = 'rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-600';
}
function limpiarErrorFormularioHabitacion() {
    const mensaje = document.getElementById('mensajeFormularioHabitacion');
    mensaje.textContent = '';
    mensaje.className = 'hidden rounded-lg px-4 py-3 text-sm font-bold';
}
function refrescarHousekeepingTrasCambio() {
    if (typeof renderHousekeeping === 'function') renderHousekeeping();
}

async function cargarHabitaciones() {
    try {
        const respuesta = await fetch(`${ENDPOINT_HABITACIONES}?accion=listar`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron cargar las habitaciones');
        datosHabitaciones = datos;
        document.getElementById('btnNuevaHabitacion').classList.toggle('hidden', !datos.puede_gestionar);
        renderizarHabitaciones();
    } catch (error) {
        mostrarMensajeHabitaciones(error.message, true);
    }
}

function renderizarHabitaciones() {
    const cuerpo = document.getElementById('tablaHabitaciones');
    const reservadas = datosHabitaciones.habitaciones.filter(h => ['Ocupada', 'Reservada'].includes(estadoVisibleHabitacion(h))).length;
    document.getElementById('resumenHabitaciones').textContent = `${reservadas} de ${datosHabitaciones.habitaciones.length} habitaciones reservadas u ocupadas`;
    cuerpo.innerHTML = habitacionesFiltradas().map(h => {
        const acciones = datosHabitaciones.puede_gestionar
            ? `<button type="button" class="text-slate-400 hover:text-primary mx-1" title="Editar habitación" aria-label="Editar habitación ${Number(h.numero)}" onclick="abrirEditarHabitacion(${Number(h.id)})"><span class="material-symbols-outlined text-lg">edit</span></button>
               <button type="button" class="text-slate-400 hover:text-red-600 mx-1" title="Eliminar habitación" aria-label="Eliminar habitación ${Number(h.numero)}" onclick="confirmarEliminacionHabitacion(${Number(h.id)})"><span class="material-symbols-outlined text-lg">delete</span></button>`
            : '<span class="text-slate-300">Solo lectura</span>';
        const descripcion = h.descripcion
            ? `<p class="max-w-xs text-xs leading-5 text-slate-500">${escaparHabitaciones(h.descripcion)}</p>`
            : '<span class="text-xs text-slate-400">Sin descripción</span>';
        const foto = h.imagen
            ? `<img src="../../${escaparHabitaciones(h.imagen)}" alt="" class="h-12 w-16 rounded object-cover">`
            : '<span class="text-xs text-slate-400">Por defecto</span>';
        const comodidades = (h.comodidades || []).length
            ? `<div class="mt-2 flex max-w-xs flex-wrap gap-1">${h.comodidades.map(c => `<span class="rounded-full bg-accent/40 px-2 py-0.5 text-[10px] font-bold text-primary">${escaparHabitaciones(c)}</span>`).join('')}</div>`
            : '';
        return `<tr class="hover:bg-slate-50/50">
            <td class="px-6 py-4">${foto}</td>
            <td class="px-6 py-4 font-black text-slate-800">${Number(h.numero)}</td>
            <td class="px-6 py-4"><span class="bg-accent/40 text-primary px-2 py-1 rounded text-xs font-bold">${escaparHabitaciones(h.tipo)}</span></td>
            <td class="px-6 py-4 font-bold text-slate-700">${escaparHabitaciones(precioHabitacionLegible(h.precio))}</td>
            <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-[10px] font-black uppercase ${claseEstadoHabitacion(estadoVisibleHabitacion(h))}">${escaparHabitaciones(estadoVisibleHabitacion(h))}</span></td>
            <td class="px-6 py-4">${celdaReservasHabitacion(h)}</td>
            <td class="px-6 py-4">${descripcion}${comodidades}</td>
            <td class="px-6 py-4 text-right whitespace-nowrap">${acciones}</td>
        </tr>`;
    }).join('') || `<tr><td colspan="8" class="px-6 py-8 text-center text-slate-400 text-sm">${datosHabitaciones.habitaciones.length ? 'No hay habitaciones con ese filtro.' : 'Aún no hay habitaciones registradas.'}</td></tr>`;
}

function abrirModalNuevaHabitacion() { abrirModalHabitacion(null); }
function abrirEditarHabitacion(id) { abrirModalHabitacion(datosHabitaciones.habitaciones.find(h => h.id === id) || null); }

function abrirModalHabitacion(habitacion) {
    document.getElementById('formHabitacion').reset();
    limpiarErrorFormularioHabitacion();
    document.getElementById('idHabitacion').value = habitacion?.id || '';
    document.getElementById('numeroHabitacion').value = habitacion?.numero ?? '';
    document.getElementById('tipoHabitacion').value = habitacion?.tipo || '';
    document.getElementById('precioHabitacion').value = habitacion ? Math.round(habitacion.precio) : '';
    document.getElementById('descripcionHabitacion').value = habitacion?.descripcion || '';
    document.getElementById('contadorDescripcionHabitacion').textContent = (habitacion?.descripcion || '').length;
    document.getElementById('comodidadesHabitacion').value = (habitacion?.comodidades || []).join('\n');
    document.getElementById('quitarImagenHabitacion').value = '0';
    const vistaPrevia = document.getElementById('vistaPreviaImagenHabitacion');
    if (vistaPrevia.dataset.objectUrl) { URL.revokeObjectURL(vistaPrevia.dataset.objectUrl); delete vistaPrevia.dataset.objectUrl; }
    vistaPrevia.src = habitacion?.imagen ? `../../${habitacion.imagen}` : '';
    vistaPrevia.classList.toggle('hidden', !habitacion?.imagen);
    document.getElementById('eliminarImagenHabitacion').classList.toggle('hidden', !habitacion?.imagen);
    document.getElementById('tituloModalHabitacion').textContent = habitacion ? `Editar habitación ${habitacion.numero}` : 'Nueva Habitación';
    const modal = document.getElementById('modalHabitacion');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
}

function cerrarModalHabitacion() {
    const modal = document.getElementById('modalHabitacion');
    modal.classList.add('modal-oculto');
    modal.classList.remove('modal-visible');
}

async function confirmarEliminacionHabitacion(id) {
    const habitacion = datosHabitaciones.habitaciones.find(h => h.id === id);
    if (!habitacion) return;
    if (!confirm(`¿Eliminar la habitación ${habitacion.numero} (${habitacion.tipo})? Esta acción no se puede deshacer.`)) return;
    try {
        const respuesta = await fetch(ENDPOINT_HABITACIONES, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new URLSearchParams({ accion: 'eliminar', id: String(id), csrf_token: CSRF_TOKEN })
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudo eliminar la habitación');
        mostrarMensajeHabitaciones(datos.mensaje);
        await cargarHabitaciones();
        refrescarHousekeepingTrasCambio();
    } catch (error) {
        mostrarMensajeHabitaciones(error.message, true);
    }
}

document.getElementById('imagenHabitacion').addEventListener('change', evento => {
    const archivo = evento.currentTarget.files[0];
    if (!archivo) return;
    const vistaPrevia = document.getElementById('vistaPreviaImagenHabitacion');
    if (vistaPrevia.dataset.objectUrl) URL.revokeObjectURL(vistaPrevia.dataset.objectUrl);
    vistaPrevia.src = URL.createObjectURL(archivo);
    vistaPrevia.dataset.objectUrl = vistaPrevia.src;
    vistaPrevia.classList.remove('hidden');
    document.getElementById('quitarImagenHabitacion').value = '0';
    document.getElementById('eliminarImagenHabitacion').classList.remove('hidden');
});
document.getElementById('eliminarImagenHabitacion').addEventListener('click', () => {
    const vistaPrevia = document.getElementById('vistaPreviaImagenHabitacion');
    const habitacion = datosHabitaciones.habitaciones.find(h => h.id === Number(document.getElementById('idHabitacion').value));
    document.getElementById('quitarImagenHabitacion').value = habitacion?.imagen ? '1' : '0';
    document.getElementById('imagenHabitacion').value = '';
    if (vistaPrevia.dataset.objectUrl) { URL.revokeObjectURL(vistaPrevia.dataset.objectUrl); delete vistaPrevia.dataset.objectUrl; }
    vistaPrevia.removeAttribute('src');
    vistaPrevia.classList.add('hidden');
    document.getElementById('eliminarImagenHabitacion').classList.add('hidden');
});

document.getElementById('descripcionHabitacion').addEventListener('input', e => {
    document.getElementById('contadorDescripcionHabitacion').textContent = e.currentTarget.value.length;
});

document.getElementById('formHabitacion').addEventListener('submit', async evento => {
    evento.preventDefault();
    limpiarErrorFormularioHabitacion();
    const numero = document.getElementById('numeroHabitacion').value.trim();
    const tipo = document.getElementById('tipoHabitacion').value.trim();
    const precio = document.getElementById('precioHabitacion').value.trim();
    if (!/^\d{1,5}$/.test(numero) || Number(numero) < 1) return mostrarErrorFormularioHabitacion('Ingresa un número de habitación válido.');
    if (!tipo) return mostrarErrorFormularioHabitacion('Indica el tipo de habitación.');
    if (!/^\d{1,8}$/.test(precio) || Number(precio) < 1) return mostrarErrorFormularioHabitacion('Ingresa un precio por noche entero y mayor a 0.');

    const cuerpo = new FormData();
    cuerpo.append('accion', 'guardar');
    cuerpo.append('csrf_token', CSRF_TOKEN);
    cuerpo.append('id', document.getElementById('idHabitacion').value);
    cuerpo.append('numero', numero);
    cuerpo.append('tipo', tipo);
    cuerpo.append('precio', precio);
    cuerpo.append('descripcion', document.getElementById('descripcionHabitacion').value.trim());
    cuerpo.append('comodidades', document.getElementById('comodidadesHabitacion').value);
    cuerpo.append('quitar_imagen', document.getElementById('quitarImagenHabitacion').value);
    const imagen = document.getElementById('imagenHabitacion').files[0];
    if (imagen) cuerpo.append('imagen', imagen);

    const boton = evento.currentTarget.querySelector('button[type="submit"]');
    boton.disabled = true;
    try {
        const respuesta = await fetch(ENDPOINT_HABITACIONES, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: cuerpo
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudo guardar la habitación');
        cerrarModalHabitacion();
        mostrarMensajeHabitaciones(datos.mensaje);
        await cargarHabitaciones();
        refrescarHousekeepingTrasCambio();
    } catch (error) {
        mostrarErrorFormularioHabitacion(error.message);
    } finally {
        boton.disabled = false;
    }
});

document.getElementById('filtroEstadoHabitaciones').addEventListener('change', renderizarHabitaciones);

cargarHabitaciones();
</script>