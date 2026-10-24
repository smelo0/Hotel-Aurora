<?php
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../configuracion/permiso.php';
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'roomservice.ver')) { return; }
?>
<section id="sec-roomservice" class="seccion-contenido hidden fade-in">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="text-2xl font-black text-primary tracking-tight">Room Service</h3>
                <p class="mt-1 text-xs text-slate-400">Pedidos de comidas y objetos a la habitación, y el catálogo que ven los huéspedes en su cuenta.</p>
            </div>
            <div class="flex items-center gap-3">
                <span id="rsContadorActivos" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600">0 pedidos activos</span>
                <button id="rsBtnNuevo" type="button" onclick="rsAbrirItem(null)" class="hidden rounded-lg bg-primary px-6 py-3 text-sm font-bold text-white shadow hover:brightness-110"><span class="material-symbols-outlined mr-1 align-middle text-sm">add</span> Nuevo producto</button>
            </div>
        </div>

        <div class="mb-6 flex gap-2 border-b border-slate-100">
            <button type="button" data-rs-tab="pedidos" onclick="rsTab('pedidos')" class="rs-tab -mb-px border-b-2 border-primary px-5 py-3 text-sm font-black text-primary">Pedidos</button>
            <button type="button" data-rs-tab="catalogo" onclick="rsTab('catalogo')" class="rs-tab -mb-px border-b-2 border-transparent px-5 py-3 text-sm font-black text-slate-400 hover:text-primary">Catálogo</button>
        </div>

        <div id="rsMensaje" class="mb-5 hidden rounded-lg px-4 py-3 text-sm font-bold" role="status"></div>

        <div id="rsPanelPedidos">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <label for="rsFiltroEstado" class="text-xs font-bold text-slate-500">Mostrar</label>
                <select id="rsFiltroEstado" onchange="rsRenderPedidos()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                    <option value="activos">Activos (pendientes y en preparación)</option>
                    <option value="por_cobrar">Por cobrar</option>
                    <option value="todos">Todos</option>
                </select>
                <span class="text-[11px] text-slate-400">Se actualiza solo cada 15 segundos.</span>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[1000px] text-left">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-4">Pedido</th><th class="px-5 py-4">Huésped y habitación</th><th class="px-5 py-4">Artículos</th><th class="px-5 py-4">Total y pago</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4 text-right">Acciones</th></tr>
                    </thead>
                    <tbody id="rsTablaPedidos" class="divide-y divide-slate-100 text-sm"></tbody>
                </table>
            </div>
        </div>

        <div id="rsPanelCatalogo" class="hidden">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[850px] text-left">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-4">Imagen</th><th class="px-5 py-4">Tipo y categoría</th><th class="px-5 py-4">Producto</th><th class="px-5 py-4">Precio</th><th class="px-5 py-4">Visible</th><th class="px-5 py-4 text-right">Acciones</th></tr>
                    </thead>
                    <tbody id="rsTablaCatalogo" class="divide-y divide-slate-100 text-sm"></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div id="rsModalItem" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-300">
    <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white p-8 shadow-2xl">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div><h2 id="rsTituloItem" class="text-2xl font-black text-primary">Nuevo producto</h2><p class="mt-1 text-xs text-slate-400">Lo que configures aquí es lo que verá el huésped en su catálogo.</p></div>
            <button type="button" onclick="rsCerrarItem()" class="text-slate-400 hover:text-primary"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="rsFormItem" class="space-y-4" enctype="multipart/form-data">
            <div id="rsErrorItem" class="hidden rounded-lg bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700" role="alert"></div>
            <input type="hidden" id="rsItemId">
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="rsItemTipo" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Sección</label>
                    <select id="rsItemTipo" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3"><option value="comida">Comidas</option><option value="objeto">Objetos</option></select></div>
                <div><label for="rsItemCategoria" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Categoría</label>
                    <input id="rsItemCategoria" type="text" maxlength="60" placeholder="Ej. Bebidas, Aseo personal" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3"></div>
            </div>
            <div><label for="rsItemNombre" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Nombre</label>
                <input id="rsItemNombre" type="text" maxlength="120" required class="w-full rounded-lg border-slate-200 bg-slate-50 p-3"></div>
            <div><label for="rsItemDescripcion" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Descripción</label>
                <textarea id="rsItemDescripcion" rows="3" maxlength="500" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3"></textarea></div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="rsItemPrecio" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Precio (COP)</label>
                    <input id="rsItemPrecio" type="number" min="0" max="9999999" step="1" value="0" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3">
                    <p class="mt-1 text-[10px] text-slate-400">0 = cortesía, sin cobro.</p></div>
                <div><label for="rsItemOrden" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Orden</label>
                    <input id="rsItemOrden" type="number" min="0" max="9999" step="1" value="0" class="w-full rounded-lg border-slate-200 bg-slate-50 p-3">
                    <p class="mt-1 text-[10px] text-slate-400">Menor = aparece primero.</p></div>
                <div class="flex items-center gap-2 pt-6"><input id="rsItemDisponible" type="checkbox" checked class="h-4 w-4 rounded"><label for="rsItemDisponible" class="text-xs font-bold text-slate-600">Visible para huéspedes</label></div>
            </div>
            <div>
                <label for="rsItemImagen" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Imagen (JPG, PNG o WebP; máximo 5 MB)</label>
                <input id="rsItemImagen" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                <div class="mt-3 flex items-center gap-3">
                    <img id="rsItemVista" class="hidden h-28 w-40 rounded-lg object-cover" alt="Vista previa">
                    <button id="rsItemQuitarImg" type="button" class="hidden rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Quitar imagen</button>
                </div>
            </div>
            <div class="flex gap-4 pt-2">
                <button type="button" onclick="rsCerrarItem()" class="flex-1 rounded-lg py-3 font-bold text-slate-400 hover:bg-slate-100">Cancelar</button>
                <button id="rsGuardarItem" type="submit" class="flex-1 rounded-lg bg-primary py-3 font-bold text-white shadow-lg hover:brightness-110 disabled:opacity-60">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const URL_RS = '../../controladores/room_service.php';
    const cop = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
    let datos = { items: [], pedidos: [], puede_gestionar: false, puede_cobrar: false };
    let imagenQuitar = false;

    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    const $ = id => document.getElementById(id);
    const rutaImagen = r => (r && /^assets\/uploads\/roomservice\/[a-f0-9]{32}\.(jpg|png|webp)$/.test(r)) ? '../../' + r : null;

    function mensaje(texto, error) {
        const caja = $('rsMensaje');
        caja.textContent = texto;
        caja.className = 'mb-5 rounded-lg px-4 py-3 text-sm font-bold ' + (error ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700');
        clearTimeout(mensaje.t);
        mensaje.t = setTimeout(() => caja.classList.add('hidden'), 5000);
    }

    async function pedir(opciones) {
        const r = await fetch(URL_RS + (opciones.query || ''), {
            method: opciones.cuerpo ? 'POST' : 'GET',
            headers: { Accept: 'application/json' },
            body: opciones.cuerpo || undefined
        });
        let json = {};
        try { json = await r.json(); } catch (_) {}
        if (!r.ok || json.status !== 'exito') throw new Error(json.mensaje || 'No se pudo completar la acción');
        return json;
    }

    function post(campos) {
        const f = new URLSearchParams(campos);
        f.set('csrf_token', CSRF_TOKEN);
        return pedir({ cuerpo: f });
    }

    async function cargar() {
        try {
            datos = await pedir({ query: '?accion=admin_datos' });
            $('rsBtnNuevo').classList.toggle('hidden', !datos.puede_gestionar);
            renderPedidos();
            renderCatalogo();
        } catch (e) { /* sesión bloqueada o sin red: se reintenta en el siguiente ciclo */ }
    }

    const badgeEstado = e => ({
        'Pendiente': 'bg-amber-100 text-amber-700', 'En preparación': 'bg-blue-100 text-blue-700',
        'Entregado': 'bg-emerald-100 text-emerald-700', 'Cancelado': 'bg-rose-100 text-rose-700'
    }[e] || 'bg-slate-100 text-slate-600');

    window.rsRenderPedidos = renderPedidos;
    function renderPedidos() {
        const filtro = $('rsFiltroEstado').value;
        const activos = datos.pedidos.filter(p => p.estado === 'Pendiente' || p.estado === 'En preparación');
        $('rsContadorActivos').textContent = activos.length + (activos.length === 1 ? ' pedido activo' : ' pedidos activos');
        let lista = datos.pedidos;
        if (filtro === 'activos') lista = activos;
        if (filtro === 'por_cobrar') lista = datos.pedidos.filter(p => p.puede_cobrar);
        if (!lista.length) {
            $('rsTablaPedidos').innerHTML = '<tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No hay pedidos para mostrar.</td></tr>';
            return;
        }
        $('rsTablaPedidos').innerHTML = lista.map(p => {
            const hora = new Date(p.creado_en.replace(' ', 'T')).toLocaleString('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
            const items = p.items.map(i => `<p class="text-xs text-slate-600">${esc(i.cantidad)} × ${esc(i.nombre)}</p>`).join('');
            const pago = p.estado_pago === 'Pagado'
                ? `<span class="text-xs font-bold text-emerald-600">Pagado · ${esc(p.metodo_pago || '')}</span>`
                : p.estado_pago === 'Sin costo' ? '<span class="text-xs font-bold text-slate-400">Cortesía</span>'
                : `<span class="text-xs font-bold ${p.estado === 'Cancelado' ? 'text-slate-400' : 'text-amber-600'}">${p.estado === 'Cancelado' ? 'Sin cobro' : 'Pendiente de pago'}</span>`;
            let estado = `<span class="rounded-full px-3 py-1 text-[10px] font-black uppercase ${badgeEstado(p.estado)}">${esc(p.estado)}</span>`;
            if (datos.puede_gestionar && p.estado !== 'Cancelado') {
                estado = `<select data-rs-estado="${p.id_pedido}" class="rounded-lg border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                    ${['Pendiente', 'En preparación', 'Entregado'].map(e => `<option ${e === p.estado ? 'selected' : ''}>${e}</option>`).join('')}</select>`;
            }
            const acciones = [];
            if (datos.puede_cobrar && p.puede_cobrar) acciones.push(`<span class="text-xs font-bold text-slate-400">Se cobra desde la reserva${p.cod_res ? ' #' + p.cod_res : ''}</span>`);
            if (datos.puede_cobrar && p.folio) acciones.push(`<button type="button" data-rs-factura="${esc(p.folio)}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Factura</button>`);
            if (datos.puede_gestionar && p.puede_cancelar) acciones.push(`<button type="button" data-rs-cancelar="${p.id_pedido}" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Cancelar</button>`);
            return `<tr class="align-top hover:bg-slate-50/70">
                <td class="px-5 py-4"><p class="font-black text-slate-800">#${p.id_pedido}</p><p class="mt-1 text-xs text-slate-400">${esc(hora)}</p></td>
                <td class="px-5 py-4"><p class="font-bold text-slate-800">${esc(p.cliente)}</p>
                    <p class="mt-1 text-xs text-slate-500">${p.num_hab ? 'Habitación ' + esc(p.num_hab) : 'Sin habitación'}${p.cod_res ? ' · Reserva #' + esc(p.cod_res) : ''}</p></td>
                <td class="px-5 py-4">${items}${p.notas ? `<p class="mt-1 text-xs italic text-slate-400">“${esc(p.notas)}”</p>` : ''}</td>
                <td class="px-5 py-4"><p class="font-black text-primary">${cop.format(p.total)}</p>${pago}</td>
                <td class="px-5 py-4">${estado}</td>
                <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2">${acciones.join('') || '<span class="text-xs text-slate-300">—</span>'}</div></td></tr>`;
        }).join('');
    }

    function renderCatalogo() {
        if (!datos.items.length) {
            $('rsTablaCatalogo').innerHTML = '<tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Aún no hay productos. Crea el primero con “Nuevo producto”.</td></tr>';
            return;
        }
        $('rsTablaCatalogo').innerHTML = datos.items.map(i => {
            const img = rutaImagen(i.imagen);
            return `<tr class="hover:bg-slate-50/70">
                <td class="px-5 py-3">${img ? `<img src="${esc(img)}" alt="" class="h-14 w-20 rounded-lg object-cover">` : `<div class="flex h-14 w-20 items-center justify-center rounded-lg bg-slate-100 text-slate-300"><span class="material-symbols-outlined">${i.tipo === 'comida' ? 'restaurant' : 'inventory_2'}</span></div>`}</td>
                <td class="px-5 py-3"><p class="text-xs font-black uppercase tracking-wider text-primary">${i.tipo === 'comida' ? 'Comidas' : 'Objetos'}</p><p class="text-xs text-slate-500">${esc(i.categoria)}</p></td>
                <td class="px-5 py-3"><p class="font-bold text-slate-800">${esc(i.nombre)}</p><p class="max-w-xs truncate text-xs text-slate-400">${esc(i.descripcion)}</p></td>
                <td class="px-5 py-3 font-black text-slate-700">${i.precio > 0 ? cop.format(i.precio) : 'Cortesía'}</td>
                <td class="px-5 py-3">${datos.puede_gestionar ? `<input type="checkbox" data-rs-visible="${i.id}" ${i.disponible ? 'checked' : ''} class="h-4 w-4 rounded" aria-label="Visible para huéspedes">` : (i.disponible ? 'Sí' : 'No')}</td>
                <td class="px-5 py-3"><div class="flex justify-end gap-2">${datos.puede_gestionar ? `
                    <button type="button" data-rs-editar="${i.id}" class="rounded-lg bg-amber-400 px-3 py-2 text-white hover:bg-amber-500" title="Editar"><span class="material-symbols-outlined text-sm">edit</span></button>
                    <button type="button" data-rs-eliminar="${i.id}" class="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600" title="Eliminar"><span class="material-symbols-outlined text-sm">delete</span></button>` : ''}</div></td></tr>`;
        }).join('');
    }

    window.rsTab = function (tab) {
        $('rsPanelPedidos').classList.toggle('hidden', tab !== 'pedidos');
        $('rsPanelCatalogo').classList.toggle('hidden', tab !== 'catalogo');
        document.querySelectorAll('.rs-tab').forEach(b => {
            const activo = b.dataset.rsTab === tab;
            b.classList.toggle('border-primary', activo); b.classList.toggle('text-primary', activo);
            b.classList.toggle('border-transparent', !activo); b.classList.toggle('text-slate-400', !activo);
        });
    };

    // ---- Producto ----
    window.rsAbrirItem = function (item) {
        $('rsFormItem').reset();
        $('rsErrorItem').classList.add('hidden');
        imagenQuitar = false;
        $('rsTituloItem').textContent = item ? 'Editar producto' : 'Nuevo producto';
        $('rsItemId').value = item ? item.id : '';
        $('rsItemTipo').value = item ? item.tipo : 'comida';
        $('rsItemCategoria').value = item ? item.categoria : '';
        $('rsItemNombre').value = item ? item.nombre : '';
        $('rsItemDescripcion').value = item ? item.descripcion : '';
        $('rsItemPrecio').value = item ? Math.round(item.precio) : 0;
        $('rsItemOrden').value = item ? item.orden : 0;
        $('rsItemDisponible').checked = item ? item.disponible : true;
        const img = item ? rutaImagen(item.imagen) : null;
        $('rsItemVista').src = img || '';
        $('rsItemVista').classList.toggle('hidden', !img);
        $('rsItemQuitarImg').classList.toggle('hidden', !img);
        $('rsModalItem').classList.remove('modal-oculto'); $('rsModalItem').classList.add('modal-visible');
    };
    window.rsCerrarItem = function () { $('rsModalItem').classList.add('modal-oculto'); $('rsModalItem').classList.remove('modal-visible'); };

    $('rsItemImagen').addEventListener('change', e => {
        const f = e.target.files[0];
        if (!f) return;
        imagenQuitar = false;
        $('rsItemVista').src = URL.createObjectURL(f);
        $('rsItemVista').classList.remove('hidden');
        $('rsItemQuitarImg').classList.remove('hidden');
    });
    $('rsItemQuitarImg').addEventListener('click', () => {
        imagenQuitar = true; $('rsItemImagen').value = '';
        $('rsItemVista').classList.add('hidden'); $('rsItemQuitarImg').classList.add('hidden');
    });

    $('rsFormItem').addEventListener('submit', async e => {
        e.preventDefault();
        const f = new FormData();
        f.set('accion', 'guardar_item'); f.set('csrf_token', CSRF_TOKEN);
        f.set('id', $('rsItemId').value); f.set('tipo', $('rsItemTipo').value);
        f.set('categoria', $('rsItemCategoria').value); f.set('nombre', $('rsItemNombre').value);
        f.set('descripcion', $('rsItemDescripcion').value); f.set('precio', $('rsItemPrecio').value);
        f.set('orden', $('rsItemOrden').value); f.set('disponible', $('rsItemDisponible').checked ? '1' : '0');
        if (imagenQuitar) f.set('quitar_imagen', '1');
        if ($('rsItemImagen').files[0]) f.set('imagen', $('rsItemImagen').files[0]);
        $('rsGuardarItem').disabled = true;
        try {
            const r = await pedir({ cuerpo: f });
            rsCerrarItem(); mensaje(r.mensaje); await cargar();
        } catch (err) {
            $('rsErrorItem').textContent = err.message; $('rsErrorItem').classList.remove('hidden');
        } finally { $('rsGuardarItem').disabled = false; }
    });

    // ---- Delegación de eventos (las tablas se vuelven a pintar) ----
    document.addEventListener('click', async e => {
        const t = e.target.closest('[data-rs-factura],[data-rs-cancelar],[data-rs-editar],[data-rs-eliminar]');
        if (!t || !$('sec-roomservice').contains(t)) return;
        try {
            if (t.dataset.rsFactura) {
                const ventana = window.open('', '_blank');
                if (!ventana) { mensaje('Permite las ventanas emergentes para ver la factura.', true); return; }
                try {
                    const r = await pedir({ query: '?accion=factura&folio=' + encodeURIComponent(t.dataset.rsFactura) });
                    imprimirFacturaReserva(r.factura, ventana);
                } catch (err) { ventana.close(); throw err; }
            } else if (t.dataset.rsCancelar) {
                if (!confirm('¿Cancelar el pedido #' + t.dataset.rsCancelar + '?')) return;
                const r = await post({ accion: 'cancelar_pedido_admin', id_pedido: t.dataset.rsCancelar });
                mensaje(r.mensaje); await cargar();
            } else if (t.dataset.rsEditar) {
                rsAbrirItem(datos.items.find(x => x.id === Number(t.dataset.rsEditar)));
            } else if (t.dataset.rsEliminar) {
                const item = datos.items.find(x => x.id === Number(t.dataset.rsEliminar));
                if (!item || !confirm('¿Eliminar “' + item.nombre + '” del catálogo? Los pedidos ya hechos no se alteran.')) return;
                const r = await post({ accion: 'eliminar_item', id: item.id });
                mensaje(r.mensaje); await cargar();
            }
        } catch (err) { mensaje(err.message, true); }
    });

    document.addEventListener('change', async e => {
        const sel = e.target.closest('[data-rs-estado]');
        const chk = e.target.closest('[data-rs-visible]');
        try {
            if (sel) {
                await post({ accion: 'cambiar_estado', id_pedido: sel.dataset.rsEstado, estado: sel.value });
                mensaje('Estado del pedido #' + sel.dataset.rsEstado + ' actualizado.'); await cargar();
            } else if (chk) {
                await post({ accion: 'alternar_item', id: chk.dataset.rsVisible, disponible: chk.checked ? '1' : '0' });
                mensaje(chk.checked ? 'El producto ya es visible para los huéspedes.' : 'El producto se ocultó del catálogo.'); await cargar();
            }
        } catch (err) { mensaje(err.message, true); await cargar(); }
    });

    window.addEventListener('roomservice:actualizar', cargar);
    cargar();
    setInterval(() => {
        const seccion = $('sec-roomservice');
        if (!document.hidden && seccion && !seccion.classList.contains('hidden')
            && $('rsModalItem').classList.contains('modal-oculto') && $('rsModalCobro').classList.contains('modal-oculto')
            && !document.activeElement.matches?.('[data-rs-estado]')) cargar();
    }, 15000);
})();
</script>