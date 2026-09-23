async function actualizarInterfaz(habitacionesLocales = null) {
    try {
        let DATA_HOTEL = habitacionesLocales;

        if (!DATA_HOTEL) {
            // Se calcula la ruta base independientemente del nivel de carpetas
            const urlControlador = window.location.pathname.substring(0, window.location.pathname.indexOf('/interfaz/')) + '/controladores/obtener_habitaciones.php';
            
            const res = await fetch(urlControlador, { cache: 'no-store' });
            if (!res.ok) {
                console.error("Error al obtener habitaciones. Status HTTP:", res.status);
                throw new Error('Respuesta HTTP no válida');
            }
            DATA_HOTEL = await res.json();
        }

        if (!Array.isArray(DATA_HOTEL)) {
            console.warn("Los datos recibidos no son un arreglo válido:", DATA_HOTEL);
            return;
        }

        habitacionesEmpleadoState = DATA_HOTEL;

        const ocupadas = DATA_HOTEL.filter(h => h.estado === 'Ocupada').length;
        const sucias = DATA_HOTEL.filter(h => h.estado === 'Sucia').length;
        const huespedes = ocupadas; 

        if (document.getElementById('dash-ocupadas')) document.getElementById('dash-ocupadas').innerText = ocupadas;
        if (document.getElementById('dash-limpieza')) document.getElementById('dash-limpieza').innerText = sucias;
        if (document.getElementById('dash-huespedes')) document.getElementById('dash-huespedes').innerText = huespedes;

        // 1. Grid Habitaciones
        const gridHab = document.getElementById('gridHabitaciones');
        if (gridHab) {
            gridHab.innerHTML = '';
            DATA_HOTEL.forEach(h => {
                const estadoUIHabitacion = obtenerUIEstadoHabitacion(h.estado);
                const color = estadoUIHabitacion.etiqueta;
                const bloqueReserva = crearBloqueReservaHabitacion(h);
                const colorTarjeta = estadoUIHabitacion.tarjeta;
                const prioridadHab = h.observacion && h.observacion.includes('Prioridad: Urgente') ? 'Urgente' : h.observacion && h.observacion.includes('Prioridad: Importante') ? 'Importante' : 'No urgente';
                const prioridadIcono = prioridadHab === 'Urgente' ? '▲' : prioridadHab === 'Importante' ? '◆' : '●';
                const prioridadColor = prioridadHab === 'Urgente' ? 'text-red-500 bg-red-50' : prioridadHab === 'Importante' ? 'text-orange-500 bg-orange-50' : 'text-green-600 bg-green-50';
                const prioridadHabitacion = h.estado === 'Mantenimiento' ? `<span class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full ${prioridadColor}"><span class="text-[7px] font-black leading-none">${prioridadIcono}</span><span class="text-[7px] font-black uppercase tracking-wide leading-none">${prioridadHab}</span></span>` : '';

                const rolActivo = typeof ROL_USUARIO !== 'undefined' ? ROL_USUARIO : 3;

                const botonGestion = rolActivo === 3 
                    ? `<button onclick="abrirGestionHabitacion(${h.id}, ${h.numero}, '${h.estado}')" class="w-full text-center bg-[#eef4f4] hover:bg-primary hover:text-white text-primary font-bold text-[10px] py-2 rounded-lg uppercase tracking-widest transition-colors">
                        Cambiar Estado
                       </button>`
                    : `<div class="w-full text-center py-2 text-[9px] font-bold text-slate-300 uppercase tracking-widest bg-slate-50 rounded-lg">
                        Solo Recepción
                       </div>`;

                gridHab.insertAdjacentHTML('beforeend', `
                    <div class="metric-card habitacion-card ${colorTarjeta} p-6 rounded-xl flex flex-col justify-between h-auto shadow-md border relative group bg-white">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[28px] font-black text-primary">${escaparHTML(h.numero)}</span>
                            <div class="flex flex-col items-end">
                                <span class="px-2.5 py-0.5 rounded-full text-[7.5px] font-black uppercase tracking-wide ${color}">${escaparHTML(h.estado)}</span>
                                ${prioridadHabitacion}
                            </div>
                        </div>
                        <div>
                            <p class="text-[8.5px] font-bold text-slate-400 uppercase tracking-wide mb-1">${escaparHTML(h.tipo)}</p>
                            ${bloqueReserva}
                            ${botonGestion}
                        </div>
                    </div>
                `);
            });
        }

        // 2. Grid Housekeeping
        const gridHousekeeping = document.getElementById('gridHousekeeping');
        if (gridHousekeeping) {
            gridHousekeeping.innerHTML = '';
            DATA_HOTEL.forEach(h => {
                const estadoUI = clasesEstadoHousekeeping(h.estado);
                const motivoMantenimiento = obtenerMotivoMantenimiento(h.observacion);
                const textoMotivo = motivoMantenimiento || 'Motivo no registrado.';
                const eventoMantenimiento = h.estado === 'Mantenimiento' ? `onclick="toggleMotivoMantenimiento(${h.id})"` : '';
                const cursorMantenimiento = h.estado === 'Mantenimiento' ? 'cursor-pointer' : '';
                const popoverMantenimiento = h.estado === 'Mantenimiento' ? `
                    <div class="maintenance-popover" role="status" aria-live="polite">
                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-red-400 mb-2">Motivo de mantenimiento</p>
                        <p class="text-xs font-bold text-slate-600 leading-relaxed">${escaparHTML(textoMotivo)}</p>
                    </div>
                ` : '';

                gridHousekeeping.insertAdjacentHTML('beforeend', `
                    <article id="housekeeping-${h.id}" ${eventoMantenimiento} class="metric-card habitacion-card housekeeping-card ${estadoUI.tarjeta} ${cursorMantenimiento} p-6 rounded-xl shadow-md border relative overflow-visible bg-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.18em] mb-2">Habitación</p>
                                <h4 class="text-3xl font-black text-heading leading-none">${escaparHTML(h.numero)}</h4>
                            </div>
                            <div class="housekeeping-icon">
                                <span class="material-symbols-outlined text-[22px]">${estadoUI.icono}</span>
                            </div>
                        </div>
                        <div class="mt-8 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">${escaparHTML(h.tipo)}</p>
                                <span class="inline-flex px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${estadoUI.etiqueta}">${estadoUI.titulo}</span>
                            </div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Turno</p>
                        </div>
                        ${popoverMantenimiento}
                    </article>
                `);
            });
        }

        // 3. Grid Huéspedes
        const gridHue = document.getElementById('gridHuespedes');
        if (gridHue) {
            gridHue.innerHTML = '';
            DATA_HOTEL.filter(h => h.estado === 'Ocupada' || h.huesped_nombre).forEach(h => {
                const detalleHuesped = crearDetalleHuespedEmpleado(h);
                gridHue.insertAdjacentHTML('beforeend', `
                    <div class="bg-white p-8 rounded-xl border border-primary/10 shadow-[0_18px_42px_-28px_rgba(15,23,42,0.45)] flex items-center gap-7 group relative overflow-hidden">
                        <div class="w-16 h-16 bg-primary/5 rounded-xl flex items-center justify-center font-black text-primary text-xl border border-primary/10">${escaparHTML(h.numero)}</div>
                        <div class="flex-1 space-y-3">
                            <h4 class="text-xl font-semibold text-heading leading-tight">${escaparHTML(h.huesped_nombre || 'Huésped registrado')}</h4>
                            ${detalleHuesped}
                            <div class="flex items-center gap-2 pt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                <span class="text-[9px] font-bold text-green-600 uppercase tracking-widest">Activo</span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-slate-100 text-7xl absolute -right-4 -bottom-4 rotate-12 group-hover:text-primary/5 transition-colors">person</span>
                    </div>`);
            });
        }

    } catch (error) {
        console.error("Error crítico al cargar la interfaz:", error);
    }
}

