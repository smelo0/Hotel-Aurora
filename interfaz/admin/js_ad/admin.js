// =======================================================
// PANEL ADMIN - FUNCIONES COMUNES + CRUD TRANSACCIONAL DE TAREAS
// =======================================================

// Reparacion: Se reemplazo window.onload por DOMContentLoaded para no pisar el arranque inline de index_ad.php.
document.addEventListener('DOMContentLoaded', () => {
    inicializarFechaAdmin();
    inicializarTogglesAdmin();
    actualizarContadorTareasAdmin();
    renderTareasAdmin();
    iniciarSincronizacionTareasAdmin();
    renderHousekeeping();
    iniciarSincronizacionHousekeeping();
    iniciarSincronizacionReservas();
});

// Reparacion: Se escucha el mismo canal en tiempo real usado por Operaciones/Housekeeping.
window.addEventListener('storage', event => {
    if (event.key === 'habitaciones_actualizadas' && typeof renderHousekeeping === 'function') {
        renderHousekeeping();
    }

    if (event.key === 'tareas_actualizadas') {
        renderTareasAdmin();
        reiniciarSincronizacionTareasAdmin();
    }

    if (event.key === 'reservas_actualizadas') {
        refrescarTablaReservas();
    }
});

const canalReservasAdmin = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('software_hotel_reservas') : null;
if (canalReservasAdmin) {
    canalReservasAdmin.addEventListener('message', event => {
        if (!event.data || event.data.tipo !== 'reserva_actualizada') return;
        refrescarTablaReservas();
    });
}

function inicializarFechaAdmin() {
    const fecha = document.getElementById('fechaHoy');
    if (fecha) {
        fecha.innerText = new Intl.DateTimeFormat('es-ES', {
            weekday: 'long',
            day: 'numeric',
            month: 'long'
        }).format(new Date());
    }
}

function inicializarTogglesAdmin() {
    document.querySelectorAll('.toggle-checkbox').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const dot = this.parentElement.querySelector('.dot');
            if (!dot || !this.nextElementSibling) return;

            if (this.checked) {
                dot.style.transform = 'translateX(100%)';
                this.nextElementSibling.style.backgroundColor = '#2C5E5E';
            } else {
                dot.style.transform = 'translateX(0)';
                this.nextElementSibling.style.backgroundColor = '#e2e8f0';
            }
        });
        toggle.dispatchEvent(new Event('change'));
    });
}

// =======================================================
// NAVEGACION Y UTILIDADES
// =======================================================
function navegar(sec, btn) {
    document.querySelectorAll('.seccion-contenido').forEach(s => s.classList.add('hidden'));
    const seccion = document.getElementById('sec-' + sec);
    if (seccion) seccion.classList.remove('hidden');
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active-nav', 'hover:bg-slate-50'));
    if (btn) btn.classList.add('active-nav');
}

function filtrarReservas() {
    const buscador = document.getElementById('buscadorReservas');
    const input = buscador ? buscador.value.toLowerCase() : '';
    const filas = document.querySelectorAll('#tablaReservas tr');
    let resultados = false;

    filas.forEach(fila => {
        const nombreHuesped = fila.children[0] ? fila.children[0].innerText.toLowerCase() : '';
        if (nombreHuesped.includes(input)) {
            fila.style.display = '';
            resultados = true;
        } else {
            fila.style.display = 'none';
        }
    });

    const noResultados = document.getElementById('noResultados');
    if (noResultados) noResultados.style.display = resultados ? 'none' : 'block';
}

function generarFactura(btn) {
    btn.disabled = true;
    btn.classList.remove('bg-primary', 'hover:bg-heading');
    btn.classList.add('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
    btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span> Procesando...';

    setTimeout(() => {
        btn.classList.remove('bg-slate-300', 'text-slate-500');
        btn.classList.add('bg-green-600', 'text-white');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span> Factura Generada';
        setTimeout(() => alert('Factura timbrada y enviada al correo del huésped exitosamente.'), 300);
    }, 1800);
}

// =======================================================
// MODALES Y HOUSEKEEPING ADMIN
// =======================================================
let habitacionesHousekeeping = new Map();
let intervaloHousekeeping = null;

function abrirModalReserva() {
    const modal = document.getElementById('modalReserva');
    if (!modal) return;
    modal.classList.remove('hidden');
    requestAnimationFrame(() => modal.classList.add('modal-reserva-visible'));
}

function cerrarModalReserva() {
    const modal = document.getElementById('modalReserva');
    if (!modal) return;
    modal.classList.remove('modal-reserva-visible');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function toggleNuevoHuesped() {
    const selector = document.getElementById('selectorHuesped');
    const camposNuevos = document.getElementById('camposNuevoHuesped');
    if (!selector || !camposNuevos) return;

    camposNuevos.classList.toggle('hidden', selector.value !== 'nuevo');
}

function mostrarVista(idSeccion) {
    const seccion = document.getElementById(idSeccion);
    if (seccion) {
        document.querySelectorAll('.seccion-contenido').forEach(s => s.classList.add('hidden'));
        seccion.classList.remove('hidden');
    }
}

function escaparHTMLHousekeeping(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[caracter]));
}

