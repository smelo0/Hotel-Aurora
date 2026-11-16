<?php
require_once __DIR__ . '/../../../configuracion/permiso.php';
/**@var mysqli $conexion */
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
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-primary">Panel de administración</h2>
            <p class="mt-1 text-xs text-slate-400">Resumen y accesos rápidos del hotel.</p>
        </div>
        <button type="button" onclick="abrirModalEmpleado()" class="rounded-lg bg-primary px-5 py-3 text-sm font-bold text-white shadow hover:brightness-110">
            <span class="material-symbols-outlined mr-1 align-middle text-sm">person_add</span>
            Crear empleado
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        <div class="admin-card bg-white p-6 rounded-xl border-t-4 border-t-primary">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ocupación</p>
            <h3 id="dash-ocupacion" class="text-4xl font-black text-heading"><?php echo $resumenAdmin['ocupacion']; ?>%</h3>
            <p id="dash-ocupacion-detalle" class="mt-1 text-xs font-bold text-slate-400"><?php echo (int) $resumenAdmin['habitaciones_ocupadas']; ?> de <?php echo (int) $resumenAdmin['total_habitaciones']; ?> habitaciones ocupadas</p>
        </div>
        
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Llegadas Hoy</p>
            <h3 id="dash-llegadas" class="text-4xl font-black text-heading"><?php echo $resumenAdmin['check_ins_hoy']; ?></h3>
        </div>
        
        <div class="admin-card bg-white p-6 rounded-xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pagos de Reservas Hoy</p>
            <h3 id="dash-ingresos" class="text-4xl font-black text-heading">$<?php echo number_format($resumenAdmin['ingresos_hoy'], 0, ',', '.'); ?></h3>
        </div>
        
        <div onclick="mostrarVista('sec-tareas')" class="admin-card bg-white p-6 rounded-xl cursor-pointer hover:ring-2 hover:ring-amber-400 transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tareas Pendientes</p>
            <h3 id="contador-tareas" class="text-4xl font-black text-heading text-amber-500">0</h3>
        </div>
    </div>

    <div class="rounded-xl border border-primary/10 bg-white p-8">
        <h3 class="text-xl font-black text-primary uppercase tracking-tighter">Actividad reciente del sistema</h3>
        <div id="dash-eventos" class="mt-5 space-y-3">
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
    <div id="modalCrearEmpleado" class="modal-rol-backdrop z-[100] flex items-center justify-center p-4 modal-oculto transition-all duration-300" role="dialog" aria-modal="true" aria-labelledby="tituloModalEmpleado">
        <div class="w-full max-w-lg rounded-xl bg-white p-8 shadow-2xl">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 id="tituloModalEmpleado" class="text-2xl font-black text-primary">Crear cuenta de empleado</h2>
                    <p class="mt-1 text-xs text-slate-400">La cuenta tendrá el perfil Empleado.</p>
                </div>
                <button type="button" onclick="cerrarModalEmpleado()" class="text-slate-400 hover:text-primary" aria-label="Cerrar">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="mensajeCrearEmpleado" class="mb-5 hidden rounded-lg px-4 py-3 text-sm font-bold" role="status" aria-live="polite"></div>
            <form id="formCrearEmpleado" class="space-y-4">
                <div>
                    <label for="nombreNuevoEmpleado" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Nombre completo</label>
                    <input type="text" id="nombreNuevoEmpleado" name="nombre" maxlength="100" autocomplete="name" required class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label for="correoNuevoEmpleado" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Correo electrónico</label>
                    <input type="email" id="correoNuevoEmpleado" name="correo" maxlength="100" autocomplete="email" required class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 outline-none focus:ring-1 focus:ring-primary">
                </div>
                <div>
                    <label for="contrasenaNuevoEmpleado" class="mb-1 block text-[10px] font-black uppercase text-slate-500">Contraseña inicial</label>
                    <input type="password" id="contrasenaNuevoEmpleado" name="contrasena" minlength="12" maxlength="72" autocomplete="new-password" required class="w-full rounded-lg border-slate-200 bg-slate-50 p-3 outline-none focus:ring-1 focus:ring-primary">
                    <p class="mt-1 text-[11px] text-slate-400">Debe tener al menos 12 caracteres. Compártela con el empleado de forma segura.</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModalEmpleado()" class="flex-1 rounded-lg py-3 font-bold text-slate-500 hover:bg-slate-100">Cancelar</button>
                    <button id="btnGuardarEmpleado" type="submit" class="flex-1 rounded-lg bg-primary py-3 font-bold text-white shadow hover:brightness-110">Crear cuenta</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const ENDPOINT_CREAR_EMPLEADO = '../../controladores/crear_empleado.php';

        function abrirModalEmpleado() {
            const modal = document.getElementById('modalCrearEmpleado');
            modal.classList.remove('modal-oculto');
            modal.classList.add('modal-visible');
            document.getElementById('nombreNuevoEmpleado').focus();
        }

        function cerrarModalEmpleado() {
            const modal = document.getElementById('modalCrearEmpleado');
            modal.classList.add('modal-oculto');
            modal.classList.remove('modal-visible');
            document.getElementById('formCrearEmpleado').reset();
            document.getElementById('mensajeCrearEmpleado').classList.add('hidden');
        }

        function mostrarMensajeCrearEmpleado(texto, error = false) {
            const mensaje = document.getElementById('mensajeCrearEmpleado');
            mensaje.textContent = texto;
            mensaje.className = `mb-5 rounded-lg px-4 py-3 text-sm font-bold ${error ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}`;
        }

        document.getElementById('formCrearEmpleado').addEventListener('submit', async evento => {
            evento.preventDefault();
            const boton = document.getElementById('btnGuardarEmpleado');
            const formulario = evento.currentTarget;
            const datos = new FormData(formulario);
            datos.set('csrf_token', CSRF_TOKEN);
            boton.disabled = true;
            boton.textContent = 'Creando...';
            try {
                const respuesta = await fetch(ENDPOINT_CREAR_EMPLEADO, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: datos
                });
                const resultado = await respuesta.json();
                if (!respuesta.ok) throw new Error(resultado.mensaje || 'No se pudo crear la cuenta.');
                mostrarMensajeCrearEmpleado(resultado.mensaje);
                formulario.reset();
            } catch (error) {
                mostrarMensajeCrearEmpleado(error.message, true);
            } finally {
                boton.disabled = false;
                boton.textContent = 'Crear cuenta';
            }
        });

        function actualizarContadorTareas() {
            fetch('../../controladores/api_tareas_pendientes.php', { headers: { Accept: 'application/json' } })
                .then(response => response.text())
                .then(texto => {
                    // El endpoint responde JSON ({"total": 4, "status": "exito"}); también se acepta un número simple.
                    let total = Number(texto);
                    if (!Number.isFinite(total)) {
                        try { total = Number(JSON.parse(texto).total); } catch (e) { total = NaN; }
                    }
                    const contador = document.getElementById('contador-tareas');
                    if (contador && Number.isFinite(total)) {
                        contador.innerText = total;
                    }
                })
                .catch(error => console.error('Error al actualizar tareas:', error));
        }

        actualizarContadorTareas();
        setInterval(actualizarContadorTareas, 5000); 

        // ---- Refresco automático del dashboard (ocupación, llegadas, pagos y actividad reciente) ----
        const DASHBOARD_INTERVALO_MS = 4000;
        let dashboardConsultando = false;
        let dashboardUltimaFirma = '';
        let dashboardUltimosEventos = '';

        function dashboardEscribir(id, texto) {
            const elemento = document.getElementById(id);
            if (elemento && elemento.textContent !== texto) elemento.textContent = texto;
        }

        function dashboardPintarEventos(eventos) {
            const contenedor = document.getElementById('dash-eventos');
            if (!contenedor) return;
            contenedor.replaceChildren();

            if (!eventos.length) {
                const vacio = document.createElement('p');
                vacio.className = 'text-sm text-slate-500';
                vacio.textContent = 'Todavía no hay eventos recientes registrados.';
                contenedor.appendChild(vacio);
                return;
            }

            eventos.forEach(evento => {
                const fila = document.createElement('div');
                fila.className = 'flex flex-wrap items-start gap-3 rounded-lg bg-slate-50 p-3';

                const nivel = document.createElement('span');
                nivel.className = 'rounded-full bg-primary/10 px-2 py-1 text-[10px] font-bold text-primary';
                nivel.textContent = evento.level;

                const hora = document.createElement('time');
                hora.className = 'text-xs text-slate-400';
                hora.textContent = evento.timestamp;

                const mensaje = document.createElement('p');
                mensaje.className = 'min-w-[220px] flex-1 text-sm text-slate-700';
                mensaje.textContent = evento.message;

                fila.append(nivel, hora, mensaje);
                contenedor.appendChild(fila);
            });
        }

        async function refrescarDashboard() {
            if (dashboardConsultando || document.hidden) return;
            dashboardConsultando = true;
            try {
                const respuesta = await fetch('index_ad.php?dashboard_datos=1', {
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!respuesta.ok) return; // 423/440 (sesión bloqueada o vencida) los maneja inactividad.js
                const texto = await respuesta.text();
                const datos = JSON.parse(texto.replace(/^\uFEFF/, ''));
                if (datos.status !== 'exito') return;

                const firma = JSON.stringify(datos);
                if (firma === dashboardUltimaFirma) return; // nada cambió: no se toca el DOM
                dashboardUltimaFirma = firma;
                const firmaEventos = JSON.stringify(datos.eventos);
                const eventosCambiaron = firmaEventos !== dashboardUltimosEventos;
                dashboardUltimosEventos = firmaEventos;

                const resumen = datos.resumen;
                dashboardEscribir('dash-ocupacion', `${resumen.ocupacion}%`);
                dashboardEscribir('dash-ocupacion-detalle', `${resumen.habitaciones_ocupadas} de ${resumen.total_habitaciones} habitaciones ocupadas`);
                dashboardEscribir('dash-llegadas', String(resumen.check_ins_hoy));
                dashboardEscribir('dash-ingresos', `$${resumen.ingresos_hoy}`);
                if (eventosCambiaron) dashboardPintarEventos(datos.eventos);
            } catch (error) {
                console.error('Error al actualizar el dashboard:', error);
            } finally {
                dashboardConsultando = false;
            }
        }

        setInterval(refrescarDashboard, DASHBOARD_INTERVALO_MS);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refrescarDashboard(); });
        // Cambios hechos desde otra pestaña (o desde la sección de reservas de esta): se refleja al instante.
        window.addEventListener('storage', evento => {
            if (['reservas_actualizadas', 'habitaciones_actualizadas', 'tareas_actualizadas'].includes(evento.key)) {
                refrescarDashboard();
            }
        });
        if (typeof BroadcastChannel !== 'undefined') {
            new BroadcastChannel('software_hotel_reservas').addEventListener('message', evento => {
                if (evento.data && evento.data.tipo === 'reserva_actualizada') refrescarDashboard();
            });
        }
        refrescarDashboard();
    </script>
</section>