function navegar(sec, btn) {
    document.querySelectorAll('.seccion-contenido').forEach(seccion => {
        seccion.classList.add('hidden');
    });

    const seccion = document.getElementById('sec-' + sec);
    if (seccion) {
        seccion.classList.remove('hidden');
    }

    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active-nav');
    });

    if (btn) {
        btn.classList.add('active-nav');
    }
}

function abrirModal() {
    const modal = document.getElementById('modalTarea');
    if (modal) {
        modal.classList.remove('modal-oculto');
        modal.classList.add('modal-visible');
    }
}

function cerrarModal() {
    const modal = document.getElementById('modalTarea');
    if (modal) {
        modal.classList.remove('modal-visible');
        modal.classList.add('modal-oculto');
    }
}

let habitacionesEmpleadoState = [];
let intervaloTareasEmpleado = null;
const tareasEmpleadoIds = new Set();

function escaparHTML(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[caracter]));
}

function redirigirDesdeDash(sec) {
    const boton = document.querySelector(`.nav-item[onclick*="${sec}"]`);
    if (boton) navegar(sec, boton);
}

function obtenerMotivoMantenimiento(observacion) {
    return String(observacion || '').replace(/^Prioridad:\s*[^\n\r]*(\r?\n)?/i, '').trim();
}

