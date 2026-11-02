const {
    wompiPublicKey: WOMPI_PUBLIC_KEY,
    csrfToken: CSRF_TOKEN,
    recaptchaSiteKey: RECAPTCHA_SITE_KEY
} = window.PORTAL_CONFIG;

const isUserAuthenticated = window.PORTAL_CONFIG.isUserAuthenticated;
const SCHEDULED_EXPERIENCES = Array.isArray(window.PORTAL_CONFIG.scheduledExperiences)
    ? window.PORTAL_CONFIG.scheduledExperiences
    : [];
const ROOM_RATE_POLICY = window.PORTAL_CONFIG.roomPricing;
const guests = { adults: 2, children: 0 };
const childAges = [];
const searchState = { checkin: '', checkout: '' };
const roomCart = [];
let selectedRoomFilter = 'all';
let latestAvailableRoomIds = null;
let availabilitySearchApplied = false;

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
        selectedRoomFilter = filter.toLowerCase();
        applyRoomVisibility();
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

function nightlyRateForRoom(room, adults, ages) {
    if (Number.isFinite(room.singleOccupancyPrice) && adults + ages.length === 1) {
        return Number(room.singleOccupancyPrice);
    }
    const includedChildren = Math.max(0, ROOM_RATE_POLICY.standardOccupancy - adults);
    const additionalChildCount = ages.slice(includedChildren).filter(age => Number(age) > ROOM_RATE_POLICY.freeChildMaxAge).length;
    return Number(room.price || 0) + additionalChildCount * ROOM_RATE_POLICY.additionalChildSupplement;
}

function validChildAge(age) {
    return String(age).trim() !== '' && Number.isInteger(Number(age)) && Number(age) >= 0 && Number(age) <= ROOM_RATE_POLICY.maxChildAge;
}

function calculateBookingNightlyRate() {
    return roomCart.reduce((total, room) => {
        const occupancy = roomCart.length > 1 ? roomGuests[room.id] : guests;
        const ages = roomCart.length > 1 ? (occupancy?.ages || []) : childAges;
        return total + nightlyRateForRoom(room, occupancy?.adults || 1, ages);
    }, 0);
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
    selectedReservation.roomPrice = calculateBookingNightlyRate();
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
    if ($('modalNightlyRate')) $('modalNightlyRate').innerText = formatCurrency(selectedReservation.roomPrice);
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
            availabilitySearchApplied = false;
            latestAvailableRoomIds = null;
            applyRoomVisibility();
            updateDateSummary(selectedDates);
            if (selectedDates.length === 2) setTimeout(() => instance.close(), 140);
        }
    });

    trigger.addEventListener('click', () => bookingCalendar.open());
}

// ---- Capacidad por habitación y límites de huéspedes ----
// Los límites de cada tipo de habitación llegan en los data-room-max-* de su tarjeta (se definen en configuracion/capacidad_habitaciones.php).
const MAX_ROOM_CAPACITY = { adults: 4, children: 4, people: 5 }; // capacidad máxima mientras no haya habitaciones elegidas
const roomGuests = {}; // id de habitación -> { adults, children, ages } (solo cuando hay 2 o más habitaciones)

function limitsText(people) {
    return `máximo ${people} personas`;
}

