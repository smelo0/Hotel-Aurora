<?php
/**
 * Historial de facturas (ver e imprimir). Se incluye en Finanzas (admin) y en Recepción.
 * Usa controladores/facturas.php y la función imprimirFacturaReserva() de admin.js.
 * Variable opcional: $facturasApiUrl (por defecto '../../controladores/facturas.php').
 */
$facturasApiUrl = $facturasApiUrl ?? '../../controladores/facturas.php';
?>
<div id="facturasHistorial" data-api="<?php echo htmlspecialchars($facturasApiUrl, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-xl border border-primary/10 bg-white p-8 shadow-sm">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h3 class="text-2xl font-black tracking-tight text-primary">Historial de facturas</h3>
            <p id="facturasNota" class="mt-1 text-sm text-slate-500">Cargando…</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <input id="facturasBuscar" type="search" placeholder="Buscar folio, huésped o reserva" class="hidden rounded-lg border border-slate-200 px-3 py-2 text-sm">
            <button id="facturasToggle" type="button" aria-expanded="true" aria-controls="facturasContenido" title="Ocultar historial de facturas" aria-label="Ocultar historial de facturas" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:text-primary">
                <span class="material-symbols-outlined text-xl">expand_less</span>
            </button>
            <button id="facturasSolicitar" type="button" class="hidden rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Solicitar ver todas</button>
        </div>
    </div>

    <div id="facturasContenido">
    <div id="facturasPendientes" class="mb-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-4"></div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-4 py-3">Folio</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Huésped</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Método</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="facturasCuerpo" class="divide-y divide-slate-100 text-sm"></tbody>
        </table>
    </div>
    </div>
