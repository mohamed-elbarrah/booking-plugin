<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap booking-app-wrap">
    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?php esc_html_e('Bookings Overview', 'booking-app'); ?></h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Total Bookings', 'booking-app'); ?></p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo esc_html($stats['total']); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Confirmed', 'booking-app'); ?></p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo esc_html($stats['confirmed']); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Pending', 'booking-app'); ?></p>
            <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo esc_html($stats['pending']); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Revenue', 'booking-app'); ?></p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">$<?php echo esc_html(number_format($stats['revenue'], 2)); ?></p>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e('Recent Bookings', 'booking-app'); ?></h2>
            <a href="<?php echo admin_url('admin.php?page=booking-app-create'); ?>" class="text-sm text-indigo-600 font-medium hover:text-indigo-800"><?php esc_html_e('Create New Booking', 'booking-app'); ?></a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consultation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_bookings)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                <?php esc_html_e('No bookings found yet.', 'booking-app'); ?>
                            </td>
                        </tr>
                    <?php
else: ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo esc_html($booking->customer_name); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo esc_html($booking->customer_email); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo esc_html(get_the_title($booking->consultation_id)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo esc_html($booking->booking_datetime_utc); ?> (UTC)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
        $status_class = 'bg-gray-100 text-gray-800';
        if ($booking->status === 'confirmed')
            $status_class = 'bg-green-100 text-green-800';
        if ($booking->status === 'pending')
            $status_class = 'bg-yellow-100 text-yellow-800';
?>
                                    <div class="flex flex-col gap-1">
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-bold uppercase rounded-full <?php echo $status_class; ?>">
                                            <?php echo esc_html($booking->status); ?>
                                        </span>
                                        <?php if ($booking->payment_status === 'paid'): ?>
                                            <span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-blue-50 text-blue-600 border border-blue-100">
                                                <span class="material-icons-outlined text-[10px] mr-1">check_circle</span>
                                                <?php esc_html_e('Paid', 'booking-app'); ?>
                                            </span>
                                        <?php
        elseif ($booking->payment_status === 'pending'): ?>
                                            <span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-yellow-50 text-yellow-600 border border-yellow-100">
                                                <span class="material-icons-outlined text-[10px] mr-1">payments</span>
                                                <?php esc_html_e('Awaiting Payment', 'booking-app'); ?>
                                            </span>
                                        <?php
        endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($total_pages) && $total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-white">
                <nav class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <?php printf(esc_html__('Showing page %d of %d', 'booking-app'), max(1, intval($paged)), intval($total_pages)); ?>
                    </div>
                    <div>
                        <ul class="inline-flex -space-x-px">
                            <?php
                            $base_url = esc_url(add_query_arg([], admin_url('admin.php?page=booking-app')));
                            for ($i = 1; $i <= $total_pages; $i++):
                                $link = add_query_arg('paged', $i, $base_url);
                                $active = ($i == $paged) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100';
                            ?>
                                <li class="mr-1">
                                    <a href="<?php echo esc_url($link); ?>" class="px-3 py-1 border rounded <?php echo $active; ?> text-sm font-medium"><?php echo esc_html($i); ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
