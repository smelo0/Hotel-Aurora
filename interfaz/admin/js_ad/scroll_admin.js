// Desplazamiento del panel de administración sin mouse.

(() => {
    const principal = document.getElementById('adminMain');
    if (!principal) return;

    const reducirMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function enfocarContenido() {
        principal.focus({ preventScroll: true });
    }

    function desplazar(delta) {
        principal.scrollBy({ top: delta, behavior: reducirMovimiento ? 'auto' : 'smooth' });
    }

    document.addEventListener('keydown', evento => {
        if (evento.defaultPrevented || evento.ctrlKey || evento.altKey || evento.metaKey) return;

        const objetivo = evento.target;
        const enPagina = objetivo === document.body || objetivo === document.documentElement;
        const enMenuLateral = objetivo instanceof Element && objetivo.closest('#adminSidebar') !== null;
        if (!enPagina && !enMenuLateral) return; // dentro del contenido, de un formulario o de un modal manda el navegador

        const pagina = principal.clientHeight * 0.9;
        switch (evento.key) {
            case 'PageDown': desplazar(pagina); break;
            case 'PageUp': desplazar(-pagina); break;
            case 'ArrowDown': desplazar(80); break;
            case 'ArrowUp': desplazar(-80); break;
            case 'Home': principal.scrollTo({ top: 0, behavior: reducirMovimiento ? 'auto' : 'smooth' }); break;
            case 'End': principal.scrollTo({ top: principal.scrollHeight, behavior: reducirMovimiento ? 'auto' : 'smooth' }); break;
            case ' ':
                // En el menú lateral la barra espaciadora activa el botón: solo se usa para bajar cuando no hay un control enfocado.
                if (!enPagina) return;
                desplazar(evento.shiftKey ? -pagina : pagina);
                break;
            default: return;
        }
        evento.preventDefault();
    });

    // Lista de tareas: solo es enfocable (Tab) cuando el panel está abierto.
    const listaTareas = document.getElementById('contenedorTareasAdmin');
    if (listaTareas) {
        const sincronizarFocoTareas = () => {
            const abierta = !listaTareas.classList.contains('pointer-events-none');
            listaTareas.setAttribute('tabindex', abierta ? '0' : '-1');
        };
        sincronizarFocoTareas();
        new MutationObserver(sincronizarFocoTareas).observe(listaTareas, { attributes: true, attributeFilter: ['class'] });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', enfocarContenido);
    else enfocarContenido();
})();