function clasesEstadoHousekeeping(estado) {
    if (estado === 'Mantenimiento') {
        return { tarjeta: 'housekeeping-card--mantenimiento', etiqueta: 'bg-red-50 text-red-600 border border-red-100', icono: 'build', titulo: 'Mantenimiento' };
    }
    if (estado === 'Sucia') {
        return { tarjeta: 'housekeeping-card--sucia', etiqueta: 'bg-amber-50 text-amber-700 border border-amber-100', icono: 'cleaning_services', titulo: 'Sucia' };
    }
    if (estado === 'Ocupada') {
        return { tarjeta: 'housekeeping-card--ocupada', etiqueta: 'bg-red-50 text-red-600 border border-red-100', icono: 'bed', titulo: 'Ocupada' };
    }
    return { tarjeta: 'housekeeping-card--disponible', etiqueta: 'bg-emerald-50 text-emerald-700 border border-emerald-100', icono: 'check_circle', titulo: estado === 'Limpio' ? 'Limpio' : 'Disponible' };
}

function toggleMotivoMantenimiento(idHabitacion) {
    const tarjeta = document.getElementById(`housekeeping-${idHabitacion}`);
    if (!tarjeta) return;
    document.querySelectorAll('.housekeeping-card.motivo-visible').forEach(otra => {
        if (otra !== tarjeta) otra.classList.remove('motivo-visible');
    });
    tarjeta.classList.toggle('motivo-visible');
}

function obtenerUIEstadoHabitacion(estado) {
    const estilos = {
        Mantenimiento: ['bg-red-50 text-red-600 border border-red-100', 'habitacion-card--mantenimiento'],
        Limpio: ['bg-cyan-50 text-cyan-700 border border-cyan-100', 'habitacion-card--limpio'],
        Ocupada: ['bg-orange-50 text-orange-600 border border-orange-100', 'habitacion-card--ocupada'],
        Sucia: ['bg-stone-100 text-stone-700 border border-stone-200', 'habitacion-card--sucia']
    };
    const [etiqueta, tarjeta] = estilos[estado] || ['bg-green-50 text-green-700 border border-green-100', 'habitacion-card--disponible'];
    return { etiqueta, tarjeta };
}

function formatearFechaHotel(fecha) {
    const valor = String(fecha || '').trim();
    if (!valor || valor === '-') return '';
    const fechaFormateada = new Date(valor.replace(' ', 'T'));
    if (Number.isNaN(fechaFormateada.getTime())) return '';
    return new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short', year: 'numeric' }).format(fechaFormateada).replace('.', '');
}

function obtenerBadgeReservaEmpleado(estado) {
    const clases = {
        Pendiente: 'bg-amber-50 text-amber-700 border border-amber-100',
        Confirmada: 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        'En Casa': 'bg-blue-50 text-blue-700 border border-blue-100',
        Ocupada: 'bg-blue-50 text-blue-700 border border-blue-100',
        Cancelada: 'bg-red-50 text-red-700 border border-red-100'
    };
    return clases[String(estado || 'Ocupada').trim()] || 'bg-slate-100 text-slate-600 border border-slate-200';
}

