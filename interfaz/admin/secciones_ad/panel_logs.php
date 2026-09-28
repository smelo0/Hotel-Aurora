<!-- SECCIÓN LOGS -->
<?php
require_once __DIR__ . '/../../../includes/obtener_logs.php'
?>

<section id="sec-logs" class="seccion-contenido hidden w-full h-full p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Monitor de Eventos del Sistema</h2>
        <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors" onclick="cargarLogs()">
            <i class="fas fa-sync-alt mr-2"></i>Recargar Logs
        </button>
    </div>
    
    <!-- Barra de Filtros -->
    <div class="flex flex-wrap gap-4 mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <input type="text" id="filtroTexto" placeholder="Buscar mensaje, ID o contexto..." class="flex-1 min-w-[200px] border-slate-300 rounded-lg text-sm p-2 outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500">
        
        <select id="filtroNivel" class="border-slate-300 rounded-lg text-sm p-2 outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500">
            <option value="">Todos los niveles</option>
            <option value="INFO">INFO</option>
            <option value="WARNING">WARNING</option>
            <option value="ERROR">ERROR</option>
        </select>
        
        <input type="date" id="filtroFecha" class="border-slate-300 rounded-lg text-sm p-2 outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500" title="Filtrar por fecha exacta">
        
        <button onclick="limpiarFiltros()" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-medium transition-colors">
            Limpiar
        </button>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="tablaLogs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-sm">
                        <th class="p-4 font-semibold" width="15%">Fecha / Hora</th>
                        <th class="p-4 font-semibold" width="10%">Nivel</th>
                        <th class="p-4 font-semibold" width="10%">IP</th>
                        <th class="p-4 font-semibold" width="35%">Mensaje</th>
                        <th class="p-4 font-semibold" width="30%">Contexto</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100 text-slate-700">
                    <tr><td colspan="5" class="p-8 text-center text-slate-500">Haz clic en la sección para cargar los logs...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
let todosLosLogs = []; // Almacena los datos originales para no volver a consultar la BD

async function cargarLogs() {
    const tbody = document.querySelector('#tablaLogs tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-500"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando eventos...</td></tr>';

    try {
        const respuesta = await fetch('../controladores/obtener_logs.php');
        const resultado = await respuesta.json();

        if (resultado.status !== 'exito') {
            tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-red-500">Error: ${resultado.mensaje}</td></tr>`;
            return;
        }

        todosLosLogs = resultado.data;
        aplicarFiltros(); // Renderiza la tabla aplicando cualquier filtro activo

    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-red-500">Error de conexión al cargar los logs.</td></tr>';
    }
}

function renderizarTabla(logs) {
    const tbody = document.querySelector('#tablaLogs tbody');
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-500">No hay eventos que coincidan con la búsqueda.</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    
    logs.forEach(log => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 transition-colors';
        
        let colorBadge = 'bg-blue-100 text-blue-800'; // INFO
        if (log.level === 'WARNING') colorBadge = 'bg-yellow-100 text-yellow-800';
        if (log.level === 'ERROR') colorBadge = 'bg-red-100 text-red-800';

        const contextoHtml = Object.keys(log.context).length > 0 
            ? `<pre class="text-xs bg-slate-100 p-2 rounded text-slate-600 whitespace-pre-wrap break-all">${JSON.stringify(log.context, null, 2)}</pre>` 
            : '<span class="text-slate-400 italic">Sin datos extra</span>';

        tr.innerHTML = `
            <td class="p-4 whitespace-nowrap text-slate-500">${log.timestamp}</td>
            <td class="p-4">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold ${colorBadge}">
                    ${log.level}
                </span>
            </td>
            <td class="p-4 text-slate-500 font-mono text-xs">${log.ip}</td>
            <td class="p-4 font-medium text-slate-800">${log.message}</td>
            <td class="p-4">${contextoHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function aplicarFiltros() {
    const texto = document.getElementById('filtroTexto').value.toLowerCase();
    const nivel = document.getElementById('filtroNivel').value;
    const fecha = document.getElementById('filtroFecha').value;

    const logsFiltrados = todosLosLogs.filter(log => {
        // 1. Filtro por texto (busca en el mensaje, en la IP o dentro del objeto contexto)
        const contextoString = JSON.stringify(log.context).toLowerCase();
        const coincideTexto = texto === '' || 
                              log.message.toLowerCase().includes(texto) || 
                              log.ip.includes(texto) || 
                              contextoString.includes(texto);
        
        // 2. Filtro por Nivel
        const coincideNivel = nivel === '' || log.level === nivel;
        
        // 3. Filtro por Fecha (verifica si el timestamp empieza con la fecha seleccionada: YYYY-MM-DD)
        const coincideFecha = fecha === '' || log.timestamp.startsWith(fecha);

        return coincideTexto && coincideNivel && coincideFecha;
    });

    renderizarTabla(logsFiltrados);
}

function limpiarFiltros() {
    document.getElementById('filtroTexto').value = '';
    document.getElementById('filtroNivel').value = '';
    document.getElementById('filtroFecha').value = '';
    aplicarFiltros();
}

// Asignar los eventos para que filtren en tiempo real al escribir o cambiar opciones
document.getElementById('filtroTexto').addEventListener('input', aplicarFiltros);
document.getElementById('filtroNivel').addEventListener('change', aplicarFiltros);
document.getElementById('filtroFecha').addEventListener('change', aplicarFiltros);
</script>