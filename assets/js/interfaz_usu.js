const {
    wompiPublicKey: WOMPI_PUBLIC_KEY,
    csrfToken: CSRF_TOKEN,
    recaptchaSiteKey: RECAPTCHA_SITE_KEY
} = window.PORTAL_CONFIG;

const isUserAuthenticated = window.PORTAL_CONFIG.isUserAuthenticated;
const guests = { adults: 2, children: 0 };
const searchState = { checkin: '', checkout: '' };
const roomCart = [];

// ─────────────────────────────────────────────────────────
// Carrusel de habitaciones
// ─────────────────────────────────────────────────────────
(function(){
    const carousel = document.getElementById('roomsCarousel');
    const prev = document.getElementById('roomsPrev');
    const next = document.getElementById('roomsNext');
    const filters = Array.from(document.querySelectorAll('.room-filter'));
    if (!carousel || !prev || !next) return;

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

    prev.addEventListener('click', () => carousel.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => carousel.scrollBy({ left: step(), behavior: 'smooth' }));

    carousel.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
            e.preventDefault();
            carousel.scrollBy({ left: e.deltaY, behavior: 'auto' });
        }
    }, { passive: false });

    function applyFilter(filter){
        const cards = Array.from(carousel.querySelectorAll('[data-room-card]'));
        let visibleCards = 0;
        cards.forEach(card => {
            const type = (card.getAttribute('data-room-type') || 'otra').toLowerCase();
            const filterLower = filter.toLowerCase();
            const show = filterLower === 'all' || type.includes(filterLower) || (filterLower === 'sencilla' && type.includes('simple'));
            card.classList.toggle('hidden', !show);
            card.style.display = show ? '' : 'none';
            if (show) visibleCards++;
        });
        const emptyState = document.getElementById('emptyRoomsState');
        const isSearchApplied = typeof currentSearchIsReady === 'function' ? currentSearchIsReady() : false;
        if (emptyState) {
            if (!isSearchApplied && cards.length > 0) emptyState.classList.add('hidden');
            else emptyState.classList.toggle('hidden', visibleCards > 0);
        }
        carousel.scrollLeft = 0;
        setTimeout(updateNavState, 120);
    }

    filters.forEach(btn => {
        btn.addEventListener('click', () => {
            filters.forEach(b => b.classList.remove('bg-white/6', 'text-white'));
            btn.classList.add('bg-white/6', 'text-white');
            applyFilter(btn.getAttribute('data-filter') || 'all');
        });
    });

    const active = document.querySelector('.room-filter[data-filter="all"]');
    if (active) active.classList.add('bg-white/6','text-white');
    applyFilter('all');
})();

// ─────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────
const $ = (id) => document.getElementById(id);

let activityModalTrigger = null;

function openActivityModal(trigger = null) {
    const modal = $('activityModal');
    if (!modal) {
        console.warn('[Aurora] No existe #activityModal en el DOM.');
        return;
    }
    if (modal.open) return;
    activityModalTrigger = trigger || $('openActivityModal');
    modal.showModal();
    $('activityModalTitle')?.focus();
}

function closeActivityModal() {
    const modal = $('activityModal');
    if (modal?.open) modal.close();
}

const selectedReservation = {
    roomId: 0,
    roomIds: [],
    roomName: '',
    roomType: '',
    roomQuantity: 1,
    roomPrice: 0,
    sourceButton: null,
    payment: 'Tarjeta',
    porcentajePago: 100,
    montoTotal: 0,
    montoAPagar: 0
};

let bookingCalendar = null;

