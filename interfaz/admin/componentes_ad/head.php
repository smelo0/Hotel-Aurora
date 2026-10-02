<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>HOTEL AURORA - Admin Pro</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries&v=2026-10-01-admin-theme"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Manrope:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏨</text></svg>">

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#03271B",
                        "primary-hover": "#021C13",
                        "secondary": "#D7B06A",
                        "gold": "#D7B06A",
                        "gold-light": "#F0D39A",
                        "dark": "#111111",
                        "accent": "#F4F8F6",
                        "heading": "#111111",
                        "surface": "#FFFFFF",
                        "card-bg": "#FFFFFF"
                    },
                    borderRadius: { "lg": "1rem", "xl": "2rem", "full": "9999px" },
                    fontFamily: { "headline": ["Plus Jakarta Sans"], "body": ["Manrope"] }
                },
            },
        }
    </script>
    <style>
        :root {
            --admin-primary: #03271B;
            --admin-primary-hover: #021C13;
            --admin-gold: #D7B06A;
            --admin-gold-light: #F0D39A;
            --admin-dark: #111111;
            --admin-surface: #FFFFFF;
            --admin-card-bg: #FFFFFF;
            --admin-accent: #F4F8F6;
            --admin-success: #034E2B;
            --admin-limpio: #06B6D4;
            --admin-ocupada: #F97316;
            --admin-sucia: #57534E;
            --admin-mantenimiento: #EF4444;
        }

        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .no-scrollbar::-webkit-scrollbar { display: none; }

        /* Forzar la paleta final del panel administrador para evitar que queden tonos legacy del slate en elementos antiguos. */
        .bg-slate-50, .bg-slate-100, .bg-slate-200, .bg-slate-300, .bg-slate-400, .bg-slate-500, .bg-slate-600, .bg-slate-700, .bg-slate-800, .bg-slate-900,
        .border-slate-100, .border-slate-200, .border-slate-300,
        .text-slate-400, .text-slate-500, .text-slate-600, .text-slate-700, .text-slate-800,
        .hover\:bg-slate-50:hover,
        .hover\:bg-slate-100:hover {
            background-color: var(--admin-accent) !important;
        }
        .text-slate-400, .text-slate-500, .text-slate-600, .text-slate-700, .text-slate-800,
        .text-slate-300 {
            color: rgba(3, 39, 27, 0.7) !important;
        }
        .border-slate-100, .border-slate-200, .border-slate-300 {
            border-color: rgba(3, 39, 27, 0.10) !important;
        }
        .active-nav { color: var(--admin-primary) !important; font-weight: 800; position: relative; }
        .active-nav::after {
            content: ''; position: absolute; left: 0; width: 4px; height: 100%;
            background: var(--admin-gold); border-radius: 0 4px 4px 0;
        }
        .seccion-contenido { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .admin-card { background: var(--admin-card-bg); border: 1px solid rgba(3, 39, 27, 0.10); border-top: 4px solid var(--admin-primary); box-shadow: 0 14px 35px -24px rgba(3, 39, 27, 0.35); transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .admin-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px -10px rgba(3, 39, 27, 0.18); background: linear-gradient(to bottom right, #fbfdfd, #eef6f6); }
        /* Modificación: Se refinó el hover de Housekeeping con una elevación más suave, sombra moderna y transición profesional. */
        .room-dot {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-size: 0.75rem;
            font-weight: 800;
            will-change: transform;
            transition: transform 0.34s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.34s ease, filter 0.34s ease;
        }
        /* Modificación: Se cambió la animación hover por un resaltado más sutil y elegante. */
        .room-dot:hover {
            transform: translateY(-4px) scale(1.018);
            box-shadow: 0 22px 38px -26px rgba(15, 23, 42, 0.72), 0 10px 18px -18px rgba(15, 23, 42, 0.35);
            filter: saturate(1.04) brightness(1.015);
        }
        /* Modificación: Se sincronizaron los colores de Housekeeping con la paleta de estados del Panel del Empleado. */
        .room-dot--disponible {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.86), rgba(34, 197, 94, 0.68));
            color: #ffffff;
            box-shadow: 0 16px 30px -24px rgba(34, 197, 94, 0.7);
        }
        /* Modificación: Limpio ahora usa azul claro/cian igual que el Panel del Empleado. */
        .room-dot--limpio {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.86), rgba(6, 182, 212, 0.66));
            color: #ffffff;
            box-shadow: 0 16px 30px -24px rgba(6, 182, 212, 0.7);
        }
        /* Modificación: Ocupada ahora usa naranja igual que el Panel del Empleado. */
        .room-dot--ocupada {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.9), rgba(249, 115, 22, 0.68));
            color: #ffffff;
            box-shadow: 0 16px 30px -24px rgba(249, 115, 22, 0.7);
        }
        /* Modificación: Sucia ahora usa gris oscuro/café igual que el Panel del Empleado. */
        .room-dot--sucia {
            background: linear-gradient(135deg, rgba(87, 83, 78, 0.92), rgba(120, 113, 108, 0.72));
            color: #ffffff;
            box-shadow: 0 16px 30px -24px rgba(87, 83, 78, 0.7);
        }
        /* Modificación: Mantenimiento conserva rojo para indicar fuera de servicio. */
        .room-dot--mantenimiento {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.92), rgba(239, 68, 68, 0.7));
            color: #ffffff;
            box-shadow: 0 16px 30px -24px rgba(239, 68, 68, 0.75);
        }
        .room-dot--clickable { cursor: pointer; }
        .room-dot:not(.room-dot--clickable) { cursor: default; }
        /* Modificación: Se creó un modal de mantenimiento con el mismo acabado visual del modal Cambiar Estado. */
        .maintenance-modal-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.28s ease;
        }
        /* Modificación: Se agregó el estado visible del fondo del modal para permitir cierre al hacer clic fuera. */
        .maintenance-modal-overlay.modal-visible {
            opacity: 1;
            pointer-events: auto;
        }
        /* Modificación: Se diseñó la tarjeta del motivo con escala inicial para animación de entrada limpia. */
        .maintenance-modal-card {
            transform: translateY(12px) scale(0.96);
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
            opacity: 0;
        }
        /* Modificación: Se agregó la animación final de aparición para la tarjeta de mantenimiento. */
        .maintenance-modal-overlay.modal-visible .maintenance-modal-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .toggle-checkbox:checked { right: 0; border-color: var(--admin-primary); }
        .toggle-checkbox:checked + .toggle-label { background-color: var(--admin-primary); }
        .ui-action {
            transition: transform 0.18s ease, box-shadow 0.22s ease, background-color 0.22s ease, color 0.22s ease, opacity 0.22s ease;
        }
        .ui-action:hover { transform: translateY(-1px); }
        .ui-action:active { transform: translateY(0) scale(0.98); }
        .ui-action:focus-visible { outline: 3px solid rgba(3, 39, 27, 0.18); outline-offset: 3px; }
        .modal-reserva-shell {
            opacity: 0;
            transition: opacity 0.3s ease-out;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .modal-reserva-shell.modal-reserva-visible { opacity: 1; }
        .modal-reserva-card {
            transform: translateY(24px) scale(0.98);
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            opacity: 0;
            font-family: 'Inter', 'Poppins', sans-serif;
        }
        .modal-reserva-shell.modal-reserva-visible .modal-reserva-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .reserva-field {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        .reserva-field:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 4px rgba(3, 39, 27, 0.12);
            background-color: #ffffff;
        }
        .modal-reserva-card label {
            font-family: 'Inter', 'Poppins', sans-serif;
            color: #64748b;
        }
        .modal-editar-reserva-shell {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .modal-editar-reserva-shell.modal-editar-reserva-visible { opacity: 1; }
        .modal-editar-reserva-card {
            transform: translateY(12px) scale(0.97);
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .modal-editar-reserva-shell.modal-editar-reserva-visible .modal-editar-reserva-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .confirm-reserva {
            opacity: 0;
            transition: opacity 0.22s ease;
        }
        .confirm-reserva.confirm-reserva-visible { opacity: 1; }
        .confirm-reserva-card {
            transform: scale(0.96) translateY(10px);
            opacity: 0;
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.22s ease;
        }
        .confirm-reserva.confirm-reserva-visible .confirm-reserva-card {
            transform: scale(1) translateY(0);
            opacity: 1;
        }
        .reserva-row-updated {
            animation: reservaRowUpdated 0.9s ease;
        }
        @keyframes reservaRowUpdated {
            0% { background: rgba(230, 242, 242, 0.95); }
            100% { background: transparent; }
        }
        .modal-rol-backdrop {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            min-width: 100vw;
            min-height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>
    <style>
        /* Traductor de emergencia para el modal del Administrador */
        .modal-oculto { opacity: 0; pointer-events: none; visibility: hidden; }
        .modal-visible { opacity: 1; pointer-events: auto; visibility: visible; }
    </style>
</head>
