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
        availabilityConfig: {
            disabledDays: [],
            minDate: null
        }
    };

    let datepickerInstance = null;

    // DOM Elements
    const elements = {
        stepTitle: $('#mbs-step-title'),
        stepSubtitle: $('#mbs-step-subtitle'),
        currentStepSpan: $('#mbs-current-step'),
        mainContent: $('#mbs-main-content'),
        stepServices: $('#step-services'),
        stepDatetime: $('#step-datetime'),
        stepDetails: $('#step-details'),
        stepSuccess: $('#step-success'),
        btnBack: $('#btn-back'),
        btnNext: $('#btn-next'),
        slotsContainer: $('#mbs-slots-container'),
        bookingForm: $('#mbs-booking-form'),
        datepicker: $('#mbs-datepicker')
    };

    // 1. Initial Load: Fetch Services
    fetchServices();

    async function fetchServices() {
        setLoading(true);
        try {
            const response = await fetch(`${bookingAppPublic.restUrl}/services`, {
                headers: { 'X-WP-Nonce': bookingAppPublic.nonce }
            });
            state.services = await response.json();
            renderServices();
        } catch (e) {
            console.error('Failed to fetch services', e);
        } finally {
            setLoading(false);
        }
    }

    function renderServices() {
        if (state.services.length === 0) {
            elements.stepServices.html('<p class="col-span-full text-center text-gray-500 py-10 font-medium">No active services available at the moment.</p>');
            return;
        }

        // Logic to determine "Popular" card - pick the middle one or second one
        const popularIndex = state.services.length > 2 ? 1 : (state.services.length > 1 ? 0 : -1);

        elements.stepServices.html(state.services.map((service, idx) => {
            const isPopular = idx === popularIndex;
            const priceLabel = parseFloat(service.price) > 0 ? '$' + parseFloat(service.price).toFixed(2) : 'Free';

            return `
                <div class="service-card group border rounded-3xl p-8 cursor-pointer relative flex flex-col justify-between ${isPopular ? 'popular scale-105 z-10' : 'bg-white border-gray-100 hover:border-indigo-100 hover:shadow-2xl hover:shadow-indigo-50'} " data-id="${service.id}">
                    <div>
                        <div class="flex justify-between items-start mb-8">
                            <span class="text-[11px] font-bold ${isPopular ? 'text-gray-400' : 'text-gray-400'} bg-black/5 px-3 py-1.5 rounded-lg uppercase tracking-wider">${service.duration} min</span>
                            <div class="icon-box h-12 w-12 ${isPopular ? 'bg-indigo-600/20 text-indigo-400' : 'bg-indigo-50 text-indigo-500'} rounded-2xl flex items-center justify-center transition-all group-hover:scale-110">
                                ${isPopular ?
                    '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>' :
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>'
                }
                            </div>
                        </div>
                        <h4 class="text-2xl font-extrabold mb-3 leading-tight">${service.name}</h4>
                        <p class="text-base mb-8 leading-relaxed opacity-80">${service.description || 'Professional consultation tailored to your needs.'}</p>
                    </div>
                    <div class="flex justify-between items-center pt-6 border-t ${isPopular ? 'border-white/10' : 'border-gray-50'}">
                        <span class="price text-xl font-bold">${priceLabel}</span>
                        ${isPopular ?
                    `<button class="btn-choose py-2.5 rounded-xl font-bold text-sm transition-all shadow-lg">Choose</button>` :
                    `<span class="text-indigo-600 font-bold text-sm flex items-center group-hover:translate-x-1 transition-transform">Select <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></span>`
                }
                    </div>
                </div>
            `;
        }).join(''));

        // Handle selection
        $('.service-card').on('click', function () {
            const id = $(this).data('id');
            state.selectedService = state.services.find(s => s.id == id);
            goToStep(2);
        });
    }

    // 2. Step 2 Logic: Flowbite Datepicker
    async function initDatePicker() {
        // Fetch config if not already fetched
        if (!state.availabilityConfig.minDate) {
            try {
                const response = await fetch(`${bookingAppPublic.restUrl}/availability-config`, {
                    headers: { 'X-WP-Nonce': bookingAppPublic.nonce }
                });
                state.availabilityConfig = await response.json();
            } catch (e) {
                console.error('Failed to fetch availability config', e);
                // Fallback to today
                state.availabilityConfig.minDate = new Date().toISOString().split('T')[0];
            }
        }

        const datepickerEl = document.getElementById('mbs-datepicker');
        if (!datepickerEl) return;

        // Manual initialization if Flowbite Datepicker class is available
        if (!datepickerInstance && typeof Datepicker !== 'undefined') {
            datepickerInstance = new Datepicker(datepickerEl, {
                autohide: true,
                format: 'yyyy-mm-dd',
                minDate: state.availabilityConfig.minDate,
                daysOfWeekDisabled: state.availabilityConfig.disabledDays,
                todayBtn: true,
                clearBtn: true,
                orientation: 'bottom left'
            });

            datepickerEl.addEventListener('changeDate', (event) => {
                const date = event.detail.date;
                if (!date) {
                    state.selectedDate = null;
                    $('#mbs-date-summary').addClass('hidden');
                    elements.slotsContainer.html('<p class="text-gray-400 text-sm col-span-full flex flex-col items-center justify-center py-20 italic text-center"><svg class="w-12 h-12 mb-4 opacity-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Slots will appear here once you pick a date.</p>');
                    $('#mbs-slot-count').text('0');
                    return;
                }

                // Correct for timezone
                const offset = date.getTimezoneOffset();
                const adjustedDate = new Date(date.getTime() - (offset * 60 * 1000));
                state.selectedDate = adjustedDate.toISOString().split('T')[0];

                // Update UI
                const formatted = new Intl.DateTimeFormat('en-US', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
                $('#mbs-selected-date-display').text(formatted);
                $('#mbs-date-summary').removeClass('hidden');

                fetchSlots();
            });
        }
    }

    async function fetchSlots() {
        if (!state.selectedService || !state.selectedDate) return;

        elements.slotsContainer.html('<div class="col-span-full py-10 text-center"><div class="inline-block animate-spin h-5 w-5 border-2 border-indigo-600 border-t-transparent rounded-full"></div></div>');

        try {
            const response = await fetch(`${bookingAppPublic.restUrl}/slots?service_id=${state.selectedService.id}&date=${state.selectedDate}`, {
                headers: { 'X-WP-Nonce': bookingAppPublic.nonce }
            });
            state.slots = await response.json();
            // Sort slots chronologically
            state.slots.sort((a, b) => new Date(a.time) - new Date(b.time));
            renderSlots();
        } catch (e) {
            console.error('Failed to fetch slots', e);
        }
    }

    function renderSlots() {
        $('#mbs-slot-count').text(state.slots.length);

        if (!state.slots || state.slots.length === 0) {
            elements.slotsContainer.html('<div class="text-gray-400 text-sm col-span-full py-16 text-center italic border-2 border-dashed border-gray-100 rounded-3xl">No slots found for this date. Try another day.</div>');
            return;
        }

        elements.slotsContainer.html(state.slots.map(slot => {
            const timeStr = slot.time;
            const duration = slot.duration || 60;
            const isAvailable = slot.available;

            const startObj = new Date(timeStr);
            const endObj = new Date(startObj.getTime() + (duration * 60 * 1000));

            const startLabel = startObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            const endLabel = endObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

            const timeInterval = `${startLabel} - ${endLabel}`;

            return `
                <button type="button" 
                    class="slot-btn p-3 text-xs font-bold rounded-xl border transition-all text-center flex flex-col items-center justify-center gap-1
                    ${isAvailable
                    ? 'bg-white border-gray-100 text-gray-900 hover:border-indigo-400 hover:shadow-lg hover:shadow-indigo-50/50 cursor-pointer'
                    : 'bg-gray-50 border-gray-100 text-gray-300 cursor-not-allowed opacity-40'}"
                    data-slot="${timeStr}"
                    ${!isAvailable ? 'disabled' : ''}
                >
                    <span class="whitespace-nowrap">${timeInterval}</span>
                    ${isAvailable ? '<span class="text-[9px] uppercase tracking-tighter text-emerald-500 font-black">Available</span>' : '<span class="text-[9px] uppercase tracking-tighter text-gray-400 font-black">Busy / Break</span>'}
                </button>
            `;
        }).join(''));

        $('.slot-btn:not([disabled])').on('click', function () {
            $('.slot-btn').removeClass('ring-4 ring-indigo-100 border-indigo-500 bg-indigo-50/30');
            $(this).addClass('ring-4 ring-indigo-100 border-indigo-500 bg-indigo-50/30');
            state.selectedSlot = $(this).data('slot');
            updateNavButtons();
        });
    }

    // 3. Navigation Logic
    function goToStep(step) {
        state.currentStep = step;

        // Hide all
        $('#step-services, #step-datetime, #step-details, #step-success').addClass('hidden');

        // Show current
        if (step === 1) {
            elements.stepServices.removeClass('hidden');
            elements.stepTitle.html('Choose a <span class="text-indigo-600">Service</span>');
            elements.stepSubtitle.text('Select the type of professional consultation you need to accelerate your project growth.');
        } else if (step === 2) {
            elements.stepDatetime.removeClass('hidden');
            elements.stepTitle.html('Select <span class="text-indigo-600">Date & Time</span>');
            elements.stepSubtitle.text(`Scheduling your ${state.selectedService.name} session.`);
            initDatePicker();
        } else if (step === 3) {
            elements.stepDetails.removeClass('hidden');
            elements.stepTitle.html('Your <span class="text-indigo-600">Details</span>');
            elements.stepSubtitle.text('Almost there! Please provide your contact info to finalize the booking.');
        } else if (step === 4) {
            elements.stepSuccess.removeClass('hidden');
            elements.stepTitle.text('All Set!');
            elements.stepSubtitle.text('Your booking has been successfully established.');
            $('#mbs-navigation').addClass('hidden');
        }

        // Update Progress Bar
        const progress = (step / 4) * 100;
        $('#mbs-progress-bar').css('width', `${progress}%`);
        $('#mbs-progress-text').text(`${Math.round(progress)}%`);

        elements.currentStepSpan.text(step);
        updateNavButtons();

        // Scroll to top of app
        $('html, body').animate({
            scrollTop: container.offset().top - 100
        }, 500);
    }

    function updateNavButtons() {
        // Back Button
        if (state.currentStep > 1 && state.currentStep < 4) {
            elements.btnBack.removeClass('invisible').prop('disabled', false);
        } else {
            elements.btnBack.addClass('invisible').prop('disabled', true);
        }

        // Next Button
        let canProceed = false;
        if (state.currentStep === 1 && state.selectedService) canProceed = true;
        if (state.currentStep === 2 && state.selectedSlot) canProceed = true;
        if (state.currentStep === 3) canProceed = true; // validation in submit

        elements.btnNext.prop('disabled', !canProceed);
        if (canProceed) {
            elements.btnNext.removeClass('cursor-not-allowed opacity-50').addClass('bg-gray-900 hover:bg-black');
        } else {
            elements.btnNext.addClass('cursor-not-allowed opacity-50').removeClass('bg-gray-900 hover:bg-black');
        }

        if (state.currentStep === 3) {
            elements.btnNext.text('Confirm Booking');
        } else {
            elements.btnNext.text('Continue');
        }
    }

    elements.btnBack.on('click', () => {
        if (state.currentStep > 1) goToStep(state.currentStep - 1);
    });

    elements.btnNext.on('click', () => {
        if (state.currentStep === 3) {
            submitBooking();
        } else {
            goToStep(state.currentStep + 1);
        }
    });

    async function submitBooking() {
        const formData = new FormData(elements.bookingForm[0]);
        const data = Object.fromEntries(formData.entries());

        // Add booking meta
        data.service_id = state.selectedService.id;
        data.booking_datetime_utc = state.selectedSlot;
        data.duration = state.selectedService.duration;
        data.status = 'confirmed'; // Auto confirm for now

        setLoading(true);
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
                alert('Booking failed: ' + result.message);
            }
        } catch (e) {
            console.error('Submission failed', e);
        } finally {
            setLoading(false);
        }
    }

    function setLoading(loading) {
        state.loading = loading;
        if (loading) {
            elements.btnNext.prop('disabled', true).html('<div class="inline-block animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2"></div> Working...');
        } else {
            updateNavButtons();
        }
    }
});
