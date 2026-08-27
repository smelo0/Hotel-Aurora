<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design System - Hotel Aurora</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Manrope:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#2C5E5E",
                        secondary: "#4A8B8B",
                        accent: "#E6F2F2",
                        heading: "#1A3B3B",
                        surface: "#DDE6E6",

                        /* Token antiguo: se conserva para pantallas previas que usaban el verde inicial. */
                        "legacy-primary": "#1a3b3b",

                        /* Paleta oficial de estados para Habitaciones y Housekeeping. */
                        "state-disponible": "#22c55e",
                        "state-limpio": "#06b6d4",
                        "state-ocupada": "#f97316",
                        "state-sucia": "#57534e",
                        "state-mantenimiento": "#ef4444",
                        "state-urgente": "#ef4444"
                    },
                    borderRadius: {
                        lg: "1rem",
                        xl: "2rem",
                        full: "9999px"
                    },
                    fontFamily: {
                        headline: ["Plus Jakarta Sans", "sans-serif"],
                        body: ["Manrope", "sans-serif"]
                    },
                    boxShadow: {
                        aurora: "0 14px 35px -24px rgba(44, 94, 94, 0.35)",
                        "aurora-hover": "0 20px 40px -10px rgba(44, 94, 94, 0.18)",
                        modal: "0 30px 70px -28px rgba(15, 23, 42, 0.45)"
                    }
                }
            }
        };
    </script>

    <style>
        /* --- TOKENS GLOBALES --- */
        :root {
            --color-primary: #2C5E5E;
            --color-secondary: #4A8B8B;
            --color-accent: #E6F2F2;
            --color-heading: #1A3B3B;
            --color-surface: #DDE6E6;
            --color-card: #fbfdfd;
            --color-white: #ffffff;

            /* Token antiguo conservado para compatibilidad. */
            --color-legacy-primary: #1a3b3b;

            --state-disponible: #22c55e;
            --state-limpio: #06b6d4;
            --state-ocupada: #f97316;
            --state-sucia: #57534e;
            --state-sucia-soft: #78716c;
            --state-mantenimiento: #ef4444;
            --state-urgente: #ef4444;

            --shadow-soft: 0 14px 35px -24px rgba(44, 94, 94, 0.35);
            --shadow-hover: 0 20px 40px -10px rgba(44, 94, 94, 0.18);
            --shadow-modal: 0 30px 70px -28px rgba(15, 23, 42, 0.45);

            --radius-card: 1rem;
            --radius-modal: 1.5rem;
            --transition-smooth: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            --transition-fast: all 0.28s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Manrope", sans-serif;
            background: var(--color-surface);
            color: var(--color-heading);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: "Plus Jakarta Sans", sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: "FILL" 1, "wght" 400, "GRAD" 0, "opsz" 24;
            line-height: 1;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* --- COLORES DE ESTADO --- */
        .state-strip--disponible { border-top-color: var(--state-disponible) !important; }
        .state-strip--limpio { border-top-color: var(--state-limpio) !important; }
        .state-strip--ocupada { border-top-color: var(--state-ocupada) !important; }
        .state-strip--sucia { border-top-color: var(--state-sucia) !important; }
        .state-strip--mantenimiento,
        .state-strip--urgente { border-top-color: var(--state-mantenimiento) !important; }

        .state-soft--disponible {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(34, 197, 94, 0.24);
            box-shadow: 0 14px 35px -26px rgba(34, 197, 94, 0.28);
        }

        .state-soft--limpio {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(103, 232, 249, 0.3);
            box-shadow: 0 14px 35px -26px rgba(6, 182, 212, 0.28);
        }

        .state-soft--ocupada {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.09), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(251, 146, 60, 0.26);
            box-shadow: 0 14px 35px -26px rgba(249, 115, 22, 0.3);
        }

        .state-soft--sucia {
            background: linear-gradient(135deg, rgba(87, 83, 78, 0.1), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(120, 113, 108, 0.26);
            box-shadow: 0 14px 35px -26px rgba(87, 83, 78, 0.3);
        }

        .state-soft--mantenimiento,
        .state-soft--urgente {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.09), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(248, 113, 113, 0.28);
            box-shadow: 0 14px 35px -26px rgba(239, 68, 68, 0.32);
        }

        .state-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.625rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .state-badge--disponible { color: #15803d; background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.16); }
        .state-badge--limpio { color: #0891b2; background: rgba(6, 182, 212, 0.1); border-color: rgba(6, 182, 212, 0.18); }
        .state-badge--ocupada { color: #ea580c; background: rgba(249, 115, 22, 0.1); border-color: rgba(249, 115, 22, 0.18); }
        .state-badge--sucia { color: #57534e; background: rgba(87, 83, 78, 0.1); border-color: rgba(87, 83, 78, 0.18); }
        .state-badge--mantenimiento,
        .state-badge--urgente { color: #dc2626; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.18); }

        /* --- ANIMACIONES Y EFECTOS --- */
        .seccion-contenido {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-hover,
        .metric-card,
        .admin-card,
        .habitacion-card,
        .housekeeping-card {
            transition: var(--transition-smooth);
            will-change: transform, box-shadow;
        }

        .card-hover:hover,
        .metric-card:hover,
        .admin-card:hover,
        .habitacion-card:hover,
        .housekeeping-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            background: linear-gradient(to bottom right, var(--color-card), #eef6f6);
        }

        .icon-box,
        .housekeeping-icon {
            transition: var(--transition-fast);
        }

        .metric-card:hover .icon-box,
        .housekeeping-card:hover .housekeeping-icon {
            color: var(--color-white);
            background: var(--color-primary);
            transform: scale(1.06);
        }

        .modal-backdrop,
        .modal-visible {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-oculto {
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
        }

        .modal-visible {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }

        .tarea-exit {
            transform: translateX(24px) scale(0.96);
            opacity: 0;
            filter: blur(3px);
            max-height: 0;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            overflow: hidden;
            transition: all 0.32s ease;
        }

        .pulse-white {
            animation: pulseW 2.5s infinite;
        }

        @keyframes pulseW {
            0% { opacity: 1; scale: 1; }
            50% { opacity: 0.3; scale: 1.1; }
            100% { opacity: 1; scale: 1; }
        }

        /* --- COMPONENTES ESTRUCTURALES --- */
        .panel-container,
        .admin-card,
        .metric-card {
            background: var(--color-card);
            border: 1px solid rgba(44, 94, 94, 0.13);
            box-shadow: var(--shadow-soft);
        }

        .admin-card,
        .metric-card,
        .habitacion-card,
        .housekeeping-card {
            border-top: 4px solid var(--color-primary);
            border-radius: var(--radius-card);
        }

        .app-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
            display: flex;
            width: 16rem;
            height: 100%;
            flex-direction: column;
            padding: 2rem 0;
            background: #f7fafa;
            border-right: 1px solid rgba(44, 94, 94, 0.15);
            box-shadow: 8px 0 30px rgba(44, 94, 94, 0.06);
        }

        .nav-item {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 2rem;
            color: #94a3b8;
            transition: var(--transition-fast);
        }

        .nav-item:hover {
            color: var(--color-primary);
        }

        .active-nav {
            color: var(--color-primary) !important;
            font-weight: 800;
            position: relative;
        }

        .active-nav::after {
            content: "";
            position: absolute;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--color-primary);
            border-radius: 0 4px 4px 0;
        }

        /* --- BOTONES BASE --- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.75rem;
            border-radius: 0.75rem;
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            font-weight: 900;
            transition: var(--transition-fast);
            cursor: pointer;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary,
        .btn-new-task {
            color: var(--color-white);
            background: var(--color-primary);
            box-shadow: 0 14px 28px -18px rgba(44, 94, 94, 0.75);
        }

        .btn-primary:hover,
        .btn-new-task:hover {
            filter: brightness(1.1);
        }

        .btn-secondary {
            color: #64748b;
            background: #f1f5f9;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-danger,
        .btn-logout {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
        }

        .btn-danger:hover,
        .btn-logout:hover {
            color: var(--color-white);
            background: #ef4444;
        }

        .sidebar-action {
            width: 100%;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 900;
            box-shadow: 0 16px 30px -18px rgba(44, 94, 94, 0.75);
        }

        /* --- MODALES --- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: opacity 0.28s ease, visibility 0.28s ease;
        }

        .modal-card {
            width: 100%;
            max-width: 28rem;
            overflow: hidden;
            border-radius: var(--radius-modal);
            background: var(--color-white);
            border: 1px solid rgba(44, 94, 94, 0.1);
            box-shadow: var(--shadow-modal);
            transform: scale(0.95);
            opacity: 0;
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
        }

        .modal-visible .modal-card,
        .modal-card.is-visible {
            transform: scale(1);
            opacity: 1;
        }

        .modal-eyebrow {
            margin-bottom: 0.25rem;
            color: var(--color-secondary);
            font-size: 0.625rem;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .modal-title {
            color: var(--color-primary);
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .modal-copy {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .form-field {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 1rem;
            color: #334155;
            font-size: 0.875rem;
            font-weight: 700;
            outline: none;
        }

        .form-field:focus {
            border-color: rgba(44, 94, 94, 0.35);
            box-shadow: 0 0 0 3px rgba(44, 94, 94, 0.12);
        }

        /* --- HABITACIONES Y HOUSEKEEPING --- */
        .habitacion-card,
        .housekeeping-card {
            background-color: var(--color-card);
            isolation: isolate;
        }

        .habitacion-card--disponible,
        .housekeeping-card--disponible {
            border-top-color: var(--state-disponible);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(34, 197, 94, 0.24);
            box-shadow: 0 14px 35px -26px rgba(34, 197, 94, 0.28);
        }

        .habitacion-card--limpio,
        .housekeeping-card--limpio {
            border-top-color: var(--state-limpio);
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.08), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(103, 232, 249, 0.3);
            box-shadow: 0 14px 35px -26px rgba(6, 182, 212, 0.28);
        }

        .habitacion-card--ocupada,
        .housekeeping-card--ocupada {
            border-top-color: var(--state-ocupada);
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.09), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(251, 146, 60, 0.26);
            box-shadow: 0 14px 35px -26px rgba(249, 115, 22, 0.3);
        }

        .habitacion-card--sucia,
        .housekeeping-card--sucia {
            border-top-color: var(--state-sucia);
            background: linear-gradient(135deg, rgba(87, 83, 78, 0.1), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(120, 113, 108, 0.26);
            box-shadow: 0 14px 35px -26px rgba(87, 83, 78, 0.3);
        }

        .habitacion-card--mantenimiento,
        .housekeeping-card--mantenimiento,
        .habitacion-card--urgente,
        .housekeeping-card--urgente {
            border-top-color: var(--state-mantenimiento);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.09), rgba(251, 253, 253, 0.96) 50%, var(--color-card));
            border-color: rgba(248, 113, 113, 0.28);
            box-shadow: 0 14px 35px -26px rgba(239, 68, 68, 0.32);
        }

        .room-dot {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-size: 0.75rem;
            font-weight: 900;
            will-change: transform;
            transition: transform 0.34s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.34s ease, filter 0.34s ease;
        }

        .room-dot:hover {
            transform: translateY(-4px) scale(1.018);
            box-shadow: 0 22px 38px -26px rgba(15, 23, 42, 0.72), 0 10px 18px -18px rgba(15, 23, 42, 0.35);
            filter: saturate(1.04) brightness(1.015);
        }

        .room-dot--disponible {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.86), rgba(34, 197, 94, 0.68));
            color: var(--color-white);
            box-shadow: 0 16px 30px -24px rgba(34, 197, 94, 0.7);
        }

        .room-dot--limpio {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.86), rgba(6, 182, 212, 0.66));
            color: var(--color-white);
            box-shadow: 0 16px 30px -24px rgba(6, 182, 212, 0.7);
        }

        .room-dot--ocupada {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.9), rgba(249, 115, 22, 0.68));
            color: var(--color-white);
            box-shadow: 0 16px 30px -24px rgba(249, 115, 22, 0.7);
        }

        .room-dot--sucia {
            background: linear-gradient(135deg, rgba(87, 83, 78, 0.92), rgba(120, 113, 108, 0.72));
            color: var(--color-white);
            box-shadow: 0 16px 30px -24px rgba(87, 83, 78, 0.7);
        }

        .room-dot--mantenimiento,
        .room-dot--urgente {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.92), rgba(239, 68, 68, 0.7));
            color: var(--color-white);
            box-shadow: 0 16px 30px -24px rgba(239, 68, 68, 0.75);
        }

        .room-dot--clickable {
            cursor: pointer;
        }

        .room-dot:not(.room-dot--clickable) {
            cursor: default;
        }

        .housekeeping-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            background: rgba(230, 242, 242, 0.75);
            border: 1px solid rgba(44, 94, 94, 0.08);
            border-radius: 12px;
        }

        /* --- TAREAS, COLA Y DRAG AND DROP --- */
        .task-item {
            cursor: grab;
            user-select: none;
        }

        .dragging {
            opacity: 0.4;
            scale: 0.98;
            border: 2px dashed var(--color-primary) !important;
        }

        aside {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .aside-oculto {
            transform: translateX(100%);
        }

        .btn-toggle-aside {
            position: absolute;
            left: -32px;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            background: var(--color-card);
            border: 1px solid rgba(44, 94, 94, 0.13);
            border-radius: 12px 0 0 12px;
            box-shadow: -4px 0 10px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .cola-arrow-btn {
            height: 36px;
            min-width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            background: var(--color-white);
            border: 1px solid rgba(44, 94, 94, 0.13);
            border-radius: 10px;
            box-shadow: 0 12px 24px -18px rgba(44, 94, 94, 0.55);
            transition: var(--transition-fast);
        }

        .cola-arrow-btn:hover {
            color: var(--color-white);
            background: var(--color-primary);
            transform: translateX(2px);
            box-shadow: 0 14px 28px -16px rgba(44, 94, 94, 0.75);
        }

        .cola-arrow-btn.w-0 {
            height: 0;
            min-width: 0;
            border-width: 0;
            box-shadow: none;
        }

        /* --- MODAL DE MANTENIMIENTO --- */
        .maintenance-modal-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.28s ease;
        }

        .maintenance-modal-overlay.modal-visible {
            opacity: 1;
            pointer-events: auto;
        }

        .maintenance-modal-card {
            transform: translateY(12px) scale(0.96);
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
            opacity: 0;
        }

        .maintenance-modal-overlay.modal-visible .maintenance-modal-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .maintenance-popover {
            position: absolute;
            left: 18px;
            right: 18px;
            top: calc(100% - 10px);
            z-index: 20;
            padding: 14px 16px;
            background: var(--color-white);
            border: 1px solid #fecaca;
            border-radius: 12px;
            box-shadow: 0 24px 45px -22px rgba(127, 29, 29, 0.35);
            opacity: 0;
            transform: translateY(-6px) scale(0.98);
            pointer-events: none;
            transition: opacity 0.22s ease, transform 0.22s ease;
        }

        .housekeeping-card.motivo-visible .maintenance-popover {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .toggle-checkbox:checked {
            right: 0;
            border-color: var(--color-primary);
        }

        .toggle-checkbox:checked + .toggle-label {
            background-color: var(--color-primary);
        }
    </style>
</head>

<body class="font-body antialiased">
    <main class="max-w-6xl mx-auto px-6 py-10 space-y-10">
        <header class="bg-[#f7fafa] border border-primary/15 rounded-xl p-8 shadow-aurora">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-secondary mb-2">Sistema visual oficial</p>
            <h1 class="text-4xl font-black text-primary tracking-tight mb-3">Design System - Hotel Aurora</h1>
            <p class="text-slate-500 max-w-3xl">
                Catálogo centralizado de variables, clases y componentes base usados en los paneles de Administrador y Empleado.
            </p>
        </header>

        <section class="panel-container rounded-xl p-8">
            <h2 class="text-xl font-black text-heading mb-2">1. Tokens Base</h2>
            <p class="text-sm text-slate-500 mb-6">Colores principales, superficies, tipografías y sombras que deben mantenerse iguales entre paneles.</p>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="rounded-lg border border-slate-100 bg-white p-4">
                    <div class="h-16 rounded-lg bg-primary mb-3"></div>
                    <p class="text-xs font-black text-heading">Primary</p>
                    <p class="text-[11px] text-slate-400">#2C5E5E</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-white p-4">
                    <div class="h-16 rounded-lg bg-secondary mb-3"></div>
                    <p class="text-xs font-black text-heading">Secondary</p>
                    <p class="text-[11px] text-slate-400">#4A8B8B</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-white p-4">
                    <div class="h-16 rounded-lg bg-accent mb-3"></div>
                    <p class="text-xs font-black text-heading">Accent</p>
                    <p class="text-[11px] text-slate-400">#E6F2F2</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-white p-4">
                    <div class="h-16 rounded-lg bg-heading mb-3"></div>
                    <p class="text-xs font-black text-heading">Heading</p>
                    <p class="text-[11px] text-slate-400">#1A3B3B</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-white p-4">
                    <div class="h-16 rounded-lg bg-legacy-primary mb-3"></div>
                    <p class="text-xs font-black text-heading">Legacy Primary</p>
                    <p class="text-[11px] text-slate-400">#1a3b3b</p>
                </div>
            </div>
        </section>

        <section class="panel-container rounded-xl p-8">
            <h2 class="text-xl font-black text-heading mb-2">2. Paleta de Estados</h2>
            <p class="text-sm text-slate-500 mb-6">Franjas superiores, fondos difuminados, badges y puntos usados por Habitaciones y Housekeeping.</p>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <article class="habitacion-card habitacion-card--disponible p-5 border">
                    <span class="state-badge state-badge--disponible">Disponible</span>
                    <h3 class="text-3xl font-black mt-5">101</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Verde</p>
                </article>

                <article class="habitacion-card habitacion-card--limpio p-5 border">
                    <span class="state-badge state-badge--limpio">Limpio</span>
                    <h3 class="text-3xl font-black mt-5">102</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Azul claro / Cian</p>
                </article>

                <article class="habitacion-card habitacion-card--ocupada p-5 border">
                    <span class="state-badge state-badge--ocupada">Ocupada</span>
                    <h3 class="text-3xl font-black mt-5">104</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Naranja</p>
                </article>

                <article class="habitacion-card habitacion-card--sucia p-5 border">
                    <span class="state-badge state-badge--sucia">Sucia</span>
                    <h3 class="text-3xl font-black mt-5">108</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Gris oscuro / Café</p>
                </article>

                <article class="habitacion-card habitacion-card--mantenimiento p-5 border">
                    <span class="state-badge state-badge--mantenimiento">Mantenimiento</span>
                    <h3 class="text-3xl font-black mt-5">109</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Rojo / Urgente</p>
                </article>
            </div>

            <div class="grid grid-cols-5 gap-3 mt-8 max-w-md">
                <div class="room-dot room-dot--disponible">101</div>
                <div class="room-dot room-dot--limpio">102</div>
                <div class="room-dot room-dot--ocupada">104</div>
                <div class="room-dot room-dot--sucia">108</div>
                <div class="room-dot room-dot--mantenimiento room-dot--clickable">109</div>
            </div>
        </section>

        <section class="panel-container rounded-xl p-8">
            <h2 class="text-xl font-black text-heading mb-2">3. Efectos y Animaciones Globales</h2>
            <p class="text-sm text-slate-500 mb-6">Hover profesional de tarjetas, entrada de secciones y overlay con blur para modales.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <article class="metric-card bg-[#fbfdfd] rounded-xl p-6 border shadow-md">
                    <div class="icon-box w-12 h-12 rounded-xl bg-accent text-primary flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined">analytics</span>
                    </div>
                    <h3 class="text-lg font-black">Metric Card</h3>
                    <p class="text-sm text-slate-500">Usa translateY, sombra elegante y transición suave.</p>
                </article>

                <article class="housekeeping-card housekeeping-card--disponible rounded-xl p-6 border shadow-md">
                    <div class="housekeeping-icon mb-5">
                        <span class="material-symbols-outlined">cleaning_services</span>
                    </div>
                    <h3 class="text-lg font-black">Housekeeping Card</h3>
                    <p class="text-sm text-slate-500">Conserva el tinte del estado durante el hover.</p>
                </article>

                <article class="rounded-xl p-6 bg-slate-900/60 text-white modal-backdrop">
                    <span class="material-symbols-outlined mb-5">blur_on</span>
                    <h3 class="text-lg font-black">Modal Backdrop</h3>
                    <p class="text-sm text-white/75">Clase base: <strong>.modal-backdrop</strong>.</p>
                </article>
            </div>
        </section>

        <section class="panel-container rounded-xl p-8">
            <h2 class="text-xl font-black text-heading mb-2">4. Sidebar y Navegación</h2>
            <p class="text-sm text-slate-500 mb-6">Estado activo compartido por el menú del Administrador y el del Empleado.</p>

            <div class="grid grid-cols-1 lg:grid-cols-[16rem_1fr] gap-8">
                <aside class="app-sidebar static h-auto rounded-xl overflow-hidden">
                    <div class="mb-8 px-8">
                        <h3 class="text-xl font-black tracking-tighter text-primary">HOTEL AURORA</h3>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Panel de Control</p>
                    </div>

                    <nav class="flex-1 flex flex-col">
                        <button class="nav-item active-nav" type="button">
                            <span class="material-symbols-outlined">dashboard</span>
                            <span class="text-sm font-bold">Dashboard</span>
                        </button>
                        <button class="nav-item" type="button">
                            <span class="material-symbols-outlined">hotel</span>
                            <span class="text-sm font-bold">Habitaciones</span>
                        </button>
                        <button class="nav-item" type="button">
                            <span class="material-symbols-outlined">cleaning_services</span>
                            <span class="text-sm font-bold">Limpieza</span>
                        </button>
                    </nav>
                </aside>

                <div class="rounded-xl border border-slate-100 bg-white p-6">
                    <p class="text-[10px] font-black uppercase tracking-widest text-secondary mb-3">Clase activa</p>
                    <code class="block rounded-lg bg-slate-950 text-slate-100 p-4 text-sm overflow-x-auto">.active-nav { color: var(--color-primary); font-weight: 800; }
.active-nav::after { width: 4px; background: var(--color-primary); }</code>
                </div>
            </div>
        </section>

        <section class="panel-container rounded-xl p-8">
            <h2 class="text-xl font-black text-heading mb-2">5. Botones Base</h2>
            <p class="text-sm text-slate-500 mb-6">Botones oficiales para tareas, cancelación y cierre de sesión.</p>

            <div class="flex flex-wrap gap-4">
                <button class="btn btn-primary" type="button">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Nueva Tarea
                </button>
                <button class="btn btn-secondary" type="button">Cancelar</button>
                <button class="btn btn-danger" type="button">
                    <span class="material-symbols-outlined text-sm">logout</span>
                    Cerrar Sesión
                </button>
            </div>
        </section>

        <section class="panel-container rounded-xl p-8">
            <h2 class="text-xl font-black text-heading mb-2">6. Modales</h2>
            <p class="text-sm text-slate-500 mb-6">Base visual para Nueva Tarea, Cerrar Sesión y avisos de mantenimiento.</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl bg-slate-900/60 modal-backdrop p-6">
                    <div class="modal-card is-visible mx-auto">
                        <div class="p-8">
                            <p class="modal-eyebrow">Operación interna</p>
                            <h3 class="modal-title mb-6">Nueva Tarea</h3>
                            <div class="space-y-3">
                                <input class="form-field" type="text" value="Revisar habitación 109" aria-label="Título de tarea">
                                <select class="form-field" aria-label="Categoría de tarea">
                                    <option>URGENTE</option>
                                    <option>LIMPIEZA</option>
                                    <option>GENERAL</option>
                                </select>
                                <textarea class="form-field resize-none" rows="3" aria-label="Detalles de tarea">Validar motivo de mantenimiento.</textarea>
                            </div>
                            <div class="flex gap-3 pt-5">
                                <button class="btn btn-secondary flex-1" type="button">Cancelar</button>
                                <button class="btn btn-primary flex-1" type="button">Confirmar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-900/60 modal-backdrop p-6">
                    <div class="modal-card is-visible mx-auto">
                        <div class="p-8 text-center">
                            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="material-symbols-outlined text-4xl">logout</span>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">Cerrar Sesión</h3>
                            <p class="modal-copy">Estás a punto de salir del sistema. Asegúrate de haber guardado todos tus cambios.</p>
                        </div>
                        <div class="flex border-t border-slate-100">
                            <button class="flex-1 px-6 py-4 text-sm font-bold text-slate-400 hover:bg-slate-50 transition-colors border-r border-slate-100" type="button">Seguir trabajando</button>
                            <button class="flex-1 px-6 py-4 text-sm font-black text-red-500 hover:bg-red-50 transition-colors" type="button">Salir</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>