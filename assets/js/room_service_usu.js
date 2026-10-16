(function () {
    'use strict';

    const seccion = document.getElementById('room-service');
    if (!seccion) return;

    const URL_RS = 'controladores/room_service.php';
    const csrf = () => (window.PORTAL_CONFIG && window.PORTAL_CONFIG.csrfToken) || '';
    const $ = id => document.getElementById(id);
    const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    const rutaImagen = r => (r && /^assets\/uploads\/roomservice\/[a-f0-9]{32}\.(jpg|png|webp)$/.test(r)) ? r : null;

    const estado = {
        catalogo: [], habitaciones: [], pedidos: [], carrito: {},
        tipo: 'comida', categoria: '', cargadoEn: Date.now(), enviando: false, bloqueado: false
    };

    function aviso(texto, tipo) {
        const caja = $('rsAviso');
        const clases = {
            ok: 'border-emerald-300/40 bg-emerald-500/20 text-emerald-100',
            error: 'border-rose-300/40 bg-rose-500/20 text-rose-100',
            info: 'border-white/20 bg-white/10 text-white'
        };
        caja.className = 'mb-5 rounded-2xl border px-5 py-4 text-sm font-bold ' + (clases[tipo] || clases.info);
        caja.textContent = texto;
        clearTimeout(aviso.t);
        if (tipo !== 'ok') aviso.t = setTimeout(() => caja.classList.add('hidden'), 8000);
        caja.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    async function llamar(opciones) {
        const r = await fetch(URL_RS + (opciones.query || ''), {
            method: opciones.cuerpo ? 'POST' : 'GET',
            headers: { Accept: 'application/json' },
            body: opciones.cuerpo || undefined
        });
        let json = {};
        try { json = await r.json(); } catch (_) {}
        if (!r.ok || json.status !== 'exito') {
            const error = new Error(json.mensaje || 'No se pudo completar la acción.');
            error.codigo = r.status;
            throw error;
        }
        return json;
    }

    async function cargar() {
        if (estado.bloqueado) return;
        try {
            const d = await llamar({ query: '?accion=cliente_datos' });
            estado.catalogo = d.catalogo;
            estado.habitaciones = d.habitaciones;
            estado.pedidos = d.pedidos;
            estado.cargadoEn = Date.now();
            $('rsEta').textContent = d.eta_minutos;
            $('rsVentana').textContent = d.ventana_cancelacion_minutos;
            // Quita del carrito lo que el administrador ya ocultó o eliminó.
            Object.keys(estado.carrito).forEach(id => {
                if (!estado.catalogo.some(i => String(i.id) === id)) delete estado.carrito[id];
            });
            pintar();
        } catch (e) {
            if (e.codigo === 423 || e.codigo === 440 || e.codigo === 401) estado.bloqueado = true;
        }
    }

    function pintar() {
        const hayEstancia = estado.habitaciones.length > 0;
        $('rsSinEstancia').classList.toggle('hidden', hayEstancia);
        $('rsContenido').classList.toggle('hidden', !hayEstancia);
        pintarTabs();
        pintarCatalogo();
        pintarCarrito();
        pintarPedidos();
    }

    function pintarTabs() {
        seccion.querySelectorAll('[data-rs-tipo]').forEach(b => {
            const activo = b.dataset.rsTipo === estado.tipo;
            b.className = 'rounded-full px-5 py-2 text-sm font-black transition ' +
                (activo ? 'bg-emerald-400 text-emerald-950' : 'border border-white/20 text-white hover:bg-white/10');
            b.setAttribute('aria-selected', activo ? 'true' : 'false');
        });
    }

    function pintarCatalogo() {
        const delTipo = estado.catalogo.filter(i => i.tipo === estado.tipo);
        const categorias = [...new Set(delTipo.map(i => i.categoria))];
        if (estado.categoria && !categorias.includes(estado.categoria)) estado.categoria = '';
        $('rsCategorias').innerHTML = categorias.length > 1
            ? ['', ...categorias].map(c => `<button type="button" data-rs-cat="${esc(c)}" class="rounded-full px-3 py-1 text-xs font-bold ${c === estado.categoria ? 'bg-white text-emerald-950' : 'border border-white/20 text-white/80 hover:bg-white/10'}">${c === '' ? 'Todo' : esc(c)}</button>`).join('')
            : '';
        const lista = delTipo.filter(i => !estado.categoria || i.categoria === estado.categoria);
        if (!lista.length) {
            $('rsCatalogo').innerHTML = '<p class="col-span-full rounded-2xl border border-dashed border-white/20 p-8 text-center text-sm text-white/60">Por ahora no hay productos disponibles en esta sección.</p>';
            return;
        }
        $('rsCatalogo').innerHTML = lista.map(i => {
            const img = rutaImagen(i.imagen);
            const cantidad = estado.carrito[i.id] || 0;
            return `<article class="flex flex-col overflow-hidden rounded-3xl bg-emerald-950/50">
                ${img ? `<img src="${esc(img)}" alt="${esc(i.nombre)}" loading="lazy" class="h-36 w-full object-cover">`
                      : `<div class="flex h-36 items-center justify-center bg-emerald-900/40 text-emerald-200/60"><span class="material-symbols-outlined text-5xl">${i.tipo === 'comida' ? 'restaurant' : 'inventory_2'}</span></div>`}
                <div class="flex flex-1 flex-col p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-emerald-300">${esc(i.categoria)}</p>
                    <h4 class="mt-1 text-base font-black text-white">${esc(i.nombre)}</h4>
                    <p class="mt-1 flex-1 text-xs text-white/60">${esc(i.descripcion)}</p>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-sm font-black text-white">${i.precio > 0 ? cop.format(i.precio) : 'Cortesía'}</span>
                        ${cantidad > 0
                            ? `<div class="flex items-center gap-2"><button type="button" data-rs-menos="${i.id}" class="h-8 w-8 rounded-full bg-white/15 font-black text-white hover:bg-white/25" aria-label="Quitar uno">−</button><span class="w-5 text-center text-sm font-black text-white">${cantidad}</span><button type="button" data-rs-mas="${i.id}" class="h-8 w-8 rounded-full bg-emerald-400 font-black text-emerald-950 hover:bg-emerald-300" aria-label="Agregar uno">+</button></div>`
                            : `<button type="button" data-rs-mas="${i.id}" class="rounded-full bg-emerald-400 px-4 py-2 text-xs font-black text-emerald-950 hover:bg-emerald-300">Agregar</button>`}
                    </div>
                </div>
            </article>`;
        }).join('');
    }

    function lineasCarrito() {
        return Object.entries(estado.carrito)
            .map(([id, cantidad]) => ({ item: estado.catalogo.find(i => String(i.id) === id), cantidad }))
            .filter(l => l.item);
    }

    function pintarCarrito() {
        const lineas = lineasCarrito();
        const total = lineas.reduce((s, l) => s + l.item.precio * l.cantidad, 0);
        const unidades = lineas.reduce((s, l) => s + l.cantidad, 0);
        $('rsCarritoLineas').innerHTML = lineas.length
            ? lineas.map(l => `<div class="flex items-center justify-between gap-3 text-sm">
                <div class="min-w-0"><p class="truncate font-bold text-white">${esc(l.item.nombre)}</p><p class="text-xs text-white/50">${l.cantidad} × ${l.item.precio > 0 ? cop.format(l.item.precio) : 'Cortesía'}</p></div>
                <div class="flex items-center gap-2"><span class="font-black text-white">${cop.format(l.item.precio * l.cantidad)}</span>
                <button type="button" data-rs-quitar="${l.item.id}" class="text-white/50 hover:text-rose-300" aria-label="Quitar ${esc(l.item.nombre)}"><span class="material-symbols-outlined text-base">close</span></button></div></div>`).join('')
            : '<p class="text-sm text-white/50">Agrega comidas u objetos desde el catálogo.</p>';
        $('rsTotal').textContent = cop.format(total);

        const selector = $('rsHabitacion');
        const previa = selector.value;
        selector.innerHTML = estado.habitaciones.map(h => `<option value="${h.cod_hab}">Habitación ${esc(h.num_hab)} · ${esc(h.tipo_hab)}</option>`).join('');
        if (previa && estado.habitaciones.some(h => String(h.cod_hab) === previa)) selector.value = previa;
        $('rsHabitacionBox').classList.toggle('hidden', estado.habitaciones.length < 2);

        $('rsPedir').disabled = lineas.length === 0 || estado.enviando;
        $('rsPedir').textContent = estado.enviando ? 'Enviando…' : 'Pedir a mi habitación';

        const flotante = $('rsIrCarrito');
        flotante.classList.toggle('hidden', unidades === 0);
        flotante.classList.toggle('flex', unidades > 0);
        $('rsIrCarritoTexto').textContent = `Ver pedido (${unidades}) · ${cop.format(total)}`;
    }

    const colorEstado = e => ({
        'Pendiente': 'bg-amber-100 text-amber-700', 'En preparación': 'bg-cyan-100 text-cyan-700',
        'Entregado': 'bg-emerald-100 text-emerald-700', 'Cancelado': 'bg-rose-100 text-rose-700'
    }[e] || 'bg-slate-100 text-slate-700');

    const reloj = s => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    const restante = s => Math.max(0, s - Math.floor((Date.now() - estado.cargadoEn) / 1000));

    function pintarPedidos() {
        if (!estado.pedidos.length) {
            $('rsPedidos').innerHTML = '<p class="rounded-2xl border border-dashed border-white/20 p-6 text-center text-sm text-white/60">Todavía no has hecho pedidos de room service.</p>';
            return;
        }
        $('rsPedidos').innerHTML = estado.pedidos.map(p => {
            const activo = p.estado === 'Pendiente' || p.estado === 'En preparación';
            const hora = new Date(p.creado_en.replace(' ', 'T')).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
            const lineas = p.items.map(i => `${esc(i.cantidad)} × ${esc(i.nombre)}`).join(' · ');
            return `<article class="rounded-2xl bg-emerald-950/50 p-4" data-rs-pedido="${p.id_pedido}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="font-black text-white">Pedido #${p.id_pedido} <span class="font-semibold text-white/50">· ${esc(hora)}${p.num_hab ? ' · Habitación ' + esc(p.num_hab) : ''}</span></p>
                        <p class="mt-1 text-sm text-emerald-100">${lineas}</p>
                        ${p.notas ? `<p class="mt-1 text-xs italic text-white/50">“${esc(p.notas)}”</p>` : ''}</div>
                    <div class="text-right"><span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[0.12em] ${colorEstado(p.estado)}">${esc(p.estado)}</span>
                        <p class="mt-1 text-sm font-black text-white">${p.total > 0 ? cop.format(p.total) : 'Cortesía'}</p></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                    ${activo ? `<span class="text-white/70" data-rs-llegada="${p.segundos_llegada}"></span>` : ''}
                    ${p.puede_cancelar ? `<button type="button" data-rs-cancelar="${p.id_pedido}" data-rs-seg="${p.segundos_cancelacion}" class="rounded-lg border border-rose-300/50 px-3 py-2 font-bold text-rose-100 hover:bg-rose-500/20"></button>` : (activo ? '<span class="text-white/40">Ya no se puede cancelar</span>' : '')}
                </div>
            </article>`;
        }).join('');
        actualizarReloj();
    }

    function actualizarReloj() {
        let vencido = false;
        seccion.querySelectorAll('[data-rs-llegada]').forEach(el => {
            const s = restante(Number(el.dataset.rsLlegada));
            el.textContent = s > 0 ? `Llega en aprox. ${reloj(s)}` : 'Debería estar llegando a tu habitación';
        });
        seccion.querySelectorAll('[data-rs-cancelar]').forEach(el => {
            const s = restante(Number(el.dataset.rsSeg));
            if (s <= 0) vencido = true;
            else el.textContent = `Cancelar pedido (${reloj(s)})`;
        });
        // Al terminar los 3 minutos el botón desaparece (el servidor también lo impide).
        if (vencido) cargar();
    }

    // ---- Acciones ----
    function cambiarCantidad(id, delta) {
        const nueva = (estado.carrito[id] || 0) + delta;
        if (nueva <= 0) delete estado.carrito[id];
        else estado.carrito[id] = Math.min(20, nueva);
        pintarCatalogo();
        pintarCarrito();
    }

    async function hacerPedido() {
        const lineas = lineasCarrito();
        if (!lineas.length || estado.enviando) return;
        estado.enviando = true;
        pintarCarrito();
        try {
            const f = new URLSearchParams({
                accion: 'crear_pedido',
                csrf_token: csrf(),
                cod_hab: $('rsHabitacion').value,
                notas: $('rsNotas').value.trim(),
                items: JSON.stringify(lineas.map(l => ({ id: l.item.id, cantidad: l.cantidad })))
            });
            const r = await llamar({ cuerpo: f });
            estado.carrito = {};
            $('rsNotas').value = '';
            aviso(r.mensaje, 'ok');
            if (window.Swal) {
                Swal.fire({ icon: 'success', title: '¡Pedido recibido!', text: r.mensaje, confirmButtonColor: '#10b981' });
            }
            await cargar();
        } catch (e) {
            aviso(e.message, 'error');
            await cargar();
        } finally {
            estado.enviando = false;
            pintarCarrito();
        }
    }

    async function cancelarPedido(id, boton) {
        boton.disabled = true;
        try {
            const r = await llamar({ cuerpo: new URLSearchParams({ accion: 'cancelar_pedido', id_pedido: id, csrf_token: csrf() }) });
            aviso(r.mensaje, 'ok');
        } catch (e) {
            aviso(e.message, 'error');
        }
        await cargar();
    }

    seccion.addEventListener('click', e => {
        const t = e.target.closest('button');
        if (!t) return;
        if (t.dataset.rsTipo) { estado.tipo = t.dataset.rsTipo; estado.categoria = ''; pintar(); }
        else if (t.dataset.rsCat !== undefined) { estado.categoria = t.dataset.rsCat; pintarCatalogo(); }
        else if (t.dataset.rsMas) cambiarCantidad(t.dataset.rsMas, 1);
        else if (t.dataset.rsMenos) cambiarCantidad(t.dataset.rsMenos, -1);
        else if (t.dataset.rsQuitar) { delete estado.carrito[t.dataset.rsQuitar]; pintarCatalogo(); pintarCarrito(); }
        else if (t.dataset.rsCancelar) cancelarPedido(t.dataset.rsCancelar, t);
        else if (t.id === 'rsPedir') hacerPedido();
    });
    $('rsIrCarrito').addEventListener('click', () => $('rsCarrito').scrollIntoView({ behavior: 'smooth', block: 'center' }));

    cargar();
    setInterval(actualizarReloj, 1000);
    setInterval(() => { if (!document.hidden && !estado.enviando) cargar(); }, 20000);
})();
