<?php
require_once '../../configuracion/conexion.php';
require_once '../../configuracion/permiso.php';
/** @var mysqli $conexion */
if (!usuario_tiene_permiso($conexion, 'roles.ver')) { return; }
?>
<section id="sec-roles" class="seccion-contenido hidden fade-in">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="mb-8">
            <h3 class="text-2xl font-black text-primary tracking-tight">Gestión de Roles y Permisos</h3>
            <p class="text-xs text-slate-400 mt-1">Administra los permisos de cada perfil del hotel.</p>
        </div>
        <div id="mensajeRoles" class="hidden mb-5 rounded-lg px-4 py-3 text-sm font-bold"></div>
        <div class="border border-slate-200 rounded-xl overflow-x-auto">
            <table class="w-full min-w-[700px] text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Código</th>
                        <th class="px-6 py-4">Rol</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4">Cuentas</th>
                        <th class="px-6 py-4">Permisos</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaRoles" class="divide-y divide-slate-100 text-sm"></tbody>
            </table>
        </div>
    </div>
</section>
<div id="modalRol" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-500">
    <div class="bg-white p-8 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h2 id="tituloModalRol" class="text-2xl font-black text-primary">Permisos del rol</h2>
                <p class="text-xs text-slate-400 mt-1">Los cambios se aplican inmediatamente.</p>
            </div>
            <button type="button" onclick="cerrarModalRol()" class="text-slate-400 hover:text-primary">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="formRol" class="space-y-5">
            <input type="hidden" id="codigoRol">
            <fieldset id="listaPermisos" class="grid sm:grid-cols-2 gap-3"></fieldset>
            <div class="flex gap-4 pt-2">
                <button type="button" onclick="cerrarModalRol()" class="flex-1 py-3 text-slate-400 font-bold hover:bg-slate-100 rounded-lg">Cancelar</button>
                <button type="submit" class="flex-1 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Guardar permisos</button>
            </div>
        </form>
    </div>
</div>
<div id="modalDescripcionRol" class="modal-rol-backdrop z-[110] flex items-center justify-center modal-oculto transition-all duration-300">
    <div class="bg-white p-8 rounded-xl w-full max-w-lg shadow-2xl">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h2 id="tituloDescripcionRol" class="text-xl font-black text-primary">Editar descripción</h2>
                <p class="text-xs text-slate-400 mt-1">Máximo 200 caracteres.</p>
            </div>
            <button type="button" onclick="cerrarModalDescripcionRol()" class="text-slate-400 hover:text-primary" aria-label="Cerrar">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="formDescripcionRol" class="space-y-5">
            <input type="hidden" id="codigoDescripcionRol">
            <textarea id="textoDescripcionRol" maxlength="200" rows="4" class="w-full rounded-lg border border-slate-200 p-3 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" aria-label="Descripción del rol"></textarea>
            <p id="errorDescripcionRol" class="hidden text-sm font-bold text-red-600" role="alert"></p>
            <div class="flex gap-4">
                <button type="button" onclick="cerrarModalDescripcionRol()" class="flex-1 rounded-lg py-3 font-bold text-slate-400 hover:bg-slate-100">Cancelar</button>
                <button id="guardarDescripcionRol" type="submit" class="flex-1 rounded-lg bg-primary py-3 font-bold text-white shadow-lg hover:brightness-110">Guardar descripción</button>
            </div>
        </form>
    </div>
</div>
<div id="modalCuentasRol" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-300" role="dialog" aria-modal="true" aria-labelledby="tituloCuentasRol">
    <div class="bg-white p-8 rounded-xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h2 id="tituloCuentasRol" class="text-2xl font-black text-primary">Cuentas del rol</h2>
                <p class="text-xs text-slate-400 mt-1">Una cuenta deshabilitada no puede iniciar sesión.</p>
            </div>
            <button type="button" onclick="cerrarModalCuentasRol()" class="text-slate-400 hover:text-primary" aria-label="Cerrar">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <input id="buscarCuentasRol" type="search" placeholder="Buscar por nombre o correo" autocomplete="off"
            class="w-full mb-4 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" aria-label="Buscar cuentas">
        <div id="mensajeCuentasRol" class="hidden mb-4 rounded-lg px-4 py-3 text-sm font-bold" role="status"></div>
        <ul id="listaCuentasRol" class="flex-1 overflow-y-auto divide-y divide-slate-100 min-h-[120px]"></ul>
        <div class="pt-5">
            <button type="button" onclick="cerrarModalCuentasRol()" class="w-full py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>
