<?php
require_once '../../configuracion/conexion.php';
require_once '../../configuracion/permiso.php';
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'experiencias.ver')) { return; }
?>
<section id="sec-experiencias" class="seccion-contenido hidden fade-in">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div><h3 class="text-2xl font-black text-primary tracking-tight">Experiencias</h3><p class="text-xs text-slate-400 mt-1">Administra las tarjetas que ven los huéspedes en la sección "Experiencias" del sitio.</p></div>
            <button id="btnNuevaExperiencia" type="button" onclick="abrirModalNuevaExperiencia()" class="hidden bg-primary text-white px-6 py-3 rounded-lg font-bold text-sm shadow hover:brightness-110"><span class="material-symbols-outlined text-sm align-middle mr-1">add</span> Nueva Experiencia</button>
        </div>
        <div id="mensajeExperiencias" class="hidden mb-5 rounded-lg px-4 py-3 text-sm font-bold"></div>
        <div class="border border-slate-200 rounded-xl overflow-x-auto"><table class="w-full min-w-[850px] text-left"><thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-4">Imagen</th><th class="px-6 py-4">Categoría</th><th class="px-6 py-4">Nombre y opciones</th><th class="px-6 py-4">Horarios</th><th class="px-6 py-4 text-right">Acciones</th></tr></thead><tbody id="tablaExperiencias" class="divide-y divide-slate-100 text-sm"></tbody></table></div>
    </div>
    <div class="mt-8 rounded-xl border border-primary/10 bg-white p-8 shadow-sm">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div><h3 class="text-2xl font-black text-primary tracking-tight">Historial de experiencias programadas</h3><p class="mt-1 text-xs text-slate-400">Solicitudes de los clientes, sus elecciones por persona y el horario reservado.</p></div>
            <span id="totalHistorialExperiencias" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600">0 solicitudes</span>
        </div>
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full min-w-[900px] text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr><th class="px-5 py-4">Cliente</th><th class="px-5 py-4">Experiencia</th><th class="px-5 py-4">Opciones por persona</th><th class="px-5 py-4">Valor estimado</th><th class="px-5 py-4">Fecha y hora</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4">Acción</th></tr>
                </thead>
                <tbody id="tablaHistorialExperiencias" class="divide-y divide-slate-100 text-sm"></tbody>
            </table>
        </div>
    </div>
