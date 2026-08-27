// vistas/empleado/js/panel.js

// 1. DATOS SIMULADOS: Esto representa lo que a futuro traerás de tu base de datos con PHP.
const DATA_HOTEL = [
    { numero: '401', tipo: 'Suite Ejecutiva', estado: 'Ocupada', huesped: 'Sofia Rodriguez' },
    { numero: '402', tipo: 'Standard King', estado: 'Sucia', huesped: null },
    { numero: '405', tipo: 'Suite Ejecutiva', estado: 'Ocupada', huesped: 'Julianne Vance' },
    { numero: '408', tipo: 'Standard Plus', estado: 'Sucia', huesped: null },
    { numero: '410', tipo: 'Standard King', estado: 'Disponible', huesped: null },
    { numero: '501', tipo: 'Penthouse View', estado: 'Ocupada', huesped: 'Marcus Thorne' },
    { numero: '505', tipo: 'Penthouse View', estado: 'Ocupada', huesped: 'Elena Rodriguez' },
    { numero: '510', tipo: 'Standard Plus', estado: 'Mantenimiento', huesped: null }
];

// Corrección: Estado local de habitaciones para repintar la interfaz en tiempo real sin recargar la página.
let habitacionesEmpleadoState = [];
let intervaloTareasEmpleado = null;
let tareasEmpleadoState = [];
const tareasEmpleadoIds = new Set();
const canalTareasEmpleado = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('software_hotel_tareas') : null;
const canalReservasEmpleado = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('software_hotel_reservas') : null;

// Borramos TAREAS_MOCK y modificamos el inicio automático
// Reparación: Se reemplazó window.onload por DOMContentLoaded para no pisar funciones globales y asegurar que el DOM exista.
document.addEventListener('DOMContentLoaded', async () => {
    // 1. Poner la fecha
    if (document.getElementById('fechaHoy')) {
        document.getElementById('fechaHoy').innerText = new Intl.DateTimeFormat('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());
    }
    
    // 2. Traer Habitaciones, contadores y huéspedes
    await actualizarInterfaz();
    
    // 3. Traer las Tareas Reales
    await cargarTareasEmpleado();
    iniciarSincronizacionTareasEmpleado();
});

// Reparación: Función centralizada para restaurar la Cola de Tareas desde MySQL y reutilizarla en sincronización.
async function cargarTareasEmpleado() {
    try {
        const respuesta = await fetch('../../controladores/obtener_tareas.php', {
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        });
        const tareasReales = await respuesta.json();
        const contenedorTareas = document.getElementById('contenedorTareas');

        if (contenedorTareas) {
            if (!respuesta.ok || !Array.isArray(tareasReales)) {
                throw new Error('No se pudo cargar la cola de tareas');
            }

            contenedorTareas.innerHTML = '';
            tareasEmpleadoIds.clear();
            // Blindaje contra undefined sin importar cómo lo envíe tu base de datos MySQL
            tareasEmpleadoState = [];
            tareasReales.forEach(t => insertarTareaEmpleadoDesdeServidor(t));
            
            // Calculamos el número de la burbuja
            actualizarContadorTareas();
        }
    } catch (error) {
        console.error("Error al cargar las tareas:", error);
    }
}



// Reparación: La cola del empleado escucha cambios del admin u otra pestaña sin recargar la página.
window.addEventListener('storage', event => {
    if (event.key === 'tareas_actualizadas') {
        cargarTareasEmpleado();
        reiniciarSincronizacionTareasEmpleado();
    }

    if (event.key === 'tarea_nueva_payload' && event.newValue) {
        try {
            // Nueva conexion: Inserta en la cola del empleado el payload enviado por Admin sin recargar la pagina.
            insertarTareaEmpleadoDesdeServidor(JSON.parse(event.newValue));
        } catch (error) {
            console.error('Error al recibir tarea en tiempo real:', error);
        }
    }

    if (event.key === 'reservas_actualizadas' || event.key === 'habitaciones_actualizadas') {
        actualizarInterfaz();
    }
});

if (canalTareasEmpleado) {
    canalTareasEmpleado.addEventListener('message', event => {
        if (event.data && event.data.tipo === 'tarea_creada') {
            // Nueva conexion: BroadcastChannel entrega la tarea al instante entre pestañas del mismo navegador.
            insertarTareaEmpleadoDesdeServidor(event.data.tarea);
        }
    });
}

if (canalReservasEmpleado) {
    canalReservasEmpleado.addEventListener('message', event => {
        if (event.data && event.data.tipo === 'reserva_actualizada') {
            actualizarInterfaz();
        }
    });
}

function iniciarSincronizacionTareasEmpleado() {
    if (intervaloTareasEmpleado) return;
    // Nueva conexion: El empleado consulta la cola cada 3s para recibir tareas creadas desde Admin sin recargar.
    intervaloTareasEmpleado = setInterval(cargarTareasEmpleado, 3000);
}

function reiniciarSincronizacionTareasEmpleado() {
    if (intervaloTareasEmpleado) {
        clearInterval(intervaloTareasEmpleado);
        intervaloTareasEmpleado = null;
    }
    iniciarSincronizacionTareasEmpleado();
}

function insertarTareaEmpleadoDesdeServidor(tarea) {
    if (!tarea || !tarea.id) return;

    const id = String(tarea.id);
    if (tareasEmpleadoIds.has(id) || document.getElementById(`tarea-${id}`)) return;

    tareasEmpleadoIds.add(id);
    tareasEmpleadoState.unshift(tarea); // Nueva conexion: El arreglo local de la cola se actualiza instantaneamente con la tarea recibida.
    añadirTareaHTML(
        tarea.id,
        tarea.titulo,
        tarea.categoria || tarea.cat,
        tarea.descripcion || tarea.desc,
        tarea.creador_formateado
    );
}

function escaparHTML(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[caracter]));
}

