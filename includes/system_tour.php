<?php
$tourSistemaRol = $tourSistemaRol ?? $ayudaSistemaRol ?? 'usuario';
$tourScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

if (in_array($tourScript, ['index_usu.php', 'recuperar_contrasena.php'], true)) {
    $tourSistemaRol = 'acceso';
} elseif ($tourScript === 'restablecer_contrasena.php') {
    $tourSistemaRol = 'recuperar';
}

$tourSistemaPasos = [
    'visitante' => [
        ['#bookingBar', 'Busca disponibilidad', 'Selecciona las fechas de llegada y salida, indica cuántas personas se hospedarán y pulsa “Buscar disponibilidad”.'],
        ['#roomsCarousel', 'Explora habitaciones', 'Usa los filtros para elegir un tipo de habitación y las flechas para recorrer las opciones disponibles.'],
        ['#habitaciones [data-room-select]', 'Reserva tu estancia', 'Elige una habitación disponible y sigue los pasos para iniciar sesión, revisar el total y completar la reserva.'],
        ['#activityForm', 'Solicita una experiencia', 'Completa este formulario para elegir una actividad, fecha y hora; el equipo confirmará la solicitud.'],
    ],
    'usuario' => [
        ['#mis-reservas', 'Consulta tu estancia', 'Aquí puedes revisar tus reservas, fechas, habitaciones y estado de cada solicitud.'],
        ['nav a[href*="interfaz_usu.php"]', 'Vuelve al hotel', 'Usa este enlace para regresar a la página principal y explorar habitaciones y experiencias.'],
        ['#systemHelpButton', 'Solicita ayuda', 'Abre este botón cuando quieras consultar respuestas frecuentes o volver a iniciar este recorrido.'],
        ['nav a[href*="logout"], nav a[href*="salir"]', 'Protege tu cuenta', 'Cierra sesión al terminar, especialmente si estás usando un dispositivo compartido.'],
    ],
    'empleado' => [
        ['.empleado-sidebar .nav-item:nth-of-type(1)', 'Navega por el panel', 'El menú lateral te permite cambiar entre Panel Hoy, Habitaciones, Limpieza y Huéspedes.'],
        ['.empleado-sidebar .nav-item:nth-of-type(2)', 'Actualiza habitaciones', 'En Habitaciones puedes consultar el estado y registrar cambios operativos.'],
        ['#panelTareasDerecho', 'Gestiona tareas', 'Usa esta cola lateral para revisar tareas y seguir su avance.'],
        ['.empleado-sidebar a[onclick*="abrirModalLogoutEmpleado"]', 'Cierra tu sesión', 'Al terminar tu turno, usa este acceso para cerrar sesión de forma segura.'],
    ],
    'admin' => [
        ['.nav-item[data-permiso="dashboard.ver"]', 'Revisa el tablero', 'El panel principal resume la operación. Usa el menú lateral para acceder a cada módulo.'],
        ['.nav-item[data-permiso="reservas.ver"]', 'Gestiona reservas', 'En esta sección puedes dar seguimiento a las reservas y revisar sus datos.'],
        ['.nav-item[data-permiso="operaciones.ver"]', 'Supervisa la operación', 'Consulta el estado de habitaciones y los procesos operativos del hotel.'],
        ['.nav-item[data-permiso="roles.ver"]', 'Administra accesos', 'En Roles y Permisos gestionas los perfiles habilitados para tu cuenta.'],
        ['#panelTareasDerechoAdmin', 'Organiza tareas', 'Usa esta cola lateral para revisar tareas pendientes y su avance.'],
        ['.nav-item[onclick*="abrirModalLogout"]', 'Finaliza de forma segura', 'Cierra sesión al terminar, especialmente si compartes el dispositivo.'],
    ],
    'acceso' => [
        ['#correo_login', 'Escribe tu correo', 'Usa el correo asociado a tu cuenta para identificarte.'],
        ['#password_login', 'Ingresa tu contraseña', 'Escribe tu contraseña y pulsa “Iniciar sesión” para entrar.'],
        ['.link-switch[href="recuperar_contrasena.php"]', 'Recupera el acceso', 'Si olvidaste la contraseña, usa este enlace y sigue las instrucciones enviadas a tu correo.'],
        ['.link-switch[onclick*="cambiarPanel"]', 'Crea una cuenta', 'Si aún no tienes una, abre esta opción y completa tus datos de registro.'],
    ],
    'recuperar' => [
        ['#email_rec', 'Indica tu correo', 'Escribe el correo asociado a tu cuenta para recibir las instrucciones de recuperación.'],
        ['form input[type="submit"]', 'Envía la solicitud', 'Pulsa este botón para solicitar un enlace seguro de recuperación.'],
        ['a[href="index_usu.php"]', 'Regresa al acceso', 'Cuando termines, vuelve al inicio de sesión con tu nueva contraseña.'],
    ],
];

