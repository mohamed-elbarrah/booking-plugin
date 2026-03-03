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
            businessName: bookingAppPublic.i18n.popular,
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
                        <p class="text-sm text-gray-500 line-clamp-1">${service.description || ''}</p>
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

                // If native checkout is visible, we might want to hide the sidebar to give more space
                if (!$('#mbs-native-checkout-container').hasClass('hidden')) {
                    elements.sidebar.addClass('hidden');
                    elements.mainContent.removeClass('md:w-2/3').addClass('w-full');
                }
            } else {
                renderSuccessStep();
                elements.stepSuccess.removeClass('hidden');
                elements.sidebar.addClass('hidden'); // Success usually full width
                elements.mainContent.removeClass('md:w-2/3').addClass('w-full');
            }
        } else if (step === 5) {
            renderSuccessStep();
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

    // 5. Stripe Elements Integration
    let stripe = null;
    let cardElement = null;

    if (bookingAppPublic.stripePublishableKey) {
        stripe = Stripe(bookingAppPublic.stripePublishableKey);
        const elements = stripe.elements();
        const style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': { color: '#aab7c4' }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
        cardElement = elements.create('card', { style: style, hidePostalCode: true });
    }

    function mountStripeElement() {
        if (cardElement && $('#mbs-card-element').length) {
            cardElement.on('ready', () => {
                setLoading(false);
            });
            cardElement.mount('#mbs-card-element');
        }
    }

    elements.bookingForm.on('submit', async function (e) {
        e.preventDefault();
        setLoading(true);

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        state.customer_name = data.customer_name || data.customer_full_name || '';
        state.customer_email = data.customer_email || data.email || '';

        data.service_id = state.selectedService.id;
        data.booking_datetime_utc = state.selectedSlot;
        data.duration = state.selectedService.duration;

        try {
            // 1. Create the booking (Pending)
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

            // 2. If free, go to success
            if (!state.selectedService.price || parseFloat(state.selectedService.price) <= 0) {
                goToStep(5);
                return;
            }

            // 3. Create Payment Intent
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

            // 4. Move to Payment Step
            renderPaymentStep();
            goToStep(4);
            mountStripeElement();

        } catch (e) {
            console.error('Submission error', e);
            setLoading(false);
        }
    });

    function renderSuccessStep() {
        const slotDate = new Date(state.selectedSlot);
        const dateStr = slotDate.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
        const timeStr = slotDate.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        const summaryHtml = `
            <div class="space-y-4">
                <div class="flex items-start gap-4">
                    <span class="material-icons-outlined text-gray-400 mt-1">inventory_2</span>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-1">Service</div>
                        <div class="text-base font-bold text-gray-900">${state.selectedService.name}</div>
                    </div>
                </div>
                <div class="flex items-start gap-4 pt-4 border-t border-gray-100">
                    <span class="material-icons-outlined text-gray-400 mt-1">calendar_today</span>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-1">Date & Time</div>
                        <div class="text-base font-bold text-gray-900">${dateStr}</div>
                        <div class="text-sm text-gray-500">${timeStr}</div>
                    </div>
                </div>
                <div class="flex items-start gap-4 pt-4 border-t border-gray-100">
                    <span class="material-icons-outlined text-gray-400 mt-1">person</span>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-1">Guest</div>
                        <div class="text-base font-bold text-gray-900">${state.customer_name}</div>
                        <div class="text-sm text-gray-500">${state.customer_email}</div>
                    </div>
                </div>
            </div>
        `;
        $('#mbs-booking-summary-final').html(summaryHtml);
        setLoading(false);
    }

    function renderPaymentStep() {
        const price = parseFloat(state.selectedService.price).toFixed(2);
        const currency = 'USD'; // Could be dynamic from settings

        const summaryHtml = `
            <div class="mbs-price-card bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-900">${state.selectedService.name}</h3>
                    <span class="text-2xl font-black text-black">$${price}</span>
                </div>
                <div class="text-xs text-gray-500 flex items-center gap-2 mb-6">
                    <span class="material-icons-outlined text-sm">calendar_today</span>
                    ${new Date(state.selectedSlot).toLocaleDateString()} @ ${new Date(state.selectedSlot).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                </div>
                
                <div class="space-y-3 pt-4 border-t border-gray-200">
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2">
                        <span>Total to Pay</span>
                        <span>$${price}</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Card Details</label>
                <div id="mbs-card-element" class="p-4 border border-gray-200 rounded-xl bg-white"></div>
                <div id="card-errors" role="alert" class="mt-2 text-sm text-red-600"></div>
            </div>
        `;
        $('#mbs-payment-summary').html(summaryHtml);

        $('#mbs-pay-now-btn').off('click').on('click', async function (e) {
            e.preventDefault();
            setLoading(true);

            if (!stripe || !cardElement) {
                alert('Stripe is not initialized.');
                setLoading(false);
                return;
            }

            const { paymentIntent, error } = await stripe.confirmCardPayment(state.clientSecret, {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: state.customer_name,
                        email: state.customer_email
                    }
                }
            });

            if (error) {
                $('#card-errors').text(error.message);
                setLoading(false);
            } else if (paymentIntent.status === 'succeeded') {
                goToStep(5);
            }
        });
    }

    function setLoading(loading) {
        state.loading = loading;
        if (loading) {
            container.addClass('opacity-50 pointer-events-none');
            $('#mbs-pay-now-btn').prop('disabled', true).text('Processing...');
        } else {
            container.removeClass('opacity-50 pointer-events-none');
            $('#mbs-pay-now-btn').prop('disabled', false).text('Pay & Confirm Booking');
        }
    }
});
