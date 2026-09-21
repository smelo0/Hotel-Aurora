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