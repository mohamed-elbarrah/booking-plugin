<?php
/**
 * Settings page template (presentation only).
 * Expects $settings = array of current values.
 */
if (!defined('ABSPATH')) {
  exit;
}

$settings = isset($settings) && is_array($settings) ? $settings : [];
?>
<div class="p-6 bg-white min-h-screen">
  <div class="max-w-7xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Booking App Settings</h1>
      <p class="text-sm text-gray-600">Configure integrations, availability, and payments.</p>
    </div>

    <form method="post" action="options.php" id="booking-app-settings-form">
      <?php settings_fields('booking_app'); ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="col-span-2">
          <!-- Tabs -->
          <div>
            <ul class="flex flex-wrap -mb-px" role="tablist">
              <li role="presentation" class="mr-2">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-general" type="button">General</button>
              </li>
              <li role="presentation" class="mr-2">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-google" type="button">Google Calendar</button>
              </li>
              <li role="presentation" class="mr-2">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-availability" type="button">Availability & Scheduling</button>
              </li>
              <li role="presentation">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-payments" type="button">Payment Gateways</button>
              </li>
            </ul>

            <div class="mt-4">
              <!-- General -->
              <div id="tab-general" class="tab-panel">
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                  <h2 class="text-lg font-medium mb-4">General Settings</h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Business Name</label>
                      <input name="booking_app_settings[business_name]" value="<?php echo esc_attr($settings['business_name'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <p class="text-xs text-gray-500">Displayed in emails and invoices.</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Admin Email</label>
                      <input name="booking_app_settings[admin_email]" value="<?php echo esc_attr($settings['admin_email'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <p class="text-xs text-gray-500">Notifications will be sent to this address.</p>
                    </div>
                  </div>

                  <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Business Logo</label>
                    <div class="flex items-center gap-4 mt-2">
                      <?php
$logo_url = $settings['business_logo_url'] ?? '';
?>
                      <div id="booking-logo-preview-wrap" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                        <img id="booking-logo-preview" src="<?php echo esc_url($logo_url); ?>" alt="Business Logo" style="height:64px;width:64px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;" />
                      </div>
                      <div>
                        <button type="button" id="booking-logo-select-btn" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-50 transition-all shadow-sm">
                          <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                          <?php echo $logo_url ? 'Change Logo' : 'Select Logo'; ?>
                        </button>
                        <?php if ($logo_url): ?>
                          <button type="button" id="booking-logo-remove-btn" class="ml-2 text-xs text-red-500 hover:text-red-700">Remove</button>
                        <?php
endif; ?>
                      </div>
                    </div>
                    <input type="hidden" name="booking_app_settings[business_logo_url]" id="booking-logo-url-input" value="<?php echo esc_attr($logo_url); ?>" />
                    <p class="text-xs text-gray-500 mt-1">Displayed on the booking form. Recommended: square image, 400×400px.</p>
                  </div>
                  <?php wp_enqueue_media(); ?>
                  <script>
                  (function($){
                    var mediaUploader;
                    $('#booking-logo-select-btn').on('click', function(e){
                      e.preventDefault();
                      if(mediaUploader){ mediaUploader.open(); return; }
                      mediaUploader = wp.media({ title: 'Select Business Logo', button: { text: 'Use this image' }, multiple: false });
                      mediaUploader.on('select', function(){
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#booking-logo-url-input').val(attachment.url);
                        $('#booking-logo-preview').attr('src', attachment.url);
                        $('#booking-logo-preview-wrap').show();
                        $('#booking-logo-select-btn').text('Change Logo');
                      });
                      mediaUploader.open();
                    });
                    $(document).on('click','#booking-logo-remove-btn', function(){
                      $('#booking-logo-url-input').val('');
                      $('#booking-logo-preview-wrap').hide();
                      $('#booking-logo-select-btn').text('Select Logo');
                    });
                  })(jQuery);
                  </script>

                  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Currency</label>
                      <select name="booking_app_settings[currency]" class="flow-input mt-1 block w-full">
                        <option value="USD" <?php selected($settings['currency'] ?? '', 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected($settings['currency'] ?? '', 'EUR'); ?>>EUR</option>
                        <option value="GBP" <?php selected($settings['currency'] ?? '', 'GBP'); ?>>GBP</option>
                      </select>
                      <p class="text-xs text-gray-500">Site currency for pricing.</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Timezone</label>
                      <select name="booking_app_settings[timezone]" class="flow-input mt-1 block w-full">
                        <?php
$zones = timezone_identifiers_list();
foreach ($zones as $tz) {
  printf('<option value="%s" %s>%s</option>', esc_attr($tz), selected($settings['timezone'] ?? '', $tz, false), esc_html($tz));
}
?>
                      </select>
                      <p class="text-xs text-gray-500">All booking times are stored in UTC; timezone used for display.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Google -->
              <div id="tab-google" class="hidden tab-panel">
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                  <h2 class="text-lg font-medium mb-4">Google Calendar Integration</h2>
                  <div class="mb-4">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm font-medium">Connection Status</p>
                        <p class="text-xs text-gray-500">Shows if a Google account is connected.</p>
                      </div>
                      <div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Connected</span>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Client ID</label>
                      <input name="booking_app_settings[google][client_id]" value="<?php echo esc_attr($settings['google']['client_id'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <p class="text-xs text-gray-500">Find this in Google Cloud Console.</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Client Secret</label>
                      <input name="booking_app_settings[google][client_secret]" value="<?php echo esc_attr($settings['google']['client_secret'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <p class="text-xs text-gray-500">Keep this secret secure.</p>
                    </div>
                  </div>

                  <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Redirect URI</label>
                    <input readonly value="<?php echo esc_attr(site_url('/?booking_app_google_callback=1')); ?>" class="flow-input flow-input--readonly mt-1 block w-full" />
                    <p class="text-xs text-gray-500">Add this URI to your Google OAuth consent settings.</p>
                  </div>

                  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="flex items-center">
                        <input type="checkbox" name="booking_app_settings[google][two_way]" value="1" <?php checked($settings['google']['two_way'] ?? '', '1'); ?> class="mr-2" />
                        <span class="text-sm">Enable Two-Way Sync</span>
                      </label>
                    </div>
                    <div>
                      <label class="flex items-center">
                        <input type="checkbox" name="booking_app_settings[google][auto_meeting]" value="1" <?php checked($settings['google']['auto_meeting'] ?? '', '1'); ?> class="mr-2" />
                        <span class="text-sm">Auto-generate Meeting Links</span>
                      </label>
                    </div>
                  </div>

                  <div class="mt-6">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600">Connect Google Account</button>
                  </div>
                </div>
              </div>

              <!-- Availability -->
              <div id="tab-availability" class="hidden tab-panel">
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                  <h2 class="text-lg font-medium mb-4">Availability & Scheduling</h2>
                  <div class="space-y-4">
                    <?php
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days as $i => $day):
  $enabled = isset($settings['availability'][$i]['enabled']) ? $settings['availability'][$i]['enabled'] : false;
