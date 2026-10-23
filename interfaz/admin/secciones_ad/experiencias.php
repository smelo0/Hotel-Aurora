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
        <div class="mb-5 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 text-xs font-black" role="group" aria-label="Vista del historial">
            <button type="button" data-vista-historial="grupos" class="rounded-lg px-4 py-2">Por experiencia</button>
            <button type="button" data-vista-historial="lista" class="rounded-lg px-4 py-2">Lista completa</button>
        </div>
        <div id="gruposHistorialExperiencias" class="space-y-5"></div>
        <div id="listaHistorialExperiencias" class="hidden overflow-x-auto rounded-xl border border-slate-200">
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
                <div><label for="categoriaExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Categoría</label><input type="text" id="categoriaExperiencia" maxlength="50" placeholder="Opcional: Ej. Sabores exclusivos" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></div>
                <div><label for="nombreExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Nombre</label><input type="text" id="nombreExperiencia" maxlength="150" placeholder="Opcional: Ej. Gastronomía" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary"></div>
            </div>
            <div><label for="descripcionExperiencia" class="block text-[10px] font-black uppercase text-slate-500 mb-1">Descripción</label><textarea id="descripcionExperiencia" class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 outline-none focus:ring-1 focus:ring-primary" rows="3" placeholder="Describe la experiencia que se ofrecerá..."></textarea></div>
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
                <p class="mb-3 text-xs text-slate-400">La opción 1 es obligatoria. Las opciones 2 y 3 son opcionales; puedes dejar cualquier precio vacío.</p>
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
                <p class="mb-3 text-xs text-slate-400">Opcionalmente, programa una o varias fechas consecutivas. Se aplicará el mismo horario a cada día del rango.</p>
                <label for="opcionHorarioExperiencia" class="mb-3 block text-xs font-bold text-slate-600">Opción a programar
                    <select id="opcionHorarioExperiencia" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></select>
                </label>
                <div class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-bold text-slate-600" for="fechaInicioHorarioExperiencia">Fecha de inicio<input id="fechaInicioHorarioExperiencia" type="date" min="<?php echo htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                        <label class="text-xs font-bold text-slate-600" for="fechaFinHorarioExperiencia">Fecha de fin<input id="fechaFinHorarioExperiencia" type="date" min="<?php echo htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                    </div>
                    <div class="border-t border-slate-100 pt-4">
                        <p class="mb-3 text-xs font-bold text-slate-600">Horario diario</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-xs font-bold text-slate-600" for="horaInicioExperiencia">Hora de inicio<input id="horaInicioExperiencia" type="time" step="1800" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                            <label class="text-xs font-bold text-slate-600" for="horaFinExperiencia">Hora de fin<input id="horaFinExperiencia" type="time" step="1800" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 p-3"></label>
                        </div>
                        <button id="agregarHorarioExperiencia" type="button" class="mt-3 w-full rounded-lg bg-primary px-4 py-3 text-sm font-bold text-white">Añadir rango de fechas</button>
                    </div>
                </div>
                <div id="listaHorariosExperiencia" class="experience-schedule-list mt-4 space-y-2" role="region" aria-label="Fechas y horas añadidas" tabindex="0"></div>
            </fieldset>
            <div class="flex gap-4 pt-2"><button type="button" onclick="cerrarModalExperiencia()" class="flex-1 py-3 text-slate-400 font-bold hover:bg-slate-100 rounded-lg">Cancelar</button><button type="submit" class="flex-1 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Guardar</button></div>
        </form>
    </div>
