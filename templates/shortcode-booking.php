<?php
if (!defined('ABSPATH')) {
    exit;
}

// Feature Flag: If MBS_USE_NATIVE_CHECKOUT is true, we simply render the WooCommerce checkout shortcode 
// if we have a pending booking in session. 
// However, the main app is still needed for step 1-3. 
// If the user is on the checkout page (after redirect), they shouldn't see the booking app again 
// unless we want them to. 

if (defined('MBS_USE_NATIVE_CHECKOUT') && MBS_USE_NATIVE_CHECKOUT) {
    // Check if we are in the "checkout" phase of our app
    // We can detect this if WC cart is not empty and our session variable is set
    if (class_exists('WooCommerce') && !empty(WC()->cart) && !WC()->cart->is_empty() && WC()->session && WC()->session->get('mbs_pending_booking_id')) {
        echo '<div class="mbs-native-checkout-wrapper p-8 bg-white rounded-xl shadow-lg border border-gray-100 max-w-4xl mx-auto my-10">';
        echo '<h2 class="text-2xl font-bold mb-6 text-gray-900">' . esc_html__('Complete Your Booking', 'mbs-booking') . '</h2>';
        echo do_shortcode('[woocommerce_checkout]');
        echo '</div>';
        return; // Stop rendering the booking app UI
    }
}
?>
<!-- Stitch Redesign: Cal.com Inspired Multi-Panel Layout -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<div id="mbs-booking-app" class="mbs-booking-wrap min-h-screen flex items-center justify-center p-4 sm:p-8 transition-colors duration-200 <?php echo is_rtl() ? 'mbs-rtl' : ''; ?>">
    
    <!-- Main Application Container -->
    <main id="mbs-main-container" class="w-full max-w-5xl bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden flex flex-col md:flex-row min-h-[600px] animate-fade-in">
        
        <!-- SIDEBAR: Static/Persistent Info (Updated via JS) -->
        <aside id="mbs-sidebar" class="mbs-sidebar w-full md:w-1/3 border-b md:border-b-0 md:border-r border-gray-200 p-6 lg:p-8 flex flex-col gap-6">
            <div class="flex flex-col gap-1">
                <div id="mbs-sidebar-logo-wrap" class="relative w-12 h-12 mb-2 hidden">
                    <img id="mbs-sidebar-logo" src="" alt="<?php echo esc_attr__('Business Logo', 'mbs-booking'); ?>" class="mbs-sidebar-avatar rounded-full w-full h-full object-cover shadow-sm">
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                </div>
                <h2 id="mbs-sidebar-business-name" class="text-sm font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Business Name', 'mbs-booking'); ?></h2>
                <h1 id="mbs-sidebar-title" class="text-2xl font-bold text-gray-900 tracking-tight"><?php echo esc_html__('Consultation', 'mbs-booking'); ?></h1>
            </div>

            <div id="mbs-sidebar-meta" class="space-y-4">
                <!-- Duration -->
                <div id="mbs-sidebar-duration-wrap" class="flex items-center gap-3 text-gray-600 hidden">
                    <span class="material-icons-outlined text-gray-400 text-lg">schedule</span>
                    <span id="mbs-sidebar-duration-text" class="font-medium text-sm"><?php echo esc_html__('30 min', 'mbs-booking'); ?></span>
                </div>
                <!-- Location/Video -->
                <div class="flex items-center gap-3 text-gray-600">
                    <span class="material-icons-outlined text-gray-400 text-lg">videocam</span>
                    <span class="font-medium text-sm"><?php echo esc_html__('Video Call', 'mbs-booking'); ?></span>
                </div>
                <!-- Timezone -->
                <div class="flex items-center gap-3 text-gray-600">
                    <span class="material-icons-outlined text-gray-400 text-lg">public</span>
                    <span id="mbs-sidebar-timezone" class="font-medium text-sm"><?php echo esc_html(get_option('timezone_string') ?: 'UTC'); ?></span>
                </div>
            </div>

            <!-- Description (Visible on Step 1 & 2) -->
            <div id="mbs-sidebar-description-wrap" class="mt-4 pt-4 border-t border-gray-100">
                <p id="mbs-sidebar-description" class="text-sm text-gray-500 leading-relaxed italic">
                    <?php echo esc_html__('Select a consultation package that best suits your needs.', 'mbs-booking'); ?>
                </p>
            </div>

            <div id="mbs-sidebar-nav" class="mt-auto pt-6 border-t border-gray-50 hidden">
                <button id="btn-back" class="text-gray-400 hover:text-gray-900 transition-colors flex items-center gap-2 text-sm font-bold disabled:opacity-30">
                    <span class="material-icons-outlined text-lg"><?php echo is_rtl() ? 'chevron_right' : 'chevron_left'; ?></span> <?php echo esc_html__('Back', 'mbs-booking'); ?>
                </button>
            </div>
        </aside>

        <!-- MAIN SECTION: Steps Content -->
        <section id="mbs-main-content" class="w-full md:w-2/3 flex flex-col">
            
            <!-- STEP 1: Package Selection -->
            <div id="step-packages" class="p-6 lg:p-10 overflow-y-auto flex-grow">
                <h3 class="text-lg font-bold text-gray-900 mb-6"><?php echo esc_html__('Select a Package', 'mbs-booking'); ?></h3>
                <div id="mbs-packages-container" class="grid grid-cols-1 gap-4">
                    <!-- Dynamic Packages injected here -->
                    <div class="animate-pulse flex flex-col gap-4">
                        <div class="h-24 bg-gray-50 rounded-lg"></div>
                        <div class="h-24 bg-gray-50 rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Calendar & Times -->
            <div id="step-datetime" class="hidden flex flex-col md:flex-row h-full flex-grow overflow-hidden">
                <!-- Calendar Panel -->
                <div class="w-full md:w-[60%] p-6 lg:p-8 border-b md:border-b-0 md:border-r border-gray-100">
                    <div id="mbs-calendar-header-wrap" class="flex items-center justify-between mb-6">
                        <h2 class="mbs-calendar-header text-gray-900">
                            <span id="fp-month"><?php echo esc_html__('Month', 'mbs-booking'); ?></span> <span id="fp-year" class="font-normal text-gray-400"><?php echo esc_html__('Year', 'mbs-booking'); ?></span>
                        </h2>
                        <div class="flex gap-1" id="mbs-datepicker-nav">
                            <!-- Custom nav injected by Flatpickr/JS if needed -->
                        </div>
                    </div>
                    <div id="mbs-calendar-container" class="w-full">
                        <input id="mbs-datepicker" type="text" class="hidden">
                    </div>
                </div>
                <!-- Slots Panel -->
                <div class="w-full md:w-[40%] p-6 lg:p-8 flex flex-col h-[500px] md:h-full">
                    <div class="flex items-center justify-between mb-6">
                        <span id="mbs-selected-day-label" class="text-gray-900 font-medium italic"><?php echo esc_html__('Select a date', 'mbs-booking'); ?></span>
                        <div class="bg-gray-100 rounded-md p-1 flex text-xs">
                            <button class="px-3 py-1 bg-white shadow-sm rounded-md text-gray-900 font-medium">12h</button>
                        </div>
                    </div>
                    <div id="mbs-slots-container" class="flex-1 overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                        <p class="text-gray-400 text-sm py-20 italic text-center"><?php printf(esc_html__('Found %s slots', 'mbs-booking'), '<span id="mbs-slot-count">0</span>'); ?></p>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Confirm Details -->
            <div id="step-details" class="hidden p-6 lg:p-10 flex-grow">
                <div class="max-w-lg mx-auto w-full">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2"><?php echo esc_html__('Enter Details', 'mbs-booking'); ?></h2>
                    <p class="text-sm text-gray-500 mb-8 leading-snug"><?php echo esc_html__('Please share anything that will help prepare for our meeting.', 'mbs-booking'); ?></p>
                    
                    <form id="mbs-booking-form" class="space-y-6">
                        <div class="grid grid-cols-1 gap-5">
                            <div class="space-y-1.5 text-left">
                                <label class="block text-sm font-semibold text-gray-900" for="customer_name"><?php echo esc_html__('Name', 'mbs-booking'); ?></label>
                                <input class="mbs-form-input block w-full rounded-lg border-gray-200 bg-white text-gray-900 shadow-sm focus:border-black focus:ring-black sm:text-sm py-2.5 px-3 transition-colors" 
                                    id="customer_name" name="customer_name" placeholder="<?php echo esc_attr__('John Doe', 'mbs-booking'); ?>" type="text" required>
                            </div>
                            <div class="space-y-1.5 text-left">
                                <label class="block text-sm font-semibold text-gray-900" for="email"><?php echo esc_html__('Email address', 'mbs-booking'); ?></label>
                                <div class="relative rounded-lg shadow-sm">
                                    <div class="pointer-events-none absolute inset-y-0 <?php echo is_rtl() ? 'right-0 pr-3' : 'left-0 pl-3'; ?> flex items-center">
                                        <span class="material-icons-outlined text-gray-400 text-lg">mail</span>
                                    </div>
                                    <input class="mbs-form-input block w-full rounded-lg border-gray-200 bg-white text-gray-900 shadow-sm focus:border-black focus:ring-black sm:text-sm py-2.5 <?php echo is_rtl() ? 'pr-10 pl-3' : 'pl-10 pr-3'; ?> transition-colors" 
                                        id="email" name="email" placeholder="<?php echo esc_attr__('you@example.com', 'mbs-booking'); ?>" type="email" required>
                                </div>
                            </div>
                            <div class="space-y-1.5 text-left">
                                <label class="block text-sm font-semibold text-gray-900" for="customer_phone"><?php echo esc_html__('Phone', 'mbs-booking'); ?></label>
                                <input class="mbs-form-input block w-full rounded-lg border-gray-200 bg-white text-gray-900 shadow-sm focus:border-black focus:ring-black sm:text-sm py-2.5 px-3 transition-colors" 
                                    id="customer_phone" name="customer_phone" placeholder="<?php echo esc_attr__('e.g. +212600000000', 'mbs-booking'); ?>" type="tel">
                            </div>
                            <div class="space-y-1.5 text-left">
                                <label class="block text-sm font-semibold text-gray-900" for="customer_country"><?php echo esc_html__('Country', 'mbs-booking'); ?></label>
                                <select id="customer_country" name="customer_country" class="mbs-form-input block w-full rounded-lg border-gray-200 bg-white text-gray-900 shadow-sm focus:border-black focus:ring-black sm:text-sm py-2.5 px-3 transition-colors">
                                    <option value=""><?php echo esc_html__('Select country', 'mbs-booking'); ?></option>
                                    <option value="US"><?php echo esc_html__('United States', 'mbs-booking'); ?></option>
                                    <option value="GB"><?php echo esc_html__('United Kingdom', 'mbs-booking'); ?></option>
                                    <option value="MA"><?php echo esc_html__('Morocco', 'mbs-booking'); ?></option>
                                    <option value="SA"><?php echo esc_html__('Saudi Arabia', 'mbs-booking'); ?></option>
                                    <option value="AE"><?php echo esc_html__('United Arab Emirates', 'mbs-booking'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-1.5 text-left">
                            <label class="block text-sm font-semibold text-gray-900" for="notes">
                                <?php echo esc_html__('Additional notes', 'mbs-booking'); ?> <span class="text-gray-400 font-normal ml-1"><?php echo esc_html__('Optional', 'mbs-booking'); ?></span>
                            </label>
                            <textarea class="mbs-form-input block w-full rounded-lg border-gray-200 bg-white text-gray-900 shadow-sm focus:border-black focus:ring-black sm:text-sm py-2.5 px-3 resize-none transition-colors" 
                                id="notes" name="notes" placeholder="<?php echo esc_attr__('Please share anything that will help prepare for our meeting.', 'mbs-booking'); ?>" rows="4"></textarea>
                        </div>
                        
                        <!-- Navigation (Internal to Step 3 Form) -->
                        <div class="pt-6 flex items-center justify-end gap-3 border-t border-gray-50 mt-8">
                            <button type="button" id="mbs-btn-back-s3" class="px-6 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                                <?php echo esc_html__('Back', 'mbs-booking'); ?>
                            </button>
                            <button type="submit" class="mbs-confirm-btn inline-flex justify-center rounded-lg border border-transparent py-2.5 px-8 text-sm font-bold text-white shadow-xl hover:shadow-gray-200 focus:outline-none focus:ring-2 focus:ring-black transition-all">
                                <?php echo esc_html__('Confirm Booking', 'mbs-booking'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STEP 4: Payment -->
            <div id="step-payment" class="hidden p-6 lg:p-10 flex-grow">
                <div id="mbs-native-checkout-container" class="hidden">
                     <div class="mbs-native-checkout-inner p-4 bg-white rounded-xl shadow-lg border border-gray-100 max-w-4xl mx-auto my-10">
                        <h2 class="text-2xl font-bold mb-6 text-gray-900"><?php echo esc_html__('Complete Your Booking', 'mbs-booking'); ?></h2>
                        <?php echo do_shortcode('[woocommerce_checkout]'); ?>
                    </div>
                </div>

                <div id="mbs-custom-payment-container" class="max-w-lg mx-auto w-full text-center">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2"><?php echo esc_html__('Complete Payment', 'mbs-booking'); ?></h2>
                    <p class="text-sm text-gray-500 mb-8"><?php echo esc_html__('Secure your booking by completing the payment via WooCommerce.', 'mbs-booking'); ?></p>
                    
                    <div id="mbs-payment-summary" class="bg-gray-50 rounded-xl p-6 mb-8 text-left">
                        <!-- Summary injected via JS -->
                    </div>

                    <form id="mbs-payment-form" class="checkout woocommerce-checkout">
                        <div id="mbs-payment-methods" class="mb-8 space-y-3 text-left">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wider"><?php echo esc_html__('Select Payment Method', 'mbs-booking'); ?></h4>
                            <!-- Gateways dynamicly injected -->
                        </div>
                    </form>

                    <div id="mbs-woocommerce-payment-container" class="min-h-[200px] flex items-center justify-center border-t border-gray-100 pt-8">
                        <div class="flex flex-col w-full gap-3">
                            <button id="mbs-pay-now-btn" class="mbs-confirm-btn w-full py-4 px-8 text-lg font-bold text-white shadow-xl hover:shadow-gray-200 focus:outline-none focus:ring-2 focus:ring-black transition-all rounded-xl">
                                <?php echo esc_html__('Pay & Confirm Booking', 'mbs-booking'); ?>
                            </button>
                            <button type="button" id="mbs-btn-back-s4" class="text-gray-500 hover:text-gray-900 transition-colors text-sm font-semibold">
                                <?php echo esc_html__('Use a different time or details', 'mbs-booking'); ?>
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mt-6"><?php echo esc_html__('Powered by WooCommerce. Your transaction is secure and encrypted.', 'mbs-booking'); ?></p>
                </div>
            </div>

            <!-- STEP 5: Success Message -->
            <div id="step-success" class="hidden p-6 lg:p-10 flex-grow flex flex-col items-center justify-center text-center animate-fade-in">
                <div class="mbs-success-icon mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-8 shadow-inner">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900 mb-3 tracking-tight"><?php echo esc_html__('Booking Confirmed!', 'mbs-booking'); ?></h2>
                <p class="text-gray-500 max-w-sm mx-auto mb-10 text-lg leading-relaxed font-medium"><?php echo esc_html__('Your appointment is scheduled. Check your email for details.', 'mbs-booking'); ?></p>
                <div class="grid grid-cols-1 gap-4 w-full max-w-xs mx-auto">
                    <button onclick="location.reload()" class="w-full py-3.5 px-6 bg-gray-900 text-white rounded-xl font-bold shadow-2xl shadow-gray-200 hover:bg-black transition-all active:scale-95"><?php echo esc_html__('Book Another session', 'mbs-booking'); ?></button>
                </div>
            </div>
            
        </section>
    </main>

    <!-- Mobile Hint -->
    <div id="mbs-scroll-hint" class="mbs-mobile-scroll-hint fixed bottom-4 left-0 right-0 md:hidden flex justify-center pointer-events-none hidden transition-opacity">
        <span class="text-white text-[10px] font-bold py-1 px-3 rounded-full uppercase tracking-tighter"><?php echo esc_html__('Scroll for options', 'mbs-booking'); ?></span>
    </div>
</div>
