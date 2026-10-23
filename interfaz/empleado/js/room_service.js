// Room service en la interfaz del empleado: ver pedidos y cambiar su estado.
(() => {
    const ENDPOINT = '../../controladores/room_service_empleado.php';
    const ESTADOS = ['Pendiente', 'En preparación', 'Entregado'];
    const COLUMNAS = { 'Pendiente': 'rsColPendiente', 'En preparación': 'rsColPreparacion', 'Entregado': 'rsColEntregado' };
    const CONTADORES = { 'Pendiente': 'rsContPendiente', 'En preparación': 'rsContPreparacion', 'Entregado': 'rsContEntregado' };
    const CLASE_ACTIVA = {
        'Pendiente': 'bg-amber-500 text-white border-amber-500',
        'En preparación': 'bg-sky-600 text-white border-sky-600',
        'Entregado': 'bg-emerald-600 text-white border-emerald-600',
    };

    let pedidos = [];
    let cargando = false;
    let cambiando = false;
    let idsConocidos = null;
    let temporizadorMensaje = null;

    const $ = id => document.getElementById(id);
    const esc = valor => String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));

    function mostrarMensaje(texto, error = false) {
        const caja = $('rsEmpleadoMensaje');
        if (!caja) return;
        caja.textContent = texto;
        caja.className = `mb-4 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`;
        clearTimeout(temporizadorMensaje);
        temporizadorMensaje = setTimeout(() => caja.classList.add('hidden'), 4500);
    }

    async function leerJson(respuesta) {
        const texto = await respuesta.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            throw new Error(respuesta.status === 404
                ? 'No se encontró controladores/room_service_empleado.php. Copia ese archivo a la carpeta "controladores".'
                : `El servidor no devolvió datos válidos (HTTP ${respuesta.status}).`);
        }
    }

    function tarjetaPedido(p) {
        const items = p.items.map(i => `<li class="flex justify-between gap-3"><span>${esc(i.nombre)}</span><span class="font-black text-slate-700">× ${Number(i.cantidad)}</span></li>`).join('');
        const tiempo = p.estado === 'Entregado'
            ? `Pedido hace ${p.minutos_transcurridos} min`
            : `Hace ${p.minutos_transcurridos} min · estimado ${p.minutos_estimados} min`;
        const atrasado = p.estado !== 'Entregado' && p.minutos_transcurridos > p.minutos_estimados;
        const botones = ESTADOS.map(estado => `
            <button type="button" data-rs-estado="${esc(estado)}" data-rs-pedido="${p.id_pedido}" ${estado === p.estado ? 'disabled aria-pressed="true"' : ''}
                class="flex-1 rounded-lg border px-2 py-2 text-[10px] font-black uppercase tracking-wide transition-colors ${estado === p.estado
                    ? CLASE_ACTIVA[estado]
                    : 'border-slate-200 bg-white text-slate-500 hover:border-primary hover:text-primary'}">${esc(estado)}</button>`).join('');
        return `
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm ${p.nuevo ? 'ring-2 ring-primary/40' : ''}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-black text-primary">${p.num_hab ? 'Hab. ' + esc(p.num_hab) : 'Sin habitación'}</p>
                        <p class="text-xs font-bold text-slate-500">${esc(p.cliente || 'Huésped')} · Pedido #${p.id_pedido}</p>
                    </div>
                    ${p.nuevo ? '<span class="rounded-full bg-primary px-2 py-0.5 text-[9px] font-black uppercase text-white">Nuevo</span>' : ''}
                </div>
                <ul class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm text-slate-600">${items || '<li class="text-slate-400">Sin detalle</li>'}</ul>
                ${p.notas ? `<p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">Nota: ${esc(p.notas)}</p>` : ''}
                <p class="mt-3 text-[11px] font-bold ${atrasado ? 'text-red-600' : 'text-slate-400'}">${esc(tiempo)}${atrasado ? ' · con retraso' : ''}</p>
                <div class="mt-3 flex gap-2">${botones}</div>
            </article>`;
    }

    function pintar() {
        Object.keys(COLUMNAS).forEach(estado => {
            const lista = pedidos.filter(p => p.estado === estado);
            const columna = $(COLUMNAS[estado]);
            if (!columna) return;
            $(CONTADORES[estado]).textContent = String(lista.length);
            columna.innerHTML = lista.map(tarjetaPedido).join('')
                || '<p class="rounded-lg border border-dashed border-slate-200 bg-white/60 px-3 py-6 text-center text-xs font-bold text-slate-400">Sin pedidos</p>';
        });
        const activos = pedidos.filter(p => p.estado !== 'Entregado').length;
        const insignia = $('rsEmpleadoBadge');
        if (insignia) {
            insignia.textContent = String(activos);
            insignia.classList.toggle('hidden', activos === 0);
        }
    }

    async function cargar(silencioso = true) {
        if (cargando || cambiando) return;
        cargando = true;
        try {
            const respuesta = await fetch(`${ENDPOINT}?accion=pedidos`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const datos = await leerJson(respuesta);
            if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudieron cargar los pedidos');
            const nuevosIds = new Set(datos.pedidos.map(p => p.id_pedido));
            pedidos = datos.pedidos.map(p => ({ ...p, nuevo: idsConocidos !== null && !idsConocidos.has(p.id_pedido) && p.estado === 'Pendiente' }));
            idsConocidos = nuevosIds;
            pintar();
            const reloj = $('rsEmpleadoActualizado');
            if (reloj) reloj.textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        } catch (error) {
            if (!silencioso) mostrarMensaje(error.message, true);
            else if (!pedidos.length) mostrarMensaje(error.message, true);
        } finally {
            cargando = false;
        }
    }

    async function cambiarEstado(idPedido, estado, boton) {
        if (cambiando) return;
        cambiando = true;
        boton.disabled = true;
        try {
            const respuesta = await fetch(ENDPOINT, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ accion: 'cambiar_estado', id_pedido: String(idPedido), estado, csrf_token: CSRF_TOKEN }),
            });
            const datos = await leerJson(respuesta);
            if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudo cambiar el estado');
            const pedido = pedidos.find(p => p.id_pedido === idPedido);
            if (pedido) { pedido.estado = estado; pedido.nuevo = false; }
            pintar();
            mostrarMensaje(`Pedido #${idPedido}: ${estado}.`);
        } catch (error) {
            mostrarMensaje(error.message, true);
        } finally {
            cambiando = false;
            await cargar(true);
        }
    }

    document.addEventListener('click', evento => {
        const boton = evento.target.closest('[data-rs-estado]');
        if (boton && !boton.disabled) {
            cambiarEstado(Number(boton.dataset.rsPedido), boton.dataset.rsEstado, boton);
            return;
        }
        if (evento.target.closest('#rsEmpleadoRefrescar')) cargar(false);
    });

    document.addEventListener('DOMContentLoaded', () => {
        cargar(false);
        setInterval(() => { if (!document.hidden) cargar(true); }, 8000);
    });
})();