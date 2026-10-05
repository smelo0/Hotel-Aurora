<div class="privacy-policy-content">
    <h1 id="cookiePolicyTitle">Política de Cookies</h1>
    <p class="privacy-policy-content__updated">Última actualización: <?php echo date('d/m/Y'); ?></p>

    <section class="privacy-policy-content__section">
        <p>
            Una cookie es un pequeño archivo que se almacena en tu navegador cuando visitas nuestro sitio.
            En <strong>Hotel Aurora</strong> utilizamos cookies propias y de terceros para asegurar el
            funcionamiento de la plataforma, recordar tus preferencias y, si lo autorizas, analizar el uso del sitio.
        </p>

        <h2>Tipos de cookies que utilizamos</h2>
        <ul>
            <li><strong>Técnicas / necesarias:</strong> imprescindibles para que puedas navegar, iniciar sesión y completar una reserva. No se pueden desactivar.</li>
            <li><strong>Analíticas:</strong> nos ayudan a entender cómo se usa el sitio para mejorar la experiencia. Solo se activan si las autorizas.</li>
            <li><strong>Marketing / publicidad:</strong> se usan para mostrar contenido y ofertas más relevantes para ti. Solo se activan si las autorizas.</li>
        </ul>

        <h2>¿Cómo gestionar tus preferencias?</h2>
        <p>
            Cuando visitas nuestro sitio por primera vez, verás un aviso en la parte inferior de la pantalla
            donde puedes aceptar todas las cookies, rechazarlas o configurar cuáles autorizas por categoría.
            Puedes cambiar tu decisión en cualquier momento borrando la cookie
            <code class="privacy-policy-content__code">preferencia_cookies</code> desde la configuración de tu navegador;
            el aviso volverá a aparecer para que elijas de nuevo.
        </p>

        <h2>Cookies de terceros</h2>
        <p>
            Algunos servicios integrados en nuestro sitio, como el inicio de sesión con Google o nuestra
            pasarela de pagos, pueden establecer sus propias cookies técnicas necesarias para su
            funcionamiento. Estas cookies se rigen por las políticas de privacidad de dichos proveedores.
        </p>
    </section>

    <p class="privacy-policy-content__cookies">
        ¿Buscas información sobre el tratamiento de tus datos personales?
        <a href="<?php echo htmlspecialchars($privacyPolicyUrl ?? 'politica_privacidad.php', ENT_QUOTES, 'UTF-8'); ?>" data-switch-policy="privacy">
            Consulta la Política de Tratamiento de Datos
        </a>.
    </p>
</div>
