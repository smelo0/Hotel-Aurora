<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>HOTEL AURORA - Recepción</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏨</text></svg>">

    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2C5E5E",
                        "secondary": "#4A8B8B",
                        "accent": "#E6F2F2",
                        "heading": "#1A3B3B",
                        "surface": "#F8FAFC",
                    },
                    borderRadius: { "lg": "1rem", "xl": "2rem", "full": "9999px" },
                    fontFamily: { "headline": ["Inter", "Arial", "sans-serif"], "body": ["Inter", "Arial", "sans-serif"] }
                },
            },
        }

        // Misma navegación del panel del gerente (admin.js la reemplaza al cargar).
        window.navegar = function(sec, btn) {
            if (!sec) return;
            document.querySelectorAll('.seccion-contenido').forEach(s => s.classList.add('hidden'));
            const seccion = document.getElementById(sec.startsWith('sec-') ? sec : 'sec-' + sec);
            if (seccion) seccion.classList.remove('hidden');
            if (btn) {
                document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active-nav', 'hover:bg-slate-50'));
                btn.classList.add('active-nav');
            }
        };
    </script>

    <link rel="stylesheet" href="../admin/CSS/estilos_ad.css?v=2">
</head>
