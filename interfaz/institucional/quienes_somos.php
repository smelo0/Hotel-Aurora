<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Quiénes somos? | Hotel Aurora</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #d7b06a; --text-muted: rgba(238, 244, 255, 0.82); }
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
        h1, h2, .font-display { font-family: 'Playfair Display', serif; }
        .gold { color: var(--gold); }
        a.inline-link { color: var(--gold); text-decoration: underline; text-underline-offset: 2px; }
        a.inline-link:hover { color: #eecf98; }
        .section-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; }
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
        <h1 class="font-display text-3xl md:text-4xl text-white mb-3">¿Quiénes somos?</h1>
        <p class="text-sm text-white/60 mb-10">Última actualización: <?php echo date('d/m/Y'); ?></p>

        <section class="section-card p-6 md:p-8 mb-8">
            <p class="mb-4">
                <strong class="text-white">Hotel Aurora</strong> es un hotel boutique frente al mar, pensado para
                quienes buscan exclusividad, calma y un servicio cercano. Cada detalle de la experiencia —desde
                nuestras habitaciones hasta el trato del equipo— está diseñado para que la estadía se sienta
                íntima y sin prisas.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Nuestra propuesta</h2>
            <p class="mb-4">
                Combinamos hospitalidad tradicional con herramientas propias de reserva y gestión, para que
                planear tu estadía —desde elegir la habitación hasta pagar tu reserva— sea simple y transparente
                de principio a fin.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Nuestros valores</h2>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li>Servicio cercano y personalizado en cada etapa de la estadía.</li>
                <li>Transparencia en tarifas, políticas y tratamiento de tus datos.</li>
                <li>Cuidado del entorno costero en el que operamos.</li>
                <li>Mejora continua de la experiencia, dentro y fuera de la plataforma.</li>
            </ul>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Misión</h2>
            <p class="mb-4">
                Ofrecer a cada huésped una estadía exclusiva y sin fricciones frente al mar, combinando un
                servicio cercano con herramientas propias de reserva que hagan simple y transparente cada paso
                de su experiencia con Hotel Aurora.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Visión</h2>
            <p>
                Ser reconocidos como un referente de hospitalidad boutique en la costa, distinguidos por la
                calidez de nuestro servicio, el cuidado de nuestro entorno y la confianza que generamos en
                quienes eligen quedarse con nosotros.
            </p>
        </section>
    </main>

    <footer class="px-4 pb-10 text-center text-sm text-white/50 md:px-6">
        Hotel Aurora © <?php echo date('Y'); ?> · Exclusividad, calma y servicio frente al mar.
    </footer>
</body>
</html>