function escapeGuestText(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function guestLimits() {
    if (roomCart.length === 0) return { ...MAX_ROOM_CAPACITY, rooms: 0 };
    return roomCart.reduce((total, room) => ({
        adults: total.adults + room.maxAdults,
        children: total.children + room.maxChildren,
        people: total.people + room.maxPeople,
        rooms: total.rooms + 1,
    }), { adults: 0, children: 0, people: 0, rooms: 0 });
}

function clampGuestsToLimits() {
    const limits = guestLimits();
    const minAdults = Math.max(1, limits.rooms); // cada habitación necesita al menos un adulto
    guests.adults = Math.min(Math.max(guests.adults, minAdults), Math.max(limits.adults, minAdults));
    guests.children = Math.min(Math.max(guests.children, 0), limits.children);
    while (guests.adults + guests.children > limits.people && guests.children > 0) guests.children--;
    while (guests.adults + guests.children > limits.people && guests.adults > minAdults) guests.adults--;
}

function updateGuestSummary() {
    const limits = guestLimits();
    const total = guests.adults + guests.children;
    if ($('adultsValue')) $('adultsValue').innerText = String(guests.adults);
    if ($('childrenValue')) $('childrenValue').innerText = String(guests.children);
    if ($('guestSummary')) $('guestSummary').innerText = `${guests.adults} adultos, ${guests.children} niños`;
    if ($('adultsMinus')) $('adultsMinus').disabled = guests.adults <= Math.max(1, limits.rooms);
    if ($('childrenMinus')) $('childrenMinus').disabled = guests.children <= 0;
    if ($('adultsPlus')) $('adultsPlus').disabled = guests.adults >= limits.adults || total >= limits.people;
    if ($('childrenPlus')) $('childrenPlus').disabled = guests.children >= limits.children || total >= limits.people;
    if ($('guestLimitHint')) {
        $('guestLimitHint').innerText = limits.rooms === 0
            ? `Cada habitación admite ${limitsText(limits.people)}. Si viajan más, agrega otra habitación.`
            : limits.rooms === 1
                ? `La habitación elegida admite ${limitsText(limits.people)}.`
                : `Tus ${limits.rooms} habitaciones admiten ${limitsText(limits.people)} en total. Cada habitación necesita al menos un adulto.`;
    }
    if ($('modalGuests')) $('modalGuests').innerText = `${guests.adults} adultos, ${guests.children} niños`;
    if (availabilitySearchApplied) applyRoomVisibility();
}

// Reparte los huéspedes entre las habitaciones: un adulto en cada una y el resto de forma pareja, respetando sus límites.
function defaultRoomGuests() {
    const distribution = {};
    roomCart.forEach(room => { distribution[room.id] = { adults: 1, children: 0, ages: [] }; });
    const hasSpace = (room, kind) => {
        const d = distribution[room.id];
        return d.adults + d.children < room.maxPeople && (kind === 'adults' ? d.adults < room.maxAdults : d.children < room.maxChildren);
    };
    const spread = (kind, amount) => {
        let placed = true;
        while (amount > 0 && placed) {
            placed = false;
            for (const room of roomCart) {
                if (amount > 0 && hasSpace(room, kind)) { distribution[room.id][kind]++; amount--; placed = true; }
            }
        }
    };
    spread('adults', guests.adults - roomCart.length);
    spread('children', guests.children);
    roomCart.forEach(room => {
        distribution[room.id].ages = Array.from({ length: distribution[room.id].children }, () => '');
    });
    return distribution;
}

function ensureRoomGuests() {
    const ids = roomCart.map(room => room.id);
    const sameRooms = ids.length === Object.keys(roomGuests).length && ids.every(id => roomGuests[id]);
    const sum = kind => ids.reduce((total, id) => total + (roomGuests[id] ? roomGuests[id][kind] : 0), 0);
    if (!sameRooms || sum('adults') !== guests.adults || sum('children') !== guests.children) {
        Object.keys(roomGuests).forEach(key => delete roomGuests[key]);
        Object.assign(roomGuests, defaultRoomGuests());
    }
}

// Devuelve el texto del problema (o '' si todo está bien) antes de pagar.
function validateGuestsForRooms() {
    if (roomCart.length === 0) return '';
    const limitText = room => `${room.name} admite ${limitsText(room.maxPeople)}.`;
    if (roomCart.length === 1) {
        const room = roomCart[0];
        if (guests.adults > room.maxAdults || guests.children > room.maxChildren || guests.adults + guests.children > room.maxPeople) {
            return limitText(room);
        }
        return childAges.length !== guests.children || childAges.some(age => !validChildAge(age))
            ? 'Indica la edad de cada niño para calcular la tarifa correcta.' : '';
    }
    ensureRoomGuests();
    let sumAdults = 0;
    let sumChildren = 0;
    for (const room of roomCart) {
        const d = roomGuests[room.id];
        if (!d) return 'Indica cuántas personas habrá en cada habitación.';
        if (d.adults < 1) return `${room.name} necesita al menos un adulto.`;
        if (d.adults > room.maxAdults || d.children > room.maxChildren || d.adults + d.children > room.maxPeople) return limitText(room);
        if (d.ages.length !== d.children || d.ages.some(age => !validChildAge(age))) {
            return `Indica la edad de cada niño en ${room.name}.`;
        }
        sumAdults += d.adults;
        sumChildren += d.children;
    }
    return sumAdults !== guests.adults || sumChildren !== guests.children
        ? 'El reparto por habitación no coincide con el total de huéspedes.' : '';
}

function refreshRoomGuestsEditor() {
    const hint = $('modalRoomGuestsHint');
    roomCart.forEach(room => {
        const d = roomGuests[room.id];
        if (!d) return;
        const total = d.adults + d.children;
        const set = (kind, value, minusDisabled, plusDisabled) => {
            const valueNode = document.querySelector(`[data-room-guest-value="${room.id}|${kind}"]`);
            if (valueNode) valueNode.textContent = String(value);
            const minus = document.querySelector(`[data-room-guest="${room.id}|${kind}|-1"]`);
            const plus = document.querySelector(`[data-room-guest="${room.id}|${kind}|1"]`);
            if (minus) minus.disabled = minusDisabled;
            if (plus) plus.disabled = plusDisabled;
        };
        set('adults', d.adults, d.adults <= 1, d.adults >= room.maxAdults || total >= room.maxPeople);
        set('children', d.children, d.children <= 0, d.children >= room.maxChildren || total >= room.maxPeople);
    });
    if (hint) {
        const problem = validateGuestsForRooms();
        hint.textContent = problem || `La tarifa cubre hasta 4 huéspedes; se permite 1 niño adicional de 0 a 12 años por habitación.`;
        hint.classList.toggle('text-rose-300', Boolean(problem));
    }
    calcularTotalesCobro(calculateNights());
}

function renderRoomGuestsEditor() {
    const box = $('modalRoomGuests');
    const list = $('modalRoomGuestsList');
    if (!box || !list) return;
    if (roomCart.length < 2) { box.classList.add('hidden'); return; }
    ensureRoomGuests();
    const stepper = (roomId, kind, label) => `
        <div class="flex items-center justify-between rounded-xl bg-white/10 px-3 py-2">
            <span class="text-xs font-bold">${label}</span>
            <span class="flex items-center gap-2">
                <button type="button" data-room-guest="${roomId}|${kind}|-1" class="h-7 w-7 rounded-full bg-white/15 text-sm font-black text-white hover:bg-white/25 disabled:opacity-30" aria-label="Quitar ${label.toLowerCase()}">-</button>
                <span data-room-guest-value="${roomId}|${kind}" class="min-w-[18px] text-center text-sm font-black">0</span>
                <button type="button" data-room-guest="${roomId}|${kind}|1" class="h-7 w-7 rounded-full bg-white/15 text-sm font-black text-white hover:bg-white/25 disabled:opacity-30" aria-label="Agregar ${label.toLowerCase()}">+</button>
            </span>
        </div>`;
    const ageFields = room => roomGuests[room.id].ages.map((age, index) => `
        <label class="block text-xs font-bold text-white/70">Edad del niño ${index + 1}
            <select data-room-child-age="${room.id}|${index}" required class="mt-1 w-full rounded-lg border border-white/15 bg-[#17354f] px-3 py-2 text-sm text-white">
                <option value="" ${age === '' ? 'selected' : ''}>Selecciona edad</option>
                ${Array.from({ length: ROOM_RATE_POLICY.maxChildAge + 1 }, (_, childAge) => `<option value="${childAge}" ${age !== '' && Number(age) === childAge ? 'selected' : ''}>${childAge} ${childAge === 1 ? 'año' : 'años'}</option>`).join('')}
            </select>
        </label>`).join('');
    list.innerHTML = roomCart.map(room => `
        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
            <p class="text-sm font-black">${escapeGuestText(room.name)}</p>
            <p class="text-[11px] text-white/55">${limitsText(room.maxPeople).replace(/^m/, 'M')}</p>
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">${stepper(room.id, 'adults', 'Adultos')}${stepper(room.id, 'children', 'Niños')}</div>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">${ageFields(room)}</div>
        </div>`).join('');
    box.classList.remove('hidden');
    refreshRoomGuestsEditor();
}

function renderSingleRoomChildAges() {
    const box = $('modalSingleRoomChildAges');
    const list = $('modalSingleRoomChildAgesList');
    if (!box || !list) return;
    if (roomCart.length !== 1 || guests.children === 0) {
        box.classList.add('hidden');
        return;
    }

    while (childAges.length < guests.children) childAges.push('');
    childAges.length = guests.children;
    list.innerHTML = childAges.map((age, index) => `
        <label class="block text-xs font-bold text-white/70">Edad del niño ${index + 1}
            <select data-single-child-age="${index}" required class="mt-1 w-full rounded-lg border border-white/15 bg-[#17354f] px-3 py-2 text-sm text-white">
                <option value="" ${age === '' ? 'selected' : ''}>Selecciona edad</option>
                ${Array.from({ length: ROOM_RATE_POLICY.maxChildAge + 1 }, (_, childAge) => `<option value="${childAge}" ${age !== '' && Number(age) === childAge ? 'selected' : ''}>${childAge} ${childAge === 1 ? 'año' : 'años'}</option>`).join('')}
            </select>
        </label>`).join('');
    box.classList.remove('hidden');
}

function changeRoomGuest(roomId, kind, delta) {
    const room = roomCart.find(r => r.id === roomId);
    const d = roomGuests[roomId];
    if (!room || !d) return;
    const total = d.adults + d.children;
    const next = d[kind] + delta;
    if (kind === 'adults' && (next < 1 || next > room.maxAdults)) return;
    if (kind === 'children' && (next < 0 || next > room.maxChildren)) return;
    if (delta > 0 && total >= room.maxPeople) return;
    d[kind] = next;
    if (kind === 'children') {
        if (delta > 0) d.ages.push('');
        else d.ages.pop();
    }
    guests.adults = roomCart.reduce((sum, r) => sum + roomGuests[r.id].adults, 0);
    guests.children = roomCart.reduce((sum, r) => sum + roomGuests[r.id].children, 0);
    updateGuestSummary();
    renderRoomGuestsEditor();
}

document.addEventListener('click', event => {
    const button = event.target.closest('[data-room-guest]');
    if (!button || button.disabled) return;
    const [roomId, kind, delta] = button.dataset.roomGuest.split('|');
    changeRoomGuest(Number(roomId), kind, Number(delta));
});

document.addEventListener('change', event => {
    const roomAge = event.target.closest('[data-room-child-age]');
    if (roomAge) {
        const [roomId, index] = roomAge.dataset.roomChildAge.split('|').map(Number);
        if (roomGuests[roomId]) roomGuests[roomId].ages[index] = roomAge.value;
        calcularTotalesCobro(calculateNights());
        return;
    }
    const singleAge = event.target.closest('[data-single-child-age]');
    if (singleAge) {
        childAges[Number(singleAge.dataset.singleChildAge)] = singleAge.value;
        calcularTotalesCobro(calculateNights());
    }
});

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
    const limits = guestLimits();
    const min = type === 'adults' ? Math.max(1, limits.rooms) : 0;
    const max = type === 'adults' ? limits.adults : limits.children;
    const otherTotal = type === 'adults' ? guests.children : guests.adults;
    let next = Math.min(Math.max(min, guests[type] + delta), max);
    if (next + otherTotal > limits.people) next = Math.max(min, limits.people - otherTotal);
    guests[type] = next;
    if (type === 'children') {
        while (childAges.length < next) childAges.push('');
        childAges.length = next;
    }
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
    const availableIds = nextAvailability.habitaciones_disponibles_ids || visibleIds || [];
    latestAvailableRoomIds = new Set(Array.from(availableIds, Number));
    availabilitySearchApplied = true;
    return applyRoomVisibility();
}

