<!DOCTYPE html>
<html class="light" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>HOTEL AURORA - Terminal Operativa</title>

    <!-- Optimizaciones de conexión para recursos de Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Tipografías de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=Manrope:wght@200;300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <!-- Tailwind CSS (Script CDN optimizado) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Configuración del Tema alineada a la estética Verde Esmeralda / Dorado / Blanco / Negro -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#03271B",     /* Verde esmeralda dominante oficial */
                        "primary-hover": "#021C13",
                        "gold": "#D7B06A",        /* Dorado de identidad */
                        "gold-light": "#F0D39A",
                        "dark": "#111111",        /* Negro Profundo */
                        "surface": "#FFFFFF",     /* Superficie blanca oficial */
                        "card-bg": "#FFFFFF"      /* Blanco Puro */
                    },
                    borderRadius: { "lg": "1rem", "xl": "2rem", "full": "9999px" },
                    fontFamily: { "headline": ["Plus Jakarta Sans", "sans-serif"], "body": ["Manrope", "sans-serif"] }
                },
            },
        }
    </script>

    <!-- Hojas de estilo locales -->
    <link rel="stylesheet" href="css/estilos.css">
    
    <!-- Favicon SVG Funcional con Isotipo en Dorado/Verde -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><circle cx='50' cy='50' r='50' fill='%2303271B'/><text x='50%' y='55%' font-size='60' text-anchor='middle' dominant-baseline='middle' fill='%23D7B06A' font-family='sans-serif' font-weight='bold'>A</text></svg>">
</head>