function obtenerMotivoMantenimiento(observacion) {
    return String(observacion || '')
        .replace(/^Prioridad:\s*[^\n\r]*(\r?\n)?/i, '')
        .trim();
}

function clasesEstadoHousekeeping(estado) {
    const estadoNormalizado = estado === 'Limpio' ? 'Disponible' : estado;

    if (estadoNormalizado === 'Mantenimiento') {
        return {
            tarjeta: 'housekeeping-card--mantenimiento',
            etiqueta: 'bg-red-50 text-red-600 border border-red-100',
            icono: 'build',
            titulo: 'Mantenimiento'
        };
    }

    if (estadoNormalizado === 'Sucia') {
        return {
            tarjeta: 'housekeeping-card--sucia',
            etiqueta: 'bg-amber-50 text-amber-700 border border-amber-100',
            icono: 'cleaning_services',
            titulo: 'Sucia'
        };
    }

    if (estadoNormalizado === 'Ocupada') {
        return {
            tarjeta: 'housekeeping-card--ocupada',
            etiqueta: 'bg-red-50 text-red-600 border border-red-100',
            icono: 'bed',
            titulo: 'Ocupada'
        };
    }

    return {
        tarjeta: 'housekeeping-card--disponible',
        etiqueta: 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        icono: 'check_circle',
        titulo: estado === 'Limpio' ? 'Limpio' : 'Disponible'
    };
}

function toggleMotivoMantenimiento(idHabitacion) {
    const tarjetaActiva = document.getElementById(`housekeeping-${idHabitacion}`);
    if (!tarjetaActiva) return;

    document.querySelectorAll('.housekeeping-card.motivo-visible').forEach(tarjeta => {
        if (tarjeta !== tarjetaActiva) tarjeta.classList.remove('motivo-visible');
    });

    tarjetaActiva.classList.toggle('motivo-visible');
}

// Modificación: Se agregó una función centralizada para aplicar colores finales por estado en habitaciones.
function obtenerUIEstadoHabitacion(estado) {
    if (estado === 'Mantenimiento') {
        return {
            etiqueta: 'bg-red-50 text-red-600 border border-red-100',
            tarjeta: 'habitacion-card--mantenimiento'
        };
    }

    if (estado === 'Limpio') {
        return {
            etiqueta: 'bg-cyan-50 text-cyan-700 border border-cyan-100',
            tarjeta: 'habitacion-card--limpio'
        };
    }

    if (estado === 'Ocupada') {
        return {
            etiqueta: 'bg-orange-50 text-orange-600 border border-orange-100',
            tarjeta: 'habitacion-card--ocupada'
        };
    }

    if (estado === 'Sucia') {
        return {
            etiqueta: 'bg-stone-100 text-stone-700 border border-stone-200',
            tarjeta: 'habitacion-card--sucia'
        };
    }

    return {
        etiqueta: 'bg-green-50 text-green-700 border border-green-100',
        tarjeta: 'habitacion-card--disponible'
    };
}

