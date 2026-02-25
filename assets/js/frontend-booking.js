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

    let fpInstance = null;

    // 2. Step 2 Logic: Flatpickr
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

        if (fpInstance) {
            fpInstance.destroy();
        }

        fpInstance = flatpickr(datepickerEl, {
            inline: true,
            minDate: "today",
            locale: {
                firstDayOfWeek: 1 // Start week on Monday
            },
            disable: [
                function (date) {
                    // disables the days of the week configured as disabled (0 = Sun, 6 = Sat)
                    return state.availabilityConfig.disabledDays.includes(date.getDay());
                }
            ],
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 0) {
                    state.selectedDate = null;
                    elements.slotsContainer.html('<p class="text-gray-400 text-sm py-20 italic text-center">Select a date on the calendar.</p>');
                    return;
                }

                const date = selectedDates[0];
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const dayStr = String(date.getDate()).padStart(2, '0');
                state.selectedDate = `${year}-${month}-${dayStr}`;

                fetchSlots();
            }
        });
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
        if (!state.slots || state.slots.length === 0) {
            $('#mbs-slot-count').text('0');
            elements.slotsContainer.html('<div class="text-gray-400 text-sm col-span-full py-16 text-center italic border-2 border-dashed border-gray-100 rounded-2xl mx-1">No slots found for this date. Try another day.</div>');
            return;
        }

        $('#mbs-slot-count').text(state.slots.length);

        elements.slotsContainer.html(state.slots.map(slot => {
            const timeStr = slot.time;
            const isAvailable = slot.available;
            const startLabel = slot.display_time || new Date(timeStr).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });

            if (isAvailable) {
                return `
                    <button type="button" 
                        class="mbs-slot-pill py-3 px-6 text-[15px] font-bold rounded-full border border-gray-200 text-gray-700 bg-white hover:border-[#0066FF] hover:text-[#0066FF] transition-all text-center cursor-pointer mbs-slot-available"
                        data-slot="${timeStr}"
                    >
                        ${startLabel}
                    </button>
                `;
            } else {
                return `
                    <button type="button" 
                        class="mbs-slot-pill py-3 px-6 text-[15px] font-bold rounded-full border border-gray-100 text-gray-300 bg-gray-50 transition-all text-center cursor-not-allowed opacity-50 mbs-slot-unavailable"
                        data-slot="${timeStr}"
                        disabled
                    >
                        ${startLabel}
                    </button>
                `;
            }
        }).join(''));

        $('.mbs-slot-available').on('click', function () {
            $('.mbs-slot-pill.mbs-slot-available').removeClass('bg-[#0066FF] text-white shadow-md border-[#0066FF]').addClass('border-gray-200 text-gray-700 bg-white');
            $(this).addClass('bg-[#0066FF] text-white shadow-md border-[#0066FF]').removeClass('border-gray-200 text-gray-700 bg-white');
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
