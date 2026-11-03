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
        const acciones = datosRoles.puede_gestionar
            ? `<button type="button" class="text-slate-400 hover:text-primary mx-1" title="Editar permisos" onclick="abrirEditarRol(${codigo})"><span class="material-symbols-outlined text-lg">tune</span></button>`
            : '<span class="text-slate-300">Solo lectura</span>';
        return `<tr class="hover:bg-slate-50/50">
            <td class="px-6 py-4 text-slate-400 font-mono text-xs">#${String(codigo).padStart(3, '0')}</td>
            <td class="px-6 py-4 font-black text-slate-800">${escaparRoles(rol.des_rol)}</td>
            <td class="px-6 py-4 text-slate-500 text-xs">${escaparRoles(rol.detalle_rol || 'Sin descripción')}</td>
            <td class="px-6 py-4 text-slate-500 text-xs">${codigo === 3 ? '2 cuentas' : '—'}</td>
            <td class="px-6 py-4 text-xs font-bold text-slate-500">${rol.permisos.length} permisos</td>
            <td class="px-6 py-4 text-right">${acciones}</td>
        </tr>`;
    }).join('');
}

function abrirEditarRol(codigo) {
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

cargarRoles();
</script>