function applyRoomVisibility() {
    const cards = document.querySelectorAll('[data-room-card]');
    let visibleCount = 0;
    let hasDateAvailableRooms = false;

    cards.forEach(card => {
        let roomIds = [];
        try { roomIds = JSON.parse(card.dataset.roomIds || '[]').map(Number); } catch (_) { roomIds = []; }
        const currentRoomId = Number(card.getAttribute('data-room-card')) || 0;
        if (currentRoomId > 0 && !roomIds.includes(currentRoomId)) roomIds.push(currentRoomId);

        const availableCount = availabilitySearchApplied
            ? roomIds.filter(id => latestAvailableRoomIds.has(id)).length
            : 1;
        const dateAvailable = !availabilitySearchApplied || availableCount > 0;
        const addButton = card.querySelector('[data-room-add]');
        const fitsGuests = !availabilitySearchApplied || (
            guests.adults <= Number(addButton?.dataset.roomMaxAdults || 0)
            && guests.children <= Number(addButton?.dataset.roomMaxChildren || 0)
            && guests.adults + guests.children <= Number(addButton?.dataset.roomMaxPeople || 0)
        );
        const type = (card.getAttribute('data-room-type') || 'otra').toLowerCase();
        const typeMatches = selectedRoomFilter === 'all'
            || type.includes(selectedRoomFilter)
            || (selectedRoomFilter === 'sencilla' && type.includes('simple'));
        const show = dateAvailable && fitsGuests && typeMatches;
        hasDateAvailableRooms = hasDateAvailableRooms || dateAvailable;
        card.classList.toggle('hidden', !show);
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;

        const button = addButton;
        const label = card.querySelector('[data-room-availability]');
        if (button) {
            button.disabled = availabilitySearchApplied && (!dateAvailable || !fitsGuests);
            const labelSpan = button.querySelector('[data-room-add-label]');
            if (labelSpan) labelSpan.textContent = button.disabled
                ? (dateAvailable ? 'Capacidad insuficiente' : 'No disponible')
                : 'Agregar';
        }
        if (label) {
            if (availabilitySearchApplied && !dateAvailable) {
                label.textContent = 'No disponible para esas fechas.';
                label.classList.remove('hidden');
            } else {
                label.textContent = '';
                label.classList.add('hidden');
            }
        }
    });

    const emptyState = document.getElementById('emptyRoomsState');
    if (emptyState) {
        if (availabilitySearchApplied && visibleCount === 0) emptyState.classList.remove('hidden');
        else if (cards.length > 0) emptyState.classList.add('hidden');
    }
    const emptyDetail = document.getElementById('emptyRoomsDetail');
    if (emptyDetail && availabilitySearchApplied && visibleCount === 0) {
        emptyDetail.textContent = hasDateAvailableRooms
            ? 'No hay habitaciones que coincidan con el tipo, las fechas y la capacidad del grupo seleccionado.'
            : 'Cambia el rango de fechas o vuelve a consultar en unos segundos.';
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
        updateVisibleRooms(ids, result);

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
        roomCart.push({
            id,
            name: button.dataset.roomName || `Habitación ${id}`,
            price: Number(button.dataset.roomPrice || 0),
            singleOccupancyPrice: button.dataset.roomSingleOccupancyPrice === ''
                ? null
                : Number(button.dataset.roomSingleOccupancyPrice),
            type: button.dataset.roomType || '',
            maxPeople: Number(button.dataset.roomMaxPeople || MAX_ROOM_CAPACITY.people),
            maxAdults: Number(button.dataset.roomMaxAdults || MAX_ROOM_CAPACITY.adults),
            maxChildren: Number(button.dataset.roomMaxChildren || MAX_ROOM_CAPACITY.children),
        });
        button.classList.add('bg-emerald-600', 'text-white');
        if (labelSpan) labelSpan.textContent = 'Quitar';
    }
    renderRoomCart();
}

