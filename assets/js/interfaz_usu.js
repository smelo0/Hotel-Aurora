(() => {
    if (window.__AURORA_PORTAL_INITIALIZED__) return;
    window.__AURORA_PORTAL_INITIALIZED__ = true;

    const {
        wompiPublicKey: WOMPI_PUBLIC_KEY,
        csrfToken: CSRF_TOKEN,
        recaptchaSiteKey: RECAPTCHA_SITE_KEY,
        isUserAuthenticated
    } = window.PORTAL_CONFIG || {};

    const STORAGE_KEY = 'aurora_booking_draft_v1';
    const guests = { adults: 2, children: 0 };
    const searchState = { checkin: '', checkout: '' };
    const roomCart = [];

    const $ = (id) => document.getElementById(id);

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

    function saveDraftToStorage() {
        try {
            const payload = {
                rooms: roomCart.map(r => ({
                    id: Number(r.id),
                    num: String(r.num || ''),
                    typeLabel: String(r.typeLabel || ''),
                    name: String(r.name || ''),
                    price: Number(r.price || 0),
                    photo: String(r.photo || '')
                })),
                checkin: searchState.checkin || '',
                checkout: searchState.checkout || '',
                adults: guests.adults,
                children: guests.children,
                updatedAt: Date.now()
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
        } catch (_) {}
    }

    function loadDraftFromStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') return;

            if (Array.isArray(parsed.rooms)) {
                roomCart.length = 0;
                parsed.rooms.forEach(r => {
                    const id = Number(r.id);
                    if (id > 0 && !roomCart.some(item => item.id === id)) {
                        roomCart.push({
                            id,
                            num: String(r.num || ''),
                            typeLabel: String(r.typeLabel || ''),
                            name: String(r.name || `Habitación ${id}`),
                            price: Number(r.price || 0),
                            photo: String(r.photo || '')
                        });
                    }
                });
            }
            const todayIso = new Date().toISOString().slice(0, 10);
            if (typeof parsed.checkin === 'string' && parsed.checkin >= todayIso) {
                searchState.checkin = parsed.checkin;
            }
            if (typeof parsed.checkout === 'string' && parsed.checkout > searchState.checkin) {
                searchState.checkout = parsed.checkout;
            }
            if (Number.isInteger(parsed.adults) && parsed.adults >= 1) {
                guests.adults = Math.min(20, parsed.adults);
            }
            if (Number.isInteger(parsed.children) && parsed.children >= 0) {
                guests.children = Math.min(20, parsed.children);
            }
        } catch (_) {}
    }

    function buildReservationUrl() {
        saveDraftToStorage();
        const params = new URLSearchParams();
        if (roomCart.length > 0) {
            params.set('rooms', roomCart.map(r => r.id).join(','));
        }
        if (searchState.checkin) params.set('checkin', searchState.checkin);
        if (searchState.checkout) params.set('checkout', searchState.checkout);
        params.set('adults', String(guests.adults));
        params.set('children', String(guests.children));
        const qs = params.toString();
        return qs ? `reservar.php?${qs}` : 'reservar.php';
    }

    function navigateToReservationPage() {
        window.location.href = buildReservationUrl();
    }

    // ─────────────────────────────────────────────────────────
    // Grilla / Carrusel y filtros de habitaciones
    // ─────────────────────────────────────────────────────────
    (function setupRoomsCatalog() {
        const container = $('roomsCarousel');
        const prev = $('roomsPrev');
        const next = $('roomsNext');
        const navWrap = $('roomsCarouselNav');
        const toggleViewBtn = $('toggleRoomsViewBtn');
        const toggleViewIcon = $('toggleRoomsViewIcon');
        const toggleViewLabel = $('toggleRoomsViewLabel');
        const filters = Array.from(document.querySelectorAll('.room-filter'));
        if (!container) return;

        let isCarouselMode = false;

        function updateNavState() {
            if (!prev || !next || !isCarouselMode) return;
            const maxScroll = container.scrollWidth - container.clientWidth;
            const atStart = container.scrollLeft <= 2;
            const atEnd = container.scrollLeft >= maxScroll - 2;
            prev.disabled = maxScroll <= 2 || atStart;
            next.disabled = maxScroll <= 2 || atEnd;
        }

        if (toggleViewBtn) {
            toggleViewBtn.addEventListener('click', () => {
                isCarouselMode = !isCarouselMode;
                container.classList.toggle('rooms-grid-view', !isCarouselMode);
                container.classList.toggle('rooms-carousel-view', isCarouselMode);
                if (navWrap) {
                    navWrap.classList.toggle('hidden', !isCarouselMode);
                    navWrap.classList.toggle('flex', isCarouselMode);
                }
                if (toggleViewIcon) {
                    toggleViewIcon.textContent = isCarouselMode ? 'grid_view' : 'view_carousel';
                }
                if (toggleViewLabel) {
                    toggleViewLabel.textContent = isCarouselMode ? 'Vista cuadrícula' : 'Vista carrusel';
                }
                setTimeout(updateNavState, 80);
            });
        }

        if (prev && next) {
            const step = () => Math.round(container.clientWidth * 0.82) || 360;
            prev.addEventListener('click', () => container.scrollBy({ left: -step(), behavior: 'smooth' }));
            next.addEventListener('click', () => container.scrollBy({ left: step(), behavior: 'smooth' }));
            window.addEventListener('resize', updateNavState);
            container.addEventListener('scroll', updateNavState, { passive: true });
        }

        function applyFilter(filter) {
            const cards = Array.from(container.querySelectorAll('[data-room-card]'));
            let visibleCards = 0;
            cards.forEach(card => {
                const type = (card.getAttribute('data-room-type') || 'otra').toLowerCase();
                const filterLower = filter.toLowerCase();
                const matchesType = filterLower === 'all'
                    || type.includes(filterLower)
                    || (filterLower === 'sencilla' && type.includes('simple'));
                const soldOut = card.dataset.soldOut === 'true';
                const show = matchesType && !soldOut;
                card.classList.toggle('hidden', !show);
                card.style.display = show ? '' : 'none';
                if (show) visibleCards++;
            });

            const emptyState = $('emptyRoomsState');
            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleCards > 0);
            }
            container.scrollLeft = 0;
            setTimeout(updateNavState, 100);
        }

        filters.forEach(btn => {
            btn.addEventListener('click', () => {
                filters.forEach(b => {
                    b.classList.remove('bg-white/15', 'border-white/25', 'text-white');
                    b.classList.add('bg-transparent', 'text-white/75');
                });
                btn.classList.remove('bg-transparent', 'text-white/75');
                btn.classList.add('bg-white/15', 'border-white/25', 'text-white');
                applyFilter(btn.getAttribute('data-filter') || 'all');
            });
        });

        applyFilter('all');
    })();

    // ─────────────────────────────────────────────────────────
    // Calendario y Huéspedes
    // ─────────────────────────────────────────────────────────
    let bookingCalendar = null;

    function currentSearchIsReady() {
        return searchState.checkin !== '' && searchState.checkout !== '';
    }

    function setControlState(element, enabled) {
        if (!element) return;
        element.classList.toggle('is-active', enabled);
    }

    function updateDateSummary(selectedDates = []) {
        const summary = $('dateSummary');
        if (!summary) return;
        if (selectedDates.length === 2) {
            const nights = Math.max(1, Math.round((selectedDates[1] - selectedDates[0]) / 86400000));
            summary.innerText = `${formatShortDate(selectedDates[0])} → ${formatShortDate(selectedDates[1])} (${nights} noche${nights > 1 ? 's' : ''})`;
            return;
        }
        if (selectedDates.length === 1) {
            summary.innerText = `${formatShortDate(selectedDates[0])} · Elige salida`;
            return;
        }
        summary.innerText = 'Selecciona check-in y check-out';
    }

    function initializeCalendar() {
        const input = $('dateRangeInput');
        const trigger = $('dateTrigger');
        if (!input || !trigger || typeof flatpickr === 'undefined') return;

        const defaultDates = (searchState.checkin && searchState.checkout)
            ? [searchState.checkin, searchState.checkout]
            : [];

        bookingCalendar = flatpickr(input, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'Y-m-d',
            defaultDate: defaultDates,
            locale: flatpickr.l10ns.es,
            disableMobile: true,
            showMonths: window.matchMedia('(min-width: 900px)').matches ? 2 : 1,
            appendTo: document.body,
            positionElement: trigger,
            onReady: (selectedDates, __, instance) => {
                instance.calendarContainer.classList.add('aurora-calendar');
                if (selectedDates.length === 2) updateDateSummary(selectedDates);
            },
            onOpen: () => setControlState(trigger.closest('.booking-control'), true),
            onClose: () => setControlState(trigger.closest('.booking-control'), false),
            onChange: (selectedDates, _dateStr, instance) => {
                searchState.checkin = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
                searchState.checkout = selectedDates[1] ? instance.formatDate(selectedDates[1], 'Y-m-d') : '';
                updateDateSummary(selectedDates);
                saveDraftToStorage();
                renderRoomCart();
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
        if ($('guestSummary')) $('guestSummary').innerText = `${guests.adults} adulto${guests.adults > 1 ? 's' : ''}, ${guests.children} niño${guests.children === 1 ? '' : 's'}`;
        if ($('adultsMinus')) $('adultsMinus').disabled = guests.adults <= 1;
        if ($('childrenMinus')) $('childrenMinus').disabled = guests.children <= 0;
        saveDraftToStorage();
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
        guests[type] = Math.max(min, Math.min(20, guests[type] + delta));
        updateGuestSummary();
    }

    // ─────────────────────────────────────────────────────────
    // Búsqueda de disponibilidad en tiempo real
    // ─────────────────────────────────────────────────────────
    function scrollToResults() {
        const target = $('vista-resultados');
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function readJsonSafely(response) {
        const text = (await response.text()).replace(/^\uFEFF/, '').trim();
        try {
            return JSON.parse(text);
        } catch (_) {
            throw new Error('Respuesta inválida del servidor.');
        }
    }

    function updateVisibleRooms(visibleIds) {
        const idsSet = visibleIds instanceof Set ? visibleIds : new Set(visibleIds || []);
        const cards = document.querySelectorAll('[data-room-card]');
        const isSearchApplied = currentSearchIsReady() && visibleIds !== null;
        let visibleCount = 0;

        cards.forEach(card => {
            let roomIds = [];
            try {
                roomIds = JSON.parse(card.dataset.roomIds || '[]').map(Number);
            } catch (_) {
                roomIds = [];
            }
            const currentRoomId = Number(card.getAttribute('data-room-card')) || 0;
            if (currentRoomId > 0 && !roomIds.includes(currentRoomId)) roomIds.push(currentRoomId);

            const availableCount = isSearchApplied
                ? roomIds.filter(id => idsSet.has(id) || idsSet.has(String(id))).length
                : 1;
            const soldOut = isSearchApplied && availableCount === 0;
            card.dataset.soldOut = soldOut ? 'true' : 'false';

            if (!soldOut) {
                card.classList.remove('hidden');
                card.style.display = '';
                visibleCount++;
            } else {
                card.classList.add('hidden');
                card.style.display = 'none';
            }

            const button = card.querySelector('[data-room-add]');
            const bookNowBtn = card.querySelector('[data-room-book-now]');
            const label = card.querySelector('[data-room-availability]');

            if (button) {
                button.disabled = soldOut;
            }
            if (bookNowBtn) {
                bookNowBtn.disabled = soldOut;
            }
            if (label) {
                if (soldOut && isSearchApplied) {
                    label.textContent = 'No disponible para las fechas seleccionadas.';
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
        syncRoomCardButtons();
        return visibleCount;
    }

    async function searchAvailability(showFeedback = true) {
        const feedback = $('searchFeedback');
        const button = $('btnBuscarDisponibilidad');

        if (!currentSearchIsReady()) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-amber-300';
                feedback.innerText = 'Selecciona tus fechas de llegada y salida en el calendario para verificar disponibilidad.';
                feedback.classList.remove('hidden');
            }
            if (bookingCalendar) bookingCalendar.open();
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
            button.innerHTML = '<span class="material-symbols-outlined text-[20px] notranslate" translate="no">hourglass_top</span><span>Consultando...</span>';
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

            const ids = new Set((result.habitaciones_ids || []).map(Number));
            const count = updateVisibleRooms(ids);

            if (showFeedback && feedback) {
                feedback.className = 'text-sm font-bold text-emerald-300';
                feedback.innerText = `${count} habitación(es) disponible(s) para tus fechas. Selecciona tus cuartos abajo o continúa a la página de reserva.`;
                feedback.classList.remove('hidden');
            }
            scrollToResults();
        } catch (error) {
            if (feedback) {
                feedback.className = 'text-sm font-bold text-rose-300';
                feedback.innerText = error.message;
                feedback.classList.remove('hidden');
            }
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = '<span class="material-symbols-outlined text-[20px] notranslate" translate="no">search</span><span>Verificar disponibilidad</span>';
            }
            toggleGuestPopover(false);
        }
    }

    // ─────────────────────────────────────────────────────────
    // Carrito de habitaciones y navegación a reservar.php
    // ─────────────────────────────────────────────────────────
    function extractRoomDataFromButton(button) {
        const id = Number(button.dataset.roomId);
        const card = button.closest('[data-room-card]');
        const imgEl = card?.querySelector('img');
        return {
            id,
            num: button.dataset.roomNum || '',
            typeLabel: button.dataset.roomTypeLabel || '',
            name: button.dataset.roomName || `Habitación ${id}`,
            price: Number(button.dataset.roomPrice || 0),
            photo: button.dataset.roomPhoto || imgEl?.getAttribute('src') || ''
        };
    }

    function syncRoomCardButtons() {
        const selectedIds = new Set(roomCart.map(r => Number(r.id)));
        document.querySelectorAll('[data-room-card]').forEach(card => {
            const id = Number(card.getAttribute('data-room-card'));
            const isSelected = selectedIds.has(id);
            card.classList.toggle('is-selected-room', isSelected);

            const addBtn = card.querySelector('[data-room-add]');
            if (addBtn) {
                const labelSpan = addBtn.querySelector('[data-room-add-label]');
                const iconSpan = addBtn.querySelector('[data-room-add-icon]');
                addBtn.classList.toggle('bg-emerald-600', isSelected);
                addBtn.classList.toggle('border-emerald-400', isSelected);
                addBtn.classList.toggle('text-white', isSelected);
                if (labelSpan) labelSpan.textContent = isSelected ? 'Seleccionada' : 'Seleccionar';
                if (iconSpan) iconSpan.textContent = isSelected ? 'check_circle' : 'add_shopping_cart';
            }
        });

        const badge = $('navCartBadge');
        if (badge) {
            if (roomCart.length > 0) {
                badge.textContent = String(roomCart.length);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        const navLink = $('navReserveLink');
        if (navLink) navLink.setAttribute('href', buildReservationUrl());
        const heroBtn = $('heroGoReserveBtn');
        if (heroBtn) heroBtn.setAttribute('href', buildReservationUrl());
    }

    function renderRoomCart() {
        const cart = $('roomCart');
        const summary = $('roomCartSummary');
        syncRoomCardButtons();
        if (!cart || !summary) return;

        if (roomCart.length === 0) {
            cart.classList.add('hidden');
            return;
        }

        const totalPerNight = roomCart.reduce((s, r) => s + Number(r.price || 0), 0);
        let nights = 1;
        if (currentSearchIsReady()) {
            const d1 = new Date(`${searchState.checkin}T00:00:00`);
            const d2 = new Date(`${searchState.checkout}T00:00:00`);
            nights = Math.max(1, Math.round((d2 - d1) / 86400000));
        }

        const nightsText = currentSearchIsReady()
            ? ` · ${nights} noche${nights > 1 ? 's' : ''} (${formatCurrency(totalPerNight * nights)} + IVA)`
            : ` · ${formatCurrency(totalPerNight)} / noche`;

        summary.textContent = `${roomCart.length} cuarto${roomCart.length > 1 ? 's' : ''}${nightsText}`;
        cart.classList.remove('hidden');
    }

    function toggleRoomInCart(button) {
        const roomData = extractRoomDataFromButton(button);
        if (!roomData.id) return;
        const idx = roomCart.findIndex(r => r.id === roomData.id);
        if (idx >= 0) {
            roomCart.splice(idx, 1);
        } else {
            roomCart.push(roomData);
        }
        saveDraftToStorage();
        renderRoomCart();
    }

    function bookRoomImmediately(button) {
        const roomData = extractRoomDataFromButton(button);
        if (roomData.id && !roomCart.some(r => r.id === roomData.id)) {
            roomCart.push(roomData);
        }
        saveDraftToStorage();
        navigateToReservationPage();
    }

    // ─────────────────────────────────────────────────────────
    // Sesión y Centro de ayuda
    // ─────────────────────────────────────────────────────────
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
                confirmButtonColor: '#0b4f32',
                cancelButtonColor: '#64748b'
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
        if (shouldOpen) $('closeSystemHelp')?.focus();
    }

    document.addEventListener('click', event => {
        const roomAdd = event.target.closest('[data-room-add]');
        if (roomAdd) {
            toggleRoomInCart(roomAdd);
            return;
        }

        const roomBookNow = event.target.closest('[data-room-book-now]');
        if (roomBookNow) {
            bookRoomImmediately(roomBookNow);
            return;
        }

        const cartCheckout = event.target.closest('#roomCartCheckout');
        if (cartCheckout) {
            navigateToReservationPage();
            return;
        }

        const cartClear = event.target.closest('#roomCartClear');
        if (cartClear) {
            roomCart.length = 0;
            saveDraftToStorage();
            renderRoomCart();
            return;
        }

        if (!event.target.closest('#guestPopover') && !event.target.closest('#guestTrigger')) {
            toggleGuestPopover(false);
        }
        if (event.target.id === 'activityModal') {
            const modal = $('activityModal');
            if (modal?.open) modal.close();
        }
        if (!event.target.closest('.system-help')) {
            toggleSystemHelp(false);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') toggleSystemHelp(false);
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadDraftFromStorage();
        initializeCalendar();
        updateGuestSummary();
        attachLogoutFlow();
        updateVisibleRooms(null);
        renderRoomCart();

        if ($('systemHelpButton')) $('systemHelpButton').addEventListener('click', () => toggleSystemHelp());
        if ($('closeSystemHelp')) $('closeSystemHelp').addEventListener('click', () => toggleSystemHelp(false));
        if ($('guestTrigger')) $('guestTrigger').addEventListener('click', () => toggleGuestPopover());
        if ($('closeGuestPopover')) $('closeGuestPopover').addEventListener('click', () => toggleGuestPopover(false));
        if ($('openActivityModal')) {
            $('openActivityModal').addEventListener('click', () => {
                const modal = $('activityModal');
                if (modal && !modal.open) {
                    modal.showModal();
                    $('activityModalTitle')?.focus();
                }
            });
        }
        if ($('closeActivityModal')) {
            $('closeActivityModal').addEventListener('click', () => {
                const modal = $('activityModal');
                if (modal?.open) modal.close();
            });
        }
        if ($('adultsMinus')) $('adultsMinus').addEventListener('click', () => changeGuest('adults', -1));
        if ($('adultsPlus')) $('adultsPlus').addEventListener('click', () => changeGuest('adults', 1));
        if ($('childrenMinus')) $('childrenMinus').addEventListener('click', () => changeGuest('children', -1));
        if ($('childrenPlus')) $('childrenPlus').addEventListener('click', () => changeGuest('children', 1));
        if ($('btnBuscarDisponibilidad')) $('btnBuscarDisponibilidad').addEventListener('click', () => searchAvailability(true));
    });

    // ─────────────────────────────────────────────────────────
    // EXPERIENCIAS — handlers
    // ─────────────────────────────────────────────────────────
    (function setupExperiences() {
        const form = $('activityForm');
        if (!form) return;

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
            } catch (_) {
                experienciaActual = null;
            }
            return experienciaActual;
        }

        function aplicarSeleccionEnModal() {
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
            const modal = $('activityModal');
            if (modal && !modal.open) {
                modal.showModal();
                $('activityModalTitle')?.focus();
            }
        }

        document.addEventListener('click', event => {
            const boton = event.target.closest('.experience-select-button');
            if (!boton || boton.disabled) return;
            cargarExperienciaDesdeBoton(boton);
            aplicarSeleccionEnModal();
        });

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
                const modal = $('activityModal');
                if (modal?.open) modal.close();
                await Swal.fire({ icon: 'success', title: 'Experiencia programada', text: result.mensaje || 'Enviada.', confirmButtonColor: '#0b4f32' });
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
})();
