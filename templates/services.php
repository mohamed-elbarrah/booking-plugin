<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap booking-app-wrap">
    <!-- Toast Notification -->
    <div id="toast-container" class="fixed top-5 right-5 z-[100] flex flex-col gap-2"></div>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><?php esc_html_e('Services Management', 'booking-app'); ?></h1>
        <button id="add-service-btn" class="block text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" type="button">
            <?php esc_html_e('Add New Service', 'booking-app'); ?>
        </button>
    </div>

    <!-- Services Grid/List -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Service Name', 'booking-app'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Duration', 'booking-app'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Price', 'booking-app'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Status', 'booking-app'); ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Actions', 'booking-app'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="services-list">
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                <?php esc_html_e('No services created yet.', 'booking-app'); ?>
                            </td>
                        </tr>
                    <?php
else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr data-service-id="<?php echo esc_attr($service->id); ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo esc_html($service->name); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo esc_html($service->duration); ?> <?php esc_html_e('min', 'booking-app'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if (floatval($service->price) <= 0): ?>
                                        <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded border border-green-400"><?php esc_html_e('Free', 'booking-app'); ?></span>
                                    <?php
        else: ?>
                                        $<?php echo esc_html(number_format($service->price, 2)); ?>
                                    <?php
        endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" value="" class="sr-only peer status-toggle" data-id="<?php echo esc_attr($service->id); ?>" <?php checked($service->status, 'active'); ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-3 edit-service" data-id="<?php echo esc_attr($service->id); ?>">
                                        <?php esc_html_e('Edit', 'booking-app'); ?>
                                    </button>
                                    <button class="text-red-600 hover:text-red-900 delete-service" data-id="<?php echo esc_attr($service->id); ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Main Modal -->
    <div id="service-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" id="close-modal-btn">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    <span class="sr-only">Close modal</span>
                </button>
                <div class="px-6 py-6 lg:px-8">
                    <h3 class="mb-4 text-xl font-medium text-gray-900 dark:text-white"><?php esc_html_e('Add New Service', 'booking-app'); ?></h3>
                    <form class="space-y-6" id="service-form">
                        <input type="hidden" name="id" id="service-id" value="0">
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Service Name</label>
                            <input type="text" name="name" id="name" class="flow-input w-full" placeholder="e.g. Legal Consultation" required>
                        </div>
                        <div>
                            <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Description</label>
                            <textarea name="description" id="description" class="flow-input w-full" rows="3"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="duration" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Duration (min)</label>
                                <input type="number" name="duration" id="duration" class="flow-input w-full" value="30" min="1" required>
                            </div>
                            <div>
                                <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Price ($)</label>
                                <input type="number" name="price" id="price" class="flow-input w-full" value="0.00" step="0.01" min="0" required>
                            </div>
                        </div>
                        <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Save Service</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
