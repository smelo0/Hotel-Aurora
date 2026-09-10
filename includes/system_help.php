<?php
$ayudaSistemaRol = $ayudaSistemaRol ?? 'usuario';
$ayudaSistemaContenido = [
    'usuario' => [
        'eyebrow' => 'Ayuda del huésped',
        'title' => '¿Cómo podemos ayudarte?',
        'questions' => [
            ['¿Cómo consulto y reservo una habitación?', 'Selecciona las fechas, indica la cantidad de huéspedes y pulsa buscar. Luego elige una habitación disponible y completa el proceso de pago.'],
            ['¿Qué métodos de pago están disponibles?', 'Puedes pagar mediante Wompi o registrar un pago en recepción, según las opciones habilitadas para tu reserva.'],
            ['¿Cómo solicito una experiencia?', 'En la sección de experiencias elige el servicio, la fecha, la hora y tus datos de contacto. El equipo confirmará la solicitud.'],
            ['¿Dónde consulto mi información?', 'Este panel es tu espacio privado para gestionar la información asociada a tu estancia y recibir novedades del hotel.'],
        ],
    ],
    'empleado' => [
        'eyebrow' => 'Ayuda operativa',
        'title' => '¿Qué necesitas consultar?',
        'questions' => [
            ['¿Cómo cambio de sección?', 'Usa el menú lateral para entrar al tablero, habitaciones, limpieza o huéspedes. La sección activa queda marcada en la navegación.'],
            ['¿Cómo actualizo el estado de una habitación?', 'Entra a Habitaciones, selecciona la habitación y usa la acción de cambio de estado. Confirma el nuevo estado para actualizar la operación.'],
            ['¿Cómo gestiono las tareas pendientes?', 'Abre la cola de tareas desde el acceso lateral. Desde allí puedes revisar, tomar y completar las tareas asignadas.'],
            ['¿Qué hago si encuentro una novedad?', 'Registra la novedad en la sección correspondiente y deja una observación clara para que el siguiente turno pueda darle seguimiento.'],
        ],
    ],
    'admin' => [
        'eyebrow' => 'Ayuda administrativa',
        'title' => '¿Qué necesitas consultar?',
        'questions' => [
            ['¿Cómo reviso la operación del hotel?', 'El tablero resume los indicadores principales. Usa Reservas, Operaciones y Finanzas para consultar el detalle de cada proceso.'],
            ['¿Cómo administro usuarios y roles?', 'Entra a Roles para consultar los perfiles existentes y aplicar las acciones permitidas según tu nivel de acceso.'],
            ['¿Cómo gestiono una reserva?', 'Abre Reservas para revisar el estado, los datos del huésped y las acciones disponibles para cada registro.'],
            ['¿Dónde configuro el sistema?', 'La sección Configuración concentra los parámetros generales disponibles para la administración del hotel.'],
        ],
    ],
];
$ayudaSistema = $ayudaSistemaContenido[$ayudaSistemaRol] ?? $ayudaSistemaContenido['usuario'];
?>

<style>
    .system-help-widget { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 120; font-family: Manrope, sans-serif; }
    .system-help-widget__panel { position: absolute; right: 0; bottom: 4.5rem; width: min(370px, calc(100vw - 2rem)); max-height: min(570px, calc(100vh - 7rem)); overflow-y: auto; border: 1px solid #d8e5e3; border-radius: 18px; background: #fff; box-shadow: 0 24px 60px rgba(26, 59, 59, .22); color: #1a3b3b; opacity: 0; pointer-events: none; transform: translateY(10px) scale(.98); transform-origin: bottom right; transition: opacity .18s ease, transform .18s ease; }
    .system-help-widget__panel.is-open { opacity: 1; pointer-events: auto; transform: translateY(0) scale(1); }
    .system-help-widget__question { border-top: 1px solid #e2eceb; }
    .system-help-widget__question summary { cursor: pointer; list-style: none; padding: 1rem 1.25rem; font-size: .875rem; font-weight: 800; }
    .system-help-widget__question summary::-webkit-details-marker { display: none; }
    .system-help-widget__question summary::after { content: '+'; float: right; color: #2c5e5e; font-size: 1.1rem; }
    .system-help-widget__question[open] summary::after { content: '-'; }
    .system-help-widget__answer { padding: 0 1.25rem 1rem; color: #526866; font-size: .8125rem; line-height: 1.6; }
    @media (max-width: 767px) { .system-help-widget { right: 1rem; bottom: 1rem; } .system-help-widget__panel { right: -.25rem; } }
</style>

<div class="system-help-widget">
    <section id="systemHelpPanel" class="system-help-widget__panel" role="dialog" aria-labelledby="systemHelpTitle" aria-hidden="true">
        <div class="flex items-start justify-between gap-4 bg-[#2C5E5E] px-5 py-4 text-white">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/70"><?php echo htmlspecialchars($ayudaSistema['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></p>
                <h2 id="systemHelpTitle" class="mt-1 text-lg font-black"><?php echo htmlspecialchars($ayudaSistema['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <button id="closeSystemHelp" type="button" class="rounded-full p-1 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Cerrar ayuda">
                <span class="material-symbols-outlined pointer-events-none">close</span>
            </button>
        </div>
        <div>
            <?php foreach ($ayudaSistema['questions'] as $pregunta): ?>
                <details class="system-help-widget__question">
                    <summary><?php echo htmlspecialchars($pregunta[0], ENT_QUOTES, 'UTF-8'); ?></summary>
                    <p class="system-help-widget__answer"><?php echo htmlspecialchars($pregunta[1], ENT_QUOTES, 'UTF-8'); ?></p>
                </details>
            <?php endforeach; ?>
            <div class="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
                ¿Necesitas más ayuda? Escríbenos a <a href="mailto:reservas@hotelaurora.com" class="font-bold text-[#2C5E5E] hover:text-[#c19046]">reservas@hotelaurora.com</a>.
            </div>
        </div>
    </section>
    <button id="systemHelpButton" type="button" class="flex items-center gap-2 rounded-xl bg-[#2C5E5E] px-4 py-3 text-sm font-black text-white shadow-xl transition hover:bg-[#1A3B3B]" aria-controls="systemHelpPanel" aria-expanded="false">
        <span class="material-symbols-outlined text-[20px]">help</span>
        <span>Ayuda</span>
    </button>
</div>
<script>
(() => {
    const panel = document.getElementById('systemHelpPanel');
    const button = document.getElementById('systemHelpButton');
    const closeButton = document.getElementById('closeSystemHelp');
    if (!panel || !button || !closeButton) return;

    const toggleHelp = (forceState = null) => {
        const shouldOpen = forceState === null ? !panel.classList.contains('is-open') : forceState;
        panel.classList.toggle('is-open', shouldOpen);
        panel.setAttribute('aria-hidden', String(!shouldOpen));
        button.setAttribute('aria-expanded', String(shouldOpen));
        if (shouldOpen) closeButton.focus();
    };

    button.addEventListener('click', () => toggleHelp());
    closeButton.addEventListener('click', () => toggleHelp(false));
    document.addEventListener('click', event => {
        if (!event.target.closest('.system-help-widget')) toggleHelp(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') toggleHelp(false);
    });
})();
</script>
