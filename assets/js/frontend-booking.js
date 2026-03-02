jQuery(document).ready(function ($) {
    const container = $('#mbs-booking-app');
    if (!container.length) return;

    // State Management
    let state = {
        currentStep: 1,
        services: [],
        selectedService: null,
        selectedDate: null,
        selectedSlot: null,
        slots: [],
        loading: false,
        timeFormat: '12h',
        availabilityConfig: {
            disabledDays: [],
            minDate: null,
            timeZone: 'UTC',
            businessName: 'Professional Services',
            businessLogo: ''
        }
    };

    let fpInstance = null;

    // Prevent concurrent checkout updates
    let checkoutUpdating = false;

    // DOM Elements
    const elements = {
        mainContainer: $('#mbs-main-container'),
        sidebar: $('#mbs-sidebar'),
        sidebarTitle: $('#mbs-sidebar-title'),
        sidebarDesc: $('#mbs-sidebar-description'),
        sidebarLogo: $('#mbs-sidebar-logo'),
        sidebarLogoWrap: $('#mbs-sidebar-logo-wrap'),
        sidebarDuration: $('#mbs-sidebar-duration-text'),
        sidebarDurationWrap: $('#mbs-sidebar-duration-wrap'),

        mainContent: $('#mbs-main-content'),
        stepPackages: $('#step-packages'),
        stepDatetime: $('#step-datetime'),
        stepDetails: $('#step-details'),
        stepSuccess: $('#step-success'),

        packagesContainer: $('#mbs-packages-container'),
        slotsContainer: $('#mbs-slots-container'),
        slotCount: $('#mbs-slot-count'),
        selectedDayLabel: $('#mbs-selected-day-label'),

        bookingForm: $('#mbs-booking-form'),
        datepicker: $('#mbs-datepicker'),

        sidebarNav: $('#mbs-sidebar-nav'),
        btnBack: $('#btn-back'),
        currentStepSpan: $('#mbs-current-step')
    };

    // 1. Initial Load: Fetch Services & Config
    init();

    async function init() {
        setLoading(true);
        try {
            // Fetch Config
            const configResp = await fetch(`${bookingAppPublic.restUrl}/availability-config`, {
                headers: { 'X-WP-Nonce': bookingAppPublic.nonce }
            });
            state.availabilityConfig = await configResp.json();
            updateSidebarBase();

            // Fetch Services
            const servicesResp = await fetch(`${bookingAppPublic.restUrl}/services`, {
                headers: { 'X-WP-Nonce': bookingAppPublic.nonce }
            });
            state.services = await servicesResp.json();
            renderServices();

            goToStep(1);
        } catch (e) {
            console.error('Initialization failed', e);
        } finally {
            setLoading(false);
        }
    }

    function updateSidebarBase() {
        const cfg = state.availabilityConfig;
        $('#mbs-sidebar-business-name').text(cfg.businessName || 'Consultation');
        $('#mbs-sidebar-timezone').text(cfg.timeZone || 'Africa/Casablanca');
        if (cfg.businessLogo) {
            elements.sidebarLogo.attr('src', cfg.businessLogo);
            elements.sidebarLogoWrap.removeClass('hidden');
        }
    }

    function renderServices() {
        if (state.services.length === 0) {
            elements.packagesContainer.html('<p class="text-center text-gray-500 py-10">No services available.</p>');
            return;
        }

        elements.packagesContainer.html(state.services.map((service, idx) => {
            const isPopular = idx === 1; // Arbitrary popular tag for design
            const priceHtml = service.price > 0 ? `<div class="text-lg font-bold text-black">$${parseFloat(service.price).toFixed(2)}</div>` : '<div class="text-sm font-semibold text-green-600">Free</div>';
            return `
                <div class="mbs-package-card group p-5 rounded-xl cursor-pointer flex items-center justify-between transition-all ${isPopular ? 'popular ring-2 ring-black' : 'hover:bg-gray-50'}" data-id="${service.id}">
                    <div class="flex-grow">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-lg font-bold text-gray-900">${service.name}</h4>
                            ${isPopular ? '<span class="mbs-package-badge bg-black text-white px-2 py-0.5 rounded text-[9px]">Popular</span>' : ''}
                        </div>
                        <p class="text-sm text-gray-500 line-clamp-1">${service.description || 'Professional consultation.'}</p>
                    </div>
                    <div class="flex items-center gap-6 text-right">
                        <div class="flex flex-col items-end">
                            ${priceHtml}
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">${service.duration} min</div>
                        </div>
                        <span class="material-icons-outlined text-gray-400 group-hover:text-black transition-colors">chevron_right</span>
                    </div>
                </div>
            `;
        }).join(''));

        // Handle selection
        $('.mbs-package-card').on('click', function () {
            const id = $(this).data('id');
            state.selectedService = state.services.find(s => s.id == id);
            goToStep(2);
        });
    }

    // 2. Step 2 Logic: Flatpickr
    async function initDatePicker() {
        if (fpInstance) fpInstance.destroy();

        fpInstance = flatpickr(elements.datepicker[0], {
            inline: true,
            minDate: "today",
            monthSelectorType: "static",
            disable: [
                (date) => state.availabilityConfig.disabledDays.includes(date.getDay())
            ],
            onMonthChange: updateCalendarHeader,
            onYearChange: updateCalendarHeader,
            onReady: (d, s, instance) => {
                updateCalendarHeader(d, s, instance);
                // Move nav buttons if needed or style them
            },
            onChange: function (selectedDates) {
                if (selectedDates.length === 0) {
                    state.selectedDate = null;
                    elements.slotsContainer.html('<p class="text-gray-400 text-sm py-20 italic text-center text-balance px-4">Select a date from the calendar to see available slots.</p>');
                    elements.selectedDayLabel.text('Select a date');
                    return;
                }

                const date = selectedDates[0];
                state.selectedDate = date.toISOString().split('T')[0];
                elements.selectedDayLabel.text(date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }));
                fetchSlots();
            }
        });
    }

    function updateCalendarHeader(selectedDates, dateStr, instance) {
        const month = instance.l10n.months.longhand[instance.currentMonth];
        const year = instance.currentYearElement.value;
        $('#fp-month').text(month);
        $('#fp-year').text(year);
    }

    async function fetchSlots() {
        elements.slotsContainer.html('<div class="py-10 text-center"><div class="inline-block animate-spin h-5 w-5 border-2 border-black border-t-transparent rounded-full font-bold"></div></div>');

        try {
            const response = await fetch(`${bookingAppPublic.restUrl}/slots?service_id=${state.selectedService.id}&date=${state.selectedDate}`, {
                headers: { 'X-WP-Nonce': bookingAppPublic.nonce }
            });
            state.slots = await response.json();
            state.slots.sort((a, b) => new Date(a.time) - new Date(b.time));
            renderSlots();
        } catch (e) {
            console.error('Failed to fetch slots', e);
        }
    }

    function renderSlots() {
        elements.slotCount.text(state.slots.length);
        if (state.slots.length === 0) {
            elements.slotsContainer.html('<p class="text-gray-400 text-sm py-20 italic text-center">No slots available for this day.</p>');
            return;
        }

        elements.slotsContainer.html(state.slots.map(slot => {
            const timeStr = slot.time;
            const isAvailable = slot.available;
            const startLabel = slot.display_time || new Date(timeStr).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

            return `
                <button type="button" 
                    class="mbs-slot-pill w-full py-2.5 px-4 rounded-lg border border-gray-200 text-sm font-semibold flex items-center justify-between transition-all ${isAvailable ? 'bg-white text-gray-900 border-gray-200 hover:border-black cursor-pointer mbs-slot-btn' : 'bg-gray-50 text-gray-300 cursor-not-allowed opacity-40'}"
                    data-slot="${timeStr}"
                    ${!isAvailable ? 'disabled' : ''}
                >
                    ${startLabel}
                    ${isAvailable ? '<div class="mbs-slot-indicator"></div>' : ''}
                </button>
            `;
        }).join(''));

        $('.mbs-slot-btn').on('click', function () {
            $('.mbs-slot-btn').removeClass('bg-black text-white border-black').addClass('bg-white text-gray-900 border-gray-200');
            $(this).addClass('bg-black text-white border-black').removeClass('bg-white text-gray-900 border-gray-200');
            state.selectedSlot = $(this).data('slot');

            // Auto advance or show next? Cal.com usually shows "Next" button in the pill or advances
            setTimeout(() => goToStep(3), 200);
        });
    }

    // 3. Navigation & Step Management
    function goToStep(step) {
        state.currentStep = step;

        // Hide all steps
        elements.stepPackages.addClass('hidden');
        elements.stepDatetime.addClass('hidden');
        elements.stepDetails.addClass('hidden');
        $('#step-payment').addClass('hidden');
        elements.stepSuccess.addClass('hidden');

        // Sidebar resets
        elements.sidebarTitle.text('Consultation');
        elements.sidebarDesc.parent().removeClass('hidden');
        elements.sidebarDurationWrap.addClass('hidden');

        if (step === 1) {
            elements.stepPackages.removeClass('hidden');
            elements.sidebarNav.addClass('hidden');
            elements.sidebarDesc.text('Select a consultation package that best suits your needs.');
        } else if (step === 2) {
            elements.stepDatetime.removeClass('hidden');
            elements.sidebarNav.removeClass('hidden');
            elements.sidebarTitle.text(state.selectedService.name);
            elements.sidebarDesc.text(state.selectedService.description || 'Pick a time that works for you.');
            elements.sidebarDuration.text(state.selectedService.duration + ' min');
            elements.sidebarDurationWrap.removeClass('hidden');
            initDatePicker();
        } else if (step === 3) {
            elements.stepDetails.removeClass('hidden');
            elements.sidebarNav.addClass('hidden'); // Step 3 has its own back button
            elements.sidebarTitle.text('Details');
            elements.sidebarDesc.parent().addClass('hidden'); // Content is high, hide desc
            elements.sidebarDuration.text(state.selectedService.duration + ' min');
            elements.sidebarDurationWrap.removeClass('hidden');

            // Set summary in sidebar content if needed
            const slotDate = new Date(state.selectedSlot);
            const summary = slotDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) + ', ' + slotDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            elements.sidebarTitle.text(state.selectedService.name);
            elements.sidebarDesc.text(summary).parent().removeClass('hidden');
        } else if (step === 4) {
            // Payment Step or Success depending on context
            if (state.selectedService.price > 0 && !state.paymentCompleted) {
                $('#step-payment').removeClass('hidden');
                elements.sidebarNav.removeClass('hidden');
                elements.sidebarTitle.text('Payment');
                elements.sidebarDesc.text('Secure your booking').parent().removeClass('hidden');
            } else {
                elements.stepSuccess.removeClass('hidden');
                elements.sidebar.addClass('hidden'); // Success usually full width
                elements.mainContent.removeClass('md:w-2/3').addClass('w-full');
            }
        } else if (step === 5) {
            elements.stepSuccess.removeClass('hidden');
            elements.sidebar.addClass('hidden');
            elements.mainContent.removeClass('md:w-2/3').addClass('w-full');
        }

        elements.currentStepSpan.text(step);
        elements.btnBack.prop('disabled', step === 1);

        // Scroll to container top
        $('html, body').animate({ scrollTop: container.offset().top - 40 }, 300);
    }

    // Events
    elements.btnBack.on('click', () => {
        if (state.currentStep === 4 && state.selectedService.price > 0) {
            goToStep(3);
        } else {
            goToStep(state.currentStep - 1);
        }
    });
    $('#mbs-btn-back-s3').on('click', () => goToStep(2));
    $('#mbs-btn-back-s4').on('click', () => goToStep(3));

    elements.bookingForm.on('submit', async function (e) {
        e.preventDefault();
        setLoading(true);

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        // Persist customer name/email to state for later use in payment flow
        state.customer_name = data.customer_name || data.customer_full_name || state.customer_name || '';
        state.customer_email = data.customer_email || data.email || state.customer_email || '';
        state.customer_phone = data.customer_phone || state.customer_phone || '';
        state.customer_country = data.customer_country || state.customer_country || '';
        data.service_id = state.selectedService.id;
        data.booking_datetime_utc = state.selectedSlot;
        data.duration = state.selectedService.duration;

        // If service is free, confirm directly
        if (!state.selectedService.price || parseFloat(state.selectedService.price) <= 0) {
            data.status = 'confirmed';
            try {
                const response = await fetch(`${bookingAppPublic.restUrl}/bookings`, {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': bookingAppPublic.nonce,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    goToStep(4);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                console.error('Submission error', e);
            } finally {
                setLoading(false);
            }
            return;
        }

        // Service has price, go to payment step
        try {
            const response = await fetch(`${bookingAppPublic.restUrl}/bookings/prepare-payment`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': bookingAppPublic.nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                state.orderData = result;
                renderPaymentStep();
                goToStep(4);
            } else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            console.error('Payment preparation error', e);
        } finally {
            setLoading(false);
        }
    });

    // Helper: populate WooCommerce billing fields (hidden inputs and visible checkout inputs)
    function fillWooBillingFields($hf, $cf) {
        $hf = $hf || $('#mbs-hidden-wc-checkout');
        $cf = $cf || $('#mbs-payment-form');
        const $booking = $('#mbs-booking-form');
        const nameVal = $booking.find('[name="customer_name"]').val() || $booking.find('[name="customer_full_name"]').val() || state.customer_name || '';
        const emailVal = $booking.find('[name="customer_email"]').val() || state.customer_email || '';
        const phoneVal = $booking.find('[name="customer_phone"]').val() || state.customer_phone || '';
        const countryVal = $booking.find('[name="customer_country"]').val() || state.customer_country || '';
        const countryText = $booking.find('[name="customer_country"] option:selected').text() || countryVal;

        let first = '', last = '';
        if (nameVal && nameVal.trim()) {
            const parts = nameVal.trim().split(/\s+/);
            first = parts.shift();
            last = parts.join(' ');
        }

        const setField = function (name, value) {
            let $f = $hf.find && $hf.find(`[name="${name}"]`);
            if (!$f || !$f.length) {
                // If $hf is not present in DOM, create a hidden input inside body to ensure serialization works later
                $f = $('<input/>', {type: 'hidden', name: name}).appendTo($hf.length ? $hf : $('body'));
            }
            $f.val(value || '');

            if ($cf && $cf.length) {
                let $t = $cf.find(`[name="${name}"]`);
                if (!$t.length) $t = $('<input/>', {type: 'hidden', name: name}).appendTo($cf);
                $t.val(value || '');
            }

            const idSel = '#' + name.replace(/_/g, '-');
            const $id = $(idSel);
            if ($id.length) $id.val(value || '');
        };

        setField('billing_country', countryVal);
        setField('billing_address_1', countryText);
        setField('billing_city', countryText);
        setField('billing_postcode', '00000');
        setField('billing_first_name', first);
        setField('billing_last_name', last);
        setField('billing_email', emailVal);
        setField('billing_phone', phoneVal);

        // Before triggering WooCommerce update, ensure Stripe isn't already mounted and no update is in progress
        if (jQuery('.wc-stripe-upe-element iframe').length > 0) {
            console.log('Stripe already mounted. Skipping update_checkout.');
            return;
        }
        if (checkoutUpdating) {
            console.log('Checkout update already in progress. Skipping update_checkout.');
            return;
        }
        $(document.body).trigger('update_checkout');
    }

    // Prevent multiple simultaneous update_checkout cycles and log methods for verification
    $(document.body).on('update_checkout', function () {
        if (checkoutUpdating) return false;
        checkoutUpdating = true;
    });
    $(document.body).on('updated_checkout', function () {
        // Sanitize payment methods immediately when WooCommerce updates the checkout fragments
        sanitizePaymentMethods();
        checkoutUpdating = false;
        console.log('Payment methods HTML:', $('ul.wc_payment_methods').html());
        console.log('payment_method inputs:', $('input[name="payment_method"]'));
        console.log('payment_method values:', $('input[name="payment_method"]').map(function(){ return this.value; }).get());
    });

    // Helper: safe id generator
    function safeId(str) {
        if (!str) return '';
        return String(str).replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    // Sanitize and validate payment method inputs and labels to avoid invalid selectors
    function sanitizePaymentMethods() {
        const $methods = $('ul.wc_payment_methods.payment_methods.methods');
        if (!$methods.length) return;

        const seenIds = {};
        $methods.find('li.wc_payment_method').each(function (i, li) {
            const $li = $(li);
            // ensure li has payment_method_{id} class
            let liClasses = ($li.attr('class') || '').split(/\s+/);
            // Inspect inputs inside
            $li.find('input[name="payment_method"]').each(function (idx, input) {
                const $inp = $(input);
                let val = ($inp.val() || '').toString();
                const dataGateway = $inp.data('gateway') || $inp.attr('data-gateway') || '';
                const idAttr = $inp.attr('id') || '';

                // If value is empty or starts with a dot or contains only dots, fix it
                if (!val || /^\.+$/.test(val) || val.charAt(0) === '.') {
                    // Prefer data-gateway, then id, then generated index-based value
                    const fallback = dataGateway || idAttr || ('pm_' + i + '_' + idx);
                    const newVal = safeId(fallback) || ('pm_' + i + '_' + idx);
                    console.warn('sanitizePaymentMethods: fixing invalid payment_method value', val, '->', newVal);
                    $inp.val(newVal);
                    val = newVal;
                }

                // Ensure id exists and labels point to it
                let inputId = $inp.attr('id');
                if (!inputId) {
                    inputId = 'payment_method_' + val;
                    inputId = safeId(inputId) || ('pm_id_' + i + '_' + idx);
                    // Avoid duplicates
                    let uniqueId = inputId;
                    let c = 1;
                    while (seenIds[uniqueId]) {
                        uniqueId = inputId + '_' + (c++);
                    }
                    inputId = uniqueId;
                    $inp.attr('id', inputId);
                }
                seenIds[inputId] = true;

                // Fix label 'for'
                $li.find('label[for]').each(function (liIdx, lab) {
                    const $lab = $(lab);
                    const forAttr = $lab.attr('for');
                    if (!forAttr || forAttr === '' || forAttr === '.') {
                        $lab.attr('for', inputId);
                    }
                });

                // Ensure input has expected class used by WC
                if (!$inp.hasClass('input-radio')) $inp.addClass('input-radio');

                // If there's a label sibling without a for, try to set it
                const $possibleLabel = $inp.next('label');
                if ($possibleLabel.length && (!$possibleLabel.attr('for') || $possibleLabel.attr('for') === '')) {
                    $possibleLabel.attr('for', inputId);
                }

                // Ensure li has matching payment_method_{val} class
                const expectedClass = 'payment_method_' + safeId(val);
                if (!$li.hasClass(expectedClass)) {
                    $li.addClass(expectedClass);
                }
            });
        });
    }

    // MutationObserver fallback: sanitize when payment container changes
    (function () {
        const container = document.querySelector('#mbs-payment-methods');
        if (!container) return;
        const mo = new MutationObserver((mutations) => {
            mutations.forEach(m => {
                if (m.addedNodes && m.addedNodes.length) {
                    sanitizePaymentMethods();
                }
            });
        });
        mo.observe(container, { childList: true, subtree: true });
    })();

    // Note: Do NOT trigger init_checkout or wc-credit-card-form-init manually.
    // WooCommerce will handle initialization after update_checkout completes.

    function renderPaymentStep() {
        const d = state.orderData;

        const summaryHtml = `
            <div class="mbs-price-card bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-900">${state.selectedService.name}</h3>
                    <span class="text-2xl font-black text-black">${d.total_formatted}</span>
                </div>
                <div class="text-xs text-gray-500 flex items-center gap-2 mb-6">
                    <i class="far fa-calendar"></i>
                    ${state.selectedDate} @ ${state.selectedSlot}
                </div>
                
                <div class="space-y-3 pt-4 border-t border-gray-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-medium">${d.total_formatted}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2">
                        <span>Total to Pay</span>
                        <span>${d.total_formatted}</span>
                    </div>
                </div>
            </div>
        `;
        $('#mbs-payment-summary').html(summaryHtml);

        // Render Gateways
        // Use a DOM-safe id for classes/selectors (replace invalid selector chars) and keep original id in data attribute
        function safeId(id) {
            return String(id).replace(/[^a-zA-Z0-9_-]/g, '_');
        }

        let gatewaysHtml = d.gateways.map(g => {
            const sid = safeId(g.id);
            return `
            <li class="wc_payment_method payment_method_${sid} mbs-payment-method-wrap mb-3 text-left" id="payment_method_${sid}">
                <label class="mbs-payment-method cursor-pointer group block">
                    <input type="radio" name="payment_method" value="${sid}" data-gateway="${g.id}" class="hidden peer">
                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-white hover:border-black peer-checked:border-black peer-checked:bg-gray-50 transition-all">
                        <div class="flex-grow">
                            <span class="block text-sm font-bold text-gray-900">${g.title}</span>
                            ${g.description ? `<span class="block text-xs text-gray-500 mt-0.5">${g.description}</span>` : ''}
                        </div>
                    </div>
                </label>
                <div class="payment_box payment_method_${sid} mbs-gateway-fields hidden p-4 bg-gray-50 rounded-b-xl border-x border-b border-gray-200 -mt-2 mb-4" id="fields-${sid}">
                    ${g.fields_html || '<p class="text-xs text-gray-500 italic">No additional details required.</p>'}
                </div>
            </li>
        `}).join('');

        // Wrap in a div that gateways might expect (mimicking WooCommerce checkout)
        // Place the required WooCommerce payment wrapper and methods list so init_payment_methods has expected DOM
        $('#mbs-payment-methods').html('<div id="payment" class="woocommerce-checkout-payment"><h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider text-[10px] text-left">Select Payment Method</h4><ul class="wc_payment_methods payment_methods methods" style="list-style:none;padding:0;margin:0;">' + gatewaysHtml + '</ul></div>');

        $('#mbs-payment-form').addClass('woocommerce-checkout');

        // Handle Toggling of fields
        $('#mbs-payment-methods').off('change', 'input[name="payment_method"]').on('change', 'input[name="payment_method"]', function () {
            $('.mbs-gateway-fields').addClass('hidden');
            const selectedSafe = $(this).val();
            if (selectedSafe) {
                $(`#fields-${selectedSafe}`).removeClass('hidden');
            }

            // Ensure billing fields are populated on the visible checkout before WooCommerce re-reads the form
            fillWooBillingFields(null, $('#mbs-payment-form'));
        });

        // Auto-select first method
        const firstRadio = $('#mbs-payment-methods input[type="radio"]').first();
        if (firstRadio.length) {
            firstRadio.prop('checked', true).trigger('change');
        }

        // Ensure billing fields are populated on the visible checkout before WooCommerce re-reads the form
        fillWooBillingFields(null, $('#mbs-payment-form'));

        $('#mbs-pay-now-btn').off('click').on('click', function (e) {
            e.preventDefault();
            const $checked = $('#mbs-payment-methods input[name="payment_method"]:checked');
            const selectedSafe = $checked.val();
            const selectedMethod = $checked.data('gateway'); // original gateway id for submission
            if (!selectedSafe || !selectedMethod) {
                alert('Please select a payment method.');
                return;
            }

            // Build or reuse a hidden WooCommerce checkout form so gateway JS (Stripe/PayPal/etc.) can initialize and tokenize
            let $hiddenForm = $('#mbs-hidden-wc-checkout');
            if (!$hiddenForm.length) {
                $hiddenForm = $('<form/>', {
                    id: 'mbs-hidden-wc-checkout',
                    class: 'checkout woocommerce-checkout',
                    style: 'display:none;'
                }).appendTo('body');
            }

            // Ensure basic billing fields exist (gateway validate_fields may inspect them)
            const custName = $('#mbs-booking-form [name="customer_name"]').val() || state.customer_name || '';
            const custEmail = $('#mbs-booking-form [name="customer_email"]').val() || state.customer_email || '';

            function setOrCreateHidden(name, value) {
                let $f = $hiddenForm.find(`[name="${name}"]`);
                if (!$f.length) {
                    $f = $('<input/>', {type: 'hidden', name: name}).appendTo($hiddenForm);
                }
                $f.val(value || '');
            }

            setOrCreateHidden('billing_first_name', custName);
            setOrCreateHidden('billing_email', custEmail);
            setOrCreateHidden('billing_phone', $('#mbs-booking-form [name="customer_phone"]').val() || state.customer_phone || '');
            setOrCreateHidden('billing_country', $('#mbs-booking-form [name="customer_country"]').val() || state.customer_country || '');

            // Populate full billing set now (global helper will set hidden & visible fields)
            fillWooBillingFields($hiddenForm, $('#mbs-payment-form'));
            // Use original gateway id for the actual form value
            setOrCreateHidden('payment_method', selectedMethod);
            setOrCreateHidden('mbs_booking_id', d.booking_id);

            // Ensure WooCommerce checkout nonce and action are present so wc-checkout JS recognizes this as a checkout form
            if (window.wc_checkout_params && wc_checkout_params.checkout_nonce) {
                setOrCreateHidden('woocommerce-process-checkout-nonce', wc_checkout_params.checkout_nonce);
            }
            setOrCreateHidden('woocommerce-process-checkout', '1');

            // Also ensure the VISIBLE payment form contains the same hidden inputs (so wc-checkout JS recognizes them)
            const $checkoutForm = $('#mbs-payment-form');
            if ($checkoutForm.length) {
                const copyFields = ['billing_first_name','billing_email','billing_phone','billing_address_1','billing_city','billing_postcode','billing_country','payment_method','mbs_booking_id','woocommerce-process-checkout'];
                copyFields.forEach(function (name) {
                    const $src = $hiddenForm.find(`[name="${name}"]`);
                    if ($src.length) {
                        let $t = $checkoutForm.find(`[name="${name}"]`);
                        if (!$t.length) $t = $('<input/>', {type: 'hidden', name: name}).appendTo($checkoutForm);
                        $t.val($src.val());
                    }
                });
                // Ensure a place_order submit button exists for WC handlers that attach to it
                if (!$checkoutForm.find('#place_order').length) {
                    $('<button/>', {type: 'submit', id: 'place_order', name: 'woocommerce_checkout_place_order', style: 'display:none'}).appendTo($checkoutForm);
                }
            }

            // We will submit the visible payment form contents to WC AJAX; do NOT clone gateway DOM nodes (Stripe elements must mount to a single container).

            // Allow WooCommerce to re-read the checkout form; do NOT call init_checkout manually
            $(document.body).trigger('update_checkout');

            // Submit the form so WC checkout JS will intercept and perform AJAX checkout (wc-ajax=checkout)
            // Attach a one-time loading indicator
            setLoading(true);

            // Submit the visible payment form so WooCommerce checkout JS (and Stripe UPE) can intercept and handle tokenization.
            try {
                const $checkoutForm = $('#mbs-payment-form');
                if ($checkoutForm.length) {
                    // If Stripe UPE is present, ensure it has produced a payment method/token before submitting
                    var upeField = $checkoutForm.find('input[name="wc-stripe-payment-method-upe"]');
                    var tokenField = $checkoutForm.find('input[name="wc-stripe-payment-token"]');
                    if (upeField.length && !upeField.val() && (!tokenField.length || tokenField.val() === 'new' || tokenField.val() === '')) {
                        alert('Please complete the card details or select a saved payment method before continuing.');
                        setLoading(false);
                        return;
                    }
                    $checkoutForm.trigger('submit');
                } else {
                    // Fallback: if visible form missing, still try to submit hidden form pattern
                    var ajaxUrl = '';
                    if (window.wc_checkout_params && wc_checkout_params.wc_ajax_url) {
                        ajaxUrl = wc_checkout_params.wc_ajax_url.replace('%%endpoint%%', 'checkout');
                    }
                    if (!ajaxUrl) ajaxUrl = '/?wc-ajax=checkout';
                    var payload = $('#mbs-hidden-wc-checkout').serialize();
                    $.post(ajaxUrl, payload, function (resp) {
                        // handled by ajaxComplete listener
                    }, 'json').fail(function () {
                        alert('Payment submission failed. Please try again.');
                        setLoading(false);
                    });
                }
            } catch (err) {
                console.error('Checkout form programmatic submit failed', err);
                alert('Payment submission failed. Please try again.');
                setLoading(false);
            }
        });

        // Global AJAX complete listener to capture WooCommerce checkout JSON responses
        $(document).off('ajaxComplete.mbs_checkout').on('ajaxComplete.mbs_checkout', function (event, xhr, settings) {
            try {
                // Many WC implementations call /?wc-ajax=checkout; detect JSON response with result
                var resp = null;
                try { resp = xhr.responseJSON || JSON.parse(xhr.responseText || '{}'); } catch (e) { resp = null; }
                if (!resp || (!resp.result && !resp.redirect && !resp.messages)) return;

                // If checkout reported failure
                if (resp.result === 'failure' || resp.result === 'error') {
                    var msg = resp.messages || resp.data || 'There was an error processing the payment.';
                    if (typeof msg === 'string') msg = msg.replace(/<[^>]*>?/gm, '');
                    alert('Error: ' + msg);
                    setLoading(false);
                    return;
                }

                // Success path
                if (resp.redirect) {
                    window.location.href = resp.redirect;
                    return;
                }

                // If no redirect and success, do NOT assume payment completed. Show informative message instead.
                if (resp.result === 'success') {
                    // Some gateways perform further client-side steps; if wc returned success without redirect,
                    // do not immediately mark booking confirmed. Let server-side hooks update booking upon order completion.
                    setLoading(false);
                    alert('Payment processing started. If you are not redirected, please complete the payment on the page or check your payment method. Your booking will be confirmed when payment completes.');
                    return;
                }
            } catch (e) {
                console.error('Error handling checkout ajaxComplete', e);
                setLoading(false);
            }
        });
    }

    function setLoading(loading) {
        state.loading = loading;
        if (loading) {
            container.addClass('opacity-50 pointer-events-none');
        } else {
            container.removeClass('opacity-50 pointer-events-none');
        }
    }
});