$tourSistemaTitulos = [
    'visitante' => 'Conoce Hotel Aurora',
    'usuario' => 'Tu espacio de huésped',
    'empleado' => 'Guía del panel operativo',
    'admin' => 'Guía de administración',
    'acceso' => 'Acceso a tu cuenta',
    'recuperar' => 'Recuperación de contraseña',
];

$tourActorId = (int) (
    $_SESSION['user_auth']['id_usuario']
    ?? $_SESSION['emp_auth']['id_usuario']
    ?? 0
);
$tourPagina = (string) ($_SERVER['SCRIPT_NAME'] ?? 'inicio');
$tourStorageKey = 'hotelAuroraTour:v1:'
    . $tourSistemaRol . ':' . $tourActorId . ':' . $tourPagina;
$tourPasos = $tourSistemaPasos[$tourSistemaRol] ?? $tourSistemaPasos['usuario'];
$tourTitulo = $tourSistemaTitulos[$tourSistemaRol] ?? 'Guía paso a paso';
?>

<style>
    .system-tour[hidden] { display: none !important; }
    .system-tour { position: fixed; inset: 0; z-index: 200; pointer-events: auto; }
    .system-tour__spotlight { position: fixed; z-index: 0; border-radius: .85rem; box-shadow: 0 0 0 9999px rgba(8, 20, 28, .68); outline: 3px solid #e0a844; outline-offset: 4px; transition: top .2s ease, left .2s ease, width .2s ease, height .2s ease; pointer-events: none; }
    .system-tour__card { position: fixed; z-index: 1; width: min(22rem, calc(100vw - 2rem)); overflow: hidden; border: 1px solid rgba(255, 255, 255, .65); border-radius: 1rem; background: #fff; box-shadow: 0 20px 60px rgba(0, 0, 0, .28); color: #183b3b; font-family: Manrope, sans-serif; pointer-events: auto; }
    .system-tour__progress { height: .3rem; background: #e6eeee; }
    .system-tour__progress span { display: block; height: 100%; background: #2c5e5e; transition: width .2s ease; }
    .system-tour__content { padding: 1.25rem; }
    .system-tour__actions { display: flex; align-items: center; gap: .65rem; border-top: 1px solid #e5eceb; padding: .85rem 1.25rem; }
    .system-tour__button { border: 0; border-radius: .7rem; padding: .7rem 1rem; font: inherit; font-size: .875rem; font-weight: 800; cursor: pointer; }
    .system-tour__button:focus-visible { outline: 3px solid #e0a844; outline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) { .system-tour__progress span { transition: none; } }
</style>

<div id="systemTour" class="system-tour" role="dialog" aria-modal="true" aria-labelledby="systemTourTitle" aria-describedby="systemTourDescription" hidden>
    <div id="systemTourSpotlight" class="system-tour__spotlight" aria-hidden="true"></div>
    <section id="systemTourCard" class="system-tour__card">
        <div class="system-tour__progress" aria-hidden="true"><span id="systemTourProgress"></span></div>
        <div class="system-tour__content">
            <p id="systemTourCounter" class="text-xs font-black uppercase tracking-[0.18em] text-[#2c5e5e]"></p>
            <h2 id="systemTourTitle" class="mt-3 text-2xl font-black"></h2>
            <h3 id="systemTourStepTitle" class="mt-6 text-lg font-extrabold text-slate-800"></h3>
            <p id="systemTourDescription" class="mt-2 text-sm leading-7 text-slate-600"></p>
        </div>
        <div class="system-tour__actions">
            <button id="systemTourSkip" type="button" class="system-tour__button mr-auto bg-transparent text-slate-500 hover:bg-slate-100">Omitir</button>
            <button id="systemTourNext" type="button" class="system-tour__button bg-[#2c5e5e] text-white hover:bg-[#1a3b3b]">Entendido</button>
        </div>
    </section>
</div>

<script>
(() => {
    const tour = document.getElementById('systemTour');
    if (!tour) return;

    const configuredSteps = <?php echo json_encode($tourPasos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const storageKey = <?php echo json_encode($tourStorageKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const title = <?php echo json_encode($tourTitulo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const counter = document.getElementById('systemTourCounter');
    const titleElement = document.getElementById('systemTourTitle');
    const stepTitle = document.getElementById('systemTourStepTitle');
    const description = document.getElementById('systemTourDescription');
    const progress = document.getElementById('systemTourProgress');
    const spotlight = document.getElementById('systemTourSpotlight');
    const card = document.getElementById('systemTourCard');
    const next = document.getElementById('systemTourNext');
    let steps = [];
    let currentStep = 0;

    function findTarget(selector) {
        const targets = Array.from(document.querySelectorAll(selector));
        return targets.find(target => {
            const rect = target.getBoundingClientRect();
            const style = window.getComputedStyle(target);
            return rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none';
        }) || null;
    }

    function updateTourPlacement(target) {
        const rect = target.getBoundingClientRect();
        const margin = 7;
        spotlight.style.left = `${Math.max(0, rect.left - margin)}px`;
        spotlight.style.top = `${Math.max(0, rect.top - margin)}px`;
        spotlight.style.width = `${Math.min(window.innerWidth, rect.width + margin * 2)}px`;
        spotlight.style.height = `${Math.min(window.innerHeight, rect.height + margin * 2)}px`;

        card.style.transform = 'none';
        const cardRect = card.getBoundingClientRect();
        const gap = 16;
        let left = rect.left + (rect.width - cardRect.width) / 2;
        let top = rect.bottom + gap;

        if (top + cardRect.height > window.innerHeight - 12) {
            top = rect.top - cardRect.height - gap;
        }
        if (top < 12) {
            left = rect.right + gap + cardRect.width <= window.innerWidth - 12
                ? rect.right + gap
                : rect.left - cardRect.width - gap;
            top = rect.top + (rect.height - cardRect.height) / 2;
        }

        card.style.left = `${Math.max(12, Math.min(left, window.innerWidth - cardRect.width - 12))}px`;
        card.style.top = `${Math.max(12, Math.min(top, window.innerHeight - cardRect.height - 12))}px`;
    }

    function positionCurrentStep() {
        const target = findTarget(steps[currentStep][0]);
        if (!target) {
            spotlight.hidden = true;
            card.style.left = '50%';
            card.style.top = '50%';
            card.style.transform = 'translate(-50%, -50%)';
            return;
        }

        spotlight.hidden = false;
        target.scrollIntoView({ behavior: 'instant', block: 'center', inline: 'nearest' });
        updateTourPlacement(target);
    }

    function renderStep() {
        const [selector, heading, copy] = steps[currentStep];
        counter.textContent = `PASO ${currentStep + 1} DE ${steps.length}`;
        titleElement.textContent = title;
        stepTitle.textContent = heading;
        description.textContent = copy;
        progress.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
        next.textContent = currentStep === steps.length - 1 ? 'Entendido y finalizar' : 'Entendido';
        positionCurrentStep();
    }

    function markComplete() {
        try {
            localStorage.setItem(storageKey, 'completed');
        } catch (error) {
            console.error('No se pudo guardar el estado de la guía del sistema.', error);
        }
    }

    function closeTour(completed) {
        tour.hidden = true;
        spotlight.hidden = true;
        if (completed) markComplete();
    }

    function startTour() {
        steps = configuredSteps.filter(step => findTarget(step[0]));
        if (steps.length === 0) {
            console.error('No se encontraron elementos visibles para iniciar la guía del sistema.');
            return;
        }
        currentStep = 0;
        tour.hidden = false;
        renderStep();
        window.setTimeout(() => next.focus(), 0);
    }

    next.addEventListener('click', () => {
        if (currentStep < steps.length - 1) {
            currentStep += 1;
            renderStep();
            next.focus();
            return;
        }
        closeTour(true);
    });

    document.getElementById('systemTourSkip').addEventListener('click', () => closeTour(true));
    document.addEventListener('keydown', event => {
        if (tour.hidden) return;
        if (event.key === 'Escape') closeTour(true);
    });
    window.addEventListener('resize', () => {
        if (!tour.hidden) positionCurrentStep();
    });
    window.addEventListener('scroll', () => {
        if (tour.hidden) return;
        const target = findTarget(steps[currentStep][0]);
        if (target) updateTourPlacement(target);
    }, true);
    document.addEventListener('animationend', event => {
        if (tour.hidden) return;
        const target = findTarget(steps[currentStep][0]);
        if (target && (event.target === target || event.target.contains(target))) {
            updateTourPlacement(target);
        }
    }, true);

    document.addEventListener('click', event => {
        if (event.target.closest('[data-start-system-tour]')) startTour();
    });

    function showInitialTour() {
        try {
            if (localStorage.getItem(storageKey) !== 'completed') startTour();
        } catch (error) {
            console.error('No se pudo consultar el estado de la guía del sistema.', error);
            startTour();
        }
    }

    let initialTourStarted = false;
    const startInitialTourOnce = () => {
        if (initialTourStarted) return;
        initialTourStarted = true;
        showInitialTour();
    };

    window.addEventListener('pageshow', () => window.setTimeout(startInitialTourOnce, 0), { once: true });
    if (document.readyState === 'complete') window.setTimeout(startInitialTourOnce, 0);
})();
</script>
