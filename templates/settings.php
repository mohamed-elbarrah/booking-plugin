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
      <h1 class="text-2xl font-semibold text-gray-900"><?php echo esc_html__('Booking App Settings', 'mbs-booking'); ?></h1>
      <p class="text-sm text-gray-600"><?php echo esc_html__('Configure integrations, availability, and payments.', 'mbs-booking'); ?></p>
    </div>

    <form method="post" action="options.php" id="booking-app-settings-form">
      <?php settings_fields('booking_app'); ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="col-span-2">
          <!-- Tabs -->
          <div>
            <ul class="flex flex-wrap -mb-px" role="tablist">
              <li role="presentation" class="mr-2">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-general" type="button"><?php echo esc_html__('General', 'mbs-booking'); ?></button>
              </li>
              <li role="presentation" class="mr-2">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-google" type="button"><?php echo esc_html__('Google Calendar', 'mbs-booking'); ?></button>
              </li>
              <li role="presentation" class="mr-2">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-availability" type="button"><?php echo esc_html__('Availability & Scheduling', 'mbs-booking'); ?></button>
              </li>
              <li role="presentation">
                <button class="inline-block py-2 px-4 text-sm font-medium text-gray-700 bg-white rounded-t-lg border-b-2 border-transparent" data-tab-target="#tab-payments" type="button"><?php echo esc_html__('Payment Gateways', 'mbs-booking'); ?></button>
              </li>
            </ul>

            <div class="mt-4">
              <!-- General -->
              <div id="tab-general" class="tab-panel">
                <div class="bg-gray-50 p-6 rounded-lg shadow">
                  <h2 class="text-lg font-medium mb-4"><?php echo esc_html__('General Settings', 'mbs-booking'); ?></h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700"><?php echo esc_html__('Business Name', 'mbs-booking'); ?></label>
                      <input name="booking_app_settings[business_name]" value="<?php echo esc_attr($settings['business_name'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <p class="text-xs text-gray-500"><?php echo esc_html__('Displayed in emails and invoices.', 'mbs-booking'); ?></p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700"><?php echo esc_html__('Admin Email', 'mbs-booking'); ?></label>
                      <input name="booking_app_settings[admin_email]" value="<?php echo esc_attr($settings['admin_email'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <p class="text-xs text-gray-500"><?php echo esc_html__('Notifications will be sent to this address.', 'mbs-booking'); ?></p>
                    </div>
                  </div>

                  <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700"><?php echo esc_html__('Business Logo', 'mbs-booking'); ?></label>
                    <input type="file" name="booking_app_logo" class="flow-input-file mt-1" />
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Upload a square logo (recommended 400x400).', 'mbs-booking'); ?></p>
                  </div>

                  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700"><?php echo esc_html__('Currency', 'mbs-booking'); ?></label>
                      <select name="booking_app_settings[currency]" class="flow-input mt-1 block w-full">
                        <option value="USD" <?php selected($settings['currency'] ?? '', 'USD'); ?>><?php echo esc_html__('USD', 'mbs-booking'); ?></option>
                        <option value="EUR" <?php selected($settings['currency'] ?? '', 'EUR'); ?>><?php echo esc_html__('EUR', 'mbs-booking'); ?></option>
                        <option value="GBP" <?php selected($settings['currency'] ?? '', 'GBP'); ?>><?php echo esc_html__('GBP', 'mbs-booking'); ?></option>
                      </select>
                      <p class="text-xs text-gray-500"><?php echo esc_html__('Site currency for pricing.', 'mbs-booking'); ?></p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700"><?php echo esc_html__('Timezone', 'mbs-booking'); ?></label>
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
                  <h2 class="text-lg font-medium mb-4"><?php echo esc_html__('Availability & Scheduling', 'mbs-booking'); ?></h2>
                  <div class="space-y-4">
                    <?php
$days = [
  __('Monday', 'mbs-booking'),
  __('Tuesday', 'mbs-booking'),
  __('Wednesday', 'mbs-booking'),
  __('Thursday', 'mbs-booking'),
  __('Friday', 'mbs-booking'),
  __('Saturday', 'mbs-booking'),
  __('Sunday', 'mbs-booking')
];
foreach ($days as $i => $day):
  $enabled = isset($settings['availability'][$i]['enabled']) ? $settings['availability'][$i]['enabled'] : false;
