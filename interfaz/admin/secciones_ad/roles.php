<?php
// Modificación: Nueva Sección: Esqueleto base para la Gestión de Roles.
// Aquí se construirá el CRUD completo de Roles y Permisos. Por ahora, la interfaz está preparada con estructura, estilos y acciones listas para conectar.

require_once '../../configuracion/conexion.php';

// Datos de ejemplo (más adelante se reemplazará por consulta real)
$roles = [
    ['codigo' => 1, 'nombre' => 'Admin', 'descripcion' => 'Acceso total al sistema', 'usuarios' => 2],
    ['codigo' => 2, 'nombre' => 'Gestor', 'descripcion' => 'Gestión de reservas y reportes', 'usuarios' => 5],
    ['codigo' => 3, 'nombre' => 'Recepcionista', 'descripcion' => 'Check-in/out y tareas diarias', 'usuarios' => 12],
    ['codigo' => 4, 'nombre' => 'Conserje', 'descripcion' => 'Mantenimiento dehabitaciones', 'usuarios' => 8],
    ['codigo' => 5, 'nombre' => 'Limpieza', 'descripcion' => 'Gestión de estado de habitaciones', 'usuarios' => 15],
];
?>

<section id="sec-roles" class="seccion-contenido hidden fade-in">
    <div class="bg-white rounded-xl p-8 border border-primary/10 shadow-sm">
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h3 class="text-2xl font-black text-primary tracking-tight">Gestión de Roles y Permisos</h3>
                <p class="text-xs text-slate-400 mt-1">Control de acceso, asignación de funcionalidades y administración de perfiles del personal.</p>
            </div>
            <button onclick="abrirModalNuevoRol()" class="bg-primary text-white px-6 py-3 rounded-lg font-bold text-sm shadow hover:brightness-110 transition-all">
                <span class="material-symbols-outlined text-sm align-middle mr-1">add</span> Nuevo Rol
            </button>
        </div>

        <!-- Modificación: Tabla de roles con formato moderno, preparada para CRUD -->
        <div class="border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Código</th>
                        <th class="px-6 py-4">Rol</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4">Usuarios</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach($roles as $rol): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-slate-400 font-mono text-xs">#<?php echo str_pad($rol['codigo'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td class="px-6 py-4 font-black text-slate-800"><?php echo htmlspecialchars($rol['nombre']); ?></td>
                        <td class="px-6 py-4 text-slate-500 text-xs"><?php echo htmlspecialchars($rol['descripcion']); ?></td>
                        <td class="px-6 py-4">
                            <span class="bg-accent/10 text-primary px-2 py-1 rounded text-xs font-bold"><?php echo $rol['usuarios']; ?> activos</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-slate-400 hover:text-primary mx-1" title="Editar rol" onclick="abrirEditarRol(<?php echo $rol['codigo']; ?>)">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </button>
                            <button class="text-slate-400 hover:text-red-600 mx-1" title="Eliminar rol" onclick="confirmarEliminacionRol(<?php echo $rol['codigo']; ?>, '<?php echo htmlspecialchars($rol['nombre'], ENT_QUOTES); ?>')">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="text-[10px] text-slate-300 mt-4 text-center">* La gestión de permisos específicos se habilitará en una fase posterior.</p>
    </div>
</section>

<!-- Modificación: Modal base para crear/editar roles (a conectar más adelante). -->
<div id="modalRol" class="modal-rol-backdrop z-[100] flex items-center justify-center modal-oculto transition-all duration-500">
    <div class="bg-white p-10 rounded-xl w-[500px] shadow-2xl">
        <h2 id="tituloModalRol" class="text-2xl font-black text-primary mb-6">Nuevo Rol</h2>
        <form id="formRol" class="space-y-4">
            <input type="hidden" id="codigoRol">
            <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Nombre del Rol</label>
                <input type="text" id="nombreRol" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 focus:ring-1 focus:ring-primary outline-none" placeholder="Ej: Supervisor">
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Descripción</label>
                <textarea id="descripcionRol" required class="w-full border-slate-200 bg-slate-50 rounded-lg p-3 focus:ring-1 focus:ring-primary outline-none" rows="3" placeholder="Breve explicación de las funciones..."></textarea>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="cerrarModalRol()" class="flex-1 py-3 text-slate-400 font-bold hover:bg-slate-100 rounded-lg transition-all">Cancelar</button>
                <button type="submit" class="flex-1 py-3 bg-primary text-white font-bold rounded-lg shadow-lg hover:brightness-110">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevoRol() {
    document.getElementById('formRol').reset();
    document.getElementById('codigoRol').value = '';
    document.getElementById('tituloModalRol').innerText = 'Nuevo Rol';
    const modal = document.getElementById('modalRol');
    modal.classList.remove('modal-oculto');
    modal.classList.add('modal-visible');
}

function abrirEditarRol(codigo) {
    // Placeholder: conectar más adelante para cargar datos y permitir edición.
    alert('Función de edición de rol se conectará cuando el backend esté listo. Código: ' + codigo);
}

function confirmarEliminacionRol(codigo, nombre) {
    if (confirm('¿Estás seguro de eliminar el rol "' + nombre + '"? Esta acción no se puede deshacer.')) {
        alert('Eliminación se implementará cuando el endpoint esté disponible. Código: ' + codigo);
    }
}

function cerrarModalRol() {
    const modal = document.getElementById('modalRol');
    modal.classList.add('modal-oculto');
    modal.classList.remove('modal-visible');
}

// Manejo del formulario (solo por ahora validación;Más adelante se conectará)
document.getElementById('formRol').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('El envío del formulario de roles se conectará en la próxima fase.');
});
</script>