function crearBloqueReservaHabitacion(habitacion) {
    if (!habitacion.huesped_nombre) return '<p class="text-[13px] font-black text-heading mb-5">Disponible para reserva</p>';
    const entrada = formatearFechaHotel(habitacion.fec_ent_res);
    const salida = formatearFechaHotel(habitacion.fec_sal_res);
    const fechas = entrada && salida ? `${entrada} - ${salida}` : '';
    const estado = habitacion.est_res || habitacion.estado || 'Ocupada';
    return `<div class="space-y-2 mb-5"><div class="flex items-center gap-2"><span class="material-symbols-outlined text-[16px] text-primary">person</span><span class="text-[14px] font-black text-heading leading-tight">${escaparHTML(habitacion.huesped_nombre)}</span></div>${fechas ? `<div class="flex items-center gap-2 text-[#64748b]"><span class="material-symbols-outlined text-[15px]">calendar_month</span><span class="text-[11px] font-bold">${escaparHTML(fechas)}</span></div>` : ''}<span class="inline-flex w-fit px-2.5 py-1 rounded-full text-[8px] font-black uppercase tracking-widest ${obtenerBadgeReservaEmpleado(estado)}">${escaparHTML(estado)}</span></div>`;
}

function crearDetalleHuespedEmpleado(habitacion) {
    const entrada = formatearFechaHotel(habitacion.fec_ent_res);
    const salida = formatearFechaHotel(habitacion.fec_sal_res);
    const fechas = entrada && salida ? `<span class="inline-flex items-center gap-1 text-slate-500"><span class="material-symbols-outlined text-[14px]">calendar_month</span>${escaparHTML(entrada)} - ${escaparHTML(salida)}</span>` : '';
    const estado = habitacion.est_res || habitacion.estado || 'Ocupada';
    return `<div class="space-y-2"><div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-bold text-slate-500"><span>${escaparHTML(habitacion.tipo)}</span>${fechas}</div><span class="inline-flex w-fit px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest ${obtenerBadgeReservaEmpleado(estado)}">${escaparHTML(estado)}</span></div>`;
}

async function cargarTareasEmpleado() {
    const contenedor = document.getElementById('contenedorTareas');
    if (!contenedor) return;
    try {
        const respuesta = await fetch('../../controladores/obtener_tareas.php', { cache: 'no-store', headers: { Accept: 'application/json' } });
        const tareas = await respuesta.json();
        if (!respuesta.ok || !Array.isArray(tareas)) throw new Error('No se pudo cargar la cola de tareas');
        contenedor.innerHTML = '';
        tareasEmpleadoIds.clear();
        tareas.forEach(tarea => insertarTareaEmpleadoDesdeServidor(tarea));
        actualizarContadorTareas();
    } catch (error) {
        console.error('Error al cargar las tareas:', error);
    }
}

function insertarTareaEmpleadoDesdeServidor(tarea) {
    if (!tarea || !tarea.id || tareasEmpleadoIds.has(String(tarea.id))) return;
    tareasEmpleadoIds.add(String(tarea.id));
    anadirTareaHTML(tarea.id, tarea.titulo, tarea.categoria || tarea.cat, tarea.descripcion || tarea.desc, tarea.creador_formateado);
}

