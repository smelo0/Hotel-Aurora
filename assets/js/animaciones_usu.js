// Animaciones al hacer scroll – portal del huésped (Hotel Aurora).
// No depende de interfaz_usu.js: si algo falla aquí, la página se ve igual que antes.
(() => {
    const root = document.documentElement;
    const activo = root.classList.contains('anim-ready');

    // ── Barra de progreso + navbar (funcionan siempre, son livianos) ──
    const barra = document.createElement('div');
    barra.className = 'scroll-progress';
    barra.setAttribute('aria-hidden', 'true');
    document.body.appendChild(barra);
    const nav = document.querySelector('.site-nav');
    let pendiente = false;

    function actualizarScroll() {
        pendiente = false;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const progreso = max > 0 ? Math.min(1, window.scrollY / max) : 0;
        barra.style.transform = `scaleX(${progreso})`;
        if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 40);
    }
    window.addEventListener('scroll', () => {
        if (!pendiente) {
            pendiente = true;
            requestAnimationFrame(actualizarScroll);
        }
    }, { passive: true });
    window.addEventListener('resize', actualizarScroll);
    actualizarScroll();

    if (!activo) return;

    // ── Elementos que se revelan al entrar en pantalla ──
    const marcar = (selector, tipo) => {
        document.querySelectorAll(selector).forEach(el => {
            if (!el.hasAttribute('data-anim')) el.setAttribute('data-anim', tipo);
        });
    };
    marcar('#inicio > div > .reveal:first-child', 'left');
    marcar('#inicio > div > .reveal:last-child', 'right');
    marcar('.reveal', 'up');
    marcar('#habitaciones .room-card, #experiencias .room-card', 'zoom');
    marcar('#historial article, #historial-experiencias article', 'up');

    const observador = new IntersectionObserver(entradas => {
        // Escalonado: los elementos que aparecen juntos entran uno tras otro.
        let orden = 0;
        entradas
            .filter(e => e.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top
                || a.boundingClientRect.left - b.boundingClientRect.left)
            .forEach(entrada => {
                const el = entrada.target;
                el.style.setProperty('--anim-delay', `${Math.min(orden, 5) * 0.09}s`);
                el.classList.add('is-visible');
                observador.unobserve(el);
                orden++;
            });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

    document.querySelectorAll('[data-anim]').forEach(el => observador.observe(el));
})();

// ── Botones laterales del carrusel de habitaciones ──
// Reutilizan los botones de arriba (#roomsPrev / #roomsNext): así comparten su lógica y su estado.
(() => {
    const carrusel = document.getElementById('roomsCarousel');
    const prev = document.getElementById('roomsPrev');
    const next = document.getElementById('roomsNext');
    if (!carrusel || !prev || !next || !carrusel.parentElement) return;

    function crear(clase, icono, etiqueta, origen) {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = `rooms-side-nav rooms-side-nav--${clase}`;
        boton.setAttribute('aria-label', etiqueta);
        boton.innerHTML = `<span class="material-symbols-outlined" aria-hidden="true">${icono}</span><span class="rooms-side-nav__ripple" aria-hidden="true"></span>`;
        boton.addEventListener('click', () => {
            boton.classList.remove('is-clicked');
            void boton.offsetWidth; // reinicia la animación de la onda
            boton.classList.add('is-clicked');
            origen.click();
        });
        return boton;
    }

    const izquierda = crear('prev', 'chevron_left', 'Habitaciones anteriores', prev);
    const derecha = crear('next', 'chevron_right', 'Siguientes habitaciones', next);
    carrusel.parentElement.append(izquierda, derecha);

    const sincronizar = () => {
        izquierda.disabled = prev.disabled;
        derecha.disabled = next.disabled;
    };
    sincronizar();
    const mo = new MutationObserver(sincronizar);
    mo.observe(prev, { attributes: true, attributeFilter: ['disabled'] });
    mo.observe(next, { attributes: true, attributeFilter: ['disabled'] });

    // Animación de entrada cuando el carrusel aparece en pantalla
    if (document.documentElement.classList.contains('anim-ready') && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver(entradas => {
            if (!entradas.some(e => e.isIntersecting)) return;
            izquierda.classList.add('is-entering--prev');
            derecha.classList.add('is-entering--next');
            io.disconnect();
        }, { threshold: 0.2 });
        io.observe(carrusel);
    }
})();