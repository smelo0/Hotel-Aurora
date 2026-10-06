
const {
    wompiPublicKey: WOMPI_PUBLIC_KEY,
    csrfToken: CSRF_TOKEN,
    recaptchaSiteKey: RECAPTCHA_SITE_KEY
} = window.PORTAL_CONFIG;

(function(){
            const carousel = document.getElementById('roomsCarousel');
            const prev = document.getElementById('roomsPrev');
            const next = document.getElementById('roomsNext');
            const filters = Array.from(document.querySelectorAll('.room-filter'));
            if (!carousel || !prev || !next) return;

            // Keep navigation controls in sync with the carousel edges.
            function updateNavState(){
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;
                const atStart = carousel.scrollLeft <= 1;
                const atEnd = carousel.scrollLeft >= maxScroll - 1;
                prev.disabled = maxScroll <= 1 || atStart;
                next.disabled = maxScroll <= 1 || atEnd;
                prev.setAttribute('aria-disabled', String(prev.disabled));
                next.setAttribute('aria-disabled', String(next.disabled));
            }
            updateNavState();
            window.addEventListener('resize', updateNavState);
            carousel.addEventListener('scroll', updateNavState, { passive: true });

            const step = () => {
                if (window.matchMedia('(max-width: 767px)').matches) {
                    const card = Array.from(carousel.querySelectorAll('[data-room-card]'))
                        .find(item => item.style.display !== 'none' && !item.classList.contains('hidden'));
                    const gap = parseFloat(getComputedStyle(carousel).columnGap) || 0;
                    return (card?.getBoundingClientRect().width || carousel.clientWidth) + gap;
                }
                return Math.round(carousel.clientWidth * 0.8) || 380;
            };

            prev.addEventListener('click', () => {
                carousel.scrollBy({ left: -step(), behavior: 'smooth' });
            });

            next.addEventListener('click', () => {
                carousel.scrollBy({ left: step(), behavior: 'smooth' });
            });

            // mousewheel horizontal scroll
            carousel.addEventListener('wheel', (e) => {
                if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                    e.preventDefault();
                    carousel.scrollBy({ left: e.deltaY, behavior: 'auto' });
                }
            }, { passive: false });

            // Filtering logic
            function applyFilter(filter){
                const cards = Array.from(carousel.querySelectorAll('[data-room-card]'));
                cards.forEach(card => {
                    const type = (card.getAttribute('data-room-type') || 'otra');
                    const show = filter === 'all' || type === filter;
                    card.style.display = show ? '' : 'none';
                });
                // reset scroll and update nav
                carousel.scrollLeft = 0;
                setTimeout(updateNavState, 120);
            }

            filters.forEach(btn => {
                btn.addEventListener('click', () => {
                    filters.forEach(b => b.classList.remove('bg-white/6', 'text-white'));
                    btn.classList.add('bg-white/6', 'text-white');
                    const filter = btn.getAttribute('data-filter') || 'all';
                    applyFilter(filter);
                });
            });


            // initialize: ensure 'Todas' is active
            const active = document.querySelector('.room-filter[data-filter="all"]');
            if (active) active.classList.add('bg-white/6','text-white');
            applyFilter('all');
        })();
    

    // 1. Helper selector de IDs
    const $ = (id) => document.getElementById(id);

    let activityModalTrigger = null;

    function openActivityModal(trigger = null) {
        const modal = $('activityModal');
        if (!modal || modal.open) return;

        activityModalTrigger = trigger || $('openActivityModal');
        modal.showModal();
        $('activityModalTitle')?.focus();
    }

    function closeActivityModal() {
        const modal = $('activityModal');
        if (modal?.open) modal.close();
    }



    // 2. Estado global
    const isUserAuthenticated = window.PORTAL_CONFIG.isUserAuthenticated;
    const guests = { adults: 2, children: 0 };
    const searchState = { checkin: '', checkout: '' };
    
    const selectedReservation = { 
        roomId: 0, 
        roomName: '', 
        roomPrice: 0, 
        sourceButton: null, 
        payment: 'Tarjeta',
        porcentajePago: 100,
        montoTotal: 0,
        montoAPagar: 0
    };

    let bookingCalendar = null;
    let roomSyncChannel = null;
    let availabilityRefreshTimer = null;

    // 3. Funciones auxiliares
    function formatShortDate(date) {
        return new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short' }).format(date);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function setControlState(element, enabled) {
        if (!element) return;
        element.classList.toggle('is-active', enabled);
    }

    function calculateNights() {
        if (!currentSearchIsReady()) return 1;
        const checkin = new Date(`${searchState.checkin}T00:00:00`);
        const checkout = new Date(`${searchState.checkout}T00:00:00`);
        return Math.max(1, Math.round((checkout - checkin) / 86400000));
    }

    function calcularTotalesCobro(noches) {
        const subtotal = selectedReservation.roomPrice * noches;
        const iva = subtotal * 0.19; 
        const total = subtotal + iva;
        const aPagar = (total * selectedReservation.porcentajePago) / 100;

        selectedReservation.montoTotal = total;
        selectedReservation.montoAPagar = aPagar;

        if ($('modalSubtotal')) $('modalSubtotal').innerText = formatCurrency(subtotal);
        if ($('modalIva')) $('modalIva').innerText = formatCurrency(iva);
        if ($('modalTotal')) $('modalTotal').innerText = formatCurrency(total);
        if ($('modalMontoPagarAhora')) $('modalMontoPagarAhora').innerText = formatCurrency(aPagar);
    }

    function currentSearchIsReady() {
        return searchState.checkin !== '' && searchState.checkout !== '';
    }

    function normalizePaymentMethod(method) {
        const value = String(method || '').trim();
        const normalized = value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        if (normalized.includes('recepcion')) return 'Recepción';
        if (normalized.includes('transferencia') || normalized.includes('pse')) return 'Transferencia';
        if (normalized.includes('tarjeta') || normalized.includes('credito') || normalized.includes('debito')) return 'Tarjeta';
        if (normalized.includes('wompi')) return 'Wompi';

        return value || 'Tarjeta';
    }

    function scrollToResults() {
        const target = $('vista-resultados');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // 4. Búsqueda y Filtros
    function updateDateSummary(selectedDates = []) {
        const summary = $('dateSummary');
        if (selectedDates.length === 2) {
            summary.innerText = `${formatShortDate(selectedDates[0])} · ${formatShortDate(selectedDates[1])}`;
            return;
        }
        if (selectedDates.length === 1) {
            summary.innerText = `${formatShortDate(selectedDates[0])} · Check-out`;
            return;
        }
        summary.innerText = 'Selecciona check-in y check-out';
    }

    function initializeCalendar() {
        const input = $('dateRangeInput');
        const trigger = $('dateTrigger');

        if (!input || !trigger || typeof flatpickr === 'undefined') return;

        bookingCalendar = flatpickr(input, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'Y-m-d',
            locale: flatpickr.l10ns.es,
            disableMobile: true,
            showMonths: window.matchMedia('(min-width: 900px)').matches ? 2 : 1,
            appendTo: document.body,
            positionElement: trigger,
            onReady: (_, __, instance) => {
                instance.calendarContainer.classList.add('aurora-calendar');
            },
            onOpen: () => setControlState(trigger.closest('.booking-control'), true),
            onClose: () => setControlState(trigger.closest('.booking-control'), false),
            onChange: (selectedDates, _dateStr, instance) => {
                searchState.checkin = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
                searchState.checkout = selectedDates[1] ? instance.formatDate(selectedDates[1], 'Y-m-d') : '';
                updateDateSummary(selectedDates);

                if (selectedDates.length === 2) {
                    setTimeout(() => instance.close(), 140);
                }
            }
        });

        trigger.addEventListener('click', () => bookingCalendar.open());
    }

    function updateGuestSummary() {
        if ($('adultsValue')) $('adultsValue').innerText = String(guests.adults);
        if ($('childrenValue')) $('childrenValue').innerText = String(guests.children);
        if ($('guestSummary')) $('guestSummary').innerText = `${guests.adults} adultos, ${guests.children} niños`;
        if ($('adultsMinus')) $('adultsMinus').disabled = guests.adults <= 1;
        if ($('childrenMinus')) $('childrenMinus').disabled = guests.children <= 0;
    }

    function toggleGuestPopover(forceState = null) {
        const popover = $('guestPopover');
        const trigger = $('guestTrigger');
        if (!popover || !trigger) return;
        const control = trigger.closest('.booking-control');
        const shouldOpen = forceState === null ? !popover.classList.contains('is-open') : forceState;

        popover.classList.toggle('is-open', shouldOpen);
        setControlState(control, shouldOpen);
    }

    function changeGuest(type, delta) {
        const min = type === 'adults' ? 1 : 0;
        guests[type] = Math.max(min, guests[type] + delta);
        updateGuestSummary();
    }

    async function readJsonSafely(response) {
        const text = (await response.text()).replace(/^\uFEFF/, '').trim();
        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error('Respuesta inválida del servidor.');
        }
    }

    function updateVisibleRooms(visibleIds) {
        const cards = document.querySelectorAll('[data-room-card]');
        let visibleCount = 0;

        cards.forEach(card => {
            const isVisible = visibleIds.has(card.getAttribute('data-room-card'));
            card.classList.toggle('hidden', !isVisible);
            if (isVisible) visibleCount += 1;
        });

        if ($('emptyRoomsState')) $('emptyRoomsState').classList.toggle('hidden', visibleCount > 0);
    }

    async function searchAvailability(showFeedback = true) {
        const feedback = $('searchFeedback');
        const button = $('btnBuscarDisponibilidad');

        if (!currentSearchIsReady()) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = 'Selecciona tus fechas para consultar disponibilidad.';
                feedback.classList.remove('hidden');
            }
            scrollToResults();
            return;
        }

        const formData = new FormData();
        formData.append('accion', 'buscar_disponibilidad');
        formData.append('checkin', searchState.checkin);
        formData.append('checkout', searchState.checkout);
        formData.append('adultos', String(guests.adults));
        formData.append('ninos', String(guests.children));

        if (button) {
            button.disabled = true;
            button.innerText = 'Buscando disponibilidad...';
        }

        try {
            const response = await fetch('controladores/portal_huesped.php', {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const result = await readJsonSafely(response);

            if (!response.ok || result.status !== 'exito') {
                throw new Error(result.mensaje || 'No se pudo consultar disponibilidad.');
            }

            const ids = new Set((result.habitaciones_ids || []).map(String));
            updateVisibleRooms(ids);

            if (showFeedback && feedback) {
                feedback.className = 'text-sm font-bold text-emerald-300';
                feedback.innerText = result.mensaje;
                feedback.classList.remove('hidden');
            }

            scrollToResults();
        } catch (error) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = error.message;
                feedback.classList.remove('hidden');
            }
            scrollToResults();
        } finally {
            if (button) {
                button.disabled = false;
                button.innerText = 'Buscar Disponibilidad';
            }
            toggleGuestPopover(false);
        }
    }

    // 5. Gestión del Modal de Reserva
    function openBookingModal(roomId, roomName, roomPrice, sourceButton) {
        if (!isUserAuthenticated) {
            Swal.fire({
                icon: 'info',
                title: 'Inicia sesión para continuar',
                text: 'Debes iniciar sesión o registrarte para realizar una reserva.',
                showCancelButton: true,
                confirmButtonText: 'Iniciar sesión',
                cancelButtonText: 'Crear cuenta',
                confirmButtonColor: '#074d2a',
                cancelButtonColor: '#b89f12',
                background: '#fffdf8'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'interfaz/loggins/index_usu.php';
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = 'interfaz/loggins/index_usu.php?vista=registro';
                }
            });
            return;
        }

        const feedback = $('searchFeedback');

        if (!currentSearchIsReady()) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = 'Selecciona tus fechas antes de elegir una habitación.';
                feedback.classList.remove('hidden');
            }
            if ($('bookingBar')) $('bookingBar').scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (bookingCalendar) bookingCalendar.open();
            return;
        }

        selectedReservation.roomId = Number(roomId);
        selectedReservation.roomName = roomName;
        selectedReservation.roomPrice = Number(roomPrice || 0);
        selectedReservation.sourceButton = sourceButton;

        const nights = calculateNights();
        if ($('modalRoomName')) $('modalRoomName').innerText = roomName;
        if ($('modalCheckin')) $('modalCheckin').innerText = searchState.checkin;
        if ($('modalCheckout')) $('modalCheckout').innerText = searchState.checkout;
        if ($('modalGuests')) $('modalGuests').innerText = `${guests.adults} adultos, ${guests.children} niños`;
        if ($('modalNights')) $('modalNights').innerText = String(nights);
        if ($('modalNightPrice')) $('modalNightPrice').innerText = formatCurrency(selectedReservation.roomPrice);

        calcularTotalesCobro(nights);

        if ($('modalFeedback')) $('modalFeedback').className = 'mt-4 hidden text-sm font-bold';
        
        const modal = $('bookingModal');
        if (modal) {
            modal.classList.add('is-open');
            modal.style.display = 'flex';
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeBookingModal() {
        const modal = $('bookingModal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.style.display = 'none';
            document.body.classList.remove('overflow-hidden');
        }
    }
async function processReservationPayment() {
    const feedback = document.getElementById('modalFeedback');
    const btn = document.getElementById('confirmBookingBtn');
    const metodoPago = normalizePaymentMethod(selectedReservation.payment);
    selectedReservation.payment = metodoPago;

    // Validar datos esenciales
    if (!selectedReservation.roomId || !searchState.checkin || !searchState.checkout) {
        if (feedback) {
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = 'Faltan datos obligatorios para la reserva (habitación o fechas).';
            feedback.classList.remove('hidden');
        }
        return;
    }

    if (metodoPago !== 'Recepción') {
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'ABRIENDO WOMPI...';
        }
    }

    try {
        let transaction = null;
        if (metodoPago !== 'Recepción') {
            if (typeof WidgetCheckout === 'undefined') {
                throw new Error('No se pudo cargar el widget de Wompi.');
            }

            const reference = `AURORA-${Date.now()}-${selectedReservation.roomId}`;
            const widgetCheckout = new WidgetCheckout({
                currency: 'COP',
                amountInCents: Math.round(selectedReservation.montoAPagar * 100),
                reference,
                publicKey: WOMPI_PUBLIC_KEY
            });

            const paymentResult = await new Promise((resolve, reject) => {
                const timeoutId = window.setTimeout(() => {
                    reject(new Error('Wompi no pudo abrirse. Verifica la conexión, bloqueadores del navegador o la llave pública.'));
                }, 15000);

                widgetCheckout.open(result => {
                    window.clearTimeout(timeoutId);
                    if (result?.transaction?.id) {
                        resolve(result);
                    } else {
                        reject(new Error('El pago fue cancelado o no recibió confirmación.'));
                    }
                });
            });

            if (btn) btn.innerText = 'VALIDANDO PAGO...';
            transaction = paymentResult.transaction;
        }

        const fd = new FormData();
        fd.append('id_habitacion', selectedReservation.roomId);
        fd.append('fecha_in', searchState.checkin);
        fd.append('fecha_out', searchState.checkout);
        fd.append('cant_adultos', guests.adults);
        fd.append('cant_ninos', guests.children);
        fd.append('metodo_pago', transaction ? 'Wompi' : metodoPago);
        fd.append('porcentaje_pago', selectedReservation.porcentajePago);
        fd.append('csrf_token', CSRF_TOKEN);
        if (RECAPTCHA_SITE_KEY) {
            const captchaToken = window.grecaptcha?.getResponse();
            if (!captchaToken) {
                throw new Error('Completa la verificación reCAPTCHA para continuar.');
            }
            fd.append('g-recaptcha-response', captchaToken);
        }
        if (transaction) {
            fd.append('referencia_pago', transaction.reference || '');
            fd.append('wompi_transaction_id', transaction.id);
        }
        fd.append('tipo_huesped', '');
        fd.append('notas_reserva', `Porcentaje de cobro: ${selectedReservation.porcentajePago}%`);

        const res = await fetch('controladores/guardar_reserva.php', {
            method: 'POST',
            body: fd,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        const rawText = await res.text();
        console.log("Respuesta del servidor PHP:", rawText);

        let result;
        try {
            result = JSON.parse(rawText.trim());
        } catch(e) {
            throw new Error('El backend no devolvió una respuesta JSON válida.');
        }

        // Si PHP devuelve status !== 'exito' (por ejemplo 400, 409 o 422)
        if (!res.ok || result.status !== 'exito') {
            throw new Error(result.mensaje || 'No se pudo guardar la reserva en la base de datos.');
        }

        // Éxito real en base de datos
        closeBookingModal();
        
        await Swal.fire({ 
            icon: 'success', 
            title: '¡Reserva Registrada!', 
            text: result.mensaje || 'Se ha guardado la reserva correctamente en el sistema.', 
            confirmButtonColor: '#17354f' 
        });

        window.location.reload();

    } catch (err) {
        console.error("Error en la reserva:", err);
        if (RECAPTCHA_SITE_KEY && window.grecaptcha?.reset) {
            window.grecaptcha.reset();
        }
        if (feedback) {
            const errorMessage = err?.message || (typeof err === 'string' ? err : '') || 'No se pudo iniciar o validar el pago con Wompi.';
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = errorMessage;
            feedback.classList.remove('hidden');
        }
    } finally {
        if (btn) {
            btn.disabled = false; 
            btn.innerText = 'CONFIRMAR Y PAGAR';
        }
    }
}

    async function submitActivityForm(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const experienciaPersonalizadaToggle = document.getElementById('experienciaPersonalizadaToggle');
        const experienciaPersonalizadaCategoria = document.getElementById('experienciaPersonalizadaCategoria');
        const experienciaPersonalizadaNombre = document.getElementById('experienciaPersonalizadaNombre');
        const cantidadPersonas = Number(document.getElementById('cantidadPersonasActividad').value);
        const selecciones = [...document.querySelectorAll('[data-seleccion-persona]')].map(selector => selector.value);
        const esPersonalizada = Boolean(experienciaPersonalizadaToggle && experienciaPersonalizadaToggle.checked);

        if (esPersonalizada) {
            const categoriaPersonalizada = (experienciaPersonalizadaCategoria?.value || '').trim();
            const nombrePersonalizado = (experienciaPersonalizadaNombre?.value || '').trim();
            if (!categoriaPersonalizada && !nombrePersonalizado) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Elige una categoría o describe la idea',
                    text: 'Puedes escoger una categoría o dejar una breve descripción, pero no necesitas poner fecha, hora ni nombre de una experiencia específica.',
                    confirmButtonColor: '#17354f'
                });
                return;
            }
        } else if (!document.getElementById('experienciaIdActividad').value
            || !Number.isInteger(cantidadPersonas) || cantidadPersonas < 1 || cantidadPersonas > 20
            || selecciones.length !== cantidadPersonas || selecciones.some(seleccion => !seleccion)
            || !document.getElementById('horaActividad').value) {
            await Swal.fire({
                icon: 'warning',
                title: 'Completa las elecciones',
                text: 'Elige una experiencia, una opción para cada persona y un horario, o marca la opción de experiencia personalizada.',
                confirmButtonColor: '#17354f'
            });
            return;
        }
        const formData = new FormData(form);
        formData.append('accion', 'agendar_actividad');
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('solicitud_personalizada', esPersonalizada ? '1' : '0');

        try {
            const response = await fetch('controladores/portal_huesped.php', {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const result = await readJsonSafely(response);

            if (!response.ok || result.status !== 'exito') {
                throw new Error(result.mensaje || 'No se pudo programar la experiencia.');
            }

            form.reset();
            document.getElementById('experienciaIdActividad').value = '';
            document.getElementById('participantesActividad').value = '';
            document.getElementById('opcionesPorPersona').replaceChildren();
            document.getElementById('cantidadPersonasActividad').value = '1';
            document.getElementById('cantidadPersonasActividad').disabled = true;
            document.getElementById('fechaActividad').disabled = true;
            document.getElementById('fechaActividad').innerHTML = '<option value="">Primero elige una experiencia</option>';
            document.getElementById('horaActividad').value = '';
            document.getElementById('listaHorasActividad').setAttribute('aria-disabled', 'true');
            document.getElementById('listaHorasActividad').innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Primero elige una fecha</p>';
            document.getElementById('seleccionExperiencia').textContent = 'Elige una experiencia en una de las tarjetas.';
            const togglePersonalizada = document.getElementById('experienciaPersonalizadaToggle');
            const categoriaPersonalizada = document.getElementById('experienciaPersonalizadaCategoria');
            const inputPersonalizado = document.getElementById('experienciaPersonalizadaNombre');
            if (togglePersonalizada) togglePersonalizada.checked = false;
            if (categoriaPersonalizada) {
                categoriaPersonalizada.value = '';
                categoriaPersonalizada.classList.add('hidden');
            }
            if (inputPersonalizado) {
                inputPersonalizado.value = '';
                inputPersonalizado.classList.add('hidden');
            }
            window.opcionesExperienciaSeleccionada = [];
            window.preciosExperienciaSeleccionada = [];
            window.numerosOpcionesExperienciaSeleccionada = [];
            window.horariosExperienciaSeleccionada = {};
            document.querySelectorAll('.experience-select-button').forEach(boton => boton.classList.remove('ring-2', 'ring-white'));
            closeActivityModal();
            await Swal.fire({
                icon: 'success',
                title: 'Solicitud enviada',
                text: 'Nuestro equipo preparará tu experiencia.',
                confirmButtonColor: '#1c582b91',
                background: '#fffdf8'
            });
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#0d3a1a'
            });
        }
    }

    async function cancelarSolicitudExperiencia(idSolicitud, boton) {
        const confirmacion = await Swal.fire({
            icon: 'warning',
            title: '¿Cancelar esta experiencia?',
            text: 'La solicitud quedará registrada como cancelada y no podrás reactivarla desde aquí.',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'Volver',
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#64748b'
        });
        if (!confirmacion.isConfirmed) return;

        boton.disabled = true;
        try {
            const cuerpo = new URLSearchParams({
                accion: 'cancelar_solicitud',
                id_agenda: String(idSolicitud),
                csrf_token: CSRF_TOKEN
            });
            const respuesta = await fetch('controladores/gestionar_experiencias.php', {
                method: 'POST',
                body: cuerpo,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const resultado = await readJsonSafely(respuesta);
            if (!respuesta.ok || resultado.status !== 'exito') {
                throw new Error(resultado.mensaje || 'No se pudo cancelar la experiencia.');
            }
            await Swal.fire({
                icon: 'success',
                title: 'Experiencia cancelada',
                text: resultado.mensaje,
                confirmButtonColor: '#17354f'
            });
            window.location.reload();
        } catch (error) {
            boton.disabled = false;
            await Swal.fire({
                icon: 'error',
                title: 'No se pudo cancelar',
                text: error.message,
                confirmButtonColor: '#17354f'
            });
        }
    }

    function numeroOpcionExperienciaSeleccionada(opcion) {
        const indice = window.opcionesExperienciaSeleccionada.indexOf(opcion);
        return indice < 0 ? 0 : Number(window.numerosOpcionesExperienciaSeleccionada?.[indice] || indice + 1);
    }

    function actualizarHorariosExperiencia() {
        const fecha = document.getElementById('fechaActividad');
        const listaHoras = document.getElementById('listaHorasActividad');
        const seleccionHora = document.getElementById('horaActividad');
        if (!fecha || !listaHoras || !seleccionHora) return;
        if (!fecha.value) {
            seleccionHora.value = '';
            listaHoras.setAttribute('aria-disabled', 'true');
            listaHoras.innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Selecciona una fecha antes de elegir la hora.</p>';
            return;
        }
        const selecciones = [...document.querySelectorAll('[data-seleccion-persona]')].map(selector => selector.value);
        const indicesOpciones = [...new Set(selecciones.map(numeroOpcionExperienciaSeleccionada))];
        const rangos = indicesOpciones.map(indice => window.horariosExperienciaSeleccionada?.[String(indice)]?.[fecha.value]);
        let franjas = [];
        if (indicesOpciones.length > 0 && indicesOpciones.every(indice => indice > 0)
            && rangos.every(rango => rango && typeof rango.inicio === 'string' && typeof rango.fin === 'string')) {
            const minutosInicio = Math.max(...rangos.map(rango => {
                const [hora, minuto] = rango.inicio.split(':').map(Number);
                return hora * 60 + minuto;
            }));
            const minutosFin = Math.min(...rangos.map(rango => {
                const [hora, minuto] = rango.fin.split(':').map(Number);
                return hora * 60 + minuto;
            }));
            for (let minutos = minutosInicio; minutos <= minutosFin; minutos += 30) {
                franjas.push(`${String(Math.floor(minutos / 60)).padStart(2, '0')}:${String(minutos % 60).padStart(2, '0')}`);
            }
        }
        const ahora = new Date();
        const fechaHoy = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}-${String(ahora.getDate()).padStart(2, '0')}`;
        if (fecha.value === fechaHoy) {
            const horaActual = `${String(ahora.getHours()).padStart(2, '0')}:${String(ahora.getMinutes()).padStart(2, '0')}`;
            franjas = franjas.filter(horario => horario > horaActual);
        }
        seleccionHora.value = '';
        listaHoras.scrollTop = 0;
        listaHoras.setAttribute('aria-disabled', 'false');
        listaHoras.innerHTML = '';
        if (franjas.length === 0) {
            listaHoras.setAttribute('aria-disabled', 'true');
            listaHoras.innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">No hay horarios para esta fecha.</p>';
            return;
        }
        listaHoras.setAttribute('aria-disabled', 'false');
        listaHoras.tabIndex = 0;
        franjas.forEach(horario => {
            const botonHora = document.createElement('button');
            botonHora.type = 'button';
            botonHora.className = 'experience-time-option w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';
            botonHora.setAttribute('role', 'option');
            botonHora.setAttribute('aria-selected', 'false');
            botonHora.textContent = horario;
            botonHora.addEventListener('click', () => {
                seleccionHora.value = horario;
                listaHoras.querySelectorAll('.experience-time-option').forEach(elemento => {
                    const seleccionado = elemento === botonHora;
                    elemento.classList.toggle('bg-primary', seleccionado);
                    elemento.classList.toggle('text-white', seleccionado);
                    elemento.setAttribute('aria-selected', String(seleccionado));
                });
            });
            listaHoras.append(botonHora);
        });
    }

    function actualizarSeleccionesPersonas() {
        const selecciones = [...document.querySelectorAll('[data-seleccion-persona]')].map(selector => selector.value);
        document.getElementById('participantesActividad').value = JSON.stringify(selecciones);
        actualizarResumenPrecioExperiencia(selecciones);
        actualizarFechasExperiencia();
    }

    function actualizarResumenPrecioExperiencia(selecciones) {
        const resumen = document.getElementById('resumenPrecioExperiencia');
        if (!resumen) return;
        if (selecciones.length === 0 || selecciones.some(opcion => !opcion)) {
            resumen.textContent = 'Elige las opciones por persona para consultar el precio.';
            return;
        }
        const precios = window.preciosExperienciaSeleccionada || [];
        const importes = selecciones.map(opcion => {
            const indice = window.opcionesExperienciaSeleccionada.indexOf(opcion);
            return indice < 0 ? null : precios[indice];
        });
        const detalle = selecciones.map((opcion, indice) => {
            const precio = importes[indice];
            const importeTexto = precio === null || precio === undefined
                ? 'sin precio'
                : new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(precio));
            return `Persona ${indice + 1}: ${opcion} (${importeTexto})`;
        });
        if (importes.some(precio => precio === null || precio === undefined)) {
            resumen.textContent = `${detalle.join(' · ')}. Total no disponible porque hay opciones sin precio definido.`;
            return;
        }
        const total = importes.reduce((suma, precio) => suma + Number(precio), 0);
        const totalTexto = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);
        resumen.textContent = `${detalle.join(' · ')}. Total estimado: ${totalTexto} COP.`;
    }

    function actualizarFechasExperiencia() {
        const selectorFecha = document.getElementById('fechaActividad');
        const seleccionAnterior = selectorFecha.value;
        const selecciones = [...document.querySelectorAll('[data-seleccion-persona]')].map(selector => selector.value);
        selectorFecha.replaceChildren(new Option('Selecciona las opciones para ver fechas comunes', ''));
        document.getElementById('horaActividad').value = '';
        if (selecciones.length === 0 || selecciones.some(opcion => !opcion)) {
            selectorFecha.disabled = true;
            actualizarHorariosExperiencia();
            return;
        }

        const indicesOpciones = [...new Set(selecciones.map(numeroOpcionExperienciaSeleccionada))];
        if (indicesOpciones.some(indice => indice < 1)) {
            selectorFecha.disabled = true;
            actualizarHorariosExperiencia();
            return;
        }

        const ahora = new Date();
        const fechaHoy = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}-${String(ahora.getDate()).padStart(2, '0')}`;
        const fechasBase = Object.keys(window.horariosExperienciaSeleccionada?.[String(indicesOpciones[0])] || {});
        const fechasComunes = fechasBase.filter(fecha => {
            if (fecha < fechaHoy) return false;
            const duranteEstancia = (window.reservasEstanciaExperiencia || []).some(estancia =>
                fecha >= estancia.inicio && fecha < estancia.fin
            );
            if (!duranteEstancia) return false;
            const rangos = indicesOpciones.map(indice => window.horariosExperienciaSeleccionada?.[String(indice)]?.[fecha]);
            if (rangos.some(rango => !rango || typeof rango.inicio !== 'string' || typeof rango.fin !== 'string')) return false;
            const inicioComun = Math.max(...rangos.map(rango => {
                const [hora, minuto] = rango.inicio.split(':').map(Number);
                return hora * 60 + minuto;
            }));
            const finComun = Math.min(...rangos.map(rango => {
                const [hora, minuto] = rango.fin.split(':').map(Number);
                return hora * 60 + minuto;
            }));
            return inicioComun <= finComun;
        }).sort();
        fechasComunes.forEach(fecha => {
            const [anio, mes, dia] = fecha.split('-').map(Number);
            selectorFecha.add(new Option(new Date(anio, mes - 1, dia).toLocaleDateString('es-CO', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }), fecha));
        });
        selectorFecha.disabled = fechasComunes.length === 0;
        if (fechasComunes.length === 0) {
            selectorFecha.replaceChildren(new Option('No hay fechas comunes para esas opciones', ''));
        } else if (fechasComunes.includes(seleccionAnterior)) {
            selectorFecha.value = seleccionAnterior;
        }
        actualizarHorariosExperiencia();
    }

    function renderizarOpcionesPorPersona() {
        const cantidad = Number(document.getElementById('cantidadPersonasActividad').value);
        const opciones = window.opcionesExperienciaSeleccionada || [];
        const contenedor = document.getElementById('opcionesPorPersona');
        const togglePersonalizada = document.getElementById('experienciaPersonalizadaToggle');
        const esPersonalizada = togglePersonalizada && togglePersonalizada.checked;
        if (esPersonalizada) {
            contenedor.replaceChildren();
            document.getElementById('participantesActividad').value = JSON.stringify([]);
            return;
        }
        if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 20 || opciones.length === 0) {
            contenedor.replaceChildren();
            document.getElementById('participantesActividad').value = '';
            actualizarFechasExperiencia();
            return;
        }

        const seleccionesAnteriores = [...contenedor.querySelectorAll('[data-seleccion-persona]')].map(selector => selector.value);
        contenedor.replaceChildren();
        for (let indice = 0; indice < cantidad; indice++) {
            const etiqueta = document.createElement('label');
            etiqueta.className = 'text-xs font-black uppercase tracking-[0.12em] text-white/70';
            etiqueta.textContent = `Persona ${indice + 1}`;
            const selector = document.createElement('select');
            selector.className = 'mt-2 w-full rounded-2xl border-white/20 bg-white/90 text-slate-900';
            selector.required = true;
            selector.dataset.seleccionPersona = 'true';
            const opcionInicial = document.createElement('option');
            opcionInicial.value = '';
            opcionInicial.textContent = 'Elige una opción';
            selector.append(opcionInicial);
            opciones.forEach(opcion => {
                const elemento = document.createElement('option');
                elemento.value = opcion;
                const precio = window.preciosExperienciaSeleccionada?.[opciones.indexOf(opcion)];
                elemento.textContent = precio === null || precio === undefined
                    ? `${opcion} · Sin precio`
                    : `${opcion} · ${new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(precio))} COP`;
                selector.append(elemento);
            });
            if (seleccionesAnteriores[indice] && opciones.includes(seleccionesAnteriores[indice])) {
                selector.value = seleccionesAnteriores[indice];
            }
            selector.addEventListener('change', actualizarSeleccionesPersonas);
            etiqueta.append(selector);
            contenedor.append(etiqueta);
        }
        actualizarSeleccionesPersonas();
    }

    function seleccionarExperiencia(boton) {
        const idExperiencia = document.getElementById('experienciaIdActividad');
        const seleccion = document.getElementById('seleccionExperiencia');
        const fecha = document.getElementById('fechaActividad');
        const selectorHora = document.getElementById('horaActividad');
        idExperiencia.value = boton.dataset.experienceId;
        seleccion.textContent = boton.dataset.experienceName;
        window.opcionesExperienciaSeleccionada = JSON.parse(boton.dataset.experienceOptions || '[]');
        window.preciosExperienciaSeleccionada = JSON.parse(boton.dataset.experiencePrices || '[]');
        window.numerosOpcionesExperienciaSeleccionada = JSON.parse(boton.dataset.experienceOptionIndices || '[]');
        window.horariosExperienciaSeleccionada = JSON.parse(boton.dataset.experienceSchedule || '{}');
        window.reservasEstanciaExperiencia = JSON.parse(boton.dataset.reservationStays || '[]');
        document.querySelectorAll('.experience-select-button').forEach(elemento => elemento.classList.remove('ring-2', 'ring-white'));
        boton.classList.add('ring-2', 'ring-white');
        document.getElementById('cantidadPersonasActividad').disabled = false;
        document.getElementById('opcionesPorPersona').replaceChildren();
        renderizarOpcionesPorPersona();
        fecha.innerHTML = '<option value="">Selecciona tus opciones por persona</option>';
        fecha.disabled = true;
        selectorHora.value = '';
        document.getElementById('listaHorasActividad').setAttribute('aria-disabled', 'true');
        document.getElementById('listaHorasActividad').innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Elige una opción para cada persona para ver fechas comunes.</p>';
        openActivityModal(boton);
    }

    function attachLogoutFlow() {
        const logoutButton = $('logoutButton');
        if (!logoutButton) return;

        logoutButton.addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'Cerrar sesión',
                text: '¿Deseas salir de tu cuenta?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#519b5b',
                cancelButtonColor: '#b37922'
            });

            if (result.isConfirmed) {
                window.location.href = 'controladores/logout.php?panel=user';
            }
        });
    }

    function toggleSystemHelp(forceState = null) {
        const panel = $('systemHelpPanel');
        const button = $('systemHelpButton');
        if (!panel || !button) return;

        const shouldOpen = forceState === null ? !panel.classList.contains('is-open') : forceState;
        panel.classList.toggle('is-open', shouldOpen);
        panel.setAttribute('aria-hidden', String(!shouldOpen));
        button.setAttribute('aria-expanded', String(shouldOpen));

        if (shouldOpen) {
            $('closeSystemHelp')?.focus();
        }
    }

    function toggleSystemHelp(forceState = null) {
        const panel = $('systemHelpPanel');
        const button = $('systemHelpButton');
        if (!panel || !button) return;

        const shouldOpen = forceState === null ? !panel.classList.contains('is-open') : forceState;
        panel.classList.toggle('is-open', shouldOpen);
        panel.setAttribute('aria-hidden', String(!shouldOpen));
        button.setAttribute('aria-expanded', String(shouldOpen));

        if (shouldOpen) {
            $('closeSystemHelp')?.focus();
        }
    }

    // 6. Event Delegator Global
    document.addEventListener('click', event => {
        // Reservar Habitación
        const roomButton = event.target.closest('[data-room-select]');
        if (roomButton) {
            openBookingModal(roomButton.dataset.roomId, roomButton.dataset.roomName, roomButton.dataset.roomPrice, roomButton);
            return;
        }

        // Modalidad de pago (100% / 50%)
        const pagoOption = event.target.closest('[data-pago-tipo]');
        if (pagoOption) {
            selectedReservation.porcentajePago = Number(pagoOption.dataset.pagoTipo);
            document.querySelectorAll('[data-pago-tipo]').forEach(node => node.classList.remove('is-active'));
            pagoOption.classList.add('is-active');
            calcularTotalesCobro(calculateNights());
            return;
        }

        // Método de pago (Tarjeta, Transferencia, Recepción)
        const paymentButton = event.target.closest('[data-payment]');
        if (paymentButton) {
            selectedReservation.payment = normalizePaymentMethod(paymentButton.dataset.payment);
            document.querySelectorAll('[data-payment]').forEach(node => node.classList.remove('is-active'));
            paymentButton.classList.add('is-active');

            const cardFields = $('cardFields');
            const transferFields = $('transferFields');

            if (selectedReservation.payment === 'Tarjeta') {
                if (cardFields) cardFields.classList.remove('hidden');
                if (transferFields) transferFields.classList.add('hidden');
            } else if (selectedReservation.payment === 'Transferencia') {
                if (cardFields) cardFields.classList.add('hidden');
                if (transferFields) transferFields.classList.remove('hidden');
            } else {
                if (cardFields) cardFields.classList.add('hidden');
                if (transferFields) transferFields.classList.add('hidden');
            }
            return;
        }

        if (!event.target.closest('#guestPopover') && !event.target.closest('#guestTrigger')) {
            toggleGuestPopover(false);
        }

        if (event.target.id === 'bookingModal') {
            closeBookingModal();
        }

        if (event.target.id === 'activityModal') {
            closeActivityModal();
        }

        if (!event.target.closest('.system-help')) {
            toggleSystemHelp(false);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') toggleSystemHelp(false);
    });

    // 7. Inicialización al cargar el DOM
    document.addEventListener('DOMContentLoaded', () => {
        initializeCalendar();
        updateGuestSummary();
        attachLogoutFlow();

        if ($('systemHelpButton')) $('systemHelpButton').addEventListener('click', () => toggleSystemHelp());
        if ($('closeSystemHelp')) $('closeSystemHelp').addEventListener('click', () => toggleSystemHelp(false));

        if ($('guestTrigger')) $('guestTrigger').addEventListener('click', () => toggleGuestPopover());
        if ($('closeGuestPopover')) $('closeGuestPopover').addEventListener('click', () => toggleGuestPopover(false));
        if ($('openActivityModal')) $('openActivityModal').addEventListener('click', event => openActivityModal(event.currentTarget));
        if ($('closeActivityModal')) $('closeActivityModal').addEventListener('click', closeActivityModal);
        if ($('activityModal')) {
            $('activityModal').addEventListener('close', () => {
                activityModalTrigger?.focus();
                activityModalTrigger = null;
            });
        }
        if ($('adultsMinus')) $('adultsMinus').addEventListener('click', () => changeGuest('adults', -1));
        if ($('adultsPlus')) $('adultsPlus').addEventListener('click', () => changeGuest('adults', 1));
        if ($('childrenMinus')) $('childrenMinus').addEventListener('click', () => changeGuest('children', -1));
        if ($('childrenPlus')) $('childrenPlus').addEventListener('click', () => changeGuest('children', 1));
        if ($('btnBuscarDisponibilidad')) $('btnBuscarDisponibilidad').addEventListener('click', () => searchAvailability(true));
        if ($('activityForm')) {
            $('activityForm').addEventListener('submit', submitActivityForm);
            $('fechaActividad').addEventListener('change', actualizarHorariosExperiencia);
            $('cantidadPersonasActividad').addEventListener('input', renderizarOpcionesPorPersona);
            const togglePersonalizada = document.getElementById('experienciaPersonalizadaToggle');
            const categoriaPersonalizada = document.getElementById('experienciaPersonalizadaCategoria');
            const campoPersonalizado = document.getElementById('experienciaPersonalizadaNombre');
            if (togglePersonalizada) {
                togglePersonalizada.addEventListener('change', () => {
                    const esPersonalizada = togglePersonalizada.checked;
                    if (categoriaPersonalizada) {
                        categoriaPersonalizada.classList.toggle('hidden', !esPersonalizada);
                    }
                    if (campoPersonalizado) {
                        campoPersonalizado.classList.toggle('hidden', !esPersonalizada);
                    }
                    if (esPersonalizada) {
                        if (categoriaPersonalizada) categoriaPersonalizada.focus();
                        document.getElementById('experienciaIdActividad').value = '';
                        document.getElementById('participantesActividad').value = JSON.stringify([]);
                        document.getElementById('fechaActividad').value = '';
                        document.getElementById('horaActividad').value = '';
                        document.getElementById('listaHorasActividad').innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">La experiencia personalizada se revisará con el equipo y no requiere fecha ni horario.</p>';
                        document.getElementById('listaHorasActividad').setAttribute('aria-disabled', 'true');
                        document.getElementById('resumenPrecioExperiencia').textContent = 'Puedes dejar fecha, hora y nombre de experiencia vacíos si prefieres.';
                        document.querySelectorAll('.experience-select-button').forEach(boton => boton.classList.remove('ring-2', 'ring-white'));
                        document.getElementById('seleccionExperiencia').textContent = 'Experiencia personalizada seleccionada.';
                        document.getElementById('cantidadPersonasActividad').disabled = false;
                    } else {
                        document.getElementById('participantesActividad').value = '';
                        document.getElementById('listaHorasActividad').innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Primero elige una fecha</p>';
                        document.getElementById('resumenPrecioExperiencia').textContent = 'Elige las opciones por persona para consultar el precio.';
                        document.getElementById('seleccionExperiencia').textContent = 'Elige una experiencia en una de las tarjetas.';
                    }
                    renderizarOpcionesPorPersona();
                });
            }
            document.querySelectorAll('.experience-select-button:not(:disabled)').forEach(boton => {
                boton.addEventListener('click', () => seleccionarExperiencia(boton));
            });
        }
        const historialExperiencias = $('tablaHistorialExperiencias');
        if (historialExperiencias) {
            historialExperiencias.querySelectorAll('[data-cancelar-experiencia]').forEach(boton => {
                const segundosRestantes = Number(boton.dataset.segundosCancelacion);
                if (segundosRestantes <= 0) {
                    boton.remove();
                    return;
                }
                window.setTimeout(() => boton.remove(), segundosRestantes * 1000);
            });
            historialExperiencias.addEventListener('click', evento => {
                const boton = evento.target.closest('[data-cancelar-experiencia]');
                if (boton) cancelarSolicitudExperiencia(boton.dataset.cancelarExperiencia, boton);
            });
        }
    });