function anadirTareaHTML(id, titulo, categoria, descripcion, creadorFormateado = '') {
    const contenedor = document.getElementById('contenedorTareas');
    if (!contenedor) return;
    const categoriaNormalizada = String(categoria || 'GENERAL').toUpperCase();
    const borde = categoriaNormalizada === 'URGENTE' ? 'border-red-400 bg-red-50/70' : categoriaNormalizada === 'LIMPIEZA' ? 'border-amber-400 bg-amber-50/70' : 'border-primary bg-[#fbfdfd]';
    const texto = categoriaNormalizada === 'URGENTE' ? 'text-red-500 bg-red-100' : categoriaNormalizada === 'LIMPIEZA' ? 'text-amber-700 bg-amber-100' : 'text-primary bg-accent';
    const icono = categoriaNormalizada === 'URGENTE' ? '▲' : categoriaNormalizada === 'LIMPIEZA' ? '◆' : '●';
    contenedor.insertAdjacentHTML('afterbegin', `<div id="tarea-${escaparHTML(id)}" draggable="true" class="task-item p-6 rounded-lg border border-primary/10 border-l-4 ${borde} shadow-md group relative transition-all duration-300 ease-out"><p class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[7px] font-black uppercase tracking-wide mb-3 ${texto}"><span>${icono}</span>${escaparHTML(categoriaNormalizada)}</p><h4 class="text-xs font-bold text-heading leading-tight">${escaparHTML(titulo)}</h4><p class="text-[10px] text-slate-400 mt-2">${escaparHTML(descripcion)}</p><p class="text-[9px] font-black uppercase tracking-tight text-primary bg-primary/5 px-2 py-1 rounded mt-3">${escaparHTML(creadorFormateado || 'Sistema - Hotel')}</p><button onclick="marcarTareaComoHecha(${Number(id)}, this)" class="mt-4 text-[9px] font-bold text-primary underline uppercase opacity-0 group-hover:opacity-100 transition-all">Hecho</button></div>`);
    initDragAndDrop();
}

async function marcarTareaComoHecha(id, boton) {
    const tarea = boton.closest('.task-item');
    const textoOriginal = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML = 'Cargando...';
    const datos = new FormData();
    datos.append('id_tarea', id);
    try {
        const respuesta = await fetch('../../controladores/completar_tarea.php', { method: 'POST', body: datos, headers: { Accept: 'application/json' } });
        const resultado = await respuesta.json();
        if (!respuesta.ok || resultado.status !== 'exito') throw new Error(resultado.mensaje || 'No se pudo completar la tarea');
        if (tarea) tarea.remove();
        actualizarContadorTareas();
        localStorage.setItem('tareas_actualizadas', Date.now().toString());
    } catch (error) {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
        alert(error.message || 'No se pudo completar la tarea.');
    }
}

function initDragAndDrop() {
    const contenedor = document.getElementById('contenedorTareas');
    if (!contenedor || contenedor.dataset.dragInicializado === '1') return;
    contenedor.dataset.dragInicializado = '1';
    contenedor.addEventListener('dragover', evento => {
        evento.preventDefault();
        const arrastrado = contenedor.querySelector('.dragging');
        if (!arrastrado) return;
        const siguiente = [...contenedor.querySelectorAll('.task-item:not(.dragging)')].find(elemento => evento.clientY <= elemento.getBoundingClientRect().top + elemento.offsetHeight / 2);
        siguiente ? contenedor.insertBefore(arrastrado, siguiente) : contenedor.appendChild(arrastrado);
    });
    contenedor.addEventListener('dragstart', evento => evento.target.classList.add('dragging'));
    contenedor.addEventListener('dragend', evento => evento.target.classList.remove('dragging'));
}

function actualizarContadorTareas() {
    const contenedor = document.getElementById('contenedorTareas');
    const badge = document.getElementById('badgeNotificaciones');
    if (!contenedor || !badge) return;
    const total = contenedor.querySelectorAll('.task-item').length;
    badge.textContent = total;
    badge.classList.toggle('hidden', total === 0);
}

function abrirColaTareas() {
    const panel = document.getElementById('panelTareasDerecho');
    const cabecera = document.getElementById('cabeceraColaTareas');
    const titulo = document.getElementById('tituloColaTareas');
    const flecha = document.getElementById('flechaCerrarColaTareas');
    const contenedor = document.getElementById('contenedorTareas');
    if (!panel || !cabecera || !titulo || !flecha || !contenedor) return;
    panel.classList.remove('w-16', 'px-3');
    panel.classList.add('w-80', 'p-6');
    cabecera.classList.remove('flex-col', 'gap-0');
    cabecera.classList.add('justify-between');
    titulo.classList.remove('opacity-0', 'w-0', 'overflow-hidden');
    flecha.classList.remove('opacity-0', 'w-0', 'overflow-hidden', 'pointer-events-none');
    contenedor.classList.remove('opacity-0', 'pointer-events-none');
    contenedor.classList.add('opacity-100');
}

