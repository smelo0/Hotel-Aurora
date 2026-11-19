// Experiencias en la interfaz del empleado: grupos programados (solo lectura).
(() => {
    const ENDPOINT = '../../controladores/experiencias_empleado.php';
    let grupos = [];
    let hoy = '';
    let filtro = 'hoy';
    let filtroElegidoPorUsuario = false;
    let cargando = false;

    const $ = id => document.getElementById(id);
    const esc = valor => String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));

    function sumarDias(fechaIso, dias) {
        const [a, m, d] = fechaIso.split('-').map(Number);
        const f = new Date(Date.UTC(a, m - 1, d + dias));
        return f.toISOString().slice(0, 10);
    }

    function etiquetaFecha(fechaIso) {
        const [a, m, d] = fechaIso.split('-').map(Number);
        const texto = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' })
            .format(new Date(Date.UTC(a, m - 1, d)));
        const cap = texto.charAt(0).toUpperCase() + texto.slice(1);
        if (fechaIso === hoy) return `Hoy · ${cap}`;
        if (fechaIso === sumarDias(hoy, 1)) return `Mañana · ${cap}`;
        return cap;
    }

    function formatearHora(hora) {
        const [h, m] = String(hora).split(':').map(Number);
        const sufijo = h >= 12 ? 'p. m.' : 'a. m.';
        const h12 = h % 12 === 0 ? 12 : h % 12;
        return `${h12}:${String(m).padStart(2, '0')} ${sufijo}`;
    }

    function mostrarMensaje(texto) {
        const caja = $('expEmpMensaje');
        if (!caja) return;
        caja.textContent = texto;
        caja.className = 'mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-700';
    }

    function ocultarMensaje() {
        $('expEmpMensaje')?.classList.add('hidden');
    }

    function gruposFiltrados() {
        if (filtro === 'hoy') return grupos.filter(g => g.fecha === hoy);
        if (filtro === 'proximos') {
            const limite = sumarDias(hoy, 7);
            return grupos.filter(g => g.fecha <= limite);
        }
        return grupos;
    }

    function tarjetaGrupo(g) {
        const participantes = g.participantes.map(p => `
            <li class="flex items-center justify-between gap-3 border-t border-slate-100 py-2 first:border-t-0">
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-bold text-slate-700">${esc(p.huesped)}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">
                        ${p.habitaciones ? 'Hab. ' + esc(p.habitaciones) : 'Sin habitación'}
                        ${p.estado === 'Pendiente' ? ' · <span class="text-amber-600">Pendiente</span>' : ''}
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-primary/5 px-2.5 py-0.5 text-[11px] font-black text-primary">${Number(p.personas)} ${Number(p.personas) === 1 ? 'persona' : 'personas'}</span>
            </li>`).join('');
        return `
            <article class="rounded-xl border border-primary/10 bg-white p-5 shadow-[0_18px_42px_-28px_rgba(15,23,42,0.45)]">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="inline-block rounded-full bg-amber-50 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-amber-700">${esc(g.actividad)}</span>
                        <h4 class="mt-2 text-base font-black leading-tight text-primary">${esc(g.opcion)}</h4>
                        <p class="mt-1 flex items-center gap-1 text-xs font-bold text-slate-500">
                            <span class="material-symbols-outlined text-[15px]">schedule</span>${esc(formatearHora(g.hora))}
                        </p>
                    </div>
                    <div class="shrink-0 rounded-xl bg-primary px-3 py-2 text-center text-white">
                        <p class="text-2xl font-black leading-none">${Number(g.total_personas)}</p>
                        <p class="text-[8px] font-bold uppercase tracking-widest">${Number(g.total_personas) === 1 ? 'persona' : 'personas'}</p>
                    </div>
                </div>
                <ul>${participantes}</ul>
            </article>`;
    }

    function pintar() {
        const contenedor = $('expEmpContenedor');
        if (!contenedor) return;

        document.querySelectorAll('.exp-filtro').forEach(boton => {
            const activo = boton.dataset.expFiltro === filtro;
            boton.className = `exp-filtro rounded-full border px-4 py-1.5 text-[11px] font-black uppercase tracking-wide transition-colors ${activo
                ? 'border-primary bg-primary text-white'
                : 'border-slate-200 bg-white text-slate-500 hover:border-primary hover:text-primary'}`;
            boton.setAttribute('aria-pressed', String(activo));
        });

        const lista = gruposFiltrados();
        if (!lista.length) {
            const vacio = filtro === 'hoy' ? 'No hay experiencias programadas para hoy.' : 'No hay experiencias programadas en este periodo.';
            contenedor.innerHTML = `<div class="rounded-xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center">
                <span class="material-symbols-outlined text-4xl text-slate-300">event_busy</span>
                <p class="mt-2 text-sm font-bold text-slate-400">${vacio}</p></div>`;
            return;
        }

        const porFecha = new Map();
        lista.forEach(g => {
            if (!porFecha.has(g.fecha)) porFecha.set(g.fecha, []);
            porFecha.get(g.fecha).push(g);
        });
        contenedor.innerHTML = [...porFecha.entries()].map(([fecha, items]) => `
            <div>
                <h3 class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">${esc(etiquetaFecha(fecha))}</h3>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">${items.map(tarjetaGrupo).join('')}</div>
            </div>`).join('');
    }

    function actualizarInsignia() {
        const badge = $('expEmpBadge');
        if (!badge) return;
        const gruposHoy = grupos.filter(g => g.fecha === hoy).length;
        badge.textContent = String(gruposHoy);
        badge.classList.toggle('hidden', gruposHoy === 0);
    }

    async function cargar() {
        if (cargando) return;
        cargando = true;
        try {
            const respuesta = await fetch(ENDPOINT, { cache: 'no-store', headers: { Accept: 'application/json' } });
            const texto = await respuesta.text();
            let datos;
            try {
                datos = JSON.parse(texto);
            } catch (e) {
                throw new Error(respuesta.status === 404
                    ? 'No se encontró controladores/experiencias_empleado.php. Copia ese archivo a la carpeta "controladores".'
                    : `El servidor no devolvió datos válidos (HTTP ${respuesta.status}).`);
            }
            if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudieron cargar las experiencias.');
            grupos = Array.isArray(datos.grupos) ? datos.grupos : [];
            hoy = String(datos.hoy || '');
            // Si hoy no hay nada y el usuario no eligió filtro, se muestra lo próximo.
            if (!filtroElegidoPorUsuario) {
                filtro = grupos.some(g => g.fecha === hoy) ? 'hoy' : 'todos';
            }
            ocultarMensaje();
            pintar();
            actualizarInsignia();
            const marca = $('expEmpActualizado');
            if (marca) marca.textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        } catch (error) {
            mostrarMensaje(error.message || 'No se pudieron cargar las experiencias.');
        } finally {
            cargando = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (!$('sec-experiencias')) return;
        $('expEmpRefrescar')?.addEventListener('click', cargar);
        $('expEmpFiltros')?.addEventListener('click', evento => {
            const boton = evento.target.closest('[data-exp-filtro]');
            if (!boton) return;
            filtro = boton.dataset.expFiltro;
            filtroElegidoPorUsuario = true;
            pintar();
        });
        document.querySelector('.empleado-sidebar [data-section="experiencias"]')?.addEventListener('click', cargar);
        cargar();
        setInterval(() => {
            if (!$('sec-experiencias').classList.contains('hidden')) cargar();
        }, 30000);
    });
})();