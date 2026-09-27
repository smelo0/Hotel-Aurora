<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Cookies | Hotel Aurora</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #d7b06a;
            --text-muted: rgba(238, 244, 255, 0.82);
        }
        body {
            background-color: #0f172a;
            background-image: linear-gradient(rgba(9, 14, 26, 0.82), rgba(9, 14, 26, 0.88)),
                               url('../../assets/images/fondo.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Manrope', sans-serif;
            color: var(--text-muted);
        }
        h1, h2, h3, .font-display { font-family: 'Playfair Display', serif; }
        .gold { color: var(--gold); }
        a.inline-link { color: var(--gold); text-decoration: underline; text-underline-offset: 2px; }
        a.inline-link:hover { color: #eecf98; }
        .section-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
        }
    </style>
</head>
<body class="min-h-screen">
    <header class="border-b border-white/10">
        <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 flex items-center justify-between gap-4">
            <a href="../../interfaz_usu.php" class="flex items-center gap-3">
                <img src="../../assets/images/logo.jpeg" alt="Hotel Aurora" class="h-10 w-auto rounded-full">
                <span class="font-display text-lg text-white">Hotel Aurora</span>
            </a>
            <a href="../../interfaz_usu.php" class="text-sm inline-link">&larr; Volver al sitio</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 md:px-6 py-10 md:py-14">
        <h1 class="font-display text-3xl md:text-4xl text-white mb-3">Política de Cookies</h1>
        <p class="text-sm text-white/60 mb-10">Última actualización: <?php echo date('d/m/Y'); ?></p>

        <section class="section-card p-6 md:p-8 mb-8">
            <p class="mb-4">
                Una cookie es un pequeño archivo que se almacena en tu navegador cuando visitas nuestro sitio.
                En <strong class="text-white">Hotel Aurora</strong> utilizamos cookies propias y de terceros para
                asegurar el funcionamiento de la plataforma, recordar tus preferencias y, si lo autorizas,
                analizar el uso del sitio.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Tipos de cookies que utilizamos</h2>
            <ul class="list-disc list-inside space-y-2 mb-4">
                <li><strong class="text-white">Técnicas / necesarias:</strong> imprescindibles para que puedas navegar, iniciar sesión y completar una reserva. No se pueden desactivar.</li>
                <li><strong class="text-white">Analíticas:</strong> nos ayudan a entender cómo se usa el sitio para mejorar la experiencia. Solo se activan si las autorizas.</li>
                <li><strong class="text-white">Marketing / publicidad:</strong> se usan para mostrar contenido y ofertas más relevantes para ti. Solo se activan si las autorizas.</li>
            </ul>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">¿Cómo gestionar tus preferencias?</h2>
            <p class="mb-4">
                Cuando visitas nuestro sitio por primera vez, verás un aviso en la parte inferior de la pantalla
                donde puedes aceptar todas las cookies, rechazarlas o configurar cuáles autorizas por categoría.
                Puedes cambiar tu decisión en cualquier momento borrando la cookie
                <code class="px-1 py-0.5 rounded bg-white/10 text-white/90">preferencia_cookies</code> desde la
                configuración de tu navegador; el aviso volverá a aparecer para que elijas de nuevo.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Cookies de terceros</h2>
            <p>
                Algunos servicios integrados en nuestro sitio, como el inicio de sesión con Google o nuestra
                pasarela de pagos, pueden establecer sus propias cookies técnicas necesarias para su
                funcionamiento. Estas cookies se rigen por las políticas de privacidad de dichos proveedores.
            </p>
        </section>

        <p class="text-sm text-white/50">
            ¿Buscas información sobre el tratamiento de tus datos personales? Consulta nuestra
            <a href="politica_privacidad.php" class="inline-link">Política de Tratamiento de Datos</a>.
        </p>
    </main>

    <footer class="px-4 pb-10 text-center text-sm text-white/50 md:px-6">
        Hotel Aurora © <?php echo date('Y'); ?> · Exclusividad, calma y servicio frente al mar.
    </footer>
</body>
</html>