</section>
<div id="modalExperiencia" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-500">
    <div class="bg-white p-8 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div><h2 id="tituloModalExperiencia" class="text-2xl font-black text-primary">Nueva Experiencia</h2><p class="text-xs text-slate-400 mt-1">Configura la imagen, las opciones y las fechas disponibles para los huéspedes.</p></div>
            <button type="button" onclick="cerrarModalExperiencia()" class="text-slate-400 hover:text-primary"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formExperiencia" class="space-y-5" enctype="multipart/form-data">
            <div id="mensajeFormularioExperiencia" class="hidden rounded-lg px-4 py-3 text-sm font-bold" role="alert" aria-live="polite"></div>
            <input type="hidden" id="idExperiencia">
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="categoriaExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Categoría</label><input type="text" id="categoriaExperiencia" maxlength="50" required placeholder="Ej. Sabores exclusivos" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></div>
                <div><label for="nombreExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Nombre</label><input type="text" id="nombreExperiencia" maxlength="150" required placeholder="Ej. Gastronomía" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></div>
            </div>
            <div><label for="descripcionExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Descripción</label><textarea id="descripcionExperiencia" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary" rows="3" placeholder="Describe la experiencia que se ofrecerá..."></textarea></div>
            <div>
                <label for="imagenExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Imagen (JPG, PNG o WebP; máximo 5 MB)</label>
                <input type="file" id="imagenExperiencia" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                <input type="hidden" id="quitarImagenExperiencia" value="0">
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <img id="vistaPreviaImagenExperiencia" class="hidden h-36 w-full rounded-lg object-cover" alt="Vista previa de experiencia">
                    <button id="eliminarImagenExperiencia" type="button" class="hidden rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Quitar imagen</button>
                </div>
            </div>
            <fieldset>
                <legend class="mb-2 text-[10px] font-black uppercase text-slate-500">Opciones y precios</legend>
                <p class="mb-3 text-xs text-slate-400">La primera opción es obligatoria; las opciones 2 y 3 son opcionales. Puedes asignar un precio en pesos colombianos o dejarlo vacío.</p>
                <div class="grid gap-3 md:grid-cols-3">
                    <?php foreach ([1, 2, 3] as $numeroOpcion): ?>
                        <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                            <label for="opcionExperiencia<?php echo $numeroOpcion; ?>" class="block text-xs font-bold text-slate-600">Opción <?php echo $numeroOpcion; ?></label>
                            <input type="text" id="opcionExperiencia<?php echo $numeroOpcion; ?>" maxlength="100" <?php echo $numeroOpcion === 1 ? 'required' : ''; ?> placeholder="<?php echo ['1' => 'Ej. Menú degustación', '2' => 'Opcional: Ej. Plato especial', '3' => 'Opcional: Ej. Cena privada'][$numeroOpcion]; ?>" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
                            <label for="precioOpcionExperiencia<?php echo $numeroOpcion; ?>" class="block text-xs font-bold text-slate-600">Precio (COP)</label>
                            <input type="number" id="precioOpcionExperiencia<?php echo $numeroOpcion; ?>" min="0" max="9999999999" step="1" inputmode="numeric" placeholder="Sin precio" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 text-sm">
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <fieldset>
                <legend class="mb-1 text-[10px] font-black uppercase text-slate-500">Fechas y horarios disponibles</legend>
                <p class="mb-3 text-xs text-slate-400">Selecciona una opción y configura sus fechas. Cada fecha tendrá su propio rango de inicio y fin, con turnos cada 30 minutos.</p>
                <label for="opcionHorarioExperiencia" class="mb-3 block text-xs font-bold text-slate-600">Opción a programar
                    <select id="opcionHorarioExperiencia" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></select>
                </label>
                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <label class="text-xs font-bold text-slate-600" for="fechaHorarioExperiencia">Fecha<input id="fechaHorarioExperiencia" type="date" min="<?php echo htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                    <label class="text-xs font-bold text-slate-600" for="horaInicioExperiencia">Hora de inicio<input id="horaInicioExperiencia" type="time" step="1800" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                    <label class="text-xs font-bold text-slate-600" for="horaFinExperiencia">Hora de fin<input id="horaFinExperiencia" type="time" step="1800" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                    <button id="agregarHorarioExperiencia" type="button" class="self-end rounded-lg bg-primary px-4 py-3 text-sm font-bold text-white">Añadir fecha</button>
                </div>
                <div id="listaHorariosExperiencia" class="experience-schedule-list mt-4 space-y-2" role="region" aria-label="Fechas y horas añadidas" tabindex="0"></div>
            </fieldset>
            <div class="flex gap-4 pt-2"><button type="button" onclick="cerrarModalExperiencia()" class="flex-1 py-3 text-slate-400 font-bold hover:bg-slate-100 rounded-lg">Cancelar</button><button type="submit" class="flex-1 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Guardar</button></div>
        </form>
    </div>
</div>
<style>
    .experience-schedule-list {
        max-height: 15rem;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        scrollbar-width: auto;
        scrollbar-color: #64748b #e2e8f0;
    }

    .experience-schedule-list::-webkit-scrollbar {
        width: 14px;
    }

    .experience-schedule-list::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 999px;
    }

    .experience-schedule-list::-webkit-scrollbar-thumb {
        background: #64748b;
        border: 3px solid #e2e8f0;
        border-radius: 999px;
    }

    .experience-schedule-list:focus-visible {
        outline: 2px solid #2c5e5e;
        outline-offset: 2px;
    }
