<?php
// ARCHIVO: interfaz/admin/index_ad.php

session_start();
header('Content-Type: text/html; charset=utf-8');

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario']) ||
    !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2], true)
) {
    header("Location: ../loggins/index_ad_em.php");
    exit();
}

$firma_actor_panel = hash_hmac(
    'sha256',
    ((int) $_SESSION['emp_auth']['id_usuario']) . '|' . ((int) $_SESSION['emp_auth']['rol_usuario']),
    'software_hotel_actor_panel_v1'
);

require_once 'componentes_ad/head.php';
?>

<body class="bg-surface font-body text-heading antialiased flex min-h-screen overflow-hidden">

    <?php 
    require_once 'componentes_ad/sidebar.php'; 
    require_once 'componentes_ad/modal_tarea.php';
    ?>

    <main class="ml-64 flex-1 h-screen overflow-y-auto no-scrollbar">
        
        <?php require_once 'componentes_ad/topbar.php'; ?>

        <div class="p-10 space-y-10">
            <?php
            require_once 'secciones_ad/dashboard.php';
            require_once 'secciones_ad/reservas.php';
            require_once 'secciones_ad/roles.php';
            require_once 'secciones_ad/operaciones.php';
            require_once 'secciones_ad/finanzas.php';
            require_once 'secciones_ad/configuracion.php';
            ?>
        </div>
    </main>

    <?php require_once 'componentes_ad/aside_tareas_admin.php'; ?>

    <div id="modalLogout" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all scale-95 opacity-0" id="cajaLogout">
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl">logout</span>
                </div>
                <h4 class="text-xl font-black text-slate-800 mb-2">¿Cerrar Sesión?</h4>
                <p class="text-sm text-slate-500">Estás a punto de salir del sistema. Asegúrate de haber guardado todos tus cambios.</p>
            </div>
            
            <div class="flex border-t border-slate-100">
                <button onclick="cerrarModalLogout()" class="flex-1 px-6 py-4 text-sm font-bold text-slate-400 hover:bg-slate-50 transition-colors border-r border-slate-100">
                    Seguir trabajando
                </button>
                <a href="../../controladores/logout.php?panel=emp" class="flex-1 px-6 py-4 text-sm font-black text-red-500 hover:bg-red-50 transition-colors text-center">
                    Sí, salir ahora
                </a>
            </div>
        </div>
    </div>

    <div id="modalMantenimientoHousekeeping" onclick="cerrarModalMantenimiento(event)" class="maintenance-modal-overlay fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[90] flex justify-center items-center p-4">
        <div id="cardMantenimientoHousekeeping" onclick="event.stopPropagation()" class="maintenance-modal-card bg-white p-8 rounded-2xl w-full max-w-md shadow-2xl border border-slate-100">
            <div class="flex justify-between items-start gap-4 mb-6">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-red-400 mb-2">Mantenimiento</p>
                    <h3 id="tituloMantenimientoHousekeeping" class="text-xl font-black text-heading">Habitación</h3>
                </div>
                <button onclick="cerrarModalMantenimiento()" class="text-slate-400 hover:text-red-500 font-black text-xl transition-colors" type="button">&times;</button>
            </div>

            <div id="prioridadMantenimientoHousekeeping" class="inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[10px] font-black uppercase tracking-widest mb-5"></div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400 mb-2">Motivo registrado</p>
                <p id="motivoMantenimientoHousekeeping" class="text-sm font-bold text-slate-600 leading-relaxed whitespace-pre-line"></p>
            </div>
        </div>
    </div>

    <?php
    $ayudaSistemaRol = 'admin';
    require_once '../../includes/system_help.php';
    ?>

    <script>
        const ROL_USUARIO = <?php echo isset($_SESSION['emp_auth']['rol_usuario']) ? (int) $_SESSION['emp_auth']['rol_usuario'] : 1; ?>;
        const ID_USUARIO_ACTIVO = <?php echo isset($_SESSION['emp_auth']['id_usuario']) ? (int) $_SESSION['emp_auth']['id_usuario'] : 0; ?>;
        const FIRMA_USUARIO_ACTIVO = "<?php echo htmlspecialchars($firma_actor_panel, ENT_QUOTES, 'UTF-8'); ?>";
    </script>

    <script src="js_ad/admin.js?v=6"></script>
</body>
</html>