function formatearFechaHotel(fecha) {
    const valor = String(fecha || '').trim();
    if (!valor || valor === '-') return '';
    const date = new Date(valor.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date).replace('.', '');
}

function obtenerBadgeReservaEmpleado(estado) {
    const estadoLimpio = String(estado || 'Ocupada').trim();
    if (estadoLimpio === 'Pendiente') return 'bg-amber-50 text-amber-700 border border-amber-100';
    if (estadoLimpio === 'Confirmada') return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
    if (estadoLimpio === 'En Casa' || estadoLimpio === 'Ocupada') return 'bg-blue-50 text-blue-700 border border-blue-100';
    if (estadoLimpio === 'Cancelada') return 'bg-red-50 text-red-700 border border-red-100';
    return 'bg-slate-100 text-slate-600 border border-slate-200';
}

function crearBloqueReservaHabitacion(h) {
    if (!h.huesped_nombre) {
        return '<p class="text-[13px] font-black text-heading mb-5">Disponible para reserva</p>';
    }

    const fechaEntrada = formatearFechaHotel(h.fec_ent_res);
    const fechaSalida = formatearFechaHotel(h.fec_sal_res);
    const fechas = fechaEntrada && fechaSalida ? `${fechaEntrada} - ${fechaSalida}` : '';
    const estadoReserva = h.est_res || h.estado || 'Ocupada';

    return `
        <div class="space-y-2 mb-5">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-primary">person</span>
                <span class="text-[14px] font-black text-heading leading-tight">${escaparHTML(h.huesped_nombre)}</span>
            </div>
            ${fechas ? `
                <div class="flex items-center gap-2 text-[#64748b]">
                    <span class="material-symbols-outlined text-[15px]">calendar_month</span>
                    <span class="text-[11px] font-bold">${escaparHTML(fechas)}</span>
                </div>
            ` : ''}
            <span class="inline-flex w-fit px-2.5 py-1 rounded-full text-[8px] font-black uppercase tracking-widest ${obtenerBadgeReservaEmpleado(estadoReserva)}">${escaparHTML(estadoReserva)}</span>
        </div>
    `;
}

function crearDetalleHuespedEmpleado(h) {
    const fechaEntrada = formatearFechaHotel(h.fec_ent_res);
    const fechaSalida = formatearFechaHotel(h.fec_sal_res);
    const fechas = fechaEntrada && fechaSalida ? `
        <span class="inline-flex items-center gap-1 text-slate-500">
            <span class="material-symbols-outlined text-[14px]">calendar_month</span>
            ${escaparHTML(fechaEntrada)} - ${escaparHTML(fechaSalida)}
        </span>
    ` : '';
    const estadoReserva = h.est_res || h.estado || 'Ocupada';

    return `
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-bold text-slate-500">
                <span>${escaparHTML(h.tipo)}</span>
                ${fechas}
            </div>
            <span class="inline-flex w-fit px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest ${obtenerBadgeReservaEmpleado(estadoReserva)}">${escaparHTML(estadoReserva)}</span>
        </div>
    `;
}

