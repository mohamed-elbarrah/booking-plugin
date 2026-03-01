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
            return `
                <div class="mbs-package-card group p-5 rounded-xl cursor-pointer flex items-center justify-between transition-all ${isPopular ? 'popular ring-2 ring-black' : 'hover:bg-gray-50'}" data-id="${service.id}">
                    <div class="flex-grow">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-lg font-bold text-gray-900">${service.name}</h4>
                            ${isPopular ? '<span class="mbs-package-badge bg-black text-white px-2 py-0.5 rounded text-[9px]">Popular</span>' : ''}
                        </div>
                        <p class="text-sm text-gray-500 line-clamp-1">${service.description || 'Professional consultation.'}</p>
                    </div>
                    <div class="flex items-center gap-4 text-right">
                        <div class="text-sm font-semibold text-gray-900">${service.duration}m</div>
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
            elements.stepSuccess.removeClass('hidden');
            elements.globalNav.addClass('hidden');
            elements.sidebar.addClass('hidden'); // Success usually full width
            elements.mainContent.removeClass('md:w-2/3').addClass('w-full');
        }

        elements.currentStepSpan.text(step);
        elements.btnBack.prop('disabled', step === 1);

        // Scroll to container top
        $('html, body').animate({ scrollTop: container.offset().top - 40 }, 300);
    }

    // Events
    elements.btnBack.on('click', () => goToStep(state.currentStep - 1));
    $('#mbs-btn-back-s3').on('click', () => goToStep(2));

    elements.bookingForm.on('submit', async function (e) {
        e.preventDefault();
        setLoading(true);

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.service_id = state.selectedService.id;
        data.booking_datetime_utc = state.selectedSlot;
        data.duration = state.selectedService.duration;
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
    });

    function setLoading(loading) {
        state.loading = loading;
        if (loading) {
            container.addClass('opacity-50 pointer-events-none');
        } else {
            container.removeClass('opacity-50 pointer-events-none');
        }
    }
});