function obtenerMotivoMantenimiento(observacion) {
    return String(observacion || '').replace(/^Prioridad:\s*[^\n\r]*(\r?\n)?/i, '').trim();
}

function obtenerPrioridadMantenimiento(observacion) {
    const coincidencia = String(observacion || '').match(/^Prioridad:\s*([^\n\r]+)/i);
    return coincidencia ? coincidencia[1].trim() : 'No urgente';
}

function obtenerUIPrioridad(prioridad) {
    if (prioridad === 'Urgente') {
        return { icono: '&#9650;', clases: 'text-red-500 bg-red-50 border-red-100', texto: 'Urgente' };
    }

    if (prioridad === 'Importante') {
        return { icono: '&#9670;', clases: 'text-orange-500 bg-orange-50 border-orange-100', texto: 'Importante' };
    }

    return { icono: '&#9679;', clases: 'text-green-600 bg-green-50 border-green-100', texto: 'No urgente' };
}

function obtenerClaseHousekeeping(estado) {
    if (estado === 'Mantenimiento') return 'room-dot--mantenimiento';
    if (estado === 'Sucia') return 'room-dot--sucia';
    if (estado === 'Ocupada') return 'room-dot--ocupada';
    if (estado === 'Limpio') return 'room-dot--limpio';
    return 'room-dot--disponible';
}

async function renderHousekeeping() {
    const grid = document.getElementById('gridHousekeeping');
    if (!grid) return;

    try {
        const respuesta = await fetch('../../controladores/obtener_habitaciones.php', { cache: 'no-store' });
        const habitaciones = await respuesta.json();

        habitacionesHousekeeping = new Map();
        grid.innerHTML = '';

        habitaciones.forEach(habitacion => {
            const esMantenimiento = habitacion.estado === 'Mantenimiento';
            const claseEstado = obtenerClaseHousekeeping(habitacion.estado);
            const motivo = obtenerMotivoMantenimiento(habitacion.observacion);
            const prioridad = obtenerPrioridadMantenimiento(habitacion.observacion);
            const accionClick = esMantenimiento ? `onclick="abrirModalMantenimiento(${Number(habitacion.id)})"` : '';
            const accesibilidad = esMantenimiento ? 'role="button" tabindex="0" aria-label="Ver motivo de mantenimiento"' : 'aria-label="Habitacion sin mantenimiento"';

            if (esMantenimiento) {
                habitacionesHousekeeping.set(String(habitacion.id), {
                    numero: habitacion.numero,
                    motivo: motivo || 'Motivo no registrado.',
                    prioridad
                });
            }

            grid.insertAdjacentHTML('beforeend', `
                <div id="housekeeping-room-${Number(habitacion.id)}" ${accionClick} ${accesibilidad} class="room-dot ${claseEstado} ${esMantenimiento ? 'room-dot--clickable' : ''} shadow-sm">
                    <span>${escaparHTMLHousekeeping(habitacion.numero)}</span>
                </div>
            `);
        });
    } catch (error) {
        console.error('Error al sincronizar Housekeeping:', error);
    }
}

function abrirModalMantenimiento(idHabitacion) {
    const datosHabitacion = habitacionesHousekeeping.get(String(idHabitacion));
    if (!datosHabitacion) return;

    const modal = document.getElementById('modalMantenimientoHousekeeping');
    const titulo = document.getElementById('tituloMantenimientoHousekeeping');
    const motivo = document.getElementById('motivoMantenimientoHousekeeping');
    const prioridad = document.getElementById('prioridadMantenimientoHousekeeping');
    if (!modal || !titulo || !motivo || !prioridad) return;

    const prioridadUI = obtenerUIPrioridad(datosHabitacion.prioridad);
    titulo.innerText = 'Habitacion ' + datosHabitacion.numero;
    motivo.innerText = datosHabitacion.motivo;
    prioridad.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest mb-5 ' + prioridadUI.clases;
    prioridad.innerHTML = `<span class="text-[9px] leading-none">${prioridadUI.icono}</span><span>${prioridadUI.texto}</span>`;
    modal.classList.add('modal-visible');
}

