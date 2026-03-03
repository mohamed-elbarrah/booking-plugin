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
            businessName: '',
            businessLogo: ''
        }
    };

    let fpInstance = null;

    // Prevent concurrent checkout updates
    let checkoutUpdating = false;

    // DOM Elements
    const elements = {
        app: container,
        mainContainer: $('#mbs-main-container'),
        loader: $('#mbs-app-loader'),

        // Sidebar Elements
        sidebar: $('#mbs-sidebar'),
        sidebarLogoWrap: $('#mbs-sidebar-logo-wrap'),
        sidebarLogo: $('#mbs-sidebar-logo'),
        sidebarBusinessName: $('#mbs-sidebar-business-name'),
        sidebarTitle: $('#mbs-sidebar-title'),
        sidebarTimezone: $('#mbs-sidebar-timezone'),

        // Summary Sections (Sidebar)
        summaryService: $('#summary-section-service'),
        summaryDatetime: $('#summary-section-datetime'),
        summaryPrice: $('#summary-section-price'),

        // Summary Info (Sidebar)
        selectedServiceName: $('#mbs-selected-service-name'),
        selectedDatetimeText: $('#mbs-selected-datetime-text'),
        selectedPriceText: $('#mbs-summary-price'),

        // Step Containers
        mainContent: $('#mbs-main-content'),
        stepPackages: $('#step-packages'),
        stepDatetime: $('#step-datetime'),
        stepDetails: $('#step-details'),
        stepSuccess: $('#step-success'),
        stepPayment: $('#step-payment'),

        // Content Containers
        packagesContainer: $('#mbs-packages-container'),
        slotsContainer: $('#mbs-slots-container'),
        selectedDayLabel: $('#mbs-selected-day-label'),

        bookingForm: $('#mbs-booking-form'),
        datepicker: $('#mbs-datepicker'),

        btnBack: $('#btn-back'),

        // In-Step Summary (Step 4 & 5)
        payServiceName: $('#mbs-pay-service-name'),
        payDatetime: $('#mbs-pay-datetime'),
        payTotal: $('#mbs-pay-total'),

        finalServiceName: $('#mbs-final-service-name'),
        finalCustomerName: $('#mbs-final-customer-name'),
        finalCustomerEmail: $('#mbs-final-customer-email'),
        finalDatetime: $('#mbs-final-datetime')
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

            // Trigger Fade-in
            setTimeout(() => {
                elements.mainContainer.removeClass('opacity-0');
            }, 100);

        } catch (e) {
            console.error('Initialization failed', e);
        } finally {
            setLoading(false);
        }
    }

    function updateSidebarBase() {
        const cfg = state.availabilityConfig;
        elements.sidebarBusinessName.text(cfg.businessName || 'Consultation');
        elements.sidebarTimezone.text(cfg.timeZone || 'UTC');
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
            const priceHtml = service.price > 0 ? `<div class="text-base font-bold text-gray-900">$${parseFloat(service.price).toFixed(2)}</div>` : '<div class="text-sm font-bold text-emerald-600">Free</div>';
            return `
                <div class="mbs-package-card group p-6 rounded-2xl flex items-center justify-between" data-id="${service.id}">
                    <div class="flex-grow">
                        <div class="flex items-center gap-3 mb-1">
                            <h4 class="text-lg font-bold text-gray-900">${service.name}</h4>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">${service.description || ''}</p>
                    </div>
                    <div class="flex items-center gap-6 text-right">
                        <div class="flex flex-col items-end">
                            ${priceHtml}
                            <div class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mt-1">${service.duration} min</div>
                        </div>
                        <span class="material-icons-outlined text-gray-300 group-hover:text-black transition-colors">chevron_right</span>
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
            monthSelectorType: "dropdown",
            locale: {
                firstDayOfWeek: 1 // Monday
            },
            disable: [
                (date) => state.availabilityConfig.disabledDays.includes(date.getDay())
            ],
            onMonthChange: updateCalendarHeader,
            onYearChange: updateCalendarHeader,
            onReady: (d, s, instance) => {
                updateCalendarHeader(d, s, instance);
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
        elements.slotsContainer.html('<div class="py-10 text-center col-span-full"><div class="inline-block animate-spin h-5 w-5 border-2 border-black border-t-transparent rounded-full font-bold"></div></div>');

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
        if (state.slots.length === 0) {
            elements.slotsContainer.html('<p class="text-gray-400 text-sm py-20 italic text-center col-span-full">No slots available for this day.</p>');
            return;
        }

        elements.slotsContainer.html(state.slots.map(slot => {
            const timeStr = slot.time;
            const isAvailable = slot.available;
            const startLabel = slot.display_time || new Date(timeStr).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

            return `
                <button type="button" 
                    class="mbs-slot-pill w-full py-3 px-2 rounded-xl border text-sm font-bold transition-all ${isAvailable ? 'bg-white text-gray-900 border-gray-100 hover:border-black cursor-pointer mbs-slot-btn' : 'bg-gray-50 text-gray-300 cursor-not-allowed opacity-40'}"
                    data-slot="${timeStr}"
                    ${!isAvailable ? 'disabled' : ''}
                >
                    ${startLabel}
                </button>
            `;
        }).join(''));

        $('.mbs-slot-btn').on('click', function () {
            $('.mbs-slot-btn').removeClass('bg-black text-white border-black').addClass('bg-white text-gray-900 border-gray-200');
            $(this).addClass('bg-black text-white border-black').removeClass('bg-white text-gray-900 border-gray-200');
            state.selectedSlot = $(this).data('slot');

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
        elements.stepPayment.addClass('hidden');
        elements.stepSuccess.addClass('hidden');

        // Sidebar resets
        elements.summaryService.addClass('hidden');
        elements.summaryDatetime.addClass('hidden');
        elements.summaryPrice.addClass('hidden');

        // Toggle focus mode (no sidebar in step 4/5)
        if (step >= 4) {
            elements.app.addClass('mbs-no-sidebar');
        } else {
            elements.app.removeClass('mbs-no-sidebar');
        }

        // Toggle back button visibility
        elements.btnBack.prop('disabled', step === 1);

        if (step === 1) {
            elements.stepPackages.removeClass('hidden');
            elements.sidebarTitle.text('Select Service');
        } else if (step === 2) {
            elements.stepDatetime.removeClass('hidden');
            elements.summaryService.removeClass('hidden');
            elements.selectedServiceName.text(state.selectedService.name);
            elements.sidebarTitle.text('Pick a Time');
            initDatePicker();
        } else if (step === 3) {
            elements.stepDetails.removeClass('hidden');
            elements.summaryService.removeClass('hidden');
            elements.summaryDatetime.removeClass('hidden');
            elements.selectedServiceName.text(state.selectedService.name);
            const slotDate = new Date(state.selectedSlot);
            const summary = slotDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) + ', ' + slotDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            elements.selectedDatetimeText.text(summary);
            elements.sidebarTitle.text('Your Details');
        } else if (step === 4) {
            if (state.selectedService.price > 0 && !state.paymentCompleted) {
                elements.stepPayment.removeClass('hidden');

                // Populate In-Step Summary
                elements.payServiceName.text(state.selectedService.name);
                const slotDate = new Date(state.selectedSlot);
                const summary = slotDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) + ', ' + slotDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                elements.payDatetime.text(summary);
                elements.payTotal.text('$' + parseFloat(state.selectedService.price).toFixed(2));

                renderPaymentStep();
                mountStripeElement();
            } else {
                goToStep(5);
            }
        } else if (step === 5) {
            elements.stepSuccess.removeClass('hidden');
            elements.btnBack.addClass('hidden');

            // Populate Final Summary
            elements.finalServiceName.text(state.selectedService.name);
            elements.finalCustomerName.text(state.customer_name);
            elements.finalCustomerEmail.text(state.customer_email);

            const slotDate = new Date(state.selectedSlot);
            const summary = slotDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) + ', ' + slotDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            elements.finalDatetime.text(summary);
        }

        $('html, body').animate({ scrollTop: container.offset().top - 40 }, 300);
    }

    elements.btnBack.on('click', () => {
        if (state.currentStep > 1) {
            goToStep(state.currentStep - 1);
        }
    });

    // 5. Stripe Elements Integration
    let stripe = null;
    let cardNumber = null;
    let cardExpiry = null;
    let cardCvc = null;

    if (bookingAppPublic.stripePublishableKey) {
        stripe = Stripe(bookingAppPublic.stripePublishableKey);
        const stripeElements = stripe.elements();
        const style = {
            base: {
                color: '#111827',
                fontFamily: '"Inter", sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': { color: '#9ca3af' }
            },
            invalid: { color: '#ef4444', iconColor: '#ef4444' }
        };

        cardNumber = stripeElements.create('cardNumber', { style: style });
        cardExpiry = stripeElements.create('cardExpiry', { style: style });
        cardCvc = stripeElements.create('cardCvc', { style: style });
    }

    function mountStripeElement() {
        if (cardNumber && $('#stripe-card-number').length) {
            cardNumber.mount('#stripe-card-number');
            cardExpiry.mount('#stripe-card-expiry');
            cardCvc.mount('#stripe-card-cvc');
        }
    }

    elements.bookingForm.on('submit', async function (e) {
        e.preventDefault();
        setLoading(true);

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        state.customer_name = data.customer_name;
        state.customer_email = data.customer_email;

        data.service_id = state.selectedService.id;
        data.booking_datetime_utc = state.selectedSlot;
        data.duration = state.selectedService.duration;

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

            if (!result.success) {
                alert('Error: ' + result.message);
                setLoading(false);
                return;
            }

            state.booking_id = result.booking_id;

            if (!state.selectedService.price || parseFloat(state.selectedService.price) <= 0) {
                goToStep(5);
                setLoading(false);
                return;
            }

            const piResponse = await fetch(`${bookingAppPublic.restUrl}/bookings/create-payment-intent`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': bookingAppPublic.nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ booking_id: state.booking_id })
            });
            const piResult = await piResponse.json();

            if (!piResult.success) {
                alert('Payment Error: ' + piResult.message);
                setLoading(false);
                return;
            }

            state.clientSecret = piResult.clientSecret;
            goToStep(4);
            setLoading(false);

        } catch (e) {
            console.error('Submission error', e);
            setLoading(false);
        }
    });

    function renderPaymentStep() {
        $('#mbs-pay-button').off('click').on('click', async function (e) {
            e.preventDefault();
            setLoading(true);

            if (!stripe || !cardNumber) {
                alert('Stripe is not initialized.');
                setLoading(false);
                return;
            }

            const { paymentIntent, error } = await stripe.confirmCardPayment(state.clientSecret, {
                payment_method: {
                    card: cardNumber,
                    billing_details: {
                        name: state.customer_name,
                        email: state.customer_email
                    }
                }
            });

            if (error) {
                $('#mbs-payment-errors').text(error.message).removeClass('hidden');
                setLoading(false);
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                goToStep(5);
                setLoading(false);
            } else {
                setLoading(false);
            }
        });
    }

    function setLoading(loading) {
        state.loading = loading;
        if (loading) {
            elements.loader.removeClass('hidden');
            $('#mbs-pay-button').prop('disabled', true).text('Processing...');
        } else {
            elements.loader.addClass('hidden');
            $('#mbs-pay-button').prop('disabled', false).text('Pay & Confirm');
        }
    }
});
