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
        <h1 class="font-display text-3xl md:text-4xl text-white mb-3">Política de Tratamiento de Datos Personales</h1>
        <p class="text-sm text-white/60 mb-10">Última actualización: <?php echo date('d/m/Y'); ?></p>

        <section class="section-card p-6 md:p-8 mb-8">
            <p class="mb-4">
                En <strong class="text-white">Hotel Aurora</strong> recolectamos y tratamos tus datos personales de acuerdo
                con la <span class="gold">Ley 1581 de 2012</span> y el <span class="gold">Decreto 1377 de 2013</span> de la
                República de Colombia, normas que regulan la protección de datos personales (Habeas Data).
                Al registrarte, reservar una habitación o utilizar nuestros servicios, aceptas que tratemos tu
                información conforme a esta política.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">¿Qué datos recolectamos?</h2>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li>Datos de identificación: nombre completo, correo electrónico y contraseña de acceso.</li>
                <li>Datos de la reserva: fechas de estadía, tipo de habitación, huéspedes y solicitudes especiales.</li>
                <li>Datos de pago: información necesaria para procesar el cobro a través de nuestra pasarela de pagos, la cual nunca almacenamos directamente en nuestros servidores.</li>
                <li>Datos técnicos: dirección IP y preferencias de navegación (ver nuestra Política de Cookies).</li>
            </ul>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">¿Para qué usamos tus datos?</h2>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li>Gestionar tu cuenta, tus reservas y la prestación del servicio hotelero.</li>
                <li>Procesar pagos de forma segura junto con nuestra pasarela de pagos.</li>
                <li>Enviarte confirmaciones, recordatorios y comunicaciones relacionadas con tu estadía.</li>
                <li>Mejorar la seguridad, el funcionamiento y la experiencia de nuestra plataforma.</li>
                <li>Cumplir obligaciones legales y contractuales.</li>
            </ul>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">Tus derechos como titular</h2>
            <p class="mb-4">Como titular de tus datos personales, tienes derecho a:</p>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li>Conocer, actualizar y rectificar tus datos personales.</li>
                <li>Solicitar prueba de la autorización otorgada para el tratamiento de tus datos.</li>
                <li>Ser informado sobre el uso que se le ha dado a tus datos.</li>
                <li>Revocar la autorización y/o solicitar la supresión de tus datos, cuando no exista un deber legal o contractual que impida su eliminación.</li>
                <li>Acceder de forma gratuita a tus datos personales tratados por Hotel Aurora.</li>
            </ul>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">¿Con quién compartimos tus datos?</h2>
            <p class="mb-4">
                No vendemos ni compartimos tus datos personales con terceros para fines comerciales ajenos a
                Hotel Aurora. Solo compartimos la información estrictamente necesaria con nuestra pasarela de
                pagos para procesar tus transacciones de forma segura, o cuando una autoridad competente lo
                exija por ley.
            </p>

            <h2 class="text-white font-semibold text-xl mt-6 mb-2">¿Cómo ejercer tus derechos?</h2>
            <p>
                Puedes ejercer cualquiera de los derechos anteriores escribiéndonos a través de los canales de
                contacto publicados en nuestro sitio, indicando tu nombre completo y el correo asociado a tu
                cuenta.
            </p>
        </section>

        <p class="text-sm text-white/50">
            ¿Buscas información sobre cookies? Consulta nuestra
            <a href="politica_cookies.php" class="inline-link">Política de Cookies</a>.
        </p>
    </main>

    <footer class="px-4 pb-10 text-center text-sm text-white/50 md:px-6">
        Hotel Aurora © <?php echo date('Y'); ?> · Exclusividad, calma y servicio frente al mar.
    </footer>
</body>
</html>