?>
                      <div class="flex items-center justify-between p-4 bg-white rounded-md border">
                        <div>
                          <p class="font-medium"><?php echo esc_html($day); ?></p>
                        </div>
                        <div class="flex items-center space-x-4">
                          <label class="text-sm"><?php echo esc_html__('Enabled', 'mbs-booking'); ?></label>
                          <input type="checkbox" name="booking_app_settings[availability][<?php echo $i; ?>][enabled]" value="1" <?php checked($enabled, '1'); ?> />
                          <label class="text-sm"><?php echo esc_html__('Start', 'mbs-booking'); ?></label>
                          <input type="time" name="booking_app_settings[availability][<?php echo $i; ?>][start]" value="<?php echo esc_attr($settings['availability'][$i]['start'] ?? '09:00'); ?>" class="flow-input--time" />
                          <label class="text-sm"><?php echo esc_html__('End', 'mbs-booking'); ?></label>
                          <input type="time" name="booking_app_settings[availability][<?php echo $i; ?>][end]" value="<?php echo esc_attr($settings['availability'][$i]['end'] ?? '17:00'); ?>" class="flow-input--time" />
                          <button type="button" class="ml-4 inline-flex text-sm text-blue-600 add-break-btn" data-day-index="<?php echo $i; ?>"><?php echo esc_html__('Add Break', 'mbs-booking'); ?></button>
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
                  <h2 class="text-lg font-medium mb-4"><?php echo esc_html__('Payment Gateways', 'mbs-booking'); ?></h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-white rounded border">
                      <h3 class="font-medium mb-2"><?php echo esc_html__('Stripe', 'mbs-booking'); ?></h3>
                      <div class="space-y-3">
                        <div>
                          <label class="block text-sm"><?php echo esc_html__('Publishable Key', 'mbs-booking'); ?></label>
                          <input name="booking_app_settings[payments][stripe][publishable]" value="<?php echo esc_attr($settings['payments']['stripe']['publishable'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                        </div>
                        <div>
                          <label class="block text-sm"><?php echo esc_html__('Secret Key', 'mbs-booking'); ?></label>
                          <input type="password" name="booking_app_settings[payments][stripe][secret]" value="" placeholder="********" class="flow-input mt-1 block w-full" />
                          <p class="text-xs text-gray-500">Leave blank to keep existing key (Encrypted).</p>
                        </div>
                        <div>
                          <label class="block text-sm"><?php echo esc_html__('Webhook Secret', 'mbs-booking'); ?></label>
                          <input type="password" name="booking_app_settings[payments][stripe][webhook_secret]" value="" placeholder="********" class="flow-input mt-1 block w-full" />
                          <p class="text-xs text-gray-500">Webhook URL: <code><?php echo esc_url(get_rest_url(null, 'my-booking/v1/webhook')); ?></code></p>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                          <label class="flex items-center">
                            <input type="checkbox" name="booking_app_settings[payments][stripe][sandbox]" value="1" <?php checked($settings['payments']['stripe']['sandbox'] ?? '', '1'); ?> class="mr-2" /> 
                            <?php echo esc_html__('Test Mode', 'mbs-booking'); ?>
                          </label>
                          <button type="button" id="mbs-test-stripe" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                            <?php echo esc_html__('Test Connection', 'mbs-booking'); ?>
                          </button>
                        </div>
                        <div id="mbs-stripe-test-result" class="mt-2 hidden text-xs p-2 rounded"></div>
                      </div>
                    </div>

                    <div class="p-4 bg-white rounded border">
                      <h3 class="font-medium mb-2"><?php echo esc_html__('PayPal', 'mbs-booking'); ?></h3>
                      <label class="block text-sm"><?php echo esc_html__('Client ID', 'mbs-booking'); ?></label>
                      <input name="booking_app_settings[payments][paypal][client_id]" value="<?php echo esc_attr($settings['payments']['paypal']['client_id'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <label class="block text-sm mt-2"><?php echo esc_html__('Secret', 'mbs-booking'); ?></label>
                      <input name="booking_app_settings[payments][paypal][secret]" value="<?php echo esc_attr($settings['payments']['paypal']['secret'] ?? ''); ?>" class="flow-input mt-1 block w-full" />
                      <label class="flex items-center mt-2"><input type="checkbox" name="booking_app_settings[payments][paypal][sandbox]" value="1" <?php checked($settings['payments']['paypal']['sandbox'] ?? '', '1'); ?> class="mr-2" /> <?php echo esc_html__('Sandbox Mode', 'mbs-booking'); ?></label>
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
        <?php submit_button(__('Save Changes', 'mbs-booking')); ?>
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