function formatShortDate(date) {
    return new Intl.DateTimeFormat('es-CO', { day: '2-digit', month: 'short' }).format(date);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(value || 0));
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
    const subtotal = selectedReservation.roomPrice * selectedReservation.roomQuantity * noches;
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
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function updateDateSummary(selectedDates = []) {
    const summary = $('dateSummary');
    if (!summary) return;
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
        onReady: (_, __, instance) => instance.calendarContainer.classList.add('aurora-calendar'),
        onOpen: () => setControlState(trigger.closest('.booking-control'), true),
        onClose: () => setControlState(trigger.closest('.booking-control'), false),
        onChange: (selectedDates, _dateStr, instance) => {
            searchState.checkin = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
            searchState.checkout = selectedDates[1] ? instance.formatDate(selectedDates[1], 'Y-m-d') : '';
            updateDateSummary(selectedDates);
            if (selectedDates.length === 2) setTimeout(() => instance.close(), 140);
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
    try { return JSON.parse(text); } catch (error) { throw new Error('Respuesta inválida del servidor.'); }
}

function formatNextAvailableDate(isoDate) {
    const [year, month, day] = String(isoDate).split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
}

function updateVisibleRooms(visibleIds, nextAvailability = {}) {
    const idsSet = visibleIds instanceof Set ? visibleIds : new Set(visibleIds || []);
    const cards = document.querySelectorAll('[data-room-card]');
    const isSearchApplied = currentSearchIsReady();
    let visibleCount = 0;

    cards.forEach(card => {
        let roomIds = [];
        try { roomIds = JSON.parse(card.dataset.roomIds || '[]').map(Number); } catch (_) { roomIds = []; }
        const currentRoomId = Number(card.getAttribute('data-room-card')) || 0;
        if (currentRoomId > 0 && !roomIds.includes(currentRoomId)) roomIds.push(currentRoomId);

        const availableCount = isSearchApplied ? roomIds.filter(id => idsSet.has(id) || idsSet.has(String(id))).length : 1;
        const soldOut = isSearchApplied && availableCount === 0;

        if (!soldOut) {
            card.classList.remove('hidden');
            card.style.display = '';
            visibleCount++;
        } else {
            card.classList.add('hidden');
            card.style.display = 'none';
        }

        const button = card.querySelector('[data-room-add]');
        const label = card.querySelector('[data-room-availability]');
        if (button) {
            button.disabled = soldOut;
            const labelSpan = button.querySelector('[data-room-add-label]');
            if (labelSpan) labelSpan.textContent = soldOut ? 'No disponible' : 'Agregar';
            if (!isSearchApplied) {
                button.disabled = false;
                if (labelSpan) labelSpan.textContent = 'Agregar';
            }
        }
        if (label) {
            if (soldOut && isSearchApplied) {
                label.textContent = 'No hay habitaciones disponibles para esas fechas.';
                label.classList.remove('hidden');
            } else {
                label.textContent = '';
                label.classList.add('hidden');
            }
        }
    });

    const emptyState = $('emptyRoomsState');
    if (emptyState) {
        if (isSearchApplied && visibleCount === 0) emptyState.classList.remove('hidden');
        else if (cards.length > 0) emptyState.classList.add('hidden');
    }
    return visibleCount;
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

    if (button) { button.disabled = true; button.innerText = 'Buscando disponibilidad...'; }

    try {
        const response = await fetch('controladores/portal_huesped.php', {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const result = await readJsonSafely(response);
        if (!response.ok || result.status !== 'exito') throw new Error(result.mensaje || 'No se pudo consultar disponibilidad.');

        const ids = new Set((result.habitaciones_ids || []).map(Number));
        updateVisibleRooms(ids, result.proximas_disponibilidades || {});

        if (showFeedback && feedback) {
            feedback.className = 'text-sm font-bold text-emerald-300';
            feedback.innerText = result.mensaje || 'Disponibilidad actualizada.';
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
        if (button) { button.disabled = false; button.innerText = 'Buscar Disponibilidad'; }
        toggleGuestPopover(false);
    }
}

function toggleRoomInCart(button) {
    const id = Number(button.dataset.roomId);
    const idx = roomCart.findIndex(r => r.id === id);
    const labelSpan = button.querySelector('[data-room-add-label]');

    if (idx >= 0) {
        roomCart.splice(idx, 1);
        button.classList.remove('bg-emerald-600', 'text-white');
        if (labelSpan) labelSpan.textContent = 'Agregar';
    } else {
        roomCart.push({ id, name: button.dataset.roomName || `Habitación ${id}`, price: Number(button.dataset.roomPrice || 0) });
        button.classList.add('bg-emerald-600', 'text-white');
        if (labelSpan) labelSpan.textContent = 'Quitar';
    }
    renderRoomCart();
}

function renderRoomCart() {
    const cart = $('roomCart');
    const summary = $('roomCartSummary');
    if (!cart || !summary) return;
    if (roomCart.length === 0) { cart.classList.add('hidden'); return; }
    const total = roomCart.reduce((s, r) => s + r.price, 0);
    summary.textContent = `${roomCart.length} habitación${roomCart.length > 1 ? 'es' : ''} · ${formatCurrency(total)}/noche`;
    cart.classList.remove('hidden');
}

function openBookingModalForCart(sourceButton = null) {
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
            if (result.isConfirmed) window.location.href = 'interfaz/loggins/index_usu.php';
            else if (result.dismiss === Swal.DismissReason.cancel) window.location.href = 'interfaz/loggins/index_usu.php?vista=registro';
        });
        return;
    }
    if (roomCart.length === 0) {
        const feedback = $('searchFeedback');
        if (feedback) {
            feedback.className = 'text-sm font-bold text-rose-300';
            feedback.innerText = 'Agrega al menos una habitación antes de reservar.';
            feedback.classList.remove('hidden');
        }
        return;
    }
    if (!currentSearchIsReady()) {
        const feedback = $('searchFeedback');
        if (feedback) {
            feedback.className = 'text-sm font-bold text-rose-300';
            feedback.innerText = 'Selecciona tus fechas antes de elegir una habitación.';
            feedback.classList.remove('hidden');
        }
        if ($('bookingBar')) $('bookingBar').scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (bookingCalendar) bookingCalendar.open();
        return;
    }

    selectedReservation.roomIds = roomCart.map(r => r.id);
    selectedReservation.roomId = roomCart[0].id;
    selectedReservation.roomQuantity = roomCart.length;
    selectedReservation.roomPrice = roomCart.reduce((sum, r) => sum + Number(r.price || 0), 0);
    selectedReservation.roomName = roomCart.length === 1 ? roomCart[0].name : `${roomCart.length} habitaciones seleccionadas`;
    selectedReservation.sourceButton = sourceButton;

    const nights = calculateNights();
    if ($('modalRoomName')) $('modalRoomName').innerText = selectedReservation.roomName;
    if ($('modalCheckin')) $('modalCheckin').innerText = searchState.checkin;
    if ($('modalCheckout')) $('modalCheckout').innerText = searchState.checkout;
    if ($('modalGuests')) $('modalGuests').innerText = `${guests.adults} adultos, ${guests.children} niños`;
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

    if (!selectedReservation.roomId || !searchState.checkin || !searchState.checkout) {
        if (feedback) {
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = 'Faltan datos obligatorios para la reserva.';
            feedback.classList.remove('hidden');
        }
        return;
    }
    if (metodoPago !== 'Recepción' && btn) {
        btn.disabled = true;
        btn.innerText = 'ABRIENDO WOMPI...';
    }

    try {
        let transaction = null;
        if (metodoPago !== 'Recepción') {
            if (typeof WidgetCheckout === 'undefined') throw new Error('No se pudo cargar el widget de Wompi.');
            const reference = `AURORA-${Date.now()}-${selectedReservation.roomId}`;
            const widgetCheckout = new WidgetCheckout({
                currency: 'COP',
                amountInCents: Math.round(selectedReservation.montoAPagar * 100),
                reference,
                publicKey: WOMPI_PUBLIC_KEY
            });
            const paymentResult = await new Promise((resolve, reject) => {
                const timeoutId = window.setTimeout(() => reject(new Error('Wompi no pudo abrirse.')), 15000);
                widgetCheckout.open(result => {
                    window.clearTimeout(timeoutId);
                    if (result?.transaction?.id) resolve(result);
                    else reject(new Error('El pago fue cancelado.'));
                });
            });
            if (btn) btn.innerText = 'VALIDANDO PAGO...';
            transaction = paymentResult.transaction;
        }

        const fd = new FormData();
        const ids = (selectedReservation.roomIds && selectedReservation.roomIds.length) ? selectedReservation.roomIds : [selectedReservation.roomId];
        ids.forEach(id => fd.append('habitaciones_ids[]', String(id)));
        fd.append('id_habitacion', String(ids[0]));
        fd.append('fecha_in', searchState.checkin);
        fd.append('fecha_out', searchState.checkout);
        fd.append('cant_adultos', guests.adults);
        fd.append('cant_ninos', guests.children);
        fd.append('metodo_pago', transaction ? 'Wompi' : metodoPago);
        fd.append('porcentaje_pago', selectedReservation.porcentajePago);
        fd.append('csrf_token', CSRF_TOKEN);
        if (RECAPTCHA_SITE_KEY) {
            const captchaToken = window.grecaptcha?.getResponse();
            if (!captchaToken) throw new Error('Completa la verificación reCAPTCHA para continuar.');
            fd.append('g-recaptcha-response', captchaToken);
        }
        if (transaction) {
            fd.append('referencia_pago', transaction.reference || '');
            fd.append('wompi_transaction_id', transaction.id);
        }

        const res = await fetch('controladores/guardar_reserva.php', {
            method: 'POST',
            body: fd,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const rawText = await res.text();
        let result;
        try { result = JSON.parse(rawText.trim()); }
        catch(e) { throw new Error('El servidor no devolvió JSON válido.'); }
        if (!res.ok || result.status !== 'exito') throw new Error(result.mensaje || 'No se pudo guardar la reserva.');

        closeBookingModal();
        await Swal.fire({ icon: 'success', title: '¡Reserva Registrada!', text: result.mensaje || 'Guardado.', confirmButtonColor: '#17354f' });
        window.location.reload();
    } catch (err) {
        if (RECAPTCHA_SITE_KEY && window.grecaptcha?.reset) window.grecaptcha.reset();
        if (feedback) {
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = err?.message || 'No se pudo procesar la reserva.';
            feedback.classList.remove('hidden');
        }
    } finally {
        if (btn) { btn.disabled = false; btn.innerText = 'CONFIRMAR Y PAGAR'; }
    }
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
        if (result.isConfirmed) window.location.href = 'controladores/logout.php?panel=user';
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
    if (shouldOpen) $('closeSystemHelp')?.focus();
}

document.addEventListener('click', event => {
    const roomAdd = event.target.closest('[data-room-add]');
    if (roomAdd) { toggleRoomInCart(roomAdd); return; }

    const cartCheckout = event.target.closest('#roomCartCheckout');
    if (cartCheckout) { openBookingModalForCart(cartCheckout); return; }

    const cartClear = event.target.closest('#roomCartClear');
    if (cartClear) {
        roomCart.length = 0;
        document.querySelectorAll('[data-room-add]').forEach(btn => {
            btn.classList.remove('bg-emerald-600', 'text-white');
            const label = btn.querySelector('[data-room-add-label]');
            if (label) label.textContent = 'Agregar';
        });
        renderRoomCart();
        return;
    }

    const pagoOption = event.target.closest('[data-pago-tipo]');
    if (pagoOption) {
        selectedReservation.porcentajePago = Number(pagoOption.dataset.pagoTipo);
        document.querySelectorAll('[data-pago-tipo]').forEach(node => node.classList.remove('is-active'));
        pagoOption.classList.add('is-active');
        calcularTotalesCobro(calculateNights());
        return;
    }

    const paymentButton = event.target.closest('[data-payment]');
    if (paymentButton) {
        selectedReservation.payment = normalizePaymentMethod(paymentButton.dataset.payment);
        document.querySelectorAll('[data-payment]').forEach(node => node.classList.remove('is-active'));
        paymentButton.classList.add('is-active');
        const cardFields = $('cardFields');
        const transferFields = $('transferFields');
        if (selectedReservation.payment === 'Transferencia') {
            if (cardFields) cardFields.classList.add('hidden');
            if (transferFields) transferFields.classList.remove('hidden');
        } else {
            if (cardFields) cardFields.classList.remove('hidden');
            if (transferFields) transferFields.classList.add('hidden');
        }
        return;
    }

    if (!event.target.closest('#guestPopover') && !event.target.closest('#guestTrigger')) toggleGuestPopover(false);
    if (event.target.id === 'bookingModal') closeBookingModal();
    if (event.target.id === 'activityModal') closeActivityModal();
    if (!event.target.closest('.system-help')) toggleSystemHelp(false);
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') toggleSystemHelp(false);
});

document.addEventListener('DOMContentLoaded', () => {
    initializeCalendar();
    updateGuestSummary();
    attachLogoutFlow();
    updateVisibleRooms(null, {});

    if ($('systemHelpButton')) $('systemHelpButton').addEventListener('click', () => toggleSystemHelp());
    if ($('closeSystemHelp')) $('closeSystemHelp').addEventListener('click', () => toggleSystemHelp(false));
    if ($('guestTrigger')) $('guestTrigger').addEventListener('click', () => toggleGuestPopover());
    if ($('closeGuestPopover')) $('closeGuestPopover').addEventListener('click', () => toggleGuestPopover(false));
    if ($('openActivityModal')) $('openActivityModal').addEventListener('click', event => openActivityModal(event.currentTarget));
    if ($('closeActivityModal')) $('closeActivityModal').addEventListener('click', closeActivityModal);
    if ($('adultsMinus')) $('adultsMinus').addEventListener('click', () => changeGuest('adults', -1));
    if ($('adultsPlus')) $('adultsPlus').addEventListener('click', () => changeGuest('adults', 1));
    if ($('childrenMinus')) $('childrenMinus').addEventListener('click', () => changeGuest('children', -1));
    if ($('childrenPlus')) $('childrenPlus').addEventListener('click', () => changeGuest('children', 1));
    if ($('btnBuscarDisponibilidad')) $('btnBuscarDisponibilidad').addEventListener('click', () => searchAvailability(true));

    console.log('[Aurora] form activityForm existe?', !!document.getElementById('activityForm'));
    console.log('[Aurora] botones .experience-select-button:', document.querySelectorAll('.experience-select-button').length);
});

// ─────────────────────────────────────────────────────────
// EXPERIENCIAS — handlers
// ─────────────────────────────────────────────────────────
(function () {
    const form = $('activityForm');
    if (!form) {
        console.warn('[Aurora] No existe #activityForm. El huésped necesita una reserva activa.');
        return;
    }

    let experienciaActual = null;

    function cargarExperienciaDesdeBoton(boton) {
        try {
            experienciaActual = {
                id: Number(boton.dataset.experienceId),
                nombre: boton.dataset.experienceName || '',
                opciones: JSON.parse(boton.dataset.experienceOptions || '[]'),
                precios: JSON.parse(boton.dataset.experiencePrices || '[]'),
                indices: JSON.parse(boton.dataset.experienceOptionIndices || '[]'),
                horarios: JSON.parse(boton.dataset.experienceSchedule || '{}'),
                reservas: JSON.parse(boton.dataset.reservationStays || '[]')
            };
        } catch (err) {
            console.error('[Aurora] Error parseando datos:', err);
            experienciaActual = null;
        }
        return experienciaActual;
    }

    function aplicarSeleccionEnModal(boton) {
        if (!experienciaActual) return;

        const resumen = $('seleccionExperiencia');
        if (resumen) {
            resumen.className = 'rounded-2xl border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-white';
            resumen.textContent = `Experiencia seleccionada: ${experienciaActual.nombre}`;
        }
        if ($('experienciaIdActividad')) $('experienciaIdActividad').value = String(experienciaActual.id);
        if ($('cantidadPersonasActividad')) {
            $('cantidadPersonasActividad').disabled = false;
            $('cantidadPersonasActividad').value = '1';
        }

        generarOpcionesPorPersona(1);
        pintarFechasDisponibles();
        actualizarResumenPrecio();
        openActivityModal(boton);
    }

    document.addEventListener('click', event => {
        const boton = event.target.closest('.experience-select-button');
        if (!boton) return;
        if (boton.disabled) return;
        cargarExperienciaDesdeBoton(boton);
        aplicarSeleccionEnModal(boton);
    });

    window.seleccionarExperienciaDesdeBoton = function (boton) {
        if (!boton || boton.disabled) return;
        cargarExperienciaDesdeBoton(boton);
        aplicarSeleccionEnModal(boton);
    };

    const togglePersonalizada = $('experienciaPersonalizadaToggle');
    if (togglePersonalizada) {
        togglePersonalizada.addEventListener('change', () => {
            const activo = togglePersonalizada.checked;
            $('experienciaPersonalizadaCategoria')?.classList.toggle('hidden', !activo);
            $('experienciaPersonalizadaNombre')?.classList.toggle('hidden', !activo);
            if (activo) {
                if ($('experienciaIdActividad')) $('experienciaIdActividad').value = '';
                if ($('cantidadPersonasActividad')) {
                    $('cantidadPersonasActividad').disabled = false;
                    $('cantidadPersonasActividad').value = '1';
                }
                if ($('seleccionExperiencia')) {
                    $('seleccionExperiencia').className = 'rounded-2xl border border-amber-400/40 bg-amber-500/10 px-4 py-3 text-sm text-white';
                    $('seleccionExperiencia').textContent = 'Solicitud personalizada';
                }
                generarOpcionesPorPersona(1);
                pintarFechasDisponibles();
                actualizarResumenPrecio();
            }
        });
    }

    const inputPersonas = $('cantidadPersonasActividad');
    if (inputPersonas) {
        inputPersonas.addEventListener('input', () => {
            const cantidad = Math.max(1, Math.min(20, Number(inputPersonas.value) || 1));
            generarOpcionesPorPersona(cantidad);
            actualizarResumenPrecio();
        });
    }

    function generarOpcionesPorPersona(cantidad) {
        const contenedor = $('opcionesPorPersona');
        if (!contenedor) return;
        if (!experienciaActual || !experienciaActual.opciones.length) {
            contenedor.innerHTML = '';
            if ($('participantesActividad')) $('participantesActividad').value = '';
            return;
        }
        let html = '';
        for (let i = 0; i < cantidad; i++) {
            const opciones = experienciaActual.opciones.map((op, idx) => {
                const precio = experienciaActual.precios[idx];
                const precioTexto = precio === null || precio === undefined ? 'Sin precio' : '$' + Number(precio).toLocaleString('es-CO');
                return `<option value="${String(op).replace(/"/g, '&quot;')}">${op} · ${precioTexto}</option>`;
            }).join('');
            html += `
                <div class="rounded-xl border border-white/15 bg-white/5 p-3">
                    <p class="text-[10px] font-black uppercase tracking-[0.16em] text-white/60 mb-2">Persona ${i + 1}</p>
                    <select data-persona-select data-persona-index="${i}" class="w-full rounded-lg border-white/20 bg-white/90 text-slate-900 text-sm">
                        ${opciones}
                    </select>
                </div>`;
        }
        contenedor.innerHTML = html;
        contenedor.querySelectorAll('[data-persona-select]').forEach(sel => sel.addEventListener('change', actualizarResumenPrecio));
        actualizarResumenPrecio();
    }

    function actualizarResumenPrecio() {
        const resumen = $('resumenPrecioExperiencia');
        const hidden = $('participantesActividad');
        if (!resumen) return;
        if (!experienciaActual || !experienciaActual.opciones.length) {
            resumen.textContent = 'Elige las opciones por persona para consultar el precio.';
            if (hidden) hidden.value = '';
            return;
        }
        const selecciones = [];
        let total = 0, totalConocido = true;
        document.querySelectorAll('[data-persona-select]').forEach(sel => {
            const opcion = sel.value;
            selecciones.push(opcion);
            const idx = experienciaActual.opciones.indexOf(opcion);
            const precio = idx >= 0 ? experienciaActual.precios[idx] : null;
            if (precio === null || precio === undefined) totalConocido = false;
            else total += Number(precio);
        });
        if (hidden) hidden.value = JSON.stringify(selecciones);
        resumen.textContent = totalConocido
            ? `Precio estimado: $${total.toLocaleString('es-CO')} COP`
            : `Precio estimado: parcial (opciones sin precio)`;
    }

    function pintarFechasDisponibles() {
        const selectFecha = $('fechaActividad');
        if (!selectFecha) return;
        selectFecha.innerHTML = '<option value="">Selecciona una fecha</option>';

        if (!experienciaActual) {
            selectFecha.disabled = false;
            const hoy = new Date();
            for (let i = 0; i < 30; i++) {
                const f = new Date(hoy.getTime() + i * 86400000);
                const iso = f.toISOString().slice(0, 10);
                selectFecha.insertAdjacentHTML('beforeend', `<option value="${iso}">${f.toLocaleDateString('es-CO', { day: 'numeric', month: 'long' })}</option>`);
            }
            return;
        }

        const reservas = experienciaActual.reservas || [];
        const fechasEnHorarios = new Set();
        Object.values(experienciaActual.horarios || {}).forEach(porFecha => {
            Object.keys(porFecha || {}).forEach(fecha => fechasEnHorarios.add(fecha));
        });
        const hoy = new Date().toISOString().slice(0, 10);
        const fechasValidas = [...fechasEnHorarios]
            .filter(f => f >= hoy)
            .filter(f => reservas.length === 0 || reservas.some(r => f >= r.inicio && f < r.fin))
            .sort();

        if (fechasValidas.length === 0) {
            selectFecha.disabled = true;
            selectFecha.innerHTML = '<option value="">No hay fechas disponibles dentro de tu estancia</option>';
            return;
        }
        selectFecha.disabled = false;
        fechasValidas.forEach(f => {
            const d = new Date(f + 'T00:00:00');
            selectFecha.insertAdjacentHTML('beforeend', `<option value="${f}">${d.toLocaleDateString('es-CO', { weekday: 'short', day: 'numeric', month: 'long' })}</option>`);
        });
    }

    const selectFecha = $('fechaActividad');
    if (selectFecha) selectFecha.addEventListener('change', () => pintarHoras(selectFecha.value));

    function pintarHoras(fecha) {
        const contenedor = $('listaHorasActividad');
        const inputHora = $('horaActividad');
        if (!contenedor) return;
        if (inputHora) inputHora.value = '';
        if (!fecha) {
            contenedor.innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Primero elige una fecha</p>';
            return;
        }
        if (!experienciaActual) {
            const horas = [];
            for (let h = 8; h <= 20; h++) {
                horas.push(`${String(h).padStart(2, '0')}:00`);
                if (h < 20) horas.push(`${String(h).padStart(2, '0')}:30`);
            }
            contenedor.innerHTML = horas.map(h => `<button type="button" data-hora="${h}" class="m-1 rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200">${h}</button>`).join('');
        } else {
            const selecciones = [...document.querySelectorAll('[data-persona-select]')].map(s => s.value);
            const indices = selecciones.map(op => experienciaActual.opciones.indexOf(op) + 1).filter(Boolean);
            const indicesUnicos = [...new Set(indices)];
            const bloques = [];
            indicesUnicos.forEach(indiceOpcion => {
                const porFecha = experienciaActual.horarios?.[String(indiceOpcion)]?.[fecha];
                if (!porFecha) return;
                const inicio = porFecha.inicio || '08:00';
                const fin = porFecha.fin || '20:00';
                const [hi, mi] = inicio.split(':').map(Number);
                const [hf, mf] = fin.split(':').map(Number);
                let t = hi * 60 + mi;
                const tFin = hf * 60 + mf;
                while (t <= tFin) {
                    const hh = String(Math.floor(t / 60)).padStart(2, '0');
                    const mm = String(t % 60).padStart(2, '0');
                    bloques.push(`${hh}:${mm}`);
                    t += 30;
                }
            });
            const unicos = [...new Set(bloques)].sort();
            if (unicos.length === 0) {
                contenedor.innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">No hay horarios disponibles para esa fecha.</p>';
                return;
            }
            contenedor.innerHTML = unicos.map(h => `<button type="button" data-hora="${h}" class="m-1 rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200">${h}</button>`).join('');
        }
        contenedor.querySelectorAll('[data-hora]').forEach(btn => {
            btn.addEventListener('click', () => {
                contenedor.querySelectorAll('[data-hora]').forEach(b => {
                    b.classList.remove('bg-emerald-600', 'text-white');
                    b.classList.add('bg-slate-100', 'text-slate-700');
                });
                btn.classList.add('bg-emerald-600', 'text-white');
                btn.classList.remove('bg-slate-100', 'text-slate-700');
                if (inputHora) inputHora.value = btn.dataset.hora;
            });
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        event.stopPropagation();
        const submitBtn = form.querySelector('button[type="submit"]');
        const textoOriginal = submitBtn ? submitBtn.innerHTML : '';
        const feedback = $('activityModalFeedback') || crearFeedbackModal();
        const esPersonalizada = $('experienciaPersonalizadaToggle')?.checked
            || ($('experienciaPersonalizadaNombre')?.value || '').trim() !== ''
            || ($('experienciaPersonalizadaCategoria')?.value || '').trim() !== '';
        const cantidadPersonas = Number($('cantidadPersonasActividad')?.value || 1);
        const participantesRaw = $('participantesActividad')?.value || '';
        const fecha = $('fechaActividad')?.value || '';
        const hora = $('horaActividad')?.value || '';
        const nombre = ($('nombreActividad')?.value || '').trim();
        const correo = ($('correoActividad')?.value || '').trim();

        if (!esPersonalizada && !experienciaActual) return mostrarError(feedback, 'Selecciona una experiencia o marca "personalizada".');
        if (!fecha) return mostrarError(feedback, 'Selecciona una fecha.');
        if (!hora) return mostrarError(feedback, 'Selecciona una hora.');
        if (!correo || !/^\S+@\S+\.\S+$/.test(correo)) return mostrarError(feedback, 'Ingresa un correo válido.');

        let participantes;
        if (esPersonalizada) {
            participantes = Array(cantidadPersonas).fill('Solicitud personalizada');
        } else {
            try { participantes = JSON.parse(participantesRaw || '[]'); } catch (_) { participantes = []; }
            if (participantes.length !== cantidadPersonas) return mostrarError(feedback, 'Selecciona una opción por persona.');
        }

        const fd = new FormData();
        fd.append('accion', 'agendar_actividad');
        fd.append('experiencia_id', esPersonalizada ? '' : String(experienciaActual.id));
        fd.append('solicitud_personalizada', esPersonalizada ? '1' : '0');
        fd.append('categoria_personalizada', $('experienciaPersonalizadaCategoria')?.value || '');
        fd.append('experiencia_personalizada', $('experienciaPersonalizadaNombre')?.value || '');
        fd.append('cantidad_personas', String(cantidadPersonas));
        fd.append('participantes', JSON.stringify(participantes));
        fd.append('fecha', fecha);
        fd.append('hora', hora);
        fd.append('nombre', nombre);
        fd.append('correo', correo);
        fd.append('csrf_token', CSRF_TOKEN);

        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = 'Enviando...'; }

        try {
            const res = await fetch('controladores/portal_huesped.php', {
                method: 'POST',
                body: fd,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const texto = (await res.text()).replace(/^\uFEFF/, '').trim();
            let result;
            try { result = JSON.parse(texto); } catch (_) { throw new Error('Respuesta inválida del servidor.'); }
            if (!res.ok || result.status !== 'exito') throw new Error(result.mensaje || 'No se pudo programar la experiencia.');
            closeActivityModal();
            await Swal.fire({ icon: 'success', title: 'Experiencia programada', text: result.mensaje || 'Enviada.', confirmButtonColor: '#17354f' });
            window.location.reload();
        } catch (err) {
            mostrarError(feedback, err.message || 'No se pudo programar la experiencia.');
        } finally {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = textoOriginal; }
        }
    });

    function mostrarError(feedback, mensaje) {
        if (!feedback) return;
        feedback.className = 'mt-3 text-sm font-bold text-rose-300';
        feedback.textContent = mensaje;
        feedback.classList.remove('hidden');
    }

    function crearFeedbackModal() {
        const p = document.createElement('p');
        p.id = 'activityModalFeedback';
        p.className = 'mt-3 hidden text-sm font-bold text-rose-300';
        const boton = form.querySelector('button[type="submit"]');
        if (boton) boton.insertAdjacentElement('beforebegin', p);
        else form.appendChild(p);
        return p;
    }
})();