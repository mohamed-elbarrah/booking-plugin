<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="mbs-booking-app" class="mbs-booking-wrap mx-auto p-4 sm:p-10 bg-white rounded-3xl shadow-2xl shadow-gray-100/50 border border-gray-50 min-h-[600px] flex flex-col font-sans">
    <!-- Header -->
    <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="flex-grow">
            <span class="inline-block text-[10px] font-bold text-indigo-500 bg-indigo-50/50 px-3 py-1.5 rounded-full uppercase tracking-widest mb-4">
                Step <span id="mbs-current-step">1</span> of 4
            </span>
            <h2 id="mbs-step-title" class="text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight">
                Choose a <span class="text-indigo-600">Service</span>
            </h2>
            <p id="mbs-step-subtitle" class="text-gray-500 text-lg mt-3 max-w-xl">
                Select the type of professional consultation you need to accelerate your project growth.
            </p>
        </div>
       
    </div>

    <!-- Main Content Area -->
    <div id="mbs-main-content" class="flex-grow">
        <!-- Step 1: Services List -->
        <div id="step-services" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
            <!-- Skeleton Loader -->
            <div class="animate-pulse bg-gray-50 h-40 rounded-xl border border-gray-100"></div>
            <div class="animate-pulse bg-gray-100 h-40 rounded-xl border border-gray-100"></div>
        </div>

        <!-- Step 2: Date & Time Picker (Hidden) -->
        <div id="step-datetime" class="hidden animate-fade-in">
             <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-24">
                 
                 <!-- Left Column: Slots Selection -->
<div class="flex flex-col">
                     <div class="mb-8 text-right">
                         <h3 class="text-xl font-bold text-gray-900">Pick a <span class="text-indigo-600">Date</span></h3>
                         <p class="text-gray-400 text-xs mt-2">Select your preferred day to see available times.</p>
                     </div>
                     
                     <div class="relative w-full flex">
                        <!-- We use a wrapper for the flatpickr so it aligns properly to the right if needed, though usually calendar is centered/full width. Image shows input as right aligned width -->
                        <div class="w-full max-w-sm">
                            <input id="mbs-datepicker" type="text" class="block w-full px-4 py-2.5 mb-2 bg-white border border-gray-200 text-gray-600 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400 text-right" placeholder="Select date" readonly>
                        </div>
                     </div>

                     <div class="mt-4 p-6 bg-indigo-50 rounded-2xl border border-indigo-100 hidden" id="mbs-date-summary">
                         <div class="flex items-center text-indigo-700">
                             <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                             <div>
                                 <p class="text-xs font-bold uppercase tracking-wider opacity-60">Selected Date</p>
                                 <p id="mbs-selected-date-display" class="text-lg font-black mt-0.5">Please choose a date</p>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- Right Column: Flowbite Datepicker Container -->
                 <div class="flex flex-col">
                     <div class="mb-8 text-center">
                         <h3 class="text-xl font-bold text-gray-900">Available <span class="text-indigo-600">Times</span></h3>
                         <p class="text-gray-400 text-xs mt-2">Found <span id="mbs-slot-count" class="font-bold text-indigo-500">0</span> slots for this day</p>
                     </div>
                     <div id="mbs-slots-container" class="grid grid-cols-2 sm:grid-cols-3 gap-4 min-h-[300px] content-start">
                         <p class="text-gray-400 text-sm col-span-full py-20 italic text-center">
                             Select a date on the calendar.
                         </p>
                     </div>
                 </div>
                 
             </div>
        </div>

        <!-- Step 3: Confirm Your Booking (Hidden) -->
        <div id="step-details" class="hidden animate-fade-in">
            <div class="mbs-step3-card" dir="ltr">

                <!-- LEFT PANEL: Business + Service Info -->
                <div class="mbs-step3-left">
                    <!-- Business Logo & Name -->
                    <div class="mbs-step3-brand" id="mbs-brand-wrap">
                        <div id="mbs-logo-wrap" class="hidden">
                            <img id="mbs-business-logo" src="" alt="Logo" class="mbs-business-logo" />
                        </div>
                        <p id="mbs-business-name" class="mbs-business-name"></p>
                    </div>

                    <!-- Service Name -->
                    <h3 id="mbs-s3-service-name" class="mbs-s3-service-name"></h3>

                    <!-- Duration -->
                    <div class="mbs-s3-meta" id="mbs-s3-duration-wrap">
                        <svg class="mbs-s3-meta-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="mbs-s3-duration-text" class="text-sm text-gray-600"></span>
                    </div>

                    <!-- Description -->
                    <p id="mbs-s3-service-desc" class="mbs-s3-service-desc"></p>
                </div>

                <!-- DIVIDER -->
                <div class="mbs-step3-divider"></div>

                <!-- RIGHT PANEL: Form -->
                <div class="mbs-step3-right">
                    <!-- Back link (inline) -->
                    <button type="button" id="mbs-s3-back" class="mbs-s3-back-link">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back
                    </button>

                    <h3 class="mbs-s3-heading">Confirm Your Booking</h3>

                    <!-- Date / Time summary card -->
                    <div class="mbs-datetime-card">
                        <div class="mbs-datetime-card-inner">
                            <svg class="mbs-datetime-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <p id="mbs-s3-date" class="mbs-s3-date-text"></p>
                                <p id="mbs-s3-time" class="mbs-s3-time-text"></p>
                            </div>
                        </div>
                        <div class="mbs-s3-tz-row">
                            <svg class="mbs-tz-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"></path>
                            </svg>
                            <span id="mbs-s3-timezone" class="mbs-s3-tz-text"></span>
                        </div>
                    </div>

                    <!-- Customer Form -->
                    <form id="mbs-booking-form" class="mbs-s3-form" novalidate>
                        <div class="mbs-s3-field">
                            <label class="mbs-s3-label">Name</label>
                            <input type="text" name="customer_name" required
                                class="mbs-s3-input" placeholder="Joe Doe">
                        </div>
                        <div class="mbs-s3-field">
                            <label class="mbs-s3-label">Email</label>
                            <input type="email" name="email" required
                                class="mbs-s3-input" placeholder="joe@example.com">
                        </div>
                        <div class="mbs-s3-field">
                            <label class="mbs-s3-label">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <textarea name="notes" rows="3"
                                class="mbs-s3-input mbs-s3-textarea" placeholder="Anything we should know?"></textarea>
                        </div>

                        <!-- Confirm button (inside the panel) -->
                        <button type="submit" class="mbs-confirm-btn">
                            Confirm
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Step 4: Success (Hidden) -->
        <div id="step-success" class="hidden text-center py-12 animate-scale-in">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Booking Confirmed!</h2>
            <p class="text-gray-500 max-w-sm mx-auto mb-8">Your appointment is scheduled. We've sent a confirmation email to your inbox.</p>
            <div class="grid grid-cols-2 gap-4 max-w-xs mx-auto">
                 <button onclick="location.reload()" class="w-full py-3 px-6 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">Book Another</button>
                 <button id="add-to-calendar" class="w-full py-3 px-6 border border-gray-200 text-gray-600 rounded-xl font-medium hover:bg-gray-50 transition-all">Add to Calendar</button>
            </div>
        </div>
    </div>

    <!-- Navigation Footer -->
    <div id="mbs-navigation" class="mt-10 pt-8 border-t border-gray-50 flex justify-between">
        <button id="btn-back" class="px-6 py-2.5 text-gray-400 font-medium hover:text-gray-900 transition-all flex items-center invisible disabled:opacity-30">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back
        </button>
        <button id="btn-next" class="px-8 py-2.5 bg-gray-900 text-white rounded-xl font-medium hover:bg-black shadow-lg shadow-gray-200 transition-all disabled:bg-gray-200 disabled:shadow-none cursor-not-allowed" disabled>
            Continue
        </button>
    </div>
</div>