</div>
<script>
(() => {
    const raiz = document.getElementById('facturasHistorial');
    if (!raiz || raiz.dataset.listo) return;
    raiz.dataset.listo = '1';

    const API = raiz.dataset.api;
    const cuerpo = document.getElementById('facturasCuerpo');
    const nota = document.getElementById('facturasNota');
    const buscar = document.getElementById('facturasBuscar');
    const botonSolicitar = document.getElementById('facturasSolicitar');
    const bloquePendientes = document.getElementById('facturasPendientes');
    const etiquetas = { alojamiento: 'Alojamiento', experiencia: 'Experiencia', room_service: 'Room service', adicional: 'Cobro adicional', reembolso: 'Reembolso' };
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const cop = v => (Number(v) < 0 ? '−$' : '$') + Math.abs(Number(v)).toLocaleString('es-CO', { maximumFractionDigits: 0 });
    const fechaCorta = v => { const d = new Date(String(v).replace(' ', 'T')); return Number.isNaN(d.getTime()) ? esc(v) : d.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' }); };
    const cabeceras = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

    async function llamar(url, opciones = {}) {
        const respuesta = await fetch(url, { credentials: 'same-origin', headers: cabeceras, ...opciones });
        const datos = await respuesta.json().catch(() => ({}));
        if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudo completar la acción.');
        return datos;
    }

    async function cargar() {
        try {
            const datos = await llamar(`${API}?accion=listar&q=${encodeURIComponent(buscar.value.trim())}`);
            pintar(datos);
        } catch (error) {
            nota.textContent = error.message;
            cuerpo.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No se pudo cargar el historial.</td></tr>';
        }
    }

    function pintar(d) {
        buscar.classList.toggle('hidden', !d.acceso_total);
        botonSolicitar.classList.add('hidden');
        if (d.acceso_total) {
            nota.textContent = d.acceso_temporal
                ? `Acceso completo concedido hasta las ${String(d.vence_en).slice(11, 16)}.`
                : 'Historial completo de facturas.';
        } else if (d.solicitud_estado === 'Pendiente') {
            nota.textContent = `Mostrando las últimas ${d.limite} facturas. Tu solicitud para ver todas está pendiente de aprobación.`;
        } else {
            nota.textContent = `Mostrando las últimas ${d.limite} facturas.` + (d.solicitud_estado === 'Rechazada' ? ' Tu última solicitud fue rechazada.' : ' Para ver todas, solicita permiso al administrador.');
            botonSolicitar.classList.remove('hidden');
        }

        if (d.puede_aprobar && d.pendientes.length) {
            bloquePendientes.classList.remove('hidden');
            bloquePendientes.innerHTML = `<p class="mb-2 text-sm font-black text-amber-800">Solicitudes de acceso al historial completo (${d.minutos_acceso} min)</p>` +
                d.pendientes.map(p => `<div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span><b>${esc(p.nom_usu || 'Empleado')}</b> · ${fechaCorta(p.creado_en)}</span>
                    <span class="flex gap-2">
                        <button type="button" data-decision="aprobar" data-acceso="${esc(p.id_acceso)}" class="rounded-lg bg-primary px-3 py-1 text-xs font-bold text-white">Aprobar</button>
                        <button type="button" data-decision="rechazar" data-acceso="${esc(p.id_acceso)}" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-bold text-rose-600">Rechazar</button>
                    </span></div>`).join('');
        } else {
            bloquePendientes.classList.add('hidden');
            bloquePendientes.innerHTML = '';
        }

        cuerpo.innerHTML = d.facturas.length === 0
            ? '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No hay facturas.</td></tr>'
            : d.facturas.map(f => `<tr class="hover:bg-slate-50/70">
                <td class="px-4 py-3 font-black text-slate-700">${esc(f.folio)}</td>
                <td class="px-4 py-3 text-slate-600">${fechaCorta(f.fecha)}</td>
                <td class="px-4 py-3"><p class="font-bold text-slate-800">${esc(f.huesped || '—')}</p><p class="text-xs text-slate-500">${esc(f.correo)}${f.cod_reserva ? ' · Reserva #' + esc(f.cod_reserva) : ''}</p></td>
                <td class="px-4 py-3 text-slate-600">${esc(etiquetas[f.tipo] || f.tipo)}</td>
                <td class="px-4 py-3 text-slate-600">${esc(f.metodo || '—')}</td>
                <td class="px-4 py-3 text-right font-black ${f.total < 0 ? 'text-rose-600' : 'text-primary'}">${cop(f.total)}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button type="button" data-factura="${f.cod_fac}" data-modo="ver" class="rounded-lg border border-slate-200 px-3 py-1 text-xs font-bold text-slate-600">Ver</button>
                    <button type="button" data-factura="${f.cod_fac}" data-modo="imprimir" class="rounded-lg bg-primary px-3 py-1 text-xs font-bold text-white">Imprimir</button>
                </td></tr>`).join('');
    }

    async function abrirFactura(id, imprimir) {
        const ventana = window.open('', '_blank');
        if (!ventana) { alert('Permite las ventanas emergentes para ver la factura.'); return; }
        try {
            const { factura } = await llamar(`${API}?accion=ver&id=${encodeURIComponent(id)}`);
            let html = '';
            // Reutiliza la plantilla de impresión existente capturando el HTML que genera.
            imprimirFacturaReserva(factura, { document: { open() {}, write(h) { html = h; }, close() {} } });
            if (factura.es_reembolso) {
                html = html.replace('Factura / comprobante de pago', 'Comprobante de reembolso')
                    .replace('Detalle del cobro adicional', 'Detalle del reembolso')
                    .replace('Cobro adicional: ', '')
                    .replace('Total pagado', 'Total reembolsado')
                    .replace('Gracias por elegir Hotel Aurora.', 'Devolución registrada por Hotel Aurora.');
            }
            if (!imprimir) {
                html = html.replace(/<script>[\s\S]*?<\/script>/, '')
                    .replace('</style>', '@media print{.no-imprimir{display:none}}</style>')
                    .replace('</main>', '<div class="no-imprimir" style="text-align:center;margin-top:24px"><button onclick="window.print()" style="background:#17354f;color:#fff;border:0;border-radius:8px;padding:10px 22px;font-weight:bold;cursor:pointer">Imprimir</button></div></main>');
            }
            ventana.document.open();
            ventana.document.write(html);
            ventana.document.close();
        } catch (error) {
            ventana.close();
            alert(error.message);
        }
    }

    raiz.addEventListener('click', async evento => {
        const boton = evento.target.closest('button');
        if (!boton) return;
        if (boton.dataset.factura) {
            abrirFactura(boton.dataset.factura, boton.dataset.modo === 'imprimir');
        } else if (boton === botonSolicitar) {
            boton.disabled = true;
            try {
                const datos = await llamar(API, { method: 'POST', body: new URLSearchParams({ accion: 'solicitar_acceso', csrf_token: CSRF_TOKEN }) });
                alert(datos.mensaje);
                await cargar();
            } catch (error) {
                alert(error.message);
            } finally {
                boton.disabled = false;
            }
        } else if (boton.dataset.decision) {
            boton.disabled = true;
            try {
                const datos = await llamar(API, { method: 'POST', body: new URLSearchParams({ accion: 'resolver_acceso', id_acceso: boton.dataset.acceso, decision: boton.dataset.decision, csrf_token: CSRF_TOKEN }) });
                alert(datos.mensaje);
                await cargar();
            } catch (error) {
                alert(error.message);
                boton.disabled = false;
            }
        }
    });

    const toggle = document.getElementById('facturasToggle');
    const contenido = document.getElementById('facturasContenido');
    const claveOculto = 'finanzas_oculto_facturas';
    const aplicarOculto = oculto => {
        contenido.classList.toggle('hidden', oculto);
        toggle.setAttribute('aria-expanded', String(!oculto));
        toggle.title = toggle.ariaLabel = (oculto ? 'Mostrar' : 'Ocultar') + ' historial de facturas';
        toggle.querySelector('.material-symbols-outlined').textContent = oculto ? 'expand_more' : 'expand_less';
    };
    let ocultoFacturas = false;
    try { ocultoFacturas = sessionStorage.getItem(claveOculto) === '1'; } catch (_) {}
    aplicarOculto(ocultoFacturas);
    toggle.addEventListener('click', () => {
        ocultoFacturas = !ocultoFacturas;
        aplicarOculto(ocultoFacturas);
        try { sessionStorage.setItem(claveOculto, ocultoFacturas ? '1' : '0'); } catch (_) {}
    });

    let temporizador = null;
    buscar.addEventListener('input', () => { clearTimeout(temporizador); temporizador = setTimeout(cargar, 300); });
    cargar();
})();
</script>