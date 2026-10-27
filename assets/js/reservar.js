(() => {
    const {
        wompiPublicKey: WOMPI_PUBLIC_KEY,
        csrfToken: CSRF_TOKEN,
        recaptchaSiteKey: RECAPTCHA_SITE_KEY,
        isUserAuthenticated,
        roomCatalog = []
    } = window.PORTAL_CONFIG || {};

    const STORAGE_KEY = 'aurora_booking_draft_v1';
    const catalogMap = new Map(roomCatalog.map(r => [Number(r.id), r]));

    const state = {
        rooms: [],
        checkin: '',
        checkout: '',
        adults: 2,
        children: 0,
        porcentajePago: 100,
        paymentMethod: 'Transferencia',
        availableRoomIds: null,
        catalogFilter: 'all',
        montoTotal: 0,
        montoAPagar: 0
    };

    const $ = (id) => document.getElementById(id);

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function formatLongDate(isoDate) {
        if (!isoDate) return 'Sin seleccionar';
        const [y, m, d] = String(isoDate).split('-').map(Number);
        if (!y || !m || !d) return isoDate;
        return new Date(y, m - 1, d).toLocaleDateString('es-CO', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function hasValidDates() {
        return state.checkin !== '' && state.checkout !== '' && state.checkout > state.checkin;
    }

    function calculateNights() {
        if (!hasValidDates()) return 1;
        const d1 = new Date(`${state.checkin}T00:00:00`);
        const d2 = new Date(`${state.checkout}T00:00:00`);
        return Math.max(1, Math.round((d2 - d1) / 86400000));
    }

    function normalizeRoom(roomObj) {
        const id = Number(roomObj?.id || 0);
        const fromCatalog = catalogMap.get(id);
        if (fromCatalog) return { ...fromCatalog };
        return {
            id,
            num: String(roomObj?.num || id),
            typeLabel: String(roomObj?.typeLabel || 'Habitación'),
            typeKey: 'otra',
            name: String(roomObj?.name || `Habitación ${id}`),
            price: Number(roomObj?.price || 0),
            photo: String(roomObj?.photo || 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=85&w=1200'),
            features: ['Baño privado', 'Wi-Fi', 'Climatización'],
            description: 'Habitación confortable con todas las comodidades.'
        };
    }

    function saveDraftToStorage() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                rooms: state.rooms.map(r => ({
                    id: r.id,
                    num: r.num,
                    typeLabel: r.typeLabel,
                    name: r.name,
                    price: r.price,
                    photo: r.photo
                })),
                checkin: state.checkin,
                checkout: state.checkout,
                adults: state.adults,
                children: state.children,
                updatedAt: Date.now()
            }));
        } catch (_) {}
    }

    function loadInitialState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    if (Array.isArray(parsed.rooms)) {
                        parsed.rooms.forEach(r => {
                            const id = Number(r.id);
                            if (id > 0 && !state.rooms.some(item => item.id === id)) {
                                state.rooms.push(normalizeRoom(r));
                            }
                        });
                    }
                    const todayIso = new Date().toISOString().slice(0, 10);
                    if (typeof parsed.checkin === 'string' && parsed.checkin >= todayIso) {
                        state.checkin = parsed.checkin;
                    }
                    if (typeof parsed.checkout === 'string' && parsed.checkout > state.checkin) {
                        state.checkout = parsed.checkout;
                    }
                    if (Number.isInteger(parsed.adults) && parsed.adults >= 1) {
                        state.adults = Math.min(20, parsed.adults);
                    }
                    if (Number.isInteger(parsed.children) && parsed.children >= 0) {
                        state.children = Math.min(20, parsed.children);
                    }
                }
            }
        } catch (_) {}

        const params = new URLSearchParams(window.location.search);
        const roomsParam = params.get('rooms');
        if (roomsParam) {
            const ids = roomsParam.split(',').map(v => Number(v.trim())).filter(n => n > 0);
            if (ids.length > 0) {
                state.rooms = [];
                ids.forEach(id => {
                    if (!state.rooms.some(r => r.id === id)) {
                        state.rooms.push(normalizeRoom({ id }));
                    }
                });
            }
        }

        const todayIso = new Date().toISOString().slice(0, 10);
        const checkinParam = params.get('checkin');
        const checkoutParam = params.get('checkout');
        if (checkinParam && checkinParam >= todayIso) state.checkin = checkinParam;
        if (checkoutParam && checkoutParam > state.checkin) state.checkout = checkoutParam;

        const adultsParam = Number(params.get('adults'));
        const childrenParam = Number(params.get('children'));
        if (Number.isInteger(adultsParam) && adultsParam >= 1) state.adults = Math.min(20, adultsParam);
        if (Number.isInteger(childrenParam) && childrenParam >= 0) state.children = Math.min(20, childrenParam);

        saveDraftToStorage();
    }

    // ─────────────────────────────────────────────────────────
    // Calendario Inline y Verificación de Disponibilidad
    // ─────────────────────────────────────────────────────────
    let inlineCalendar = null;

    function initCalendar() {
        const input = $('inlineCalendarInput');
        const container = $('inlineCalendarContainer');
        if (!input || !container || typeof flatpickr === 'undefined') return;

        const defaultDates = hasValidDates() ? [state.checkin, state.checkout] : [];

        inlineCalendar = flatpickr(input, {
            inline: true,
            mode: 'range',
            minDate: 'today',
            dateFormat: 'Y-m-d',
            defaultDate: defaultDates,
            locale: flatpickr.l10ns.es,
            showMonths: window.matchMedia('(min-width: 768px)').matches ? 2 : 1,
            appendTo: container,
            onReady: (_, __, instance) => {
                instance.calendarContainer.classList.add('aurora-calendar');
            },
            onChange: (selectedDates, _dateStr, instance) => {
                state.checkin = selectedDates[0] ? instance.formatDate(selectedDates[0], 'Y-m-d') : '';
                state.checkout = selectedDates[1] ? instance.formatDate(selectedDates[1], 'Y-m-d') : '';
                saveDraftToStorage();
                renderAll();
                if (selectedDates.length === 2) {
                    checkAvailabilityForDates();
                } else {
                    state.availableRoomIds = null;
                    updateAvailabilityBadge('Selecciona la fecha de salida (check-out)', 'neutral');
                }
            }
        });
    }

    function updateAvailabilityBadge(text, status = 'neutral') {
        const badge = $('availabilityBadge');
        const badgeText = $('availabilityBadgeText');
        if (!badge || !badgeText) return;
        badgeText.textContent = text;
        if (status === 'ok') {
            badge.className = 'inline-flex items-center gap-1.5 rounded-full border border-emerald-400/40 bg-emerald-950/70 px-3.5 py-1.5 text-xs font-bold text-emerald-200';
        } else if (status === 'warn') {
            badge.className = 'inline-flex items-center gap-1.5 rounded-full border border-rose-400/40 bg-rose-950/70 px-3.5 py-1.5 text-xs font-bold text-rose-200';
        } else {
            badge.className = 'inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white/85';
        }
    }

    async function checkAvailabilityForDates() {
        if (!hasValidDates()) return;
        updateAvailabilityBadge('Verificando disponibilidad...', 'neutral');

        const fd = new FormData();
        fd.append('accion', 'buscar_disponibilidad');
        fd.append('checkin', state.checkin);
        fd.append('checkout', state.checkout);
        fd.append('adultos', String(state.adults));
        fd.append('ninos', String(state.children));

        try {
            const res = await fetch('controladores/portal_huesped.php', {
                method: 'POST',
                body: fd,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const text = (await res.text()).replace(/^\uFEFF/, '').trim();
            const data = JSON.parse(text);
            if (!res.ok || data.status !== 'exito') {
                throw new Error(data.mensaje || 'No se pudo verificar disponibilidad.');
            }
            state.availableRoomIds = new Set((data.habitaciones_ids || []).map(Number));

            const unavailableSelected = state.rooms.filter(r => !state.availableRoomIds.has(Number(r.id)));
            if (unavailableSelected.length > 0) {
                updateAvailabilityBadge(`${unavailableSelected.length} cuarto(s) seleccionado(s) ocupado(s) en esas fechas`, 'warn');
            } else {
                updateAvailabilityBadge(`${state.availableRoomIds.size} habitación(es) disponible(s) para tus fechas`, 'ok');
            }
            renderAll();
        } catch (err) {
            updateAvailabilityBadge(err.message || 'Error al verificar fechas', 'warn');
        }
    }

    // ─────────────────────────────────────────────────────────
    // Renderizado de Cuartos Seleccionados, Catálogo y Resumen
    // ─────────────────────────────────────────────────────────
    function renderDatesAndGuests() {
        const nights = calculateNights();
        if ($('displayCheckin')) $('displayCheckin').textContent = formatLongDate(state.checkin);
        if ($('displayCheckout')) $('displayCheckout').textContent = formatLongDate(state.checkout);
        if ($('displayNights')) $('displayNights').textContent = `${nights} noche${nights > 1 ? 's' : ''}`;

        if ($('resAdultsValue')) $('resAdultsValue').textContent = String(state.adults);
        if ($('resChildrenValue')) $('resChildrenValue').textContent = String(state.children);
        if ($('resAdultsMinus')) $('resAdultsMinus').disabled = state.adults <= 1;
        if ($('resChildrenMinus')) $('resChildrenMinus').disabled = state.children <= 0;

        if ($('summaryCheckin')) $('summaryCheckin').textContent = hasValidDates() ? formatLongDate(state.checkin) : 'Selecciona en el calendario';
        if ($('summaryCheckout')) $('summaryCheckout').textContent = hasValidDates() ? formatLongDate(state.checkout) : 'Selecciona en el calendario';
        if ($('summaryNights')) $('summaryNights').textContent = `${nights} noche${nights > 1 ? 's' : ''}`;
        if ($('summaryGuests')) $('summaryGuests').textContent = `${state.adults} adulto${state.adults > 1 ? 's' : ''}, ${state.children} niño${state.children === 1 ? '' : 's'}`;
    }

    function renderSelectedRooms() {
        const list = $('selectedRoomsList');
        const empty = $('emptySelectedRooms');
        if (!list || !empty) return;

        if (state.rooms.length === 0) {
            list.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');
        const nights = calculateNights();

        list.innerHTML = state.rooms.map(room => {
            const unitPrice = Number(room.price || 0);
            const roomSubtotal = unitPrice * nights;
            const isAvailable = state.availableRoomIds === null || state.availableRoomIds.has(Number(room.id));
            const statusPill = isAvailable
                ? `<span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 border border-emerald-400/35 px-2.5 py-0.5 text-[11px] font-bold text-emerald-200">
                     <span class="material-symbols-outlined text-[14px] notranslate" translate="no">check_circle</span> Disponible
                   </span>`
                : `<span class="inline-flex items-center gap-1 rounded-full bg-rose-500/25 border border-rose-400/40 px-2.5 py-0.5 text-[11px] font-bold text-rose-200">
                     <span class="material-symbols-outlined text-[14px] notranslate" translate="no">error</span> No disponible en estas fechas
                   </span>`;

            const featuresHtml = (room.features || []).slice(0, 4).map(f =>
                `<span class="rounded-full bg-white/10 border border-white/15 px-2.5 py-0.5 text-[11px] font-semibold text-white/85">${escapeHtml(f)}</span>`
            ).join('');

            return `
                <article class="flex flex-col sm:flex-row items-stretch justify-between gap-4 rounded-2xl border ${isAvailable ? 'border-white/15 bg-white/8' : 'border-rose-400/50 bg-rose-950/30'} p-4 transition">
                    <div class="flex flex-col sm:flex-row gap-4 min-w-0 flex-1">
                        <img src="${escapeHtml(room.photo)}" alt="${escapeHtml(room.name)}" class="h-36 sm:h-28 sm:w-40 w-full rounded-xl object-cover shrink-0">
                        <div class="min-w-0 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[11px] font-black uppercase tracking-[0.14em] text-[#f0d39a]">${escapeHtml(room.typeLabel)}</span>
                                    ${statusPill}
                                </div>
                                <h3 class="font-display text-2xl font-bold text-white mt-0.5">${escapeHtml(room.name)}</h3>
                                <div class="mt-2 flex flex-wrap gap-1.5">${featuresHtml}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex sm:flex-col items-center sm:items-end justify-between border-t sm:border-t-0 sm:border-l border-white/10 pt-3 sm:pt-0 sm:pl-4 shrink-0">
                        <div class="text-left sm:text-right">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-white/60">Precio por unidad</p>
                            <p class="text-lg font-black text-[#f0d39a]">${formatCurrency(unitPrice)} <span class="text-xs font-normal text-white/70">/ noche</span></p>
                            <p class="text-xs font-bold text-emerald-300 mt-0.5">
                                ${nights} noche${nights > 1 ? 's' : ''}: ${formatCurrency(roomSubtotal)}
                            </p>
                        </div>

                        <button
                            type="button"
                            data-remove-room="${room.id}"
                            class="inline-flex items-center gap-1 rounded-xl border border-rose-300/35 bg-rose-500/15 px-3 py-1.5 text-xs font-bold text-rose-200 transition hover:bg-rose-500/30 mt-2"
                        >
                            <span class="material-symbols-outlined text-[16px] notranslate" translate="no">delete</span>
                            <span>Quitar</span>
                        </button>
                    </div>
                </article>
            `;
        }).join('');
    }

    function renderAvailableCatalog() {
        const grid = $('availableCatalogGrid');
        if (!grid) return;

        const selectedIds = new Set(state.rooms.map(r => Number(r.id)));
        const filtered = roomCatalog.filter(room => {
            if (state.catalogFilter !== 'all' && room.typeKey !== state.catalogFilter) {
                return false;
            }
            if (state.availableRoomIds !== null && !state.availableRoomIds.has(Number(room.id))) {
                return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div class="sm:col-span-2 rounded-2xl border border-white/15 bg-white/5 p-6 text-center text-sm text-white/75">
                    No hay más habitaciones disponibles con ese filtro para las fechas seleccionadas.
                </div>
            `;
            return;
        }

        grid.innerHTML = filtered.map(room => {
            const isSelected = selectedIds.has(Number(room.id));
            return `
                <div class="flex items-center justify-between gap-3 rounded-2xl border ${isSelected ? 'border-emerald-400/50 bg-emerald-950/35' : 'border-white/15 bg-white/6'} p-3.5 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="${escapeHtml(room.photo)}" alt="${escapeHtml(room.name)}" class="h-16 w-20 rounded-xl object-cover shrink-0">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#f0d39a]">${escapeHtml(room.typeLabel)}</p>
                            <p class="truncate text-sm font-extrabold text-white">${escapeHtml(room.name)}</p>
                            <p class="text-xs font-black text-emerald-300 mt-0.5">${formatCurrency(room.price)} / noche</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        data-toggle-catalog-room="${room.id}"
                        class="shrink-0 rounded-xl px-3.5 py-2 text-xs font-black uppercase tracking-[0.1em] transition ${isSelected ? 'bg-emerald-600 text-white' : 'bg-white text-slate-900 hover:bg-[#f0d39a]'}"
                    >
                        ${isSelected ? 'Agregada' : '+ Agregar'}
                    </button>
                </div>
            `;
        }).join('');
    }

    function renderFinancialSummary() {
        const nights = calculateNights();
        const breakdownEl = $('summaryRoomsBreakdown');

        if (breakdownEl) {
            if (state.rooms.length === 0) {
                breakdownEl.innerHTML = '<p class="rounded-xl bg-white/5 px-3 py-2.5 text-xs text-white/60">Selecciona al menos un cuarto para calcular el total.</p>';
            } else {
                breakdownEl.innerHTML = state.rooms.map(room => {
                    const sub = Number(room.price || 0) * nights;
                    return `
                        <div class="flex items-start justify-between gap-2 rounded-xl bg-white/5 px-3 py-2">
                            <div class="min-w-0">
                                <p class="font-bold text-white truncate">${escapeHtml(room.name)}</p>
                                <p class="text-[11px] text-white/65">${formatCurrency(room.price)}/noche × ${nights} noche${nights > 1 ? 's' : ''}</p>
                            </div>
                            <span class="font-extrabold text-[#f0d39a] shrink-0">${formatCurrency(sub)}</span>
                        </div>
                    `;
                }).join('');
            }
        }

        const totalPerNight = state.rooms.reduce((sum, r) => sum + Number(r.price || 0), 0);
        const subtotal = totalPerNight * nights;
        const iva = Math.round(subtotal * 0.19);
        const total = subtotal + iva;
        const payNow = Math.round((total * state.porcentajePago) / 100);

        state.montoTotal = total;
        state.montoAPagar = payNow;

        if ($('summaryPerNight')) $('summaryPerNight').textContent = formatCurrency(totalPerNight);
        if ($('summarySubtotal')) $('summarySubtotal').textContent = formatCurrency(subtotal);
        if ($('summaryIva')) $('summaryIva').textContent = formatCurrency(iva);
        if ($('summaryTotal')) $('summaryTotal').textContent = formatCurrency(total);
        if ($('summaryPayNow')) $('summaryPayNow').textContent = formatCurrency(payNow);

        if ($('summaryPayNowHint')) {
            if (state.paymentMethod === 'Recepción') {
                $('summaryPayNowHint').textContent = 'Se registrará tu reserva para pago en recepción';
            } else if (state.porcentajePago === 50) {
                $('summaryPayNowHint').textContent = 'Abono inicial del 50% (resto en recepción)';
            } else {
                $('summaryPayNowHint').textContent = '100% del total con impuestos incluidos';
            }
        }
    }

    function renderAll() {
        renderDatesAndGuests();
        renderSelectedRooms();
        renderAvailableCatalog();
        renderFinancialSummary();
    }

    function showFeedback(message, isError = true) {
        const box = $('checkoutFeedback');
        if (!box) return;
        box.textContent = message;
        box.className = isError
            ? 'mt-4 block rounded-xl border border-rose-400/40 bg-rose-950/70 px-4 py-3 text-xs font-bold text-rose-200'
            : 'mt-4 block rounded-xl border border-emerald-400/40 bg-emerald-950/70 px-4 py-3 text-xs font-bold text-emerald-200';
    }

    function hideFeedback() {
        const box = $('checkoutFeedback');
        if (box) box.classList.add('hidden');
    }

    // ─────────────────────────────────────────────────────────
    // Procesamiento de la Reserva y Cobro
    // ─────────────────────────────────────────────────────────
    async function submitReservation() {
        hideFeedback();

        if (!hasValidDates()) {
            showFeedback('Paso 1 pendiente: Selecciona tus fechas de llegada (check-in) y salida (check-out) en el calendario.');
            $('paso-calendario')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (state.rooms.length === 0) {
            showFeedback('Paso 2 pendiente: Selecciona al menos una habitación para reservar.');
            $('paso-cuartos')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (state.availableRoomIds !== null) {
            const occupied = state.rooms.filter(r => !state.availableRoomIds.has(Number(r.id)));
            if (occupied.length > 0) {
                showFeedback(`La habitación "${occupied[0].name}" no está disponible en las fechas elegidas. Quítala o cambia las fechas.`);
                $('paso-cuartos')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }

        if (!isUserAuthenticated) {
            saveDraftToStorage();
            const res = await Swal.fire({
                icon: 'info',
                title: 'Inicia sesión para confirmar',
                text: 'Tus cuartos y fechas seleccionados ya están guardados. Inicia sesión o crea tu cuenta para completar la reserva.',
                showCancelButton: true,
                confirmButtonText: 'Iniciar sesión',
                cancelButtonText: 'Crear cuenta',
                confirmButtonColor: '#0b4f32',
                cancelButtonColor: '#c89b4b'
            });
            if (res.isConfirmed) {
                window.location.href = 'interfaz/loggins/index_usu.php';
            } else if (res.dismiss === Swal.DismissReason.cancel) {
                window.location.href = 'interfaz/loggins/index_usu.php?vista=registro';
            }
            return;
        }

        const btn = $('confirmReservationBtn');
        const btnLabel = $('confirmReservationBtnLabel');

        try {
            let transaction = null;
            if (state.paymentMethod !== 'Recepción') {
                if (typeof WidgetCheckout === 'undefined') {
                    throw new Error('No se pudo cargar la pasarela de pago Wompi. Intenta de nuevo o selecciona Pago en Recepción.');
                }
                if (btn) btn.disabled = true;
                if (btnLabel) btnLabel.textContent = 'Abriendo Wompi...';

                const reference = `AURORA-${Date.now()}-${state.rooms[0].id}`;
                const widgetCheckout = new WidgetCheckout({
                    currency: 'COP',
                    amountInCents: Math.round(state.montoAPagar * 100),
                    reference,
                    publicKey: WOMPI_PUBLIC_KEY
                });

                const paymentResult = await new Promise((resolve, reject) => {
                    const timeoutId = window.setTimeout(() => reject(new Error('Tiempo de espera agotado al abrir Wompi.')), 15000);
                    widgetCheckout.open(result => {
                        window.clearTimeout(timeoutId);
                        if (result?.transaction?.id) resolve(result);
                        else reject(new Error('El pago en línea fue cancelado.'));
                    });
                });

                if (btnLabel) btnLabel.textContent = 'Validando pago...';
                transaction = paymentResult.transaction;
            } else {
                if (btn) btn.disabled = true;
                if (btnLabel) btnLabel.textContent = 'Guardando reserva...';
            }

            const fd = new FormData();
            state.rooms.forEach(r => fd.append('habitaciones_ids[]', String(r.id)));
            fd.append('id_habitacion', String(state.rooms[0].id));
            fd.append('fecha_in', state.checkin);
            fd.append('fecha_out', state.checkout);
            fd.append('cant_adultos', String(state.adults));
            fd.append('cant_ninos', String(state.children));
            fd.append('notas_reserva', ($('reservationNotes')?.value || '').trim());
            fd.append('metodo_pago', transaction ? 'Wompi' : state.paymentMethod);
            fd.append('porcentaje_pago', String(state.porcentajePago));
            fd.append('csrf_token', CSRF_TOKEN);

            if (RECAPTCHA_SITE_KEY) {
                const captchaToken = window.grecaptcha?.getResponse();
                if (!captchaToken) {
                    throw new Error('Por favor completa la verificación reCAPTCHA para continuar.');
                }
                fd.append('g-recaptcha-response', captchaToken);
            }

            if (transaction) {
                fd.append('referencia_pago', transaction.reference || '');
                fd.append('wompi_transaction_id', transaction.id);
            }

            const response = await fetch('controladores/guardar_reserva.php', {
                method: 'POST',
                body: fd,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });

            const rawText = (await response.text()).replace(/^\uFEFF/, '').trim();
            let result;
            try {
                result = JSON.parse(rawText);
            } catch (_) {
                throw new Error('Respuesta inesperada del servidor al guardar la reserva.');
            }

            if (!response.ok || result.status !== 'exito') {
                throw new Error(result.mensaje || 'No fue posible registrar la reserva.');
            }

            state.rooms = [];
            saveDraftToStorage();

            await Swal.fire({
                icon: 'success',
                title: '¡Reserva Confirmada!',
                text: result.mensaje || 'Tu estancia ha sido registrada exitosamente.',
                confirmButtonText: 'Ver mis reservas',
                confirmButtonColor: '#0b4f32'
            });

            window.location.href = 'interfaz_usu.php#historial';
        } catch (err) {
            if (RECAPTCHA_SITE_KEY && window.grecaptcha?.reset) {
                window.grecaptcha.reset();
            }
            showFeedback(err?.message || 'No se pudo completar la reserva.', true);
        } finally {
            if (btn) btn.disabled = false;
            if (btnLabel) btnLabel.textContent = 'Confirmar y Reservar';
        }
    }

    // ─────────────────────────────────────────────────────────
    // Event Listeners
    // ─────────────────────────────────────────────────────────
    document.addEventListener('click', event => {
        const removeBtn = event.target.closest('[data-remove-room]');
        if (removeBtn) {
            const id = Number(removeBtn.dataset.removeRoom);
            state.rooms = state.rooms.filter(r => Number(r.id) !== id);
            saveDraftToStorage();
            renderAll();
            return;
        }

        const toggleCatalogBtn = event.target.closest('[data-toggle-catalog-room]');
        if (toggleCatalogBtn) {
            const id = Number(toggleCatalogBtn.dataset.toggleCatalogRoom);
            const idx = state.rooms.findIndex(r => Number(r.id) === id);
            if (idx >= 0) {
                state.rooms.splice(idx, 1);
            } else {
                state.rooms.push(normalizeRoom({ id }));
            }
            saveDraftToStorage();
            renderAll();
            return;
        }

        const filterBtn = event.target.closest('[data-catalog-filter]');
        if (filterBtn) {
            state.catalogFilter = filterBtn.dataset.catalogFilter || 'all';
            document.querySelectorAll('[data-catalog-filter]').forEach(b => {
                b.classList.remove('bg-white/15', 'border-white/25', 'text-white');
                b.classList.add('bg-transparent', 'border-white/15', 'text-white/70');
            });
            filterBtn.classList.remove('bg-transparent', 'border-white/15', 'text-white/70');
            filterBtn.classList.add('bg-white/15', 'border-white/25', 'text-white');
            renderAvailableCatalog();
            return;
        }

        const pagoTipoBtn = event.target.closest('[data-pago-tipo]');
        if (pagoTipoBtn) {
            state.porcentajePago = Number(pagoTipoBtn.dataset.pagoTipo) || 100;
            document.querySelectorAll('[data-pago-tipo]').forEach(b => b.classList.remove('is-active'));
            pagoTipoBtn.classList.add('is-active');
            renderFinancialSummary();
            return;
        }

        const paymentBtn = event.target.closest('[data-payment]');
        if (paymentBtn) {
            state.paymentMethod = paymentBtn.dataset.payment || 'Transferencia';
            document.querySelectorAll('[data-payment]').forEach(b => b.classList.remove('is-active'));
            paymentBtn.classList.add('is-active');
            renderFinancialSummary();
            return;
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadInitialState();
        initCalendar();
        renderAll();

        if (hasValidDates()) {
            checkAvailabilityForDates();
        }

        $('resAdultsMinus')?.addEventListener('click', () => {
            state.adults = Math.max(1, state.adults - 1);
            saveDraftToStorage();
            renderDatesAndGuests();
        });
        $('resAdultsPlus')?.addEventListener('click', () => {
            state.adults = Math.min(20, state.adults + 1);
            saveDraftToStorage();
            renderDatesAndGuests();
        });
        $('resChildrenMinus')?.addEventListener('click', () => {
            state.children = Math.max(0, state.children - 1);
            saveDraftToStorage();
            renderDatesAndGuests();
        });
        $('resChildrenPlus')?.addEventListener('click', () => {
            state.children = Math.min(20, state.children + 1);
            saveDraftToStorage();
            renderDatesAndGuests();
        });

        $('clearAllRoomsBtn')?.addEventListener('click', () => {
            state.rooms = [];
            saveDraftToStorage();
            renderAll();
        });

        $('confirmReservationBtn')?.addEventListener('click', submitReservation);

        $('logoutButton')?.addEventListener('click', async () => {
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
    });
})();