?>
                      <div class="flex items-center justify-between p-4 bg-white rounded-md border">
                        <div>
                          <p class="font-medium"><?php echo esc_html($day); ?></p>
                        </div>
                        <div class="flex items-center space-x-4">
                          <label class="text-sm">Enabled</label>
                          <input type="checkbox" name="booking_app_settings[availability][<?php echo $i; ?>][enabled]" value="1" <?php checked($enabled, '1'); ?> />
                          <label class="text-sm">Start</label>
                          <input type="time" name="booking_app_settings[availability][<?php echo $i; ?>][start]" value="<?php echo esc_attr($settings['availability'][$i]['start'] ?? '09:00'); ?>" class="flow-input--time" />
                          <label class="text-sm">End</label>
                          <input type="time" name="booking_app_settings[availability][<?php echo $i; ?>][end]" value="<?php echo esc_attr($settings['availability'][$i]['end'] ?? '17:00'); ?>" class="flow-input--time" />
                          <button type="button" class="ml-4 inline-flex text-sm text-blue-600 add-break-btn" data-day-index="<?php echo $i; ?>">Add Break</button>
                        </div>
                      </div>
                      <div class="mt-2">
                        <div class="breaks-list" data-day-index="<?php echo $i; ?>">
                          <!-- Break rows for day <?php echo $i; ?> will be inserted here -->
                        </div>
                      </div>
                    <?php
endforeach; ?>
                  </div>
                </div>
              </div>

              <!-- Payments -->
              <div id="tab-payments" class="hidden tab-panel">
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                  <h2 class="text-lg font-medium mb-4">Payment Gateways</h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-white rounded border">
                      <h3 class="font-medium mb-2">Stripe</h3>
                      <label class="block text-sm">Publishable Key</label>
                      <input name="booking_app_settings[payments][stripe][publishable]" value="<?php echo esc_attr($settings['payments']['stripe']['publishable'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <label class="block text-sm mt-2">Secret Key</label>
                      <input name="booking_app_settings[payments][stripe][secret]" value="<?php echo esc_attr($settings['payments']['stripe']['secret'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <label class="flex items-center mt-2"><input type="checkbox" name="booking_app_settings[payments][stripe][sandbox]" value="1" <?php checked($settings['payments']['stripe']['sandbox'] ?? '', '1'); ?> class="mr-2" /> Sandbox Mode</label>
                    </div>

                    <div class="p-4 bg-white rounded border">
                      <h3 class="font-medium mb-2">PayPal</h3>
                      <label class="block text-sm">Client ID</label>
                      <input name="booking_app_settings[payments][paypal][client_id]" value="<?php echo esc_attr($settings['payments']['paypal']['client_id'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <label class="block text-sm mt-2">Secret</label>
                      <input name="booking_app_settings[payments][paypal][secret]" value="<?php echo esc_attr($settings['payments']['paypal']['secret'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <label class="flex items-center mt-2"><input type="checkbox" name="booking_app_settings[payments][paypal][sandbox]" value="1" <?php checked($settings['payments']['paypal']['sandbox'] ?? '', '1'); ?> class="mr-2" /> Sandbox Mode</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Sticky Save Bar -->
      <div class="fixed left-0 right-0 bottom-0 bg-white border-t py-3 px-6 flex justify-end items-center z-40">
        <?php submit_button(__('Save Changes', 'booking-app')); ?>
      </div>
    </form>
    
    <!-- Hidden template for a break row -->
    <template id="booking-app-break-row-template">
      <div class="break-row" style="display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem">
        <input type="time" data-name-start name="__start__" class="flow-input--time" />
        <input type="time" data-name-end name="__end__" class="flow-input--time" />
        <button type="button" class="remove-break-btn text-sm" style="color:#dc2626;background:none;border:none;">Remove</button>
      </div>
    </template>
  </div>
</div>