function renderRoomCart() {
    // Al agregar o quitar habitaciones cambian los límites de huéspedes: se ajustan los totales a lo que cabe.
    clampGuestsToLimits();
    updateGuestSummary();
    const cart = $('roomCart');
    const summary = $('roomCartSummary');
    if (!cart || !summary) return;
    if (roomCart.length === 0) { cart.classList.add('hidden'); return; }
    summary.textContent = `${roomCart.length} habitación${roomCart.length > 1 ? 'es' : ''} · tarifa ajustada a la ocupación`;
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
    renderRoomGuestsEditor();
    renderSingleRoomChildAges();
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

document.querySelectorAll('input[name="hospedaje_a_nombre_de"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const esOtraPersona = document.querySelector('input[name="hospedaje_a_nombre_de"]:checked')?.value === 'otra_persona';
        const wrapper = $('nombreHuespedAlternativoWrapper');
        const input = $('nombreHuespedAlternativo');
        if (wrapper) wrapper.classList.toggle('hidden', !esOtraPersona);
        if (input) {
            input.disabled = !esOtraPersona;
            input.required = esOtraPersona;
            if (!esOtraPersona) input.value = '';
        }
    });
});

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
    // Se valida el límite de personas ANTES de cobrar: si no cabe, no se abre el pago.
    const guestsProblem = validateGuestsForRooms();
    if (guestsProblem) {
        if (feedback) {
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = guestsProblem;
            feedback.classList.remove('hidden');
        }
        return;
    }

    const titularHospedaje = document.querySelector('input[name="hospedaje_a_nombre_de"]:checked')?.value || 'cuenta';
    const nombreHuespedAlternativo = $('nombreHuespedAlternativo')?.value.trim() || '';
    if (titularHospedaje === 'otra_persona' && nombreHuespedAlternativo === '') {
        if (feedback) {
            feedback.className = 'mt-4 text-sm font-bold text-rose-400 block';
            feedback.innerText = 'Escribe el nombre de la persona que se hospedará.';
            feedback.classList.remove('hidden');
        }
        $('nombreHuespedAlternativo')?.focus();
        return;
    }

    const confirmation = await Swal.fire({
        icon: 'question',
        title: '¿Confirmas esta reserva?',
        text: `${selectedReservation.roomName} · ${searchState.checkin} al ${searchState.checkout} · Hospedaje a nombre de ${titularHospedaje === 'otra_persona' ? nombreHuespedAlternativo : 'tu cuenta'} · Total: ${formatCurrency(selectedReservation.montoTotal)}. La disponibilidad se validará nuevamente antes de guardar.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Revisar reserva',
        confirmButtonColor: '#17354f',
        cancelButtonColor: '#64748b',
        background: '#fffdf8'
    });
    if (!confirmation.isConfirmed) return;

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
        fd.append('hospedaje_a_nombre_de', titularHospedaje);
        if (titularHospedaje === 'otra_persona') {
            fd.append('nombre_huesped_estadia', nombreHuespedAlternativo);
        }
        fd.append('cant_adultos', guests.adults);
        fd.append('cant_ninos', guests.children);
        fd.append('edades_ninos', JSON.stringify(childAges));
        if (roomCart.length > 1) {
            fd.append('huespedes_por_habitacion', JSON.stringify(roomCart.map(r => ({
                id: r.id,
                adultos: roomGuests[r.id].adults,
                ninos: roomGuests[r.id].children,
                edades_ninos: roomGuests[r.id].ages,
            }))));
        }
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
    document.querySelectorAll('[data-collapse-target]').forEach(button => {
        button.addEventListener('click', () => {
            const content = document.getElementById(button.dataset.collapseTarget);
            if (!content) return;
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            content.classList.toggle('hidden', isExpanded);
            button.setAttribute('aria-expanded', String(!isExpanded));
            button.textContent = isExpanded ? button.dataset.collapseShow : button.dataset.collapseHide;
        });
    });

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
                exclusiones: JSON.parse(boton.dataset.experienceExclusions || '{}'),
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

    const inputPersonas = $('cantidadPersonasActividad');
    if (inputPersonas) {
        inputPersonas.addEventListener('input', () => {
            const cantidad = Math.max(1, Math.min(20, Number(inputPersonas.value) || 1));
            generarOpcionesPorPersona(cantidad);
            actualizarResumenPrecio();
            pintarFechasDisponibles();
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
                return `<option value="${escapeGuestText(op)}">${escapeGuestText(op)} · ${precioTexto}</option>`;
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
        contenedor.querySelectorAll('[data-persona-select]').forEach(sel => sel.addEventListener('change', () => {
            actualizarResumenPrecio();
            pintarFechasDisponibles();
        }));
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

    function optionIndexesSelected() {
        if (!experienciaActual) return [];
        return [...document.querySelectorAll('[data-persona-select]')]
            .map(select => {
                const index = experienciaActual.opciones.indexOf(select.value);
                return index >= 0 ? Number(experienciaActual.indices[index] || index + 1) : 0;
            })
            .filter(Boolean)
            .filter((index, position, all) => all.indexOf(index) === position);
    }

    function horarioCompartido(fecha) {
        const indices = optionIndexesSelected();
        if (!indices.length) return null;
        const rangos = indices.map(index => experienciaActual.horarios?.[String(index)]?.[fecha]);
        if (rangos.some(rango => !rango)) return null;
        const inicio = rangos.reduce((maximo, rango) => rango.inicio > maximo ? rango.inicio : maximo, '00:00');
        const fin = rangos.reduce((minimo, rango) => rango.fin < minimo ? rango.fin : minimo, '23:59');
        return inicio <= fin ? { inicio, fin } : null;
    }

    function fechaActualBogota() {
        const partes = new Intl.DateTimeFormat('en', {
            timeZone: 'America/Bogota',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23'
        }).formatToParts(new Date());
        const valores = Object.fromEntries(partes.map(parte => [parte.type, parte.value]));
        return {
            fecha: `${valores.year}-${valores.month}-${valores.day}`,
            minutos: Number(valores.hour) * 60 + Number(valores.minute)
        };
    }

    function horarioTieneHoraDisponible(fecha, rango, ahora) {
        if (fecha !== ahora.fecha) return true;
        const [horaInicio, minutoInicio] = rango.inicio.split(':').map(Number);
        const [horaFin, minutoFin] = rango.fin.split(':').map(Number);
        const primerInicio = Math.max(
            horaInicio * 60 + minutoInicio,
            (Math.floor(ahora.minutos / 30) + 1) * 30
        );
        return primerInicio <= horaFin * 60 + minutoFin;
    }

    function fechasDentroEstancia(fechas, reservas) {
        if (reservas.length === 0) return [];
        return fechas.filter(fecha =>
            reservas.some(reserva => fecha >= reserva.inicio && fecha < reserva.fin)
        );
    }

    function siguienteFecha(fecha, dias) {
        const [anio, mes, dia] = fecha.split('-').map(Number);
        return new Date(Date.UTC(anio, mes - 1, dia + dias)).toISOString().slice(0, 10);
    }

    function etiquetaDisponibilidadFechas(fechas, exclusiones = []) {
        if (fechas.length === 1) return etiquetaRangoFechas(fechas[0], fechas[0]);
        const rango = etiquetaRangoFechas(fechas[0], fechas[fechas.length - 1]);
        const textoExclusiones = exclusiones.length
            ? `, excepto ${exclusiones.map(fecha => etiquetaRangoFechas(fecha, fecha)).join(' y ')}`
            : '';
        return `${rango}${textoExclusiones}`;
    }

    function resumirHorariosOpcion(fechas, horarios, fechasExcluidas = []) {
        const grupos = new Map();
        fechas.forEach(fecha => {
            const rango = horarios[fecha];
            const clave = `${rango.inicio}|${rango.fin}`;
            if (!grupos.has(clave)) grupos.set(clave, []);
            grupos.get(clave).push(fecha);
        });
        return [...grupos.entries()].map(([clave, fechasGrupo]) => {
            const [inicio, fin] = clave.split('|');
            const segmentos = [];
            let segmento = [fechasGrupo[0]];
            let exclusionesSegmento = [];
            for (let indice = 1; indice < fechasGrupo.length; indice++) {
                const fechaAnterior = fechasGrupo[indice - 1];
                const fechaActual = fechasGrupo[indice];
                const huecos = [];
                let fechaHueco = siguienteFecha(fechaAnterior, 1);
                while (fechaHueco < fechaActual) {
                    huecos.push(fechaHueco);
                    fechaHueco = siguienteFecha(fechaHueco, 1);
                }
                if (huecos.length > 0 && !huecos.every(fecha => fechasExcluidas.includes(fecha))) {
                    segmentos.push({ fechas: segmento, exclusiones: exclusionesSegmento });
                    segmento = [fechaActual];
                    exclusionesSegmento = [];
                    continue;
                }
                exclusionesSegmento.push(...huecos);
                segmento.push(fechaActual);
            }
            segmentos.push({ fechas: segmento, exclusiones: exclusionesSegmento });
            return segmentos.map(item =>
                `Disponible ${etiquetaDisponibilidadFechas(item.fechas, item.exclusiones)} · ${inicio}–${fin}`
            );
        }).flat();
    }

    function fechasDisponibles() {
        const indices = optionIndexesSelected();
        if (!indices.length) return [];
        const reservas = experienciaActual.reservas || [];
        if (reservas.length === 0) return [];
        const ahora = fechaActualBogota();
        const hoyIso = ahora.fecha;
        const candidatas = Object.keys(experienciaActual.horarios?.[String(indices[0])] || {}).sort();
        return candidatas.filter(fecha => {
            const rango = horarioCompartido(fecha);
            if (fecha < hoyIso || !rango || !horarioTieneHoraDisponible(fecha, rango, ahora)) return false;
            return reservas.length === 0 || reservas.some(reserva => fecha >= reserva.inicio && fecha < reserva.fin);
        });
    }

    function resumenSinDisponibilidadComun() {
        const indices = optionIndexesSelected();
        if (!indices.length) return 'Selecciona una opción para cada persona.';

        const ahora = fechaActualBogota();
        const hoy = ahora.fecha;
        const reservas = experienciaActual.reservas || [];
        if (reservas.length === 0) {
            return 'Necesitas una reserva vigente para ver fechas disponibles.';
        }
        const opciones = indices.map(indice => {
            const nombre = experienciaActual.opciones[
                experienciaActual.indices.indexOf(indice)
            ] || `Opción ${indice}`;
            const horariosOpcion = experienciaActual.horarios?.[String(indice)] || {};
            const fechasFuturas = Object.keys(horariosOpcion)
                .filter(fecha => fecha >= hoy)
                .sort();
            const fechasConHoraFutura = fechasFuturas.filter(fecha =>
                horarioTieneHoraDisponible(fecha, horariosOpcion[fecha], ahora)
            );
            const fechasEnEstancia = fechasDentroEstancia(fechasConHoraFutura, reservas);
            const fechasExcluidas = experienciaActual.exclusiones?.[String(indice)] || [];
            const horarios = resumirHorariosOpcion(fechasEnEstancia, horariosOpcion, fechasExcluidas);
            let detalle = horarios.join('; ');
            if (!detalle) {
                const horarioDeHoyFinalizado = fechasFuturas.includes(hoy)
                    && !horarioTieneHoraDisponible(hoy, horariosOpcion[hoy], ahora);
                if (horarioDeHoyFinalizado) {
                    detalle = 'el horario publicado para hoy ya finalizó';
                } else if (fechasConHoraFutura.length > 0 && reservas.length > 0) {
                    detalle = 'horarios fuera de tu estancia';
                } else {
                    detalle = 'sin horarios futuros publicados';
                }
            }
            return `${nombre}: ${detalle}`;
        });

        return `No hay una fecha y horario común dentro de tu estancia para todas las opciones seleccionadas. ${opciones.join(' · ')}`;
    }

    function fechaLegible(fecha) {
        return new Date(`${fecha}T00:00:00`).toLocaleDateString('es-CO', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
    }

    function etiquetaRangoFechas(inicio, fin) {
        const fechaInicio = new Date(`${inicio}T00:00:00`);
        const fechaFin = new Date(`${fin}T00:00:00`);
        const formato = (fecha, opciones) => new Intl.DateTimeFormat('es-CO', opciones).format(fecha);
        if (inicio === fin) return formato(fechaInicio, { day: 'numeric', month: 'long', year: 'numeric' });
        if (fechaInicio.getFullYear() === fechaFin.getFullYear() && fechaInicio.getMonth() === fechaFin.getMonth()) {
            return `del ${fechaInicio.getDate()} al ${formato(fechaFin, { day: 'numeric', month: 'long', year: 'numeric' })}`;
        }
        return `del ${formato(fechaInicio, { day: 'numeric', month: 'short' })} al ${formato(fechaFin, { day: 'numeric', month: 'short', year: 'numeric' })}`;
    }

    function agruparFechasConHorario(fechas) {
        const grupos = [];
        fechas.forEach(fecha => {
            const rango = horarioCompartido(fecha);
            const ultimo = grupos[grupos.length - 1];
            let fechaSiguiente = null;
            if (ultimo) {
                const siguiente = new Date(`${ultimo.fin}T00:00:00`);
                siguiente.setDate(siguiente.getDate() + 1);
                fechaSiguiente = `${siguiente.getFullYear()}-${String(siguiente.getMonth() + 1).padStart(2, '0')}-${String(siguiente.getDate()).padStart(2, '0')}`;
            }
            if (ultimo && fecha === fechaSiguiente && ultimo.inicio_hora === rango.inicio && ultimo.fin_hora === rango.fin) {
                ultimo.fin = fecha;
            } else {
                grupos.push({ inicio: fecha, fin: fecha, inicio_hora: rango.inicio, fin_hora: rango.fin });
            }
        });
        return grupos;
    }

    function pintarFechasDisponibles() {
        const selectFecha = $('fechaActividad');
        if (!selectFecha) return;
        const fechaAnterior = selectFecha.value;
        selectFecha.innerHTML = '<option value="">Selecciona un día</option>';
        const fechasValidas = experienciaActual ? fechasDisponibles() : [];

        if (fechasValidas.length === 0) {
            selectFecha.disabled = true;
            selectFecha.innerHTML = `<option value="">${experienciaActual ? 'No hay fechas disponibles para las opciones seleccionadas' : 'Primero elige una experiencia'}</option>`;
            const resumenDisponibilidad = $('experienceAvailabilitySummary');
            if (resumenDisponibilidad) resumenDisponibilidad.textContent = experienciaActual
                ? resumenSinDisponibilidadComun()
                : '';
            pintarHoras('');
            return;
        }

        selectFecha.disabled = false;
        fechasValidas.forEach(fecha => selectFecha.add(new Option(fechaLegible(fecha), fecha)));
        const grupos = agruparFechasConHorario(fechasValidas);
        const resumenDisponibilidad = $('experienceAvailabilitySummary');
        if (resumenDisponibilidad) {
            resumenDisponibilidad.textContent = `Fechas disponibles dentro de tu estancia (${fechasValidas.length}): ` + grupos
                .map(grupo => `${etiquetaRangoFechas(grupo.inicio, grupo.fin)} · ${grupo.inicio_hora}–${grupo.fin_hora}`)
                .join(' | ');
        }
        selectFecha.value = fechasValidas.includes(fechaAnterior) ? fechaAnterior : '';
        pintarHoras(selectFecha.value);
    }

    const selectFecha = $('fechaActividad');
    if (selectFecha) selectFecha.addEventListener('change', () => pintarHoras(selectFecha.value));

    function pintarHoras(fecha) {
        const contenedor = $('listaHorasActividad');
        const inputHora = $('horaActividad');
        if (!contenedor) return;
        if (inputHora) inputHora.value = '';
        if (!fecha || !experienciaActual) {
            contenedor.textContent = 'Primero elige una experiencia y un día.';
            return;
        }

        const rango = horarioCompartido(fecha);
        if (!rango) {
            contenedor.textContent = 'No hay un horario común disponible para las opciones seleccionadas.';
            return;
        }
        const [horaInicio, minutoInicio] = rango.inicio.split(':').map(Number);
        const [horaFin, minutoFin] = rango.fin.split(':').map(Number);
        let inicioMinutos = horaInicio * 60 + minutoInicio;
        const finMinutos = horaFin * 60 + minutoFin;
        const ahoraBogota = fechaActualBogota();
        if (fecha === ahoraBogota.fecha) {
            inicioMinutos = Math.max(inicioMinutos, (Math.floor(ahoraBogota.minutos / 30) + 1) * 30);
        }
        if (inicioMinutos > finMinutos) {
            contenedor.textContent = 'El horario publicado para hoy ya pasó. Elige otra fecha disponible.';
            return;
        }
        const horaProgramada = `${String(Math.floor(inicioMinutos / 60)).padStart(2, '0')}:${String(inicioMinutos % 60).padStart(2, '0')}`;
        if (inputHora) inputHora.value = horaProgramada;
        contenedor.textContent = `Horario publicado: ${rango.inicio}–${rango.fin}. La experiencia quedará programada a las ${horaProgramada}.`;
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        event.stopPropagation();
        const submitBtn = form.querySelector('button[type="submit"]');
        const textoOriginal = submitBtn ? submitBtn.innerHTML : '';
        const feedback = $('activityModalFeedback') || crearFeedbackModal();
        const cantidadPersonas = Number($('cantidadPersonasActividad')?.value || 1);
        const participantesRaw = $('participantesActividad')?.value || '';
        const fecha = $('fechaActividad')?.value || '';
        const hora = $('horaActividad')?.value || '';

        if (!experienciaActual) return mostrarError(feedback, 'Selecciona una experiencia disponible.');
        if (!fecha) return mostrarError(feedback, 'Selecciona una fecha.');
        if (!fechasDisponibles().includes(fecha)) {
            pintarFechasDisponibles();
            return mostrarError(feedback, 'La fecha debe tener horario publicado y estar dentro de una reserva vigente a tu nombre.');
        }
        if (!hora) return mostrarError(feedback, 'No hay un horario válido para esa fecha. Elige otra fecha disponible.');

        let participantes;
        try { participantes = JSON.parse(participantesRaw || '[]'); } catch (_) { participantes = []; }
        if (participantes.length !== cantidadPersonas) return mostrarError(feedback, 'Selecciona una opción por persona.');

        const fd = new FormData();
        fd.append('accion', 'agendar_actividad');
        fd.append('experiencia_id', String(experienciaActual.id));
        fd.append('cantidad_personas', String(cantidadPersonas));
        fd.append('participantes', JSON.stringify(participantes));
        fd.append('fecha', fecha);
        fd.append('hora', hora);
        fd.append('csrf_token', CSRF_TOKEN);

        try {
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = 'Confirmando...'; }
            closeActivityModal();
            const experienciasMismoDia = SCHEDULED_EXPERIENCES.filter(experiencia =>
                experiencia.date === fecha
                && experiencia.status.trim().toLocaleLowerCase('es-CO') !== 'cancelada'
            );
            const textoConfirmacion = experienciasMismoDia.length > 0
                ? `Ya tienes ${experienciasMismoDia.length === 1 ? 'una experiencia programada' : `${experienciasMismoDia.length} experiencias programadas`} para el ${fechaLegible(fecha)}${experienciasMismoDia.length === 1 ? '' : `: ${experienciasMismoDia.map(experiencia => experiencia.name).join(', ')}`}. ¿Seguro que quieres programar "${experienciaActual.nombre}" para este día?`
                : `Tu reserva vigente cubre el ${fechaLegible(fecha)}. ¿Confirmas programar "${experienciaActual.nombre}" ese día a las ${hora}? La solicitud quedará asociada a esa reserva.`;
            const confirmacion = await Swal.fire({
                icon: 'question',
                title: experienciasMismoDia.length > 0 ? 'Ya tienes experiencias para este día' : 'Confirma tu experiencia',
                text: textoConfirmacion,
                showCancelButton: true,
                confirmButtonText: 'Sí, programar',
                cancelButtonText: 'Seguir revisando',
                confirmButtonColor: '#17354f',
                cancelButtonColor: '#64748b',
                background: '#fffdf8'
            });
            if (!confirmacion.isConfirmed) {
                openActivityModal();
                return;
            }

            if (submitBtn) submitBtn.innerHTML = 'Enviando...';
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
            openActivityModal();
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