</div>
<script>
const ENDPOINT_EXPERIENCIAS = '../../controladores/gestionar_experiencias.php';
let datosExperiencias = { experiencias: [], puede_gestionar: false };
let horariosExperienciaBorrador = {};
function escaparExperiencias(valor) { return String(valor ?? '').replace(/[&<>"']/g, caracter => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[caracter])); }
function fechaExperienciaLegible(fecha) { const [anio, mes, dia] = fecha.split('-').map(Number); return new Date(anio, mes - 1, dia).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }); }
function fechaExperienciaCorta(fecha) { return fecha.split('-').reverse().join('/'); }
function agruparFechasHorarioExperiencia(fechas) {
    const grupos = [];
    Object.keys(fechas).sort().forEach(fecha => {
        const rango = fechas[fecha];
        const anterior = grupos[grupos.length - 1];
        const siguienteAlAnterior = anterior
            && new Date(`${anterior.fin}T00:00:00Z`).getTime() + 86400000 === new Date(`${fecha}T00:00:00Z`).getTime();
        if (siguienteAlAnterior && anterior.horaInicio === rango.inicio && anterior.horaFin === rango.fin) {
            anterior.fin = fecha;
            return;
        }
        grupos.push({ inicio: fecha, fin: fecha, horaInicio: rango.inicio, horaFin: rango.fin });
    });
    return grupos;
}
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
        lista.innerHTML = '<p class="text-xs text-slate-400">Completa la opción para configurar fechas y horarios.</p>';
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
    if (!opcion) {
        mostrarMensajeExperiencias('Completa una opción antes de programar fechas y horarios.', true);
        return;
    }
    const fechaInicio = document.getElementById('fechaInicioHorarioExperiencia').value;
    const fechaFin = document.getElementById('fechaFinHorarioExperiencia').value;
    const inicio = document.getElementById('horaInicioExperiencia').value;
    const fin = document.getElementById('horaFinExperiencia').value;
    if (!fechaInicio || !fechaFin || !inicio || !fin) {
        mostrarMensajeExperiencias('Selecciona las fechas de inicio y fin, y las horas de inicio y fin.', true);
        return;
    }
    if (fechaInicio < document.getElementById('fechaInicioHorarioExperiencia').min || fechaFin < fechaInicio) {
        mostrarMensajeExperiencias('Selecciona un rango de fechas válido, desde hoy en adelante.', true);
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
    const milisegundosPorDia = 24 * 60 * 60 * 1000;
    const primerDia = Date.parse(`${fechaInicio}T00:00:00Z`);
    const ultimoDia = Date.parse(`${fechaFin}T00:00:00Z`);
    const cantidadDias = Math.floor((ultimoDia - primerDia) / milisegundosPorDia) + 1;
    if (!Number.isSafeInteger(cantidadDias) || cantidadDias < 1 || cantidadDias > 100) {
        mostrarMensajeExperiencias('Cada rango admite hasta 100 fechas programadas.', true);
        return;
    }
    horariosExperienciaBorrador[opcion] ||= {};
    // Guardamos un horario por día para mantener compatible la agenda pública.
    const fechasRango = Array.from({ length: cantidadDias }, (_, indice) =>
        new Date(primerDia + indice * milisegundosPorDia).toISOString().slice(0, 10)
    );
    const nuevasFechas = fechasRango.filter(fecha => !horariosExperienciaBorrador[opcion][fecha]);
    if (Object.keys(horariosExperienciaBorrador[opcion]).length + nuevasFechas.length > 100) {
        mostrarMensajeExperiencias('Cada opción admite hasta 100 fechas programadas.', true);
        return;
    }
    fechasRango.forEach(fecha => {
        horariosExperienciaBorrador[opcion][fecha] = { inicio, fin };
    });
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
            const indiceOpcion = (experiencia.numeros_opciones || [1, 2, 3]).indexOf(numero);
            const nombreOpcion = indiceOpcion < 0 ? `Opción ${numero}` : experiencia.opciones?.[indiceOpcion];
            return agruparFechasHorarioExperiencia(experiencia.horarios?.[String(numero)] || {}).map(grupo =>
                `<div class="rounded-md bg-slate-50 px-2 py-1"><strong class="text-slate-700">${escaparExperiencias(nombreOpcion)}</strong>; ${escaparExperiencias(fechaExperienciaCorta(grupo.inicio))}; ${escaparExperiencias(fechaExperienciaCorta(grupo.fin))}; ${escaparExperiencias(grupo.horaInicio)}–${escaparExperiencias(grupo.horaFin)}</div>`
            );
        }).join('');
        return `<tr class="hover:bg-slate-50/50"><td class="px-6 py-4">${imagen}</td><td class="px-6 py-4"><span class="bg-accent/40 text-primary px-2 py-1 rounded text-xs font-bold">${escaparExperiencias(experiencia.categoria)}</span></td><td class="px-6 py-4"><p class="font-black text-slate-800">${escaparExperiencias(experiencia.nombre)}</p><p class="mt-1 max-w-xs text-xs text-slate-500">${opciones}</p></td><td class="space-y-1 px-6 py-4 text-xs leading-5 text-slate-500">${horarios || 'Sin horarios'}</td><td class="px-6 py-4 text-right">${acciones}</td></tr>`;
    }).join('') || '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">Aún no hay experiencias registradas.</td></tr>';
}
function renderizarHistorialExperiencias() {
    const historial = datosExperiencias.historial || [];
    document.getElementById('totalHistorialExperiencias').textContent = `${historial.length} ${historial.length === 1 ? 'solicitud' : 'solicitudes'}`;
    renderizarGruposHistorialExperiencias(historial);
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
// ---- Historial agrupado: cuántas personas pidieron cada experiencia, por opción y horario ----
let vistaHistorialExperiencias = 'grupos';

function personasDeSolicitudExperiencia(solicitud) {
    const selecciones = Array.isArray(solicitud.selecciones_personas) ? solicitud.selecciones_personas.filter(opcion => String(opcion ?? '').trim() !== '') : [];
    if (selecciones.length) return selecciones.map(opcion => String(opcion));
    return [String(solicitud.opcion_actividad || 'Sin detalle de opción')];
}

function textoPersonasExperiencia(cantidad) { return `${cantidad} ${cantidad === 1 ? 'persona' : 'personas'}`; }

function renderizarGruposHistorialExperiencias(historial) {
    const contenedor = document.getElementById('gruposHistorialExperiencias');
    if (!historial.length) {
        contenedor.innerHTML = '<p class="rounded-xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-400">Todavía no hay experiencias programadas por los clientes.</p>';
        return;
    }
    const ahora = new Date();
    const grupos = new Map();
    historial.forEach(solicitud => {
        const nombre = String(solicitud.actividad || 'Sin nombre');
        if (!grupos.has(nombre)) grupos.set(nombre, { nombre, personas: 0, solicitudes: 0, pendientes: 0, confirmadas: 0, canceladas: 0, sesiones: new Map() });
        const grupo = grupos.get(nombre);
        const estado = solicitud.estado_agenda || 'Pendiente';
        if (estado === 'Cancelada') { grupo.canceladas++; return; }
        grupo.solicitudes++;
        if (estado === 'Confirmada') grupo.confirmadas++; else grupo.pendientes++;
        const fecha = /^\d{4}-\d{2}-\d{2}$/.test(solicitud.fecha_agenda || '') ? solicitud.fecha_agenda : '';
        const hora = String(solicitud.hora_agenda || '').slice(0, 5);
        personasDeSolicitudExperiencia(solicitud).forEach(opcion => {
            const clave = `${opcion}|${fecha}|${hora}`;
            if (!grupo.sesiones.has(clave)) grupo.sesiones.set(clave, { opcion, fecha, hora, personas: 0, solicitudes: new Map() });
            const sesion = grupo.sesiones.get(clave);
            sesion.personas++;
            grupo.personas++;
            const aporte = sesion.solicitudes.get(solicitud.id_agenda) || { solicitud, personas: 0 };
            aporte.personas++;
            sesion.solicitudes.set(solicitud.id_agenda, aporte);
        });
    });

    const instanteSesion = sesion => sesion.fecha && sesion.hora ? new Date(`${sesion.fecha}T${sesion.hora}:00`) : null;
    const ordenSesiones = (a, b) => {
        const da = instanteSesion(a), db = instanteSesion(b);
        if (!da || !db) return da ? -1 : db ? 1 : a.opcion.localeCompare(b.opcion, 'es');
        const futuraA = da >= ahora, futuraB = db >= ahora;
        if (futuraA !== futuraB) return futuraA ? -1 : 1;
        return futuraA ? da - db : db - da;
    };
    const chip = (texto, clases) => `<span class="rounded-full px-3 py-1 text-[10px] font-black uppercase ${clases}">${texto}</span>`;

    contenedor.innerHTML = [...grupos.values()]
        .sort((a, b) => b.personas - a.personas || a.nombre.localeCompare(b.nombre, 'es'))
        .map(grupo => {
            const sesiones = [...grupo.sesiones.values()].sort(ordenSesiones).map(sesion => {
                const instante = instanteSesion(sesion);
                const cuando = sesion.fecha
                    ? `${fechaExperienciaLegible(sesion.fecha)}${sesion.hora ? ' · ' + escaparExperiencias(sesion.hora) : ''}`
                    : 'Sin fecha definida';
                const pasada = instante && instante < ahora;
                const filas = [...sesion.solicitudes.values()].map(({ solicitud, personas }) => {
                    const estado = solicitud.estado_agenda || 'Pendiente';
                    const claseEstado = estado === 'Confirmada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                    const fechaHora = new Date(`${solicitud.fecha_agenda}T${String(solicitud.hora_agenda || '').slice(0, 8)}`);
                    const puedeCancelar = Number.isFinite(fechaHora.getTime()) && fechaHora > ahora;
                    const accion = puedeCancelar
                        ? `<button type="button" data-cancelar-solicitud-admin="${Number(solicitud.id_agenda)}" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">Cancelar solicitud</button>`
                        : '';
                    return `<li class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 px-4 py-3">
                        <div><p class="text-sm font-bold text-slate-800">${escaparExperiencias(solicitud.nombre_cliente || solicitud.nombre_contacto)}</p>
                        <p class="text-xs text-slate-500">${escaparExperiencias(solicitud.correo_cliente || solicitud.correo_contacto)} · ${textoPersonasExperiencia(personas)}</p></div>
                        <div class="flex items-center gap-3">${chip(escaparExperiencias(estado), claseEstado)}${accion}</div></li>`;
                }).join('');
                return `<div class="px-5 py-4 ${pasada ? 'opacity-75' : ''}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div><p class="font-black text-slate-800">${escaparExperiencias(sesion.opcion)}</p>
                        <p class="text-xs text-slate-500">${cuando}${pasada ? ' · ya pasó' : ''}</p></div>
                        <span class="rounded-full bg-accent/40 px-3 py-1 text-xs font-black text-primary">${textoPersonasExperiencia(sesion.personas)}</span>
                    </div>
                    <ul class="mt-3 space-y-2">${filas}</ul></div>`;
            }).join('') || '<p class="px-5 py-4 text-xs text-slate-400">Todas las solicitudes de esta experiencia fueron canceladas.</p>';
            const frase = grupo.personas === 1 ? 'persona pidió esta experiencia' : 'personas pidieron esta experiencia';
            const chips = [
                chip(`${grupo.solicitudes} ${grupo.solicitudes === 1 ? 'solicitud' : 'solicitudes'}`, 'bg-slate-100 text-slate-600'),
                grupo.pendientes ? chip(`${grupo.pendientes} pendiente${grupo.pendientes === 1 ? '' : 's'}`, 'bg-amber-100 text-amber-700') : '',
                grupo.confirmadas ? chip(`${grupo.confirmadas} confirmada${grupo.confirmadas === 1 ? '' : 's'}`, 'bg-emerald-100 text-emerald-700') : '',
                grupo.canceladas ? chip(`${grupo.canceladas} cancelada${grupo.canceladas === 1 ? '' : 's'}`, 'bg-rose-100 text-rose-700') : '',
            ].join('');
            return `<details open class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div><p class="text-lg font-black text-primary">${escaparExperiencias(grupo.nombre)}</p>
                    <p class="text-sm font-bold text-slate-600"><span class="text-2xl font-black text-primary">${grupo.personas}</span> ${frase}</p></div>
                    <div class="flex flex-wrap items-center gap-2">${chips}</div>
                </summary>
                <div class="divide-y divide-slate-100 border-t border-slate-100">${sesiones}</div>
            </details>`;
        }).join('');
}

function aplicarVistaHistorialExperiencias() {
    const agrupada = vistaHistorialExperiencias === 'grupos';
    document.getElementById('gruposHistorialExperiencias').classList.toggle('hidden', !agrupada);
    document.getElementById('listaHistorialExperiencias').classList.toggle('hidden', agrupada);
    document.querySelectorAll('[data-vista-historial]').forEach(boton => {
        const activo = boton.dataset.vistaHistorial === vistaHistorialExperiencias;
        boton.className = `rounded-lg px-4 py-2 ${activo ? 'bg-primary text-white shadow' : 'text-slate-500 hover:text-primary'}`;
        boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
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
    const numerosOpciones = experiencia?.numeros_opciones || [1, 2, 3].slice(0, experiencia?.opciones?.length || 0);
    [1, 2, 3].forEach(numero => {
        const indice = numerosOpciones.indexOf(numero);
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
document.getElementById('opcionHorarioExperiencia').addEventListener('change', () => {
    const opcion = document.getElementById('opcionHorarioExperiencia').value;
    const primeraFecha = Object.keys(horariosExperienciaBorrador[opcion] || {}).sort()[0];
    if (primeraFecha) {
        document.getElementById('fechaInicioHorarioExperiencia').value = primeraFecha;
        document.getElementById('fechaFinHorarioExperiencia').value = primeraFecha;
    }
});
document.getElementById('listaHorariosExperiencia').addEventListener('click', evento => {
    const quitarFecha = evento.target.closest('[data-eliminar-fecha]');
    if (quitarFecha) {
        delete horariosExperienciaBorrador[quitarFecha.dataset.eliminarOpcion][quitarFecha.dataset.eliminarFecha];
        renderizarHorariosExperiencia();
    }
});
['tablaHistorialExperiencias', 'gruposHistorialExperiencias'].forEach(idContenedor => document.getElementById(idContenedor).addEventListener('click', evento => {
    const boton = evento.target.closest('[data-cancelar-solicitud-admin]');
    if (boton) cancelarSolicitudExperienciaAdmin(boton.dataset.cancelarSolicitudAdmin, boton);
}));
document.querySelectorAll('[data-vista-historial]').forEach(boton => boton.addEventListener('click', () => {
    vistaHistorialExperiencias = boton.dataset.vistaHistorial;
    aplicarVistaHistorialExperiencias();
}));
aplicarVistaHistorialExperiencias();
[1, 2, 3].forEach(numero => {
    document.getElementById(`opcionExperiencia${numero}`).addEventListener('input', () => {
        renderizarSelectorOpcionHorario();
        renderizarHorariosExperiencia();
    });
});
cargarExperiencias();
</script>