<script>
const ENDPOINT_ROLES = '../../controladores/gestionar_roles.php';
let datosRoles = { roles: [], permisos: [], puede_gestionar: false, puede_asignar: false };

function escaparRoles(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[caracter]));
}

function mostrarMensajeRoles(texto, error = false) {
    const mensaje = document.getElementById('mensajeRoles');
    mensaje.textContent = texto;
    mensaje.className = `mb-5 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`;
}

async function cargarRoles() {
    try {
        const respuesta = await fetch(`${ENDPOINT_ROLES}?accion=listar`, {
            headers: { Accept: 'application/json' }
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron cargar los roles');
        datosRoles = datos;
        renderizarRoles();
    } catch (error) {
        mostrarMensajeRoles(error.message, true);
    }
}

function renderizarRoles() {
    document.getElementById('tablaRoles').innerHTML = datosRoles.roles.map(rol => {
        const codigo = Number(rol.cod_rol);
        const acciones = datosRoles.puede_gestionar && codigo !== 1
            ? `<button type="button" class="text-slate-400 hover:text-primary mx-1" title="Editar permisos" onclick="abrirEditarRol(${codigo})"><span class="material-symbols-outlined text-lg">tune</span></button>`
            : codigo === 1
                ? '<span class="text-slate-400 text-xs">Acceso total protegido</span>'
            : '<span class="text-slate-300">Solo lectura</span>';
        const totalCuentas = Number(rol.total_cuentas || 0);
        const cuentasActivas = Number(rol.cuentas_activas || 0);
        const celdaCuentas = totalCuentas > 0
            ? `<button type="button" class="inline-flex items-center gap-1 rounded px-2 py-1 -mx-2 font-bold text-primary hover:bg-primary/10" title="Ver las cuentas de ${escaparRoles(rol.des_rol)}" aria-label="Ver las cuentas de ${escaparRoles(rol.des_rol)}" onclick="abrirCuentasRol(${codigo})"><span class="material-symbols-outlined text-base">group</span>${totalCuentas} ${totalCuentas === 1 ? 'cuenta' : 'cuentas'}</button><span class="block text-[10px] text-slate-400">${cuentasActivas} ${cuentasActivas === 1 ? 'activa' : 'activas'}</span>`
            : '—';
        return `<tr class="hover:bg-slate-50/50">
            <td class="px-6 py-4 text-slate-400 font-mono text-xs">#${String(codigo).padStart(3, '0')}</td>
            <td class="px-6 py-4 font-black text-slate-800">${escaparRoles(rol.des_rol)}</td>
            <td class="px-6 py-4 text-slate-500 text-xs">
                <div class="flex items-center justify-between gap-3">
                    <span>${escaparRoles(rol.detalle_rol || 'Sin descripción')}</span>
                    ${datosRoles.puede_gestionar ? `<button type="button" class="shrink-0 rounded px-2 py-1 font-bold text-primary hover:bg-primary/10" title="Editar descripción" aria-label="Editar descripción de ${escaparRoles(rol.des_rol)}" onclick="abrirEditarDescripcionRol(${codigo})">Editar</button>` : ''}
                </div>
            </td>
            <td class="px-6 py-4 text-slate-500 text-xs">${celdaCuentas}</td>
            <td class="px-6 py-4 text-xs font-bold text-slate-500">${rol.permisos.length} permisos</td>
            <td class="px-6 py-4 text-right">${acciones}</td>
        </tr>`;
    }).join('');
}

function abrirEditarRol(codigo) {
    if (codigo === 1) return;
    const rol = datosRoles.roles.find(item => Number(item.cod_rol) === codigo);
    if (!rol) return;
    document.getElementById('formRol').reset();
    document.getElementById('codigoRol').value = rol.cod_rol;
    document.getElementById('tituloModalRol').textContent = `Permisos: ${rol.des_rol}`;
    document.getElementById('listaPermisos').innerHTML = datosRoles.permisos.map(permiso => `
        <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 cursor-pointer hover:bg-slate-50">
            <input type="checkbox" name="permisos" value="${escaparRoles(permiso.cod_permiso)}"
                ${rol.permisos.includes(permiso.cod_permiso) ? 'checked' : ''}
                ${datosRoles.puede_asignar ? '' : 'disabled'} class="mt-1 accent-primary">
            <span>
                <strong class="block text-xs text-slate-700">${escaparRoles(permiso.cod_permiso)}</strong>
                <small class="text-[11px] text-slate-400">${escaparRoles(permiso.des_permiso)}</small>
            </span>
        </label>`).join('');
    const modal = document.getElementById('modalRol');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
}

function cerrarModalRol() {
    const modal = document.getElementById('modalRol');
    modal.classList.add('modal-oculto');
    modal.classList.remove('modal-visible');
}

function abrirEditarDescripcionRol(codigo) {
    const rol = datosRoles.roles.find(item => Number(item.cod_rol) === codigo);
    if (!rol || !datosRoles.puede_gestionar) return;
    document.getElementById('codigoDescripcionRol').value = String(codigo);
    document.getElementById('tituloDescripcionRol').textContent = `Descripción: ${rol.des_rol}`;
    document.getElementById('textoDescripcionRol').value = rol.detalle_rol || '';
    document.getElementById('errorDescripcionRol').classList.add('hidden');
    const modal = document.getElementById('modalDescripcionRol');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
}

function cerrarModalDescripcionRol() {
    const modal = document.getElementById('modalDescripcionRol');
    modal.classList.add('modal-oculto');
    modal.classList.remove('modal-visible');
}

document.getElementById('formDescripcionRol').addEventListener('submit', async evento => {
    evento.preventDefault();
    const boton = document.getElementById('guardarDescripcionRol');
    const error = document.getElementById('errorDescripcionRol');
    boton.disabled = true;
    error.classList.add('hidden');
    try {
        const respuesta = await fetch(ENDPOINT_ROLES, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new URLSearchParams({
                accion: 'guardar_descripcion',
                cod_rol: document.getElementById('codigoDescripcionRol').value,
                detalle_rol: document.getElementById('textoDescripcionRol').value.trim(),
                csrf_token: CSRF_TOKEN
            })
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudo guardar la descripción');
        cerrarModalDescripcionRol();
        mostrarMensajeRoles(datos.mensaje);
        await cargarRoles();
    } catch (errorPeticion) {
        error.textContent = errorPeticion.message;
        error.classList.remove('hidden');
    } finally {
        boton.disabled = false;
    }
});

document.getElementById('formRol').addEventListener('submit', async evento => {
    evento.preventDefault();
    const cuerpo = new URLSearchParams({
        accion: 'guardar',
        cod_rol: document.getElementById('codigoRol').value,
        csrf_token: CSRF_TOKEN
    });
    document.querySelectorAll('#listaPermisos input[name="permisos"]:checked')
        .forEach(input => cuerpo.append('permisos[]', input.value));
    try {
        const respuesta = await fetch(ENDPOINT_ROLES, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: cuerpo
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron guardar los permisos');
        cerrarModalRol();
        mostrarMensajeRoles(datos.mensaje);
        await cargarRoles();
    } catch (error) {
        mostrarMensajeRoles(error.message, true);
    }
});

let estadoCuentasRol = { codigo: 0, cuentas: [], puede_gestionar: false };

function iniciales(nombre) {
    const partes = String(nombre || '?').trim().split(/\s+/).filter(Boolean);
    return (partes[0]?.[0] || '?').toUpperCase() + (partes.length > 1 ? partes[1][0].toUpperCase() : '');
}

function mostrarMensajeCuentas(texto, error = false) {
    const mensaje = document.getElementById('mensajeCuentasRol');
    mensaje.textContent = texto;
    mensaje.className = `mb-4 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`;
}

async function abrirCuentasRol(codigo) {
    const rol = datosRoles.roles.find(item => Number(item.cod_rol) === codigo);
    if (!rol) return;
    estadoCuentasRol = { codigo, cuentas: [], puede_gestionar: false };
    document.getElementById('tituloCuentasRol').textContent = `Cuentas: ${rol.des_rol}`;
    document.getElementById('buscarCuentasRol').value = '';
    document.getElementById('mensajeCuentasRol').classList.add('hidden');
    document.getElementById('listaCuentasRol').innerHTML = '<li class="py-8 text-center text-sm text-slate-400">Cargando cuentas…</li>';
    const modal = document.getElementById('modalCuentasRol');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
    document.getElementById('buscarCuentasRol').focus();
    try {
        const respuesta = await fetch(`${ENDPOINT_ROLES}?accion=cuentas&cod_rol=${codigo}`, {
            headers: { Accept: 'application/json' }
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudieron cargar las cuentas');
        estadoCuentasRol = { codigo, cuentas: datos.cuentas, puede_gestionar: datos.puede_gestionar };
        renderizarCuentasRol();
    } catch (error) {
        document.getElementById('listaCuentasRol').innerHTML = '';
        mostrarMensajeCuentas(error.message, true);
    }
}

function cerrarModalCuentasRol() {
    const modal = document.getElementById('modalCuentasRol');
    modal.classList.add('modal-oculto');
    modal.classList.remove('modal-visible');
}

function renderizarCuentasRol() {
    const filtro = document.getElementById('buscarCuentasRol').value.trim().toLowerCase();
    const cuentas = estadoCuentasRol.cuentas.filter(cuenta =>
        !filtro || cuenta.nombre.toLowerCase().includes(filtro) || cuenta.correo.toLowerCase().includes(filtro));
    const lista = document.getElementById('listaCuentasRol');
    if (cuentas.length === 0) {
        lista.innerHTML = '<li class="py-8 text-center text-sm text-slate-400">No hay cuentas para mostrar.</li>';
        return;
    }
    lista.innerHTML = cuentas.map(cuenta => {
        const insignia = cuenta.activa
            ? '<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700">Activa</span>'
            : '<span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Deshabilitada</span>';
        let accion;
        if (cuenta.es_actual) {
            accion = '<span class="text-xs text-slate-400">Tu cuenta</span>';
        } else if (!cuenta.editable) {
            accion = '<span class="text-xs text-slate-400">Protegida</span>';
        } else if (!estadoCuentasRol.puede_gestionar) {
            accion = '<span class="text-xs text-slate-300">Solo lectura</span>';
        } else if (cuenta.activa) {
            accion = `<button type="button" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50" onclick="cambiarEstadoCuenta(${cuenta.id_usu}, false, this)">Deshabilitar</button>`;
        } else {
            accion = `<button type="button" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white hover:brightness-110" onclick="cambiarEstadoCuenta(${cuenta.id_usu}, true, this)">Habilitar</button>`;
        }
        return `<li class="flex items-center justify-between gap-3 py-3 ${cuenta.activa ? '' : 'opacity-70'}">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-black text-primary">${escaparRoles(iniciales(cuenta.nombre))}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-800">${escaparRoles(cuenta.nombre)}</p>
                    <p class="truncate text-xs text-slate-400">${escaparRoles(cuenta.correo)}${cuenta.rol ? ' · ' + escaparRoles(cuenta.rol) : ''}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-3">${insignia}${accion}</div>
        </li>`;
    }).join('');
}

async function cambiarEstadoCuenta(idCuenta, activar, boton) {
    // Deshabilitar pide una segunda pulsación para confirmar.
    if (!activar && boton.dataset.confirmar !== '1') {
        boton.dataset.confirmar = '1';
        boton.textContent = '¿Confirmar?';
        boton.className = 'rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:brightness-110';
        setTimeout(() => { if (boton.isConnected && boton.dataset.confirmar === '1') renderizarCuentasRol(); }, 4000);
        return;
    }
    boton.disabled = true;
    try {
        const respuesta = await fetch(ENDPOINT_ROLES, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new URLSearchParams({
                accion: 'cambiar_estado_cuenta',
                id_usu: String(idCuenta),
                est_usu: activar ? '1' : '0',
                csrf_token: CSRF_TOKEN
            })
        });
        const datos = await respuesta.json();
        if (!respuesta.ok) throw new Error(datos.mensaje || 'No se pudo actualizar la cuenta');
        const cuenta = estadoCuentasRol.cuentas.find(item => item.id_usu === idCuenta);
        if (cuenta) cuenta.activa = datos.activa;
        renderizarCuentasRol();
        mostrarMensajeCuentas(datos.mensaje);
        cargarRoles();
    } catch (error) {
        renderizarCuentasRol();
        mostrarMensajeCuentas(error.message, true);
    }
}

document.getElementById('buscarCuentasRol').addEventListener('input', renderizarCuentasRol);
document.addEventListener('keydown', evento => {
    if (evento.key === 'Escape' && document.getElementById('modalCuentasRol').classList.contains('modal-visible')) {
        cerrarModalCuentasRol();
    }
});

cargarRoles();
</script>