<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Tratamiento de Datos Personales | Hotel Aurora</title>
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
        a.inline-link { color: var(--gold); text-decoration: underline; text-underline-offset: 2px; }
        a.inline-link:hover { color: #eecf98; }
        .privacy-policy-content h1,
        .privacy-policy-content h2 { font-family: 'Playfair Display', serif; }
        .privacy-policy-content h1 { color: #fff; font-size: 2.25rem; line-height: 1.2; margin: 0 0 .75rem; }
        .privacy-policy-content h2 { color: #fff; font-size: 1.25rem; margin: 1.5rem 0 .5rem; }
        .privacy-policy-content p { margin: 0 0 1rem; }
        .privacy-policy-content ul { list-style: disc; padding-left: 1.5rem; margin: 0 0 1rem; }
        .privacy-policy-content li { margin: .25rem 0; }
        .privacy-policy-content strong { color: #fff; }
        .privacy-policy-content__highlight { color: var(--gold); }
        .privacy-policy-content__updated { color: rgba(255,255,255,.6); font-size: .875rem; margin-bottom: 2.5rem !important; }
        .privacy-policy-content__section { padding: 1.5rem; margin-bottom: 2rem; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1); border-radius: 1rem; }
        .privacy-policy-content__cookies { color: rgba(255,255,255,.5); font-size: .875rem; }
        .privacy-policy-content__cookies a { color: var(--gold); text-decoration: underline; text-underline-offset: 2px; }
        .privacy-policy-content__cookies a:hover { color: #eecf98; }
        @media (max-width: 640px) {
            .privacy-policy-content h1 { font-size: 1.9rem; }
            .privacy-policy-content__section { padding: 1rem; }
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/translate.php'; ?>
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
        <?php
        $privacyPolicyCookiesUrl = 'politica_cookies.php';
        include __DIR__ . '/contenido_politica_privacidad.php';
        ?>
    </main>

    <footer class="px-4 pb-10 text-center text-sm text-white/50 md:px-6">
        Hotel Aurora © <?php echo date('Y'); ?> · Exclusividad, calma y servicio frente al mar.
    </footer>
</body>
</html>