// 3. ACTUALIZAR INTERFAZ: Llena las secciones con los datos REALES de MySQL
async function actualizarInterfaz(habitacionesLocales = null) {
    try {
        // Corrección: Si recibimos habitaciones locales, renderizamos desde state; si no, consultamos MySQL una sola vez.
        const DATA_HOTEL = habitacionesLocales || await (await fetch('../../controladores/obtener_habitaciones.php')).json();
        habitacionesEmpleadoState = DATA_HOTEL;

        const ocupadas = DATA_HOTEL.filter(h => h.estado === 'Ocupada').length;
        const sucias = DATA_HOTEL.filter(h => h.estado === 'Sucia').length;
        const huespedes = ocupadas; 

        if(document.getElementById('dash-ocupadas')) document.getElementById('dash-ocupadas').innerText = ocupadas;
        if(document.getElementById('dash-limpieza')) document.getElementById('dash-limpieza').innerText = sucias;
        if(document.getElementById('dash-huespedes')) document.getElementById('dash-huespedes').innerText = huespedes;

        const gridHab = document.getElementById('gridHabitaciones');
        if(gridHab) {
            gridHab.innerHTML = '';
            DATA_HOTEL.forEach(h => {
                // Modificación: Se ajustaron las etiquetas de estado a colores sutiles y coherentes con la nueva lógica visual.
                // Modificación: La etiqueta y la tarjeta ahora usan la paleta final por estado, incluyendo Limpio en cian y Sucia en gris/café.
                const estadoUIHabitacion = obtenerUIEstadoHabitacion(h.estado);
                const color = estadoUIHabitacion.etiqueta;
                // Conexión: Se muestra huésped real y fechas de reserva desde SQL.
                const bloqueReserva = crearBloqueReservaHabitacion(h);

                // Modificación: Se reemplazaron fondos sólidos por recuadros blancos con difuminado suave por estado.
                // Modificación: La clase de tarjeta ahora separa Disponible verde de Limpio cian.
                const colorTarjeta = estadoUIHabitacion.tarjeta;
                const prioridadHab = h.observacion && h.observacion.includes('Prioridad: Urgente') ? 'Urgente' : h.observacion && h.observacion.includes('Prioridad: Importante') ? 'Importante' : 'No urgente';
                const prioridadIcono = prioridadHab === 'Urgente' ? '▲' : prioridadHab === 'Importante' ? '◆' : '●';
                const prioridadColor = prioridadHab === 'Urgente' ? 'text-red-500 bg-red-50' : prioridadHab === 'Importante' ? 'text-orange-500 bg-orange-50' : 'text-green-600 bg-green-50';
                const prioridadHabitacion = h.estado === 'Mantenimiento' ? `<span class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full ${prioridadColor}"><span class="text-[7px] font-black leading-none">${prioridadIcono}</span><span class="text-[7px] font-black uppercase tracking-wide leading-none">${prioridadHab}</span></span>` : '';

                // SALVAVIDAS: Si ROL_USUARIO existe, lo usa. Si no, asume temporalmente que es 3 (Yulli) para no romper la app.
                const rolActivo = typeof ROL_USUARIO !== 'undefined' ? ROL_USUARIO : 3;

                const botonGestion = rolActivo === 3 
                    ? `<button onclick="abrirGestionHabitacion(${h.id}, ${h.numero}, '${h.estado}')" class="w-full text-center bg-[#eef4f4] hover:bg-primary hover:text-white text-primary font-bold text-[10px] py-2 rounded-lg uppercase tracking-widest transition-colors">
                        Cambiar Estado
                       </button>`
                    : `<div class="w-full text-center py-2 text-[9px] font-bold text-slate-300 uppercase tracking-widest bg-slate-50 rounded-lg">
                        Solo Recepción
                       </div>`;

                gridHab.insertAdjacentHTML('beforeend', `
                    <!-- Modificación: Se aplica la clase de tint suave para conservar base blanca en la tarjeta de habitación. -->
                    <div class="metric-card habitacion-card ${colorTarjeta} p-6 rounded-xl flex flex-col justify-between h-auto shadow-md border relative group">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[28px] font-black text-primary">${h.numero}</span>
                            <div class="flex flex-col items-end">
                                <span class="px-2.5 py-0.5 rounded-full text-[7.5px] font-black uppercase tracking-wide ${color}">${h.estado}</span>
                                ${prioridadHabitacion}
                            </div>
                        </div>
                        <div>
                            <p class="text-[8.5px] font-bold text-slate-400 uppercase tracking-wide mb-1">${h.tipo}</p>
                            ${bloqueReserva}
                            ${botonGestion}
                        </div>
                    </div>
                `);
            });
        }

        const gridHousekeeping = document.getElementById('gridHousekeeping');
        if(gridHousekeeping) {
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

                // Corrección: Se arregló la estructura de la franja y el difuminado en la sección Limpieza para heredar los estilos funcionales de Habitaciones.
                gridHousekeeping.insertAdjacentHTML('beforeend', `
                    <!-- Modificación: Se aplicó fondo blanco y animación hover igual a la sección habitaciones en los recuadros de limpieza. -->
                    <article id="housekeeping-${h.id}" ${eventoMantenimiento} class="metric-card habitacion-card housekeeping-card ${estadoUI.tarjeta} ${cursorMantenimiento} p-6 rounded-xl shadow-md border relative overflow-visible">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.18em] mb-2">Habitacion</p>
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

        const gridHue = document.getElementById('gridHuespedes');
        if(gridHue) {
            gridHue.innerHTML = '';
            DATA_HOTEL.filter(h => h.estado === 'Ocupada' || h.huesped_nombre).forEach(h => {
                const detalleHuesped = crearDetalleHuespedEmpleado(h);
                gridHue.insertAdjacentHTML('beforeend', `
                    <div class="bg-white p-8 rounded-xl border border-primary/10 shadow-[0_18px_42px_-28px_rgba(15,23,42,0.45)] flex items-center gap-7 group relative overflow-hidden">
                        <div class="w-16 h-16 bg-primary/5 rounded-xl flex items-center justify-center font-black text-primary text-xl border border-primary/10">${h.numero}</div>
                        <div class="flex-1 space-y-3">
                            <h4 class="text-xl font-semibold text-heading leading-tight">${escaparHTML(h.huesped_nombre || 'Hu�sped registrado')}</h4>
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
        console.error("Error al cargar la interfaz:", error);
    }
} // <-- Ahora sí, una sola llave de cierre.
// 4. LÓGICA DE NAVEGACIÓN: Oculta y muestra pestañas
function navegar(sec, btn) {
    document.querySelectorAll('.seccion-contenido').forEach(s => s.classList.add('hidden'));
    document.getElementById('sec-' + sec).classList.remove('hidden');
    document.getElementById('tituloCabecera').innerText = sec === 'dashboard' ? 'Panel Hoy' : { 'habitaciones': 'Habitaciones', 'limpieza': 'Limpieza', 'huespedes': 'Huéspedes' }[sec];
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active-nav'));
    btn.classList.add('active-nav');
}

function redirigirDesdeDash(sec) {
    const btn = document.querySelector(`.nav-item[onclick*="${sec}"]`);
    if (btn) navegar(sec, btn);
}

// 5. LÓGICA DEL MODAL DE TAREAS
function abrirModal() { document.getElementById('modalTarea').className = "fixed inset-0 bg-primary/20 z-[100] flex items-center justify-center modal-visible transition-all duration-500"; }
function cerrarModal() { document.getElementById('modalTarea').className = "fixed inset-0 bg-primary/20 z-[100] flex items-center justify-center modal-oculto transition-all duration-500"; }

// Carga de nuevas tareas desde el formulario CONECTADO A MYSQL
const formTarea = document.getElementById('formTarea');
if(formTarea){
    formTarea.addEventListener('submit', async function(e) {
        e.preventDefault();

        const botonSubmit = this.querySelector('button[type="submit"]');
        const textoOriginalSubmit = botonSubmit ? botonSubmit.innerHTML : '';

        const titulo = document.getElementById('tituloTarea').value;
        const categoria = document.getElementById('categoriaTarea').value;
        const descripcion = document.getElementById('descTarea').value;

        const formData = new FormData();
        formData.append('titulo', titulo);
        formData.append('categoria', categoria);
        formData.append('descripcion', descripcion);
        formData.append('id_creador_panel', typeof ID_USUARIO_ACTIVO !== 'undefined' ? ID_USUARIO_ACTIVO : '');
        formData.append('rol_creador_panel', typeof ROL_USUARIO !== 'undefined' ? ROL_USUARIO : '');
        formData.append('firma_creador_panel', typeof FIRMA_USUARIO_ACTIVO !== 'undefined' ? FIRMA_USUARIO_ACTIVO : '');
        formData.append('panel_origen', 'empleado'); // Transacción: Conserva la identidad real del panel que crea la tarea.

        try {
            // Transacción: Estado de carga para impedir doble envío mientras responde la base de datos.
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
                // Transacción: Error de validación controlado por el servidor.
                alert(resultado.mensaje || 'No se pudo guardar la tarea.');
                return;
            }

            const nuevoId = resultado.id_tarea ? resultado.id_tarea : Date.now();

            // Transacción: La UI solo se actualiza cuando el servidor confirma éxito.
            añadirTareaHTML(nuevoId, titulo, categoria, descripcion, resultado.tarea ? resultado.tarea.creador_formateado : '');
            this.reset();
            cerrarModal();
            actualizarContadorTareas();
            localStorage.setItem('tareas_actualizadas', Date.now().toString());
        } catch (error) {
            console.error("Error crítico de base de datos:", error);
            alert("Error crítico de base de datos. No se pudo guardar la tarea.");
        } finally {
            // Transacción: Restauración obligatoria para que el usuario no quede bloqueado.
            if (botonSubmit) {
                botonSubmit.disabled = false;
                botonSubmit.innerHTML = textoOriginalSubmit;
            }
        }
    });
}

// Actualizamos la función para que reciba el ID de la base de datos
function añadirTareaHTML(id, titulo, cat, desc, creadorFormateado = '') {
    tareasEmpleadoIds.add(String(id));
    // Reparación: Se escapan los datos de MySQL antes de inyectarlos en la Cola de Tareas.
    const categoria = String(cat || 'GENERAL').toUpperCase();
    const bColor = categoria === 'URGENTE' ? 'border-red-400 bg-red-50/70' : categoria === 'LIMPIEZA' ? 'border-amber-400 bg-amber-50/70' : 'border-primary bg-[#fbfdfd]';
    const tColor = categoria === 'URGENTE' ? 'text-red-500 bg-red-100' : categoria === 'LIMPIEZA' ? 'text-amber-700 bg-amber-100' : 'text-primary bg-accent';
    const iconoTarea = categoria === 'URGENTE' ? '▲' : categoria === 'LIMPIEZA' ? '◆' : '●';
    const html = `
        <div id="tarea-${escaparHTML(id)}" draggable="true" class="task-item p-6 rounded-lg border border-primary/10 border-l-4 ${bColor} shadow-md group relative transition-all duration-300 ease-out">
            <p class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[7px] font-black uppercase tracking-wide mb-3 ${tColor}"><span>${iconoTarea}</span>${escaparHTML(categoria)}</p>
            <h4 class="text-xs font-bold text-heading leading-tight">${escaparHTML(titulo)}</h4>
            <p class="text-[10px] text-slate-400 mt-2">${escaparHTML(desc)}</p>
            <p class="text-[9px] font-black uppercase tracking-tight text-primary bg-primary/5 px-2 py-1 rounded mt-3">${escaparHTML(creadorFormateado || 'Sistema - Hotel')}</p>
            <button onclick="marcarTareaComoHecha(${Number(id)}, this)" class="mt-4 text-[9px] font-bold text-primary underline uppercase opacity-0 group-hover:opacity-100 transition-all">Hecho</button>
        </div>`;
    document.getElementById('contenedorTareas').insertAdjacentHTML('afterbegin', html);
    initDragAndDrop();
    actualizarContadorTareas();
}

// Reparación: La tarea ya no desaparece antes de que el servidor confirme la operación.
async function marcarTareaComoHecha(id, botonHTML) {
    const tarea = botonHTML.closest('.task-item');
    const textoOriginal = botonHTML.innerHTML;

    // Transacción: Se muestra estado de carga y se deshabilita el botón mientras MySQL responde.
    botonHTML.disabled = true;
    botonHTML.classList.add('opacity-60', 'cursor-not-allowed');
    botonHTML.innerHTML = 'Cargando...';

    // 2. Le avisamos a la Base de Datos para que sea permanente
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

        // Transacción: La UI solo elimina la tarjeta después del éxito 200 confirmado por el servidor.
        if (tarea) {
            tarea.classList.add('tarea-exit');
            setTimeout(() => {
                tarea.remove();
                actualizarContadorTareas();
            }, 320);
        }

        localStorage.setItem('tareas_actualizadas', Date.now().toString()); // Modificación: Rutina transaccional para actualizar estado de tarea.
        console.log("Tarea " + id + " completada en MySQL");
    } catch (error) {
        console.error("Error al completar la tarea:", error);
        botonHTML.disabled = false;
        botonHTML.classList.remove('opacity-60', 'cursor-not-allowed');
        botonHTML.innerHTML = textoOriginal;
        alert(error.message || "No se pudo completar la tarea.");
    }
}