function cerrarColaTareas(event) {
    if (event) event.stopPropagation();
    const panel = document.getElementById('panelTareasDerecho');
    const cabecera = document.getElementById('cabeceraColaTareas');
    const titulo = document.getElementById('tituloColaTareas');
    const flecha = document.getElementById('flechaCerrarColaTareas');
    const contenedor = document.getElementById('contenedorTareas');
    if (!panel || !cabecera || !titulo || !flecha || !contenedor) return;
    panel.classList.remove('w-80', 'p-6');
    panel.classList.add('w-16', 'px-3');
    cabecera.classList.add('flex-col', 'gap-0');
    cabecera.classList.remove('justify-between');
    titulo.classList.add('opacity-0', 'w-0', 'overflow-hidden');
    flecha.classList.add('opacity-0', 'w-0', 'overflow-hidden', 'pointer-events-none');
    contenedor.classList.add('opacity-0', 'pointer-events-none');
    contenedor.classList.remove('opacity-100');
}

function abrirGestionHabitacion(id, numero, estadoActual) {
    document.getElementById('idHabitacionModal').value = id;
    document.getElementById('tituloModalHab').innerText = `Habitación ${numero}`;
    document.getElementById('estadoHabitacionModal').value = estadoActual;
    document.getElementById('prioridadMantenimientoModal').value = 'No urgente';
    document.getElementById('descripcionMantenimientoModal').value = '';
    actualizarPrioridadMantenimiento();
    toggleDescripcionMantenimiento();
    const modal = document.getElementById('modalHabitacion');
    const contenido = document.getElementById('contenidoModalHabitacion');
    if (!modal || !contenido) return;
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100');
    contenido.classList.remove('scale-95', 'translate-y-3');
    contenido.classList.add('scale-100', 'translate-y-0');
}

function cerrarModalHabitacion() {
    const modal = document.getElementById('modalHabitacion');
    const contenido = document.getElementById('contenidoModalHabitacion');
    if (!modal || !contenido) return;
    modal.classList.add('opacity-0', 'pointer-events-none');
    modal.classList.remove('opacity-100');
    contenido.classList.add('scale-95', 'translate-y-3');
    contenido.classList.remove('scale-100', 'translate-y-0');
}

function actualizarPrioridadMantenimiento() {
    const prioridad = document.getElementById('prioridadMantenimientoModal');
    if (!prioridad) return;
    prioridad.classList.remove('border-red-500', 'text-red-600', 'border-orange-500', 'text-orange-600', 'border-green-500', 'text-green-600');
    const clases = prioridad.value === 'Urgente' ? ['border-red-500', 'text-red-600'] : prioridad.value === 'Importante' ? ['border-orange-500', 'text-orange-600'] : ['border-green-500', 'text-green-600'];
    prioridad.classList.add(...clases);
}

function toggleDescripcionMantenimiento() {
    const estado = document.getElementById('estadoHabitacionModal');
    const prioridad = document.getElementById('areaPrioridadMantenimiento');
    const areaDescripcion = document.getElementById('areaDescripcionMantenimiento');
    const descripcion = document.getElementById('descripcionMantenimientoModal');
    const boton = document.getElementById('guardarCambiosHabitacion');
    if (!estado || !prioridad || !areaDescripcion || !descripcion || !boton) return;
    const requiereDescripcion = estado.value === 'Mantenimiento';
    prioridad.classList.toggle('hidden', !requiereDescripcion);
    areaDescripcion.classList.toggle('hidden', !requiereDescripcion);
    descripcion.toggleAttribute('required', requiereDescripcion);
    if (!requiereDescripcion) descripcion.value = '';
    boton.disabled = requiereDescripcion && descripcion.value.trim() === '';
    boton.classList.toggle('opacity-50', boton.disabled);
    boton.classList.toggle('cursor-not-allowed', boton.disabled);
}

