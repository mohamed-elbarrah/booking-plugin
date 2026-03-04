<?php
/**
 * Modern Booking App Template - Summary Sidebar Architecture
 * 
 * 30% Sidebar (Fixed Summary) / 70% Main Workspace (Active Flow)
 */

if (!defined('ABSPATH'))
    exit;
?>

<div id="mbs-booking-app" class="mbs-booking-wrap min-h-[700px] flex items-center justify-center md:p-10">
    
    <!-- Unified 30/70 Container -->
    <main id="mbs-main-container" class="w-full max-w-[1100px] bg-white rounded-xl shadow-[0_20px_60px_rgba(0,0,0,0.12)] border border-gray-100 overflow-hidden flex flex-col md:flex-row animate-fade-in opacity-0">
        
        <!-- Modern Loader Overlay -->
        <div id="mbs-app-loader" class="hidden">
            <div class="flex flex-col items-center">
                <span class="mbs-spinner mb-4"></span>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]"><?php echo esc_html__('Loading', 'mbs-booking'); ?></p>
            </div>
        </div>

        <!-- SIDEBAR (30% WIDTH) - FIXED SUMMARY -->
        <aside id="mbs-sidebar" class="md:w-[320px] bg-white border-b md:border-b-0 md:border-r border-gray-100 p-10 flex flex-col transition-all duration-300">
            <!-- Top: Brand -->
            <div class="mb-12">
                <div id="mbs-sidebar-logo-wrap" class="mb-4 hidden">
                    <img id="mbs-sidebar-logo" src="" alt="<?php echo esc_attr__('Logo', 'mbs-booking'); ?>" class="w-14 h-14 rounded-full object-cover shadow-sm ring-4 ring-gray-50">
                </div>
                <div class="space-y-1">
                    <h2 id="mbs-sidebar-business-name" class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.2em] leading-none mb-1"></h2>
                    <h1 id="mbs-sidebar-title" class="text-3xl font-extrabold text-gray-900 tracking-tight leading-tight"><?php echo esc_html__('Booking', 'mbs-booking'); ?></h1>
                </div>
            </div>

            <!-- Middle: Live Summary (Progressive Reveal) -->
            <div id="mbs-summary-area" class="flex-grow space-y-8">
                <!-- Selected Service -->
                <div id="summary-section-service" class="group hidden animate-fade-in-up">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5"><?php echo esc_html__('Service', 'mbs-booking'); ?></p>
                    <div class="flex items-start gap-2">
                        <span class="material-icons-outlined text-gray-400 text-sm mt-0.5">category</span>
                        <h4 id="mbs-selected-service-name" class="text-sm font-bold text-gray-900 leading-snug"></h4>
                    </div>
                </div>

                <!-- Selected Date & Time -->
                <div id="summary-section-datetime" class="group hidden animate-fade-in-up">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5"><?php echo esc_html__('Date & Time', 'mbs-booking'); ?></p>
                    <div class="flex items-start gap-2">
                        <span class="material-icons-outlined text-gray-400 text-sm mt-0.5">calendar_today</span>
                        <div id="mbs-selected-datetime-text" class="text-sm font-bold text-gray-900 leading-relaxed"></div>
                    </div>
                </div>

                <!-- Price Summary -->
                <div id="summary-section-price" class="group hidden animate-fade-in-up">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5"><?php echo esc_html__('Total Price', 'mbs-booking'); ?></p>
                    <div class="flex items-start gap-2">
                        <span class="material-icons-outlined text-gray-400 text-sm mt-0.5">payments</span>
                        <h4 id="mbs-summary-price" class="text-sm font-black text-emerald-600 tracking-tight"></h4>
                    </div>
                </div>
            </div>

            <!-- Bottom: Footer Info & Actions -->
            <div class="mt-auto pt-8 border-t border-gray-100 space-y-6">
                <!-- Timezone -->
                <div class="flex items-center gap-2 text-gray-400">
                    <span class="material-icons-outlined text-base">public</span>
                    <span id="mbs-sidebar-timezone" class="text-[11px] font-bold"><?php echo esc_html(get_option('timezone_string') ?: 'UTC'); ?></span>
                </div>

                <!-- Back Action -->
                <button id="btn-back" class="group flex items-center gap-2 text-gray-400 hover:text-black transition-all font-bold text-[11px] uppercase tracking-[0.2em] disabled:opacity-0 disabled:pointer-events-none">
                    <?php echo esc_html__('Back', 'mbs-booking'); ?>
                </button>
            </div>
        </aside>

        <!-- MAIN AREA (70% WIDTH) - ACTIVE FLOW -->
        <section id="mbs-main-content" class="flex-grow flex flex-col bg-[#f9fafb]">
            <div class="w-full max-w-[800px] mx-auto p-4 md:p-12 lg:p-16 flex flex-col flex-grow">
                
                <!-- STEP 1: Package Selection -->
                <div id="step-packages" class="flex flex-col flex-grow">
                    <div class="mb-10">
                        <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3"><?php echo esc_html__('Step 1', 'mbs-booking'); ?></p>
                        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight"><?php echo esc_html__('Choose a Service', 'mbs-booking'); ?></h3>
                    </div>
                    
                    <div id="mbs-packages-container" class="grid grid-cols-1 gap-5">
                        <!-- Dynamic Packages -->
                    </div>
                </div>

                <!-- STEP 2: Calendar & Times -->
                <div id="step-datetime" class="hidden flex flex-col flex-grow">
                    <div class="mb-10">
                        <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3"><?php echo esc_html__('Step 2', 'mbs-booking'); ?></p>
                        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight"><?php echo esc_html__('Pick Date & Time', 'mbs-booking'); ?></h3>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-8 bg-white border border-gray-200/50 rounded-2xl p-6 shadow-sm overflow-hidden min-h-[500px]">
                        <!-- Calendar View -->
                        <div class=" lg:border-r border-gray-100 pr-0 lg:pr-8">
                            <div id="mbs-calendar-header-wrap" class="flex items-center justify-between mb-8">
                                <h2 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest leading-none">
                                    <span id="fp-month"></span> <span id="fp-year" class="font-normal opacity-60 ml-1"></span>
                                </h2>
                            </div>
                            <div id="mbs-calendar-container" class="mbs-flatpickr-custom">
                                <input id="mbs-datepicker" type="text" class="hidden">
                            </div>
                        </div>

                        <!-- Slots View -->
                        <div class="w-full lg:w-[45%] flex flex-col">
                            <h2 id="mbs-selected-day-label" class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-8 text-center italic leading-none"><?php echo esc_html__('Select a date', 'mbs-booking'); ?></h2>
                            
                            <div id="mbs-slots-container" class="grid grid-cols-2 md:grid-cols-3 gap-3 overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
                                <!-- Dynamic Slots -->
                                <div class="flex flex-col items-center justify-center h-full text-center py-10">
                                    <span class="material-icons-outlined text-gray-200 text-5xl mb-4">event</span>
                                    <p class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.2em]"><?php echo esc_html__('Select a date above', 'mbs-booking'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Details -->
                <div id="step-details" class="hidden flex flex-col flex-grow max-w-[550px]">
                    <div class="mb-10">
                        <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3"><?php echo esc_html__('Step 3', 'mbs-booking'); ?></p>
                        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight"><?php echo esc_html__('Your Details', 'mbs-booking'); ?></h3>
                    </div>

                    <form id="mbs-booking-form" class="space-y-6 animate-fade-in-up">
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block"><?php echo esc_html__('Full Name', 'mbs-booking'); ?></label>
                                <input type="text" id="mbs-customer-name" name="customer_name" required class="mbs-form-input w-full p-4 rounded-xl border-gray-100 bg-white shadow-sm transition-all focus:ring-2 focus:ring-black outline-none" placeholder="<?php echo esc_attr__('John Doe', 'mbs-booking'); ?>">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block"><?php echo esc_html__('Email Address', 'mbs-booking'); ?></label>
                                <input type="email" id="mbs-customer-email" name="customer_email" required class="mbs-form-input w-full p-4 rounded-xl border-gray-100 bg-white shadow-sm transition-all focus:ring-2 focus:ring-black outline-none" placeholder="<?php echo esc_attr__('john@example.com', 'mbs-booking'); ?>">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block"><?php echo esc_html__('Notes (Optional)', 'mbs-booking'); ?></label>
                                <textarea id="mbs-customer-notes" name="customer_notes" rows="4" class="mbs-form-input w-full p-4 rounded-xl border-gray-100 bg-white shadow-sm transition-all focus:ring-2 focus:ring-black outline-none resize-none" placeholder="<?php echo esc_attr__('Anything we should know?', 'mbs-booking'); ?>"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="mbs-confirm-btn w-full py-5 rounded-xl text-white font-black uppercase tracking-[0.2em] transition-all hover:scale-[1.02] active:scale-[0.98] shadow-lg"><?php echo esc_html__('Confirm Details', 'mbs-booking'); ?></button>
                    </form>
                </div>

                <!-- STEP 4: Payment -->
                <div id="step-payment" class="hidden flex flex-col flex-grow max-w-[800px] w-full mx-auto">
                    <div class="mb-10 text-center">
                        <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3"><?php echo esc_html__('Step 4', 'mbs-booking'); ?></p>
                        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight"><?php echo esc_html__('Review & Pay', 'mbs-booking'); ?></h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-fade-in-up">
                        <!-- Left: Order Summary -->
                        <div class="bg-white p-8 rounded-xl border border-gray-100 h-full">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-6"><?php echo esc_html__('Order Summary', 'mbs-booking'); ?></h4>
                            <div class="space-y-6">
                                <div>
                                    <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><?php echo esc_html__('Service', 'mbs-booking'); ?></p>
                                    <p id="mbs-pay-service-name" class="text-xl font-black text-gray-900"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><?php echo esc_html__('Date & Time', 'mbs-booking'); ?></p>
                                    <p id="mbs-pay-datetime" class="text-base font-bold text-gray-600"></p>
                                </div>
                                <div class="pt-6 border-t border-gray-200">
                                    <div class="flex justify-between items-end">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?php echo esc_html__('Total', 'mbs-booking'); ?></p>
                                        <p id="mbs-pay-total" class=" font-black text-gray-900"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Payment Form -->
                        <div class="bg-white p-8 rounded-xl border border-gray-100 shadow-lg h-full flex flex-col justify-center">
                            <div class="space-y-4 mb-8">
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block"><?php echo esc_html__('Card Number', 'mbs-booking'); ?></label>
                                    <div id="stripe-card-number" class="stripe-field-container"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block"><?php echo esc_html__('Expiry', 'mbs-booking'); ?></label>
                                        <div id="stripe-card-expiry" class="stripe-field-container"></div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block"><?php echo esc_html__('CVC', 'mbs-booking'); ?></label>
                                        <div id="stripe-card-cvc" class="stripe-field-container"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="mbs-payment-errors" class="text-red-500 text-[10px] font-bold uppercase tracking-widest mb-6 hidden"></div>
                            <button id="mbs-pay-button" class="mbs-confirm-btn w-full py-5 rounded-xl text-white font-black uppercase tracking-[0.2em] transition-all hover:scale-[1.02] shadow-xl"><?php echo esc_html__('Pay & Confirm', 'mbs-booking'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Success -->
                <div id="step-success" class="hidden flex flex-col items-center justify-center flex-grow py-10 animate-fade-in w-full max-w-[800px] mx-auto">
                    <div class="bg-white p-6 lg:p-14 rounded-[40px] shadow-[0_30px_70px_rgba(0,0,0,0.1)] border border-gray-100 w-full">
                        <div class="text-center mb-10">
                            <div class="w-16 h-16 mbs-success-icon rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="material-icons-outlined text-3xl">check</span>
                            </div>
                            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight mb-2"><?php echo esc_html__('Thank You!', 'mbs-booking'); ?></h1>
                            <p class="text-gray-500 font-medium"><?php echo esc_html__('Your booking has been successfully confirmed.', 'mbs-booking'); ?></p>
                        </div>

                        <div class="bg-gray-50/50 rounded-3xl p-8 mb-10 border border-gray-100/50">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-6 border-b border-gray-200 pb-4"><?php echo esc_html__('Booking Details', 'mbs-booking'); ?></h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><?php echo esc_html__('Service', 'mbs-booking'); ?></p>
                                        <p id="mbs-final-service-name" class="text-lg font-black text-gray-900"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><?php echo esc_html__('Customer', 'mbs-booking'); ?></p>
                                        <p id="mbs-final-customer-name" class="text-base font-bold text-gray-600"></p>
                                        <p id="mbs-final-customer-email" class="text-sm text-gray-400"></p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><?php echo esc_html__('Date & Time', 'mbs-booking'); ?></p>
                                        <p id="mbs-final-datetime" class="text-base font-bold text-gray-900"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1"><?php echo esc_html__('Status', 'mbs-booking'); ?></p>
                                        <div class="inline-flex items-center py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-black uppercase">
                                            <?php echo esc_html__('Paid & Confirmed', 'mbs-booking'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button onclick="window.location.reload()" class="w-full bg-gray-900 py-5 rounded-xl text-white font-black uppercase tracking-[0.2em] hover:bg-black transition-all shadow-lg hover:shadow-xl"><?php echo esc_html__('Book Another Appointment', 'mbs-booking'); ?></button>
                    </div>
                </div>

            </div>
        </section>

    </main>
</div>
