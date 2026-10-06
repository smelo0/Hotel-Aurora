<?php
require_once __DIR__ . '/../../../configuracion/permiso.php';
if (!usuario_tiene_permiso($conexion, 'dashboard.ver')) {
    return;
}

require_once __DIR__ . '/../../../vendor/autoload.php';
$dashboardRepository = new \App\Admin\AdminDashboardRepository($conexion);
$resumenAdmin = $dashboardRepository->obtenerResumen();
$eventosRecientesAdmin = $dashboardRepository->obtenerEventosRecientes();
$escaparDashboard = static fn($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<section id="sec-dashboard" class="seccion-contenido">
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        <div class="admin-card bg-white p-6 rounded-xl border-t-4 border-t-primary">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ocupación</p>
            <h3 class="text-4xl font-black text-heading"><?php echo $resumenAdmin['ocupacion']; ?>%</h3>
        </div>
        
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Llegadas Hoy</p>
            <h3 class="text-4xl font-black text-heading"><?php echo $resumenAdmin['check_ins_hoy']; ?></h3>
        </div>
        
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pagos de Reservas Hoy</p>
            <h3 class="text-4xl font-black text-heading">$<?php echo number_format($resumenAdmin['ingresos_hoy'], 0, ',', '.'); ?></h3>
        </div>
        
        <div onclick="mostrarVista('sec-tareas')" class="admin-card bg-white p-6 rounded-xl cursor-pointer hover:ring-2 hover:ring-amber-400 transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tareas Pendientes</p>
            <h3 id="contador-tareas" class="text-4xl font-black text-heading text-amber-500">0</h3>
        </div>
    </div>

    <div class="rounded-xl border border-primary/10 bg-white p-8">
        <h3 class="text-xl font-black text-primary uppercase tracking-tighter">Actividad reciente del sistema</h3>
        <div class="mt-5 space-y-3">
            <?php if ($eventosRecientesAdmin === []): ?>
                <p class="text-sm text-slate-500">Todavía no hay eventos recientes registrados.</p>
            <?php else: ?>
                <?php foreach ($eventosRecientesAdmin as $evento): ?>
                    <div class="flex flex-wrap items-start gap-3 rounded-lg bg-slate-50 p-3">
                        <span class="rounded-full bg-primary/10 px-2 py-1 text-[10px] font-bold text-primary"><?php echo $escaparDashboard($evento['level']); ?></span>
                        <time class="text-xs text-slate-400"><?php echo $escaparDashboard($evento['timestamp']); ?></time>
                        <p class="min-w-[220px] flex-1 text-sm text-slate-700"><?php echo $escaparDashboard($evento['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function actualizarContadorTareas() {
            fetch('../../controladores/api_tareas_pendientes.php')
                .then(response => response.text())
                .then(data => {
                    const contador = document.getElementById('contador-tareas');
                    if(contador) {
                        contador.innerText = data;
                    }
                })
                .catch(error => console.error('Error al actualizar tareas:', error));
        }

        actualizarContadorTareas();
        setInterval(actualizarContadorTareas, 5000); 
    </script>
</section>