</style>
<script>
const ENDPOINT_EXPERIENCIAS = '../../controladores/gestionar_experiencias.php';
let datosExperiencias = { experiencias: [], puede_gestionar: false };
let horariosExperienciaBorrador = {};
function escaparExperiencias(valor) { return String(valor ?? '').replace(/[&<>"']/g, caracter => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[caracter])); }
function fechaExperienciaLegible(fecha) { const [anio, mes, dia] = fecha.split('-').map(Number); return new Date(anio, mes - 1, dia).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }); }
function precioExperienciaLegible(precio) { return precio === null || precio === undefined || precio === '' ? 'Sin precio' : new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(precio)); }
function numerosOpcionesExperienciaDefinidas() {
    return [1, 2, 3].filter(numero => document.getElementById(`opcionExperiencia${numero}`).value.trim() !== '');
}
function renderizarSelectorOpcionHorario() {
    const selector = document.getElementById('opcionHorarioExperiencia');
    const seleccionAnterior = selector.value || '1';
    const opcionesDefinidas = numerosOpcionesExperienciaDefinidas();
    selector.innerHTML = opcionesDefinidas.map(numero => {
        const nombre = document.getElementById(`opcionExperiencia${numero}`).value.trim();
        return `<option value="${numero}">${escaparExperiencias(nombre)}</option>`;
    }).join('');
    selector.value = opcionesDefinidas.includes(Number(seleccionAnterior))
        ? seleccionAnterior
        : String(opcionesDefinidas[0] || '');
}
function renderizarHorariosExperiencia() {
    const lista = document.getElementById('listaHorariosExperiencia');
    const opciones = numerosOpcionesExperienciaDefinidas();
    if (opciones.length === 0) {
        lista.innerHTML = '<p class="text-xs text-slate-400">Añade al menos una opción para configurar sus fechas y horarios.</p>';
        return;
    }
    lista.innerHTML = opciones.map(numero => {
        const nombre = document.getElementById(`opcionExperiencia${numero}`).value.trim();
        const fechas = Object.keys(horariosExperienciaBorrador[String(numero)] || {}).sort();
        const filas = fechas.map(fecha => {
            const rango = horariosExperienciaBorrador[String(numero)][fecha];
            return `<div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3"><div><strong class="text-sm text-slate-700">${escaparExperiencias(fechaExperienciaLegible(fecha))}</strong><p class="mt-1 text-xs text-slate-500">${escaparExperiencias(rango.inicio)} – ${escaparExperiencias(rango.fin)}</p></div><button type="button" data-eliminar-opcion="${numero}" data-eliminar-fecha="${escaparExperiencias(fecha)}" class="text-xs font-bold text-red-600 hover:text-red-800">Quitar fecha</button></div>`;
        }).join('') || '<p class="text-xs text-slate-400">Sin fechas configuradas para esta opción.</p>';
        return `<section class="rounded-lg bg-slate-50 p-3"><h4 class="mb-2 text-xs font-black uppercase text-primary">${escaparExperiencias(nombre)}</h4><div class="space-y-2">${filas}</div></section>`;
    }).join('');
}
function agregarHorarioExperiencia() {
    const opcion = document.getElementById('opcionHorarioExperiencia').value;
    const fecha = document.getElementById('fechaHorarioExperiencia').value;
    const inicio = document.getElementById('horaInicioExperiencia').value;
    const fin = document.getElementById('horaFinExperiencia').value;
    if (!fecha || !inicio || !fin) {
        mostrarMensajeExperiencias('Selecciona la fecha, la hora de inicio y la hora de fin.', true);
        return;
    }
    if (fecha < document.getElementById('fechaHorarioExperiencia').min) {
        mostrarMensajeExperiencias('No puedes añadir una fecha pasada.', true);
        return;
    }
    if (!/^(?:[01]\d|2[0-3]):(?:00|30)$/.test(inicio) || !/^(?:[01]\d|2[0-3]):(?:00|30)$/.test(fin)) {
        mostrarMensajeExperiencias('El inicio y el fin deben estar en intervalos de 30 minutos.', true);
        return;
    }
    if (fin < inicio) {
        mostrarMensajeExperiencias('La hora de fin no puede ser anterior a la hora de inicio.', true);
        return;
    }
    horariosExperienciaBorrador[opcion] ||= {};
    if (!horariosExperienciaBorrador[opcion][fecha] && Object.keys(horariosExperienciaBorrador[opcion]).length >= 100) {
        mostrarMensajeExperiencias('Cada opción admite hasta 100 fechas programadas.', true);
        return;
    }
    horariosExperienciaBorrador[opcion][fecha] = { inicio, fin };
    renderizarHorariosExperiencia();
    document.getElementById('horaInicioExperiencia').value = '';
    document.getElementById('horaFinExperiencia').value = '';
    document.getElementById('listaHorariosExperiencia').scrollTop = document.getElementById('listaHorariosExperiencia').scrollHeight;
}
function mostrarMensajeExperiencias(texto, error = false) { const mensaje = document.getElementById('mensajeExperiencias'); mensaje.textContent = texto; mensaje.className = `mb-5 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`; mensaje.classList.remove('hidden'); }
function mostrarErrorFormularioExperiencia(texto) { const mensaje = document.getElementById('mensajeFormularioExperiencia'); mensaje.textContent = texto; mensaje.className = 'rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-600'; }
function limpiarErrorFormularioExperiencia() { const mensaje = document.getElementById('mensajeFormularioExperiencia'); mensaje.textContent = ''; mensaje.className = 'hidden rounded-lg px-4 py-3 text-sm font-bold'; }
async function cargarExperiencias() { try { const respuesta = await fetch(`${ENDPOINT_EXPERIENCIAS}?accion=listar`, { headers: { Accept: 'application/json' } }); const datos = await respuesta.json(); if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron cargar las experiencias'); datosExperiencias = datos; document.getElementById('btnNuevaExperiencia').classList.toggle('hidden', !datos.puede_gestionar); renderizarExperiencias(); renderizarHistorialExperiencias(); } catch (error) { mostrarMensajeExperiencias(error.message, true); } }
function renderizarExperiencias() {
    document.getElementById('tablaExperiencias').innerHTML = datosExperiencias.experiencias.map(experiencia => {
        const acciones = datosExperiencias.puede_gestionar
            ? `<button type="button" class="text-slate-400 hover:text-primary mx-1" title="Editar experiencia" onclick="abrirEditarExperiencia(${experiencia.id})"><span class="material-symbols-outlined text-lg">edit</span></button><button type="button" class="text-slate-400 hover:text-red-600 mx-1" title="Eliminar experiencia" onclick="confirmarEliminacionExperiencia(${experiencia.id})"><span class="material-symbols-outlined text-lg">delete</span></button>`
            : '<span class="text-slate-300">Solo lectura</span>';
        const imagen = experiencia.imagen ? `<img src="../../${escaparExperiencias(experiencia.imagen)}" alt="" class="h-12 w-16 rounded object-cover">` : '<span class="text-xs text-slate-400">Sin imagen</span>';
        const opciones = (experiencia.opciones || []).map((opcion, indice) => `${escaparExperiencias(opcion)} (${escaparExperiencias(precioExperienciaLegible(experiencia.precios?.[indice]))})`).join(' · ');
        const horarios = [1, 2, 3].flatMap(numero => {
            const nombreOpcion = experiencia.opciones?.[numero - 1] || `Opción ${numero}`;
            return Object.keys(experiencia.horarios?.[String(numero)] || {}).sort().map(fecha => {
                const rango = experiencia.horarios[String(numero)][fecha];
                return `<strong>${escaparExperiencias(nombreOpcion)} · ${escaparExperiencias(fechaExperienciaLegible(fecha))}:</strong> ${escaparExperiencias(rango.inicio)}–${escaparExperiencias(rango.fin)}`;
            });
        }).join('<br>');
        return `<tr class="hover:bg-slate-50/50"><td class="px-6 py-4">${imagen}</td><td class="px-6 py-4"><span class="bg-accent/40 text-primary px-2 py-1 rounded text-xs font-bold">${escaparExperiencias(experiencia.categoria)}</span></td><td class="px-6 py-4"><p class="font-black text-slate-800">${escaparExperiencias(experiencia.nombre)}</p><p class="mt-1 max-w-xs text-xs text-slate-500">${opciones}</p></td><td class="px-6 py-4 text-xs leading-5 text-slate-500">${horarios || 'Sin horarios'}</td><td class="px-6 py-4 text-right">${acciones}</td></tr>`;
    }).join('') || '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">Aún no hay experiencias registradas.</td></tr>';
}
function renderizarHistorialExperiencias() {
    const historial = datosExperiencias.historial || [];
    document.getElementById('totalHistorialExperiencias').textContent = `${historial.length} ${historial.length === 1 ? 'solicitud' : 'solicitudes'}`;
    document.getElementById('tablaHistorialExperiencias').innerHTML = historial.map(solicitud => {
        const selecciones = Array.isArray(solicitud.selecciones_personas) ? solicitud.selecciones_personas : [];
        const precios = Array.isArray(solicitud.precios_personas) ? solicitud.precios_personas : [];
        const moneda = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
        const opciones = selecciones.map((opcion, indice) => {
            const precio = precios[indice];
            const detallePrecio = precio === null || precio === undefined ? 'sin precio' : moneda.format(Number(precio));
            return `<span class="inline-flex rounded-full bg-accent/40 px-2 py-1 text-xs font-bold text-primary">Persona ${indice + 1}: ${escaparExperiencias(opcion)} · ${escaparExperiencias(detallePrecio)}</span>`;
        }).join(' ');
        const monto = solicitud.monto_experiencia === null || solicitud.monto_experiencia === undefined
            ? 'No definido'
            : moneda.format(Number(solicitud.monto_experiencia));
        const fecha = /^\d{4}-\d{2}-\d{2}$/.test(solicitud.fecha_agenda || '')
            ? fechaExperienciaLegible(solicitud.fecha_agenda)
            : escaparExperiencias(solicitud.fecha_agenda);
        const estado = solicitud.estado_agenda || 'Pendiente';
        const claseEstado = estado === 'Cancelada' ? 'bg-rose-100 text-rose-700' : estado === 'Confirmada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
        const fechaHora = new Date(`${solicitud.fecha_agenda}T${String(solicitud.hora_agenda || '').slice(0, 8)}`);
        const puedeCancelar = estado !== 'Cancelada' && Number.isFinite(fechaHora.getTime()) && fechaHora > new Date();
        const accion = puedeCancelar
            ? `<button type="button" data-cancelar-solicitud-admin="${Number(solicitud.id_agenda)}" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">Cancelar</button>`
            : '<span class="text-xs text-slate-400">—</span>';
        return `<tr class="hover:bg-slate-50/70"><td class="px-5 py-4"><p class="font-bold text-slate-800">${escaparExperiencias(solicitud.nombre_cliente || solicitud.nombre_contacto)}</p><p class="mt-1 text-xs text-slate-500">${escaparExperiencias(solicitud.correo_cliente || solicitud.correo_contacto)}</p></td><td class="px-5 py-4 font-black text-slate-800">${escaparExperiencias(solicitud.actividad)}</td><td class="px-5 py-4"><div class="flex max-w-sm flex-wrap gap-1">${opciones || '<span class="text-xs text-slate-400">Sin detalle de opciones</span>'}</div></td><td class="px-5 py-4 font-bold text-slate-700">${escaparExperiencias(monto)}</td><td class="px-5 py-4 text-slate-600"><p>${fecha}</p><p class="mt-1 text-xs">${escaparExperiencias(String(solicitud.hora_agenda || '').slice(0, 5))}</p></td><td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-[10px] font-black uppercase ${claseEstado}">${escaparExperiencias(estado)}</span></td><td class="px-5 py-4">${accion}</td></tr>`;
    }).join('') || '<tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">Todavía no hay experiencias programadas por los clientes.</td></tr>';
}
async function cancelarSolicitudExperienciaAdmin(idSolicitud, boton) {
    if (!confirm('¿Cancelar esta experiencia programada? Esta acción quedará reflejada en el historial del cliente.')) return;
    boton.disabled = true;
    try {
        const respuesta = await fetch(ENDPOINT_EXPERIENCIAS, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new URLSearchParams({ accion: 'cancelar_solicitud', id_agenda: String(idSolicitud), csrf_token: CSRF_TOKEN })
        });
        const datos = await respuesta.json();
        if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudo cancelar la experiencia');
        mostrarMensajeExperiencias(datos.mensaje);
        await cargarExperiencias();
    } catch (error) {
        mostrarMensajeExperiencias(error.message, true);
        boton.disabled = false;
    }
}
function abrirModalNuevaExperiencia() { abrirModalExperiencia(null); }
function abrirEditarExperiencia(id) { abrirModalExperiencia(datosExperiencias.experiencias.find(experiencia => experiencia.id === id)); }
function abrirModalExperiencia(experiencia) {
    const formulario = document.getElementById('formExperiencia');
    formulario.reset();
    limpiarErrorFormularioExperiencia();
    document.getElementById('idExperiencia').value = experiencia?.id || '';
    document.getElementById('categoriaExperiencia').value = experiencia?.categoria || '';
    document.getElementById('nombreExperiencia').value = experiencia?.nombre || '';
    document.getElementById('descripcionExperiencia').value = experiencia?.descripcion || '';
    document.getElementById('quitarImagenExperiencia').value = '0';
    [1, 2, 3].forEach((numero, indice) => {
        document.getElementById(`opcionExperiencia${numero}`).value = experiencia?.opciones?.[indice] || '';
        document.getElementById(`precioOpcionExperiencia${numero}`).value = experiencia?.precios?.[indice] ?? '';
    });
    horariosExperienciaBorrador = Object.fromEntries([1, 2, 3].map(numero => [
        String(numero),
        Object.fromEntries(Object.entries(experiencia?.horarios?.[String(numero)] || {})
            .filter(([fecha, rango]) => /^\d{4}-\d{2}-\d{2}$/.test(fecha) && rango && typeof rango === 'object')
            .map(([fecha, rango]) => [fecha, { inicio: rango.inicio, fin: rango.fin }]))
    ]));
    renderizarSelectorOpcionHorario();
    renderizarHorariosExperiencia();
    const vistaPrevia = document.getElementById('vistaPreviaImagenExperiencia');
    vistaPrevia.src = experiencia?.imagen ? `../../${experiencia.imagen}` : '';
    vistaPrevia.classList.toggle('hidden', !experiencia?.imagen);
    document.getElementById('eliminarImagenExperiencia').classList.toggle('hidden', !experiencia?.imagen);
    document.getElementById('tituloModalExperiencia').textContent = experiencia ? `Editar: ${experiencia.nombre}` : 'Nueva Experiencia';
    const modal = document.getElementById('modalExperiencia');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
}
function cerrarModalExperiencia() { const modal = document.getElementById('modalExperiencia'); modal.classList.add('modal-oculto'); modal.classList.remove('modal-visible'); }
async function confirmarEliminacionExperiencia(id) {
    const experiencia = datosExperiencias.experiencias.find(item => item.id === id);
    if (!experiencia) return;
    const nombre = experiencia.nombre;
    if (!confirm(`¿Eliminar la experiencia "${nombre}"?`)) return;
    try {
        const respuesta = await fetch(ENDPOINT_EXPERIENCIAS, { method: 'POST', headers: { Accept: 'application/json' }, body: new URLSearchParams({ accion: 'eliminar', id, csrf_token: CSRF_TOKEN }) });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudo eliminar la experiencia');
        mostrarMensajeExperiencias(datos.mensaje);
        await cargarExperiencias();
    } catch (error) {
        mostrarMensajeExperiencias(error.message, true);
    }
}
document.getElementById('formExperiencia').addEventListener('submit', async evento => {
    evento.preventDefault();
    limpiarErrorFormularioExperiencia();
    const formulario = evento.currentTarget;
    const cuerpo = new FormData();
    cuerpo.append('accion', 'guardar');
    cuerpo.append('csrf_token', CSRF_TOKEN);
    cuerpo.append('id', document.getElementById('idExperiencia').value);
    cuerpo.append('categoria', document.getElementById('categoriaExperiencia').value.trim());
    cuerpo.append('nombre', document.getElementById('nombreExperiencia').value.trim());
    cuerpo.append('descripcion', document.getElementById('descripcionExperiencia').value.trim());
    cuerpo.append('quitar_imagen', document.getElementById('quitarImagenExperiencia').value);
    [1, 2, 3].forEach(numero => {
        cuerpo.append(`opcion_${numero}`, document.getElementById(`opcionExperiencia${numero}`).value.trim());
        cuerpo.append(`precio_opcion_${numero}`, document.getElementById(`precioOpcionExperiencia${numero}`).value.trim());
    });
<<<<<<< HEAD
    const opcionesDefinidas = numerosOpcionesExperienciaDefinidas();
    if (opcionesDefinidas.length === 0) {
        mostrarMensajeExperiencias('Define al menos una opción para la experiencia.', true);
        return;
    }
    if (opcionesDefinidas.some((numero, indice) => numero !== indice + 1)) {
        mostrarMensajeExperiencias('Completa las opciones en orden, sin dejar espacios entre ellas.', true);
        return;
    }
    if (opcionesDefinidas.some(numero => Object.keys(horariosExperienciaBorrador[String(numero)] || {}).length === 0)) {
        mostrarMensajeExperiencias('Configura al menos una fecha y un horario para cada opción definida.', true);
=======
    if ([1, 2, 3].some(numero => Object.keys(horariosExperienciaBorrador[String(numero)] || {}).length === 0)) {
        mostrarErrorFormularioExperiencia('Configura al menos una fecha y un horario para cada opción.');
>>>>>>> 5e3348b9c14dfceda52231c3b04767be5145f18c
        return;
    }
    cuerpo.append('horarios', JSON.stringify({ opciones: horariosExperienciaBorrador }));
    const imagen = document.getElementById('imagenExperiencia').files[0];
    if (imagen) cuerpo.append('imagen', imagen);
    const boton = formulario.querySelector('button[type="submit"]');
    boton.disabled = true;
    try {
        const respuesta = await fetch(ENDPOINT_EXPERIENCIAS, { method: 'POST', headers: { Accept: 'application/json' }, body: cuerpo });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudo guardar la experiencia');
        cerrarModalExperiencia();
        mostrarMensajeExperiencias(datos.mensaje);
        await cargarExperiencias();
    } catch (error) {
        mostrarErrorFormularioExperiencia(error.message);
    } finally {
        boton.disabled = false;
    }
});
document.getElementById('imagenExperiencia').addEventListener('change', evento => {
    const archivo = evento.currentTarget.files[0];
    if (!archivo) return;
    const vistaPrevia = document.getElementById('vistaPreviaImagenExperiencia');
    if (vistaPrevia.dataset.objectUrl) URL.revokeObjectURL(vistaPrevia.dataset.objectUrl);
    vistaPrevia.src = URL.createObjectURL(archivo);
    vistaPrevia.dataset.objectUrl = vistaPrevia.src;
    vistaPrevia.classList.remove('hidden');
    document.getElementById('quitarImagenExperiencia').value = '0';
    document.getElementById('eliminarImagenExperiencia').classList.remove('hidden');
});
document.getElementById('eliminarImagenExperiencia').addEventListener('click', () => {
    const inputImagen = document.getElementById('imagenExperiencia');
    const vistaPrevia = document.getElementById('vistaPreviaImagenExperiencia');
    const experiencia = datosExperiencias.experiencias.find(item => item.id === Number(document.getElementById('idExperiencia').value));
    document.getElementById('quitarImagenExperiencia').value = experiencia?.imagen ? '1' : '0';
    inputImagen.value = '';
    if (vistaPrevia.dataset.objectUrl) {
        URL.revokeObjectURL(vistaPrevia.dataset.objectUrl);
        delete vistaPrevia.dataset.objectUrl;
    }
    vistaPrevia.removeAttribute('src');
    vistaPrevia.classList.add('hidden');
    document.getElementById('eliminarImagenExperiencia').classList.add('hidden');
});
document.getElementById('agregarHorarioExperiencia').addEventListener('click', agregarHorarioExperiencia);
document.getElementById('listaHorariosExperiencia').addEventListener('click', evento => {
    const quitarFecha = evento.target.closest('[data-eliminar-fecha]');
    if (quitarFecha) {
        delete horariosExperienciaBorrador[quitarFecha.dataset.eliminarOpcion][quitarFecha.dataset.eliminarFecha];
        renderizarHorariosExperiencia();
    }
});
document.getElementById('tablaHistorialExperiencias').addEventListener('click', evento => {
    const boton = evento.target.closest('[data-cancelar-solicitud-admin]');
    if (boton) cancelarSolicitudExperienciaAdmin(boton.dataset.cancelarSolicitudAdmin, boton);
});
document.getElementById('opcionHorarioExperiencia').addEventListener('change', () => {
    const opcion = document.getElementById('opcionHorarioExperiencia').value;
    const primeraFecha = Object.keys(horariosExperienciaBorrador[opcion] || {}).sort()[0];
    if (primeraFecha) document.getElementById('fechaHorarioExperiencia').value = primeraFecha;
});
[1, 2, 3].forEach(numero => {
    document.getElementById(`opcionExperiencia${numero}`).addEventListener('input', () => {
        renderizarSelectorOpcionHorario();
        renderizarHorariosExperiencia();
    });
});
cargarExperiencias();
</script>