function enlazarEventosEmpleado() {
    document.querySelector('[data-action="abrir-cola"]')?.addEventListener('click', abrirColaTareas);
    document.querySelector('[data-action="cerrar-cola"]')?.addEventListener('click', cerrarColaTareas);
    document.querySelector('[data-action="cerrar-modal-hab"]')?.addEventListener('click', cerrarModalHabitacion);
    document.getElementById('estadoHabitacionModal')?.addEventListener('change', toggleDescripcionMantenimiento);
    document.getElementById('prioridadMantenimientoModal')?.addEventListener('change', actualizarPrioridadMantenimiento);
    document.getElementById('descripcionMantenimientoModal')?.addEventListener('input', toggleDescripcionMantenimiento);

    const formTarea = document.getElementById('formTarea');
    formTarea?.addEventListener('submit', async evento => {
        evento.preventDefault();
        const boton = formTarea.querySelector('button[type="submit"]');
        const textoOriginal = boton?.innerHTML || '';
        const datos = new FormData();
        datos.append('titulo', document.getElementById('tituloTarea').value.trim());
        datos.append('categoria', document.getElementById('categoriaTarea').value);
        datos.append('descripcion', document.getElementById('descTarea').value.trim());
        datos.append('id_creador_panel', typeof ID_USUARIO_ACTIVO !== 'undefined' ? ID_USUARIO_ACTIVO : '');
        datos.append('rol_creador_panel', typeof ROL_USUARIO !== 'undefined' ? ROL_USUARIO : '');
        datos.append('firma_creador_panel', typeof FIRMA_USUARIO_ACTIVO !== 'undefined' ? FIRMA_USUARIO_ACTIVO : '');
        datos.append('panel_origen', 'empleado');
        try {
            if (boton) { boton.disabled = true; boton.innerHTML = 'Guardando...'; }
            const respuesta = await fetch('../../controladores/guardar_tarea.php', { method: 'POST', body: datos, headers: { Accept: 'application/json' } });
            const resultado = await respuesta.json();
            if (!respuesta.ok || resultado.status !== 'exito') throw new Error(resultado.mensaje || 'No se pudo guardar la tarea');
            anadirTareaHTML(resultado.id_tarea, datos.get('titulo'), datos.get('categoria'), datos.get('descripcion'), resultado.tarea?.creador_formateado);
            formTarea.reset();
            cerrarModal();
            actualizarContadorTareas();
            localStorage.setItem('tareas_actualizadas', Date.now().toString());
        } catch (error) {
            alert(error.message || 'No se pudo guardar la tarea.');
        } finally {
            if (boton) { boton.disabled = false; boton.innerHTML = textoOriginal; }
        }
    });

    const formHabitacion = document.getElementById('formGestionHabitacion');
    formHabitacion?.addEventListener('submit', async evento => {
        evento.preventDefault();
        const datos = new FormData();
        datos.append('id_hab', document.getElementById('idHabitacionModal').value);
        datos.append('estado', document.getElementById('estadoHabitacionModal').value);
        datos.append('prioridad_mantenimiento', document.getElementById('prioridadMantenimientoModal').value);
        datos.append('descripcion_mantenimiento', document.getElementById('descripcionMantenimientoModal').value.trim());
        const respuesta = await fetch('../../controladores/actualizar_estado_habitacion.php', { method: 'POST', body: datos, headers: { Accept: 'application/json' } });
        const resultado = await respuesta.json();
        if (!respuesta.ok || resultado.status !== 'exito') { alert(resultado.mensaje || 'No se pudo actualizar la habitación.'); return; }
        cerrarModalHabitacion();
        actualizarInterfaz();
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    const fecha = document.getElementById('fechaHoy');
    if (fecha) fecha.innerText = new Intl.DateTimeFormat('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
    enlazarEventosEmpleado();
    await actualizarInterfaz();
    await cargarTareasEmpleado();
    intervaloTareasEmpleado = setInterval(cargarTareasEmpleado, 3000);
});

window.addEventListener('storage', evento => {
    if (evento.key === 'tareas_actualizadas') cargarTareasEmpleado();
    if (evento.key === 'reservas_actualizadas' || evento.key === 'habitaciones_actualizadas') actualizarInterfaz();
});