function iniciarSincronizacionHousekeeping() {
    if (intervaloHousekeeping) return;
    intervaloHousekeeping = setInterval(renderHousekeeping, 3000);
}

// Compatibilidad: helpers que antes estaban inline en index_ad.php
function abrirModalLogout(e) {
    if (e) e.preventDefault();
    const modal = document.getElementById('modalLogout');
    const caja = document.getElementById('cajaLogout');
    if (!modal || !caja) return;
    modal.classList.remove('hidden');
    setTimeout(() => {
        caja.classList.remove('scale-95', 'opacity-0');
        caja.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function cerrarModalLogout() {
    const modal = document.getElementById('modalLogout');
    const caja = document.getElementById('cajaLogout');
    if (!modal || !caja) return;
    caja.classList.remove('scale-100', 'opacity-100');
    caja.classList.add('scale-95', 'opacity-0');
    setTimeout(() => modal.classList.add('hidden'), 200);
}

function cerrarModalMantenimiento(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('modalMantenimientoHousekeeping');
    if (modal) modal.classList.remove('modal-visible');
}

// =======================================================
// COLA DE TAREAS ADMIN
// =======================================================
let tareasAdminState = [];
let intervaloColaAdmin = null;
let renderTareasAdminEnCurso = false;

function escaparHTMLAdmin(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[caracter]));
}

function obtenerNombreRolAdmin(rol) {
    const roles = { 1: 'Admin', 2: 'Gestor', 3: 'Recepcionista', 4: 'Conserje', 5: 'Limpieza' };
    return roles[Number(rol)] || 'Personal';
}

function limpiarNombreCreadorAdmin(nombre, rolNombre) {
    const nombreLimpio = String(nombre || 'Usuario').trim();
    const rolLimpio = String(rolNombre || '').trim();
    if (!rolLimpio) return nombreLimpio || 'Usuario';

    return nombreLimpio.replace(new RegExp('\\s+-?\\s*' + rolLimpio + '$', 'iu'), '').trim() || 'Usuario';
}

function formatearCreadorAdmin(tarea) {
    // Reparacion: El creador se normaliza siempre como [Nombre] - [Rol].
    if (tarea.creador_formateado) return tarea.creador_formateado;

    const rolNombre = tarea.rol_nombre || obtenerNombreRolAdmin(tarea.rol);
    const nombre = limpiarNombreCreadorAdmin(tarea.creador_nombre || tarea.creador || 'Usuario', rolNombre);
    return `${nombre} - ${rolNombre}`;
}

function obtenerHoraTareaAdmin(fecha) {
    const fechaTarea = fecha ? new Date(String(fecha).replace(' ', 'T')) : new Date();
    const fechaValida = Number.isNaN(fechaTarea.getTime()) ? new Date() : fechaTarea;

    return new Intl.DateTimeFormat('es-CO', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(fechaValida);
}

function obtenerEstilosTareaAdmin(categoria) {
    const cat = String(categoria || 'GENERAL').toUpperCase();

    if (cat === 'URGENTE') {
        return {
            tarjeta: 'border-red-400 bg-red-50/70',
            etiqueta: 'text-red-500 bg-red-100',
            icono: 'priority_high'
        };
    }

    if (cat === 'LIMPIEZA') {
        return {
            tarjeta: 'border-amber-400 bg-amber-50/70',
            etiqueta: 'text-amber-700 bg-amber-100',
            icono: 'cleaning_services'
        };
    }

    return {
        tarjeta: 'border-primary bg-[#fbfdfd]',
        etiqueta: 'text-primary bg-accent',
        icono: 'radio_button_checked'
    };
}

function crearTareaAdminHTML(tarea) {
    const categoria = String(tarea.categoria || tarea.cat || 'GENERAL').toUpperCase();
    const estilos = obtenerEstilosTareaAdmin(categoria);
    const creador = formatearCreadorAdmin(tarea);
    const fecha = tarea.fecha ? obtenerHoraTareaAdmin(tarea.fecha) : '';

    return `
        <div id="tarea-admin-${escaparHTMLAdmin(tarea.id)}" draggable="true" class="task-item p-6 rounded-lg border border-primary/10 border-l-4 ${estilos.tarjeta} shadow-md group relative transition-all duration-300 ease-out">
            <p class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[7px] font-black uppercase tracking-wide mb-3 ${estilos.etiqueta}">
                <span class="material-symbols-outlined text-[12px]">${estilos.icono}</span>${escaparHTMLAdmin(categoria)}
            </p>
            <h4 class="text-xs font-bold text-heading leading-tight">${escaparHTMLAdmin(tarea.titulo)}</h4>
            <p class="text-[10px] text-slate-400 mt-2">${escaparHTMLAdmin(tarea.descripcion || tarea.desc || '')}</p>
            <div class="flex items-center gap-3 mt-3">
                <span class="text-[10px] font-black uppercase tracking-tighter text-primary bg-primary/5 px-2 py-1 rounded">${escaparHTMLAdmin(creador)}</span>
                <span class="text-[10px] text-slate-400 font-bold">${escaparHTMLAdmin(fecha)}</span>
            </div>
            <div class="mt-4">
                <button type="button" onclick="marcarTareaComoHechaAdmin(${Number(tarea.id)}, this)" class="accion-tarea text-[9px] font-bold text-primary underline uppercase opacity-0 group-hover:opacity-100 transition-all">Hecho</button>
            </div>
        </div>`;
}

async function renderTareasAdmin() {
    const contenedor = document.getElementById('contenedorTareasAdmin');
    if (!contenedor || renderTareasAdminEnCurso) return;

    renderTareasAdminEnCurso = true;

    try {
        // Transaccion: La UI lee de servidor con cache desactivada para mantener consistencia del CRUD.
        const respuesta = await fetch('../../controladores/obtener_tareas.php', {
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        });
        const tareas = await respuesta.json();

        if (!respuesta.ok || !Array.isArray(tareas)) {
            throw new Error('No se pudo cargar la cola de tareas');
        }

        tareasAdminState = tareas;
        contenedor.innerHTML = tareas.map(crearTareaAdminHTML).join('');
        initDragAndDropAdmin();
        actualizarContadorTareasAdmin();
    } catch (error) {
        console.error('Error cargando tareas admin:', error);
    } finally {
        renderTareasAdminEnCurso = false;
    }
}

async function marcarTareaComoHechaAdmin(id, botonHTML) {
    const tarea = botonHTML.closest('.task-item');
    const textoOriginal = botonHTML.innerHTML;

    // Transaccion: Se deshabilita el boton y se muestra Cargando mientras responde MySQL.
    botonHTML.disabled = true;
    botonHTML.classList.add('opacity-60', 'cursor-not-allowed');
    botonHTML.innerHTML = 'Cargando...';

    const formData = new FormData();
    formData.append('id_tarea', id);

    try {
        const respuesta = await fetch('../../controladores/completar_tarea.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || resultado.status !== 'exito') {
            throw new Error(resultado.mensaje || 'No se pudo completar la tarea');
        }

        // Transaccion: La tarjeta solo se quita despues de confirmacion 200 del servidor.
        if (tarea) {
            tarea.classList.add('opacity-0', 'translate-x-6', 'scale-95');
            setTimeout(() => {
                tarea.remove();
                actualizarContadorTareasAdmin();
            }, 260);
        }

        notificarCambioTareasAdmin();
    } catch (error) {
        console.error('Error completando tarea:', error);
        botonHTML.disabled = false;
        botonHTML.classList.remove('opacity-60', 'cursor-not-allowed');
        botonHTML.innerHTML = textoOriginal;
        alert(error.message || 'No se pudo completar la tarea.');
    }
}

// Reparacion: Alias para no romper botones antiguos que llamaban marcarTareaComoHecha desde la cola admin.
function marcarTareaComoHecha(id, botonHTML) {
    return marcarTareaComoHechaAdmin(id, botonHTML);
}

function actualizarContadorTareasAdmin() {
    const contenedor = document.getElementById('contenedorTareasAdmin');
    const badge = document.getElementById('badgeNotificacionesAdmin');
    const contadorDashboard = document.getElementById('contador-tareas');
    if (!contenedor) return;

    const cantidad = contenedor.querySelectorAll('.task-item').length;
    if (badge) {
        if (cantidad > 0) {
            badge.innerText = cantidad;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    // Reparacion: Sincroniza tambien el contador del dashboard cuando existe.
    if (contadorDashboard) contadorDashboard.innerText = cantidad;
}

function iniciarSincronizacionTareasAdmin() {
    if (intervaloColaAdmin) return;
    // Reparacion: Se replica la sincronizacion periodica de Operaciones para recibir tareas sin recargar.
    intervaloColaAdmin = setInterval(renderTareasAdmin, 3000);
}

function reiniciarSincronizacionTareasAdmin() {
    if (intervaloColaAdmin) {
        clearInterval(intervaloColaAdmin);
        intervaloColaAdmin = null;
    }
    iniciarSincronizacionTareasAdmin();
}

function notificarCambioTareasAdmin(tarea = null) {
    // Nueva conexion: Envia la tarea creada como payload para que el panel empleado la pinte sin F5.
    if (tarea) {
        const payload = JSON.stringify({ ...tarea, emitida_en: Date.now() });
        localStorage.setItem('tarea_nueva_payload', payload);

        if (typeof BroadcastChannel !== 'undefined') {
            const canal = new BroadcastChannel('software_hotel_tareas');
            canal.postMessage({ tipo: 'tarea_creada', tarea });
            canal.close();
        }
    }

    localStorage.setItem('tareas_actualizadas', Date.now().toString());
}

function initDragAndDropAdmin() {
    const contenedor = document.getElementById('contenedorTareasAdmin');
    if (!contenedor) return;

    inicializarItemsDragAdmin();
    if (contenedor.dataset.dragInicializado === '1') return;

    // Reparacion: El listener dragover se instala una sola vez para evitar duplicados por cada render.
    contenedor.dataset.dragInicializado = '1';
    contenedor.addEventListener('dragover', event => {
        event.preventDefault();
        const afterElement = getDragAfterElementAdmin(contenedor, event.clientY);
        const draggable = contenedor.querySelector('.dragging');
        if (!draggable) return;

        if (afterElement == null) contenedor.appendChild(draggable);
        else contenedor.insertBefore(draggable, afterElement);
    });
}

function inicializarItemsDragAdmin() {
    const contenedor = document.getElementById('contenedorTareasAdmin');
    if (!contenedor) return;

    contenedor.querySelectorAll('.task-item').forEach(task => {
        task.addEventListener('dragstart', () => task.classList.add('dragging'));
        task.addEventListener('dragend', () => task.classList.remove('dragging'));
    });
}

function getDragAfterElementAdmin(container, y) {
    const elements = [...container.querySelectorAll('.task-item:not(.dragging)')];
    return elements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        return offset < 0 && offset > closest.offset ? { offset, element: child } : closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function abrirColaTareasAdmin() {
    const panel = document.getElementById('panelTareasDerechoAdmin');
    const cabecera = document.getElementById('cabeceraColaTareasAdmin');
    const titulo = document.getElementById('tituloColaTareasAdmin');
    const flecha = document.getElementById('flechaCerrarColaTareasAdmin');
    const contenedor = document.getElementById('contenedorTareasAdmin');
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

function cerrarColaTareasAdmin(event) {
    if (event) event.stopPropagation();

    const panel = document.getElementById('panelTareasDerechoAdmin');
    const cabecera = document.getElementById('cabeceraColaTareasAdmin');
    const titulo = document.getElementById('tituloColaTareasAdmin');
    const flecha = document.getElementById('flechaCerrarColaTareasAdmin');
    const contenedor = document.getElementById('contenedorTareasAdmin');
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

function toggleColaTareasAdmin(event) {
    const panel = document.getElementById('panelTareasDerechoAdmin');
    if (!panel) return;

    if (panel.classList.contains('w-16')) abrirColaTareasAdmin();
    else cerrarColaTareasAdmin(event);
}

// =======================================================
// MODAL DE TAREAS ADMIN
// =======================================================
function abrirModal() {
    const modal = document.getElementById('modalTarea');
    if (modal) {
        modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-[5px] z-[100] flex items-center justify-center modal-visible transition-all duration-500';
    }
}

function cerrarModal() {
    const modal = document.getElementById('modalTarea');
    if (modal) {
        modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-[5px] z-[100] flex items-center justify-center modal-oculto transition-all duration-500';
    }
}

const formTarea = document.getElementById('formTarea');
if (formTarea) {
    formTarea.addEventListener('submit', async function(event) {
        event.preventDefault();

        const botonSubmit = this.querySelector('button[type="submit"]');
        const textoOriginal = botonSubmit ? botonSubmit.innerHTML : '';
        const titulo = document.getElementById('tituloTarea').value.trim();
        const categoria = document.getElementById('categoriaTarea').value;
        const descripcion = document.getElementById('descTarea').value.trim();

        const formData = new FormData();
        formData.append('titulo', titulo);
        formData.append('categoria', categoria);
        formData.append('descripcion', descripcion);
        formData.append('id_creador_panel', typeof ID_USUARIO_ACTIVO !== 'undefined' ? ID_USUARIO_ACTIVO : '');
        formData.append('rol_creador_panel', typeof ROL_USUARIO !== 'undefined' ? ROL_USUARIO : '');
        formData.append('firma_creador_panel', typeof FIRMA_USUARIO_ACTIVO !== 'undefined' ? FIRMA_USUARIO_ACTIVO : '');
        formData.append('panel_origen', 'admin'); // Correccion: El backend identifica que la tarea nace desde el panel administrador.

        try {
            // Transaccion: Estado de carga para impedir doble envio mientras responde la base de datos.
            if (botonSubmit) {
                botonSubmit.disabled = true;
                botonSubmit.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">sync</span> Guardando...';
            }

            const respuesta = await fetch('../../controladores/guardar_tarea.php', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const resultado = await respuesta.json();

            if (!respuesta.ok || resultado.status !== 'exito') {
                // Transaccion: Error de validacion controlado por el servidor.
                alert(resultado.mensaje || 'No se pudo guardar la tarea.');
                return;
            }

            // Transaccion: La UI se actualiza solo con exito 200/201 confirmado por servidor.
            this.reset();
            cerrarModal();
            await renderTareasAdmin();
            notificarCambioTareasAdmin(resultado.tarea);
        } catch (error) {
            console.error('Error critico de base de datos:', error);
            alert('Error critico de base de datos. No se pudo guardar la tarea.');
        } finally {
            // Transaccion: Restauracion obligatoria para que el usuario no quede bloqueado.
            if (botonSubmit) {
                botonSubmit.disabled = false;
                botonSubmit.innerHTML = textoOriginal;
            }
        }
    });
}

// =======================================================
// RESERVAS: RENDER REACTIVO Y ACCIONES AJAX
// =======================================================
function obtenerClaseEstadoReserva(estado) {
    if (estado === 'Confirmada') return 'bg-green-100 text-green-700';
    if (estado === 'Pendiente') return 'bg-amber-100 text-amber-700';
    if (estado === 'En Casa') return 'bg-blue-100 text-blue-700';
    if (estado === 'Cancelada') return 'bg-red-100 text-red-700';
    return 'bg-slate-100 text-slate-700';
}

function formatearFechaReserva(fecha) {
    const date = new Date(String(fecha).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date).replace('.', '');
}

function separarNotasReserva(notas) {
    const texto = String(notas || '').trim();
    const match = texto.match(/^Adultos:\s*(\d+)\s*\|\s*Ni(?:ñ|n)os:\s*(\d+)\s*([\s\S]*)$/i);
    if (!match) {
        return { huespedes: '', peticion: texto };
    }

    const adultos = Number(match[1]);
    const ninos = Number(match[2]);
    return {
        huespedes: `${adultos} Adulto${adultos === 1 ? '' : 's'}, ${ninos} Niño${ninos === 1 ? '' : 's'}`,
        peticion: String(match[3] || '').trim()
    };
}

function crearFilaReservaHTML(reserva) {
    const habitacion = reserva.cod_hab_det ? reserva.cod_hab_det : 'Sin asignar';
    const notas = reserva.not_res || '';
    const notasSeparadas = separarNotasReserva(notas);
    const huespedesTabla = notasSeparadas.huespedes ? `<p class="text-xs font-black text-slate-700">${escaparHTMLAdmin(notasSeparadas.huespedes)}</p>` : '';
    const peticionTabla = notasSeparadas.peticion ? `<p class="text-xs italic text-slate-400 mt-1">${escaparHTMLAdmin(notasSeparadas.peticion)}</p>` : '';
    const notasTabla = huespedesTabla || peticionTabla ? `${huespedesTabla}${peticionTabla}` : '<span class="text-slate-300 italic">Ninguna</span>';
    const fechaIn = formatearFechaReserva(reserva.fec_ent_res);
    const fechaOut = formatearFechaReserva(reserva.fec_sal_res);
    const estado = reserva.est_res || 'Pendiente';

    return `
        <tr class="hover:bg-slate-50 transition-colors" data-reserva-id="${Number(reserva.cod_res)}">
            <td class="py-5 px-4 align-middle font-semibold text-slate-800">${escaparHTMLAdmin(reserva.nom_usu || '')}</td>
            <td class="py-5 px-4 align-middle text-slate-600 font-bold">${escaparHTMLAdmin(habitacion)}</td>
            <td class="py-5 px-4 align-middle text-slate-500"><div class="flex items-center gap-2 text-xs font-bold"><span>${escaparHTMLAdmin(fechaIn)}</span><span class="material-symbols-outlined text-[15px] text-slate-300">arrow_forward</span><span>${escaparHTMLAdmin(fechaOut)}</span></div></td>
            <td class="py-5 px-4 align-middle"><span class="${obtenerClaseEstadoReserva(estado)} px-3 py-1 rounded-full text-[9px] uppercase tracking-widest">${escaparHTMLAdmin(estado)}</span></td>
            <td class="py-5 px-4 align-middle max-w-xs">${notasTabla}</td>
            <td class="py-5 px-4 align-middle"><div class="flex justify-center gap-2">
                <button onclick="abrirEdicion(this)" class="ui-action bg-amber-400 text-white px-5 py-2 rounded-lg hover:bg-amber-500 hover:shadow-lg hover:opacity-95 shadow-sm btn-editar-reserva" title="Editar Reserva" data-id="${Number(reserva.cod_res)}" data-estado="${escaparHTMLAdmin(estado)}" data-habitacion="${escaparHTMLAdmin(habitacion)}" data-notas="${escaparHTMLAdmin(notas)}">
                    <span class="material-symbols-outlined text-sm">edit</span>
                </button>
                <button onclick="confirmarEliminacion(${Number(reserva.cod_res)})" class="ui-action bg-red-500 text-white px-5 py-2 rounded-lg hover:bg-red-600 hover:shadow-lg hover:opacity-95 shadow-sm" title="Eliminar Reserva">
                    <span class="material-symbols-outlined text-sm">delete</span>
                </button>
            </div></td>
        </tr>`;
}

function actualizarFilaReserva(reserva) {
    const tabla = document.getElementById('tablaReservas');
    if (!tabla || !reserva || !reserva.cod_res) return;

    tabla.querySelectorAll('tr').forEach(fila => {
        if (fila.querySelector('td[colspan]')) fila.remove();
    });

    const filaActual = tabla.querySelector(`[data-reserva-id="${Number(reserva.cod_res)}"]`);
    const template = document.createElement('tbody');
    template.innerHTML = crearFilaReservaHTML(reserva).trim();
    const filaNueva = template.firstElementChild;

    if (filaActual && filaNueva) {
        filaActual.replaceWith(filaNueva);
        filaNueva.classList.add('reserva-row-updated');
        setTimeout(() => filaNueva.classList.remove('reserva-row-updated'), 900);
    } else if (!filaActual && filaNueva) {
        tabla.insertAdjacentElement('afterbegin', filaNueva);
        filaNueva.classList.add('reserva-row-updated');
        setTimeout(() => filaNueva.classList.remove('reserva-row-updated'), 900);
    }
}

function notificarCambioReservas() {
    localStorage.setItem('reservas_actualizadas', Date.now().toString());
    localStorage.setItem('habitaciones_actualizadas', Date.now().toString());
    if (canalReservasAdmin) {
        canalReservasAdmin.postMessage({ tipo: 'reserva_actualizada', emitida_en: Date.now() });
    }
}

let intervaloReservasAdmin = null;
function iniciarSincronizacionReservas() {
    if (intervaloReservasAdmin) return;
    intervaloReservasAdmin = setInterval(() => {
        if (!document.hidden) refrescarTablaReservas();
    }, 4000);
}

async function refrescarTablaReservas() {
    try {
        const respuesta = await fetch('../../interfaz/admin/secciones_ad/reservas.php', {
            cache: 'no-store',
            headers: { 'Accept': 'text/html' }
        });
        if (!respuesta.ok) throw new Error('No se pudo sincronizar reservas');
        const html = await respuesta.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nuevoBody = doc.querySelector('#tablaReservas');
        const bodyActual = document.querySelector('#tablaReservas');
        if (!nuevoBody || !bodyActual) throw new Error('Respuesta de reservas incompleta');
        bodyActual.innerHTML = nuevoBody.innerHTML;
    } catch (error) {
        console.error('Error refrescando reservas:', error);
    }
}

function filtrarHuespedesReserva() {
    const input = document.getElementById('buscadorHuespedReserva');
    const selector = document.getElementById('selectorHuesped');
    if (!input || !selector) return;

    const termino = input.value.trim().toLowerCase();
    [...selector.options].forEach(option => {
        if (option.value === 'nuevo' || option.value === '') {
            option.hidden = false;
            return;
        }
        option.hidden = termino !== '' && !String(option.dataset.busqueda || option.textContent).includes(termino);
    });
}

function crearConfirmacionReserva() {
    let modal = document.getElementById('confirmacionEliminarReserva');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'confirmacionEliminarReserva';
    modal.className = 'confirm-reserva hidden fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-[8px] p-4';
    modal.innerHTML = `
        <div class="confirm-reserva-card bg-white rounded-xl shadow-2xl border border-slate-100 w-full max-w-sm p-6">
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined">delete</span>
            </div>
            <h3 class="text-lg font-black text-heading mb-2">Eliminar reserva</h3>
            <p class="text-sm text-slate-500 font-semibold leading-relaxed mb-6">Esta accion eliminara la reserva y sus detalles asociados.</p>
            <div class="flex gap-3">
                <button type="button" data-accion="cancelar" class="ui-action flex-1 border border-slate-200 text-slate-500 font-bold rounded-lg py-3 hover:bg-slate-50">Cancelar</button>
                <button type="button" data-accion="confirmar" class="ui-action flex-1 bg-red-500 text-white font-bold rounded-lg py-3 hover:bg-red-600 shadow-md">Eliminar</button>
            </div>
        </div>`;
    document.body.appendChild(modal);
    return modal;
}

function confirmarEliminacionReservaPremium(idReserva) {
    const modal = crearConfirmacionReserva();
    const btnCancelar = modal.querySelector('[data-accion="cancelar"]');
    const btnConfirmar = modal.querySelector('[data-accion="confirmar"]');

    modal.classList.remove('hidden');
    requestAnimationFrame(() => modal.classList.add('confirm-reserva-visible'));

    const cerrar = () => {
        modal.classList.remove('confirm-reserva-visible');
        setTimeout(() => modal.classList.add('hidden'), 220);
    };

    btnCancelar.onclick = cerrar;
    btnConfirmar.onclick = async () => {
        cerrar();
        await eliminarReservaEnVivo(idReserva);
    };
}

window.eliminarReservaEnVivo = async function(idReserva) {
    try {
        const fd = new FormData();
        fd.append('id', idReserva);
        const response = await fetch('../../controladores/eliminar_reserva.php', {
            method: 'POST',
            body: fd
        });
        const res = await response.json();
        if (res.status === 'exito') {
            await refrescarTablaReservas();
            notificarCambioReservas();
        } else {
            alert('Error: ' + (res.mensaje || 'No se pudo eliminar'));
        }
    } catch (err) {
        alert('Error critico de conexion con la base de datos.');
    }
};

document.addEventListener('submit', async function(e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    const esFormEditar = form.id === 'formEditarReserva';
    const esFormCrear = form.getAttribute('action') === '../../controladores/guardar_reserva.php';
    if (!esFormEditar && !esFormCrear) return;

    e.preventDefault();
    e.stopPropagation();

    const btn = form.querySelector('button[type="submit"]');
    if (!btn) return;

    // Evita doble envio por listeners duplicados
    if (btn.dataset.enviando === '1') return;
    btn.dataset.enviando = '1';

    const originalText = btn.innerHTML;
    btn.innerHTML = 'Guardando...';
    btn.disabled = true;

    try {
        if (esFormCrear && !form.querySelector('[name="tipo_huesped"]')?.value) {
            alert('Selecciona un huésped del buscador antes de guardar.');
            return;
        }

        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        });
        const res = await response.json();

        if (res.status === 'exito') {
            if (esFormEditar && typeof cerrarModalEditar === 'function') cerrarModalEditar();
            if (esFormCrear && typeof cerrarModalReserva === 'function') cerrarModalReserva();
            if (esFormCrear) {
                form.reset();
                if (typeof limpiarHuespedSeleccionado === 'function') {
                    limpiarHuespedSeleccionado();
                } else {
                    document.getElementById('huespedSeleccionadoReserva')?.classList.add('hidden');
                    document.getElementById('listaHuespedesReserva')?.querySelectorAll('button').forEach(btn => btn.classList.remove('bg-primary/10'));
                }
                if (typeof cambiarHuespedesAdmin === 'function') {
                    while (Number(document.getElementById('cantAdultosReserva')?.value || 1) > 1) cambiarHuespedesAdmin('adultos', -1);
                    while (Number(document.getElementById('cantNinosReserva')?.value || 0) > 0) cambiarHuespedesAdmin('ninos', -1);
                }
            }

            if (res.reserva) {
                actualizarFilaReserva(res.reserva);
            } else {
                await refrescarTablaReservas();
            }
            notificarCambioReservas();
        } else {
            alert('Error: ' + (res.mensaje || 'No se pudo procesar la reserva'));
        }
    } catch (err) {
        alert('Error critico de conexion con la base de datos.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        btn.dataset.enviando = '0';
    }
}, true);