// 6. LÓGICA DRAG AND DROP (Arrastrar y soltar)
function initDragAndDrop() {
    const container = document.getElementById('contenedorTareas');
    if (!container) return;

    const tasks = container.querySelectorAll('.task-item');
    tasks.forEach(task => {
        task.addEventListener('dragstart', () => task.classList.add('dragging'));
        task.addEventListener('dragend', () => task.classList.remove('dragging'));
    });

    // Reparación: El dragover se instala una sola vez para que la cola no acumule listeners al recargarse.
    if (container.dataset.dragInicializado === '1') return;
    container.dataset.dragInicializado = '1';

    container.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(container, e.clientY);
        const draggable = container.querySelector('.dragging');
        if (!draggable) return;
        if (afterElement == null) container.appendChild(draggable);
        else container.insertBefore(draggable, afterElement);
    });
}

function getDragAfterElement(container, y) {
    const elements = [...container.querySelectorAll('.task-item:not(.dragging)')];
    return elements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
        else return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// --- LÓGICA DEL MODAL DE HABITACIONES ---
// 1. Función para abrir y cargar los datos de la habitación en el modal
function abrirGestionHabitacion(id, numero, estadoActual) {
    document.getElementById('idHabitacionModal').value = id;
    document.getElementById('tituloModalHab').innerText = `Habitación ${numero}`;
    document.getElementById('estadoHabitacionModal').value = estadoActual;
    document.getElementById('prioridadMantenimientoModal').value = 'No urgente';
    document.getElementById('descripcionMantenimientoModal').value = '';
    actualizarPrioridadMantenimiento();
    toggleDescripcionMantenimiento();
    
    const modal = document.getElementById('modalHabitacion');
    const contenidoModal = document.getElementById('contenidoModalHabitacion');
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.classList.add('opacity-100');
    contenidoModal.classList.remove('scale-95', 'translate-y-3');
    contenidoModal.classList.add('scale-100', 'translate-y-0');
}

// 2. Función para cerrar el modal
function cerrarModalHabitacion() {
    const modal = document.getElementById('modalHabitacion');
    const contenidoModal = document.getElementById('contenidoModalHabitacion');
    modal.classList.add('opacity-0', 'pointer-events-none');
    modal.classList.remove('opacity-100');
    contenidoModal.classList.add('scale-95', 'translate-y-3');
    contenidoModal.classList.remove('scale-100', 'translate-y-0');
}

function toggleDescripcionMantenimiento() {
    const estado = document.getElementById('estadoHabitacionModal').value;
    // Modificación: El contenedor de prioridad se controla de forma condicional según el estado seleccionado.
    const areaPrioridad = document.getElementById('areaPrioridadMantenimiento');
    const areaDescripcion = document.getElementById('areaDescripcionMantenimiento');
    const descripcion = document.getElementById('descripcionMantenimientoModal');
    const botonGuardar = document.getElementById('guardarCambiosHabitacion');

    if (estado === 'Mantenimiento') {
        // Modificación: El campo prioridad solo aparece cuando el estado seleccionado es Mantenimiento.
        areaPrioridad.classList.remove('hidden');
        areaDescripcion.classList.remove('hidden');
        descripcion.setAttribute('required', 'required');
        botonGuardar.disabled = descripcion.value.trim() === '';
    } else {
        // Modificación: El campo prioridad se oculta para Limpio, Disponible, Ocupada y Sucia.
        areaPrioridad.classList.add('hidden');
        areaDescripcion.classList.add('hidden');
        descripcion.removeAttribute('required');
        descripcion.value = '';
        botonGuardar.disabled = false;
    }

    if (botonGuardar.disabled) {
        botonGuardar.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        botonGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function actualizarPrioridadMantenimiento() {
    const prioridad = document.getElementById('prioridadMantenimientoModal');

    prioridad.classList.remove('border-red-500', 'text-red-600', 'border-orange-500', 'text-orange-600', 'border-green-500', 'text-green-600');

    if (prioridad.value === 'Urgente') {
        prioridad.classList.add('border-red-500', 'text-red-600');
    } else if (prioridad.value === 'Importante') {
        prioridad.classList.add('border-orange-500', 'text-orange-600');
    } else {
        prioridad.classList.add('border-green-500', 'text-green-600');
    }
}

// 3. Envío de datos al servidor por Fetch
const formHabitacion = document.getElementById('formGestionHabitacion');
if(formHabitacion) {
    document.getElementById('estadoHabitacionModal').addEventListener('change', toggleDescripcionMantenimiento);
    document.getElementById('prioridadMantenimientoModal').addEventListener('change', actualizarPrioridadMantenimiento);
    document.getElementById('descripcionMantenimientoModal').addEventListener('input', toggleDescripcionMantenimiento);

    formHabitacion.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('idHabitacionModal').value;
        const estado = document.getElementById('estadoHabitacionModal').value;
        const prioridad_mantenimiento = document.getElementById('prioridadMantenimientoModal').value;
        const descripcion_mantenimiento = document.getElementById('descripcionMantenimientoModal').value;

        if (estado === 'Mantenimiento' && descripcion_mantenimiento.trim() === '') {
            document.getElementById('descripcionMantenimientoModal').reportValidity();
            return;
        }
        
        const formData = new FormData();
        formData.append('id_hab', id);
        formData.append('estado', estado);
        formData.append('prioridad_mantenimiento', prioridad_mantenimiento);
        formData.append('descripcion_mantenimiento', descripcion_mantenimiento);
        
        try {
            const respuesta = await fetch('../../controladores/actualizar_estado_habitacion.php', {
                method: 'POST',
                body: formData
            });
            const resultado = await respuesta.json();
            
            if(resultado.status === 'exito') {
                cerrarModalHabitacion();
                // Corrección: Actualización reactiva del estado local de habitaciones sin recargar la página.
                const observacionActualizada = estado === 'Mantenimiento'
                    ? `Prioridad: ${prioridad_mantenimiento}\n${descripcion_mantenimiento}`
                    : '';

                habitacionesEmpleadoState = habitacionesEmpleadoState.map(habitacion => {
                    if (String(habitacion.id) !== String(id)) return habitacion;

                    return {
                        ...habitacion,
                        estado,
                        observacion: observacionActualizada
                    };
                });

                actualizarInterfaz(habitacionesEmpleadoState);
                // Corrección: Notificamos a otras pestañas/paneles para que Housekeeping admin se repinte en tiempo real.
                localStorage.setItem('habitaciones_actualizadas', Date.now().toString());
            }
        } catch (error) {
            console.error("Error en la conexión:", error);
        }
    });
}

// Función estilo YouTube: Cuenta cuántas tarjetas hay y actualiza la burbuja
function actualizarContadorTareas() {
    const contenedor = document.getElementById('contenedorTareas');
    const badge = document.getElementById('badgeNotificaciones');
    
    if (contenedor && badge) {
        // Cuenta cuántos elementos tienen la clase 'task-item'
        const cantidadTareas = contenedor.querySelectorAll('.task-item').length;
        
        if (cantidadTareas > 0) {
            badge.innerText = cantidadTareas;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

// Modificación: Se hizo que la sección de la Cola de Tareas sea desplegable.
function abrirColaTareas() {
    const panel = document.getElementById('panelTareasDerecho');
    const titulo = document.getElementById('tituloColaTareas');
    const flechaCerrar = document.getElementById('flechaCerrarColaTareas');
    const contenedor = document.getElementById('contenedorTareas');
    const cabecera = document.getElementById('cabeceraColaTareas');
    if (!panel || !titulo || !flechaCerrar || !contenedor || !cabecera) return;

    const estaCerrado = panel.classList.contains('w-16');

    if (!estaCerrado) return;

    panel.classList.remove('w-16', 'px-3');
    panel.classList.add('w-80', 'p-6');
    // Modificación: Se actualizó la separación cerrada para mantener alineada la línea de la cola de tareas.
    // Modificación: Se actualizó la separación cerrada a gap-0 para que la barra quede más cerca del logo al abrir/cerrar.
    cabecera.classList.remove('flex-col', 'gap-0');
    cabecera.classList.add('justify-between');
    titulo.classList.remove('opacity-0', 'w-0', 'overflow-hidden');
    flechaCerrar.classList.remove('opacity-0', 'w-0', 'overflow-hidden', 'pointer-events-none');
    contenedor.classList.remove('opacity-0', 'pointer-events-none');
    contenedor.classList.add('opacity-100');
}

function cerrarColaTareas(event) {
    if (event) event.stopPropagation();

    const panel = document.getElementById('panelTareasDerecho');
    const titulo = document.getElementById('tituloColaTareas');
    const flechaCerrar = document.getElementById('flechaCerrarColaTareas');
    const contenedor = document.getElementById('contenedorTareas');
    const cabecera = document.getElementById('cabeceraColaTareas');
    if (!panel || !titulo || !flechaCerrar || !contenedor || !cabecera) return;

    panel.classList.remove('w-80', 'p-6');
    panel.classList.add('w-16', 'px-3');
    // Modificación: Se redujo el espacio entre el icono y la línea al minimizar la cola de tareas.
    // Modificación: Se redujo el espacio entre el icono y la línea al mínimo al minimizar la cola de tareas.
    cabecera.classList.add('flex-col', 'gap-0');
    cabecera.classList.remove('justify-between');
    titulo.classList.add('opacity-0', 'w-0', 'overflow-hidden');
    flechaCerrar.classList.add('opacity-0', 'w-0', 'overflow-hidden', 'pointer-events-none');
    contenedor.classList.add('opacity-0', 'pointer-events-none');
    contenedor.classList.remove('opacity-100');
}

function toggleColaTareas(event) {
    const panel = document.getElementById('panelTareasDerecho');
    if (panel && panel.classList.contains('w-16')) {
        abrirColaTareas();
    } else {
        cerrarColaTareas(event);
    }
}

