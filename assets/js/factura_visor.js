// Visor de facturas del portal del huésped. La plantilla es la misma que usa el panel admin.
function imprimirFacturaReserva(factura, ventana) {
    const escapar = valor => String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[caracter]);
    const moneda = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
    const fecha = valor => {
        const texto = String(valor);
        const fechaSolo = /^(\d{4})-(\d{2})-(\d{2})$/.exec(texto);
        const parsed = fechaSolo
            ? new Date(Number(fechaSolo[1]), Number(fechaSolo[2]) - 1, Number(fechaSolo[3]))
            : new Date(texto.replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? escapar(valor) : escapar(parsed.toLocaleDateString('es-CO'));
    };
    const esExperiencia = factura.tipo === 'experiencia';
    const esRoomService = factura.tipo === 'room_service';
    const esAdicional = factura.tipo === 'adicional';
    const filasRoomService = (factura.items || []).map(item => `
            <tr>
                <td>Pedido #${escapar(item.pedido)} · ${escapar(item.cantidad)} × ${escapar(item.nombre)}</td>
                <td>${escapar(factura.metodo_pago)}</td>
                <td>${moneda.format(Number(item.subtotal) || 0)}</td>
            </tr>`).join('');
    const detalleRoomService = `<h2>Detalle del consumo</h2>
           <p>${factura.cod_reserva ? `Reserva #${escapar(factura.cod_reserva)} · ` : ''}Habitación ${escapar(factura.habitacion)}</p>
           <p>Room service · pedidos ${(factura.pedidos || []).map(id => '#' + escapar(id)).join(', ')}. El alojamiento y las experiencias se facturan por separado.</p>`;
    const filasFactura = esAdicional
        ? `<tr><td>Cobro adicional: ${escapar(factura.descripcion)}</td><td>${escapar(factura.metodo_pago)}</td><td>${moneda.format(Number(factura.monto) || 0)}</td></tr>`
        : esRoomService
        ? filasRoomService
        : esExperiencia
        ? ((factura.opciones || []).length > 0 ? factura.opciones : [{ opcion: factura.experiencia, monto: factura.monto }]).map((item, indice) => `
            <tr>
                <td>Persona ${indice + 1}: ${escapar(item.opcion)}</td>
                <td>${escapar(factura.metodo_pago)}</td>
                <td>${item.monto === null ? 'Incluido' : moneda.format(Number(item.monto) || 0)}</td>
            </tr>`).join('')
        : `<tr><td>Alojamiento (${escapar(factura.noches)} noche(s), IVA incluido)</td><td>${escapar(factura.metodo_pago)}</td><td>${moneda.format(Number(factura.monto) || 0)}</td></tr>`;
    const detalleFactura = esAdicional
        ? `<h2>Detalle del cobro adicional</h2>
           <p>${factura.cod_reserva ? `Reserva #${escapar(factura.cod_reserva)} · ` : ''}${escapar(factura.descripcion)}</p>`
        : esRoomService
        ? detalleRoomService
        : esExperiencia
        ? `<h2>Detalle de la experiencia</h2>
           <p><strong>${escapar(factura.experiencia)}</strong></p>
           ${factura.cod_reserva ? `<p>Vinculada a la reserva #${escapar(factura.cod_reserva)}. El cobro de alojamiento se factura por separado.</p>` : ''}
           <p>${(factura.opciones || []).map(item => escapar(item.opcion)).join(' · ')}</p>
           <p>Programada para el ${fecha(factura.fecha_experiencia)} a las ${escapar(String(factura.hora_experiencia || '').slice(0, 5))}</p>`
        : `<h2>Detalle de la estancia</h2>
           <p>Reserva #${escapar(factura.cod_reserva)} · Habitación ${escapar(factura.habitacion)} (${escapar(factura.tipo_habitacion)})</p>
           <p>${fecha(factura.fecha_entrada)} al ${fecha(factura.fecha_salida)} · ${escapar(factura.noches)} noche(s)</p>`;

    ventana.document.open();
    ventana.document.write(`<!doctype html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Factura ${escapar(factura.folio)}</title>
            <style>
                body{font-family:Arial,sans-serif;color:#172b3d;margin:0;padding:36px}
                main{max-width:760px;margin:0 auto;border:1px solid #d8e0e8;padding:36px}
                header{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #17354f;padding-bottom:20px}
                h1{margin:0 0 8px;font-size:26px}h2{margin:28px 0 12px;font-size:15px}
                p{margin:6px 0;color:#475569}.folio{text-align:right}
                table{width:100%;border-collapse:collapse;margin-top:28px}
                th,td{text-align:left;padding:12px;border-bottom:1px solid #e2e8f0}
                th:last-child,td:last-child{text-align:right}.total{font-size:19px;font-weight:bold}
                footer{margin-top:32px;text-align:center;color:#64748b;font-size:12px}
                @media print{body{padding:0}main{border:0;padding:12mm}}
            </style>
        </head>
        <body>
            <main>
                <header>
                    <div><h1>Hotel Aurora</h1><p>Factura / comprobante de pago</p></div>
                    <div class="folio"><strong>${escapar(factura.folio)}</strong><p>Fecha: ${fecha(factura.fecha)}</p></div>
                </header>
                <h2>Huésped</h2>
                <p><strong>${escapar(factura.huesped)}</strong></p>
                <p>${escapar(factura.correo)}</p>
                ${detalleFactura}
                <table>
                    <thead><tr><th>Concepto</th><th>Pago</th><th>Total</th></tr></thead>
                    <tbody>${filasFactura}</tbody>
                    <tfoot><tr><td colspan="2" class="total">Total pagado</td><td class="total">${moneda.format(Number(factura.monto) || 0)}</td></tr></tfoot>
                </table>
                <footer>Gracias por elegir Hotel Aurora.</footer>
            </main>
            <script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));<\/script>
        </body>
        </html>`);
    ventana.document.close();
}

/** HTML de la factura. Para solo verla se quita la impresión automática y se agrega un botón Imprimir. */
function htmlFacturaHuesped(factura, imprimir) {
    let html = '';
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
    return html;
}

/** Descarga la factura del huésped y la escribe en una ventana ya abierta (abrirla antes evita el bloqueo de ventanas emergentes). */
async function mostrarFacturaHuesped(idFactura, imprimir, ventana) {
    try {
        const respuesta = await fetch(`controladores/mis_facturas.php?accion=ver&id=${encodeURIComponent(idFactura)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const datos = await respuesta.json().catch(() => ({}));
        if (!respuesta.ok || datos.status !== 'exito') throw new Error(datos.mensaje || 'No se pudo cargar la factura.');
        ventana.document.open();
        ventana.document.write(htmlFacturaHuesped(datos.factura, imprimir));
        ventana.document.close();
    } catch (error) {
        ventana.close();
        alert(error.message);
    }
}