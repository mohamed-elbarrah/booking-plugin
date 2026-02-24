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
             <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                 <!-- Flowbite Datepicker Container -->
                 <div class="flex flex-col">
                     <div class="mb-6">
                         <h3 class="text-2xl font-black text-gray-900 leading-tight">Pick a <span class="text-indigo-600">Date</span></h3>
                         <p class="text-gray-500 text-sm mt-1">Select your preferred day to see available times.</p>
                     </div>
                     
                     <div class="relative max-w-sm">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                        </div>
                        <input id="mbs-datepicker" datepicker datepicker-buttons datepicker-autoselect-today type="text" class="block w-full ps-10 pe-3 py-3 bg-white border border-gray-200 text-gray-900 text-sm rounded-2xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm placeholder-gray-400" placeholder="Select date" readonly>
                     </div>

                     <div class="mt-8 p-6 bg-indigo-50 rounded-2xl border border-indigo-100 hidden" id="mbs-date-summary">
                         <div class="flex items-center text-indigo-700">
                             <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                             <div>
                                 <p class="text-xs font-bold uppercase tracking-wider opacity-60">Selected Date</p>
                                 <p id="mbs-selected-date-display" class="text-lg font-black mt-0.5">Please choose a date</p>
                             </div>
                         </div>
                     </div>
                 </div>
                 
                 <!-- Slots Selection -->
                 <div class="flex flex-col">
                     <div class="mb-6">
                         <h3 class="text-2xl font-black text-gray-900 leading-tight">Available <span class="text-indigo-600">Times</span></h3>
                         <p class="text-gray-500 text-sm mt-1">Found <span id="mbs-slot-count" class="font-bold text-indigo-500">0</span> slots for this day</p>
                     </div>
                     <div id="mbs-slots-container" class="grid grid-cols-3 sm:grid-cols-4 gap-3 bg-white/50 p-6 rounded-[32px] border border-gray-100 min-h-[300px]">
                         <p class="text-gray-400 text-sm col-span-full flex flex-col items-center justify-center py-20 italic text-center">
                             <svg class="w-12 h-12 mb-4 opacity-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                             Slots will appear here once you pick a date.
                         </p>
                     </div>
                 </div>
             </div>
        </div>

        <!-- Step 3: Customer Details (Hidden) -->
        <div id="step-details" class="hidden max-w-lg mx-auto animate-fade-in">
            <form id="mbs-booking-form" class="space-y-5 bg-gray-50 p-8 rounded-2xl border border-gray-100">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all placeholder-gray-300" placeholder="John Doe">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all placeholder-gray-300" placeholder="john@example.com">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" name="phone" required class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all placeholder-gray-300" placeholder="+1 (555) 000-0000">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700">Special Notes (Optional)</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all placeholder-gray-300" placeholder="Anything we should know?"></textarea>
                </div>
            </form>
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
