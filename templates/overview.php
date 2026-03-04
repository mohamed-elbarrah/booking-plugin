<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap booking-app-wrap">
    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?php esc_html_e('Bookings Overview', 'mbs-booking'); ?></h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Total Bookings', 'mbs-booking'); ?></p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo esc_html($stats['total']); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Confirmed', 'mbs-booking'); ?></p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?php echo esc_html($stats['confirmed']); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Pending', 'mbs-booking'); ?></p>
            <p class="text-3xl font-bold text-yellow-600 mt-1"><?php echo esc_html($stats['pending']); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase"><?php esc_html_e('Revenue', 'mbs-booking'); ?></p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">$<?php echo esc_html(number_format($stats['revenue'], 2)); ?></p>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e('Recent Bookings', 'mbs-booking'); ?></h2>

                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="inline-block">
                        <input type="hidden" name="page" value="booking-app" />
                        <details class="relative inline-block">
                            <summary class="px-3 py-1 border rounded bg-white text-sm cursor-pointer"><?php esc_html_e('Filters', 'mbs-booking'); ?></summary>
                            <div class="absolute mt-2 p-4 bg-white border rounded shadow w-72">
                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-700"><?php esc_html_e('Sort', 'mbs-booking'); ?></label>
                                    <select name="sort" class="mt-1 block w-full text-sm">
                                        <?php $s = $selected_sort ?? 'newest'; ?>
                                        <option value="newest" <?php selected($s, 'newest'); ?>><?php esc_html_e('Newest booking first', 'mbs-booking'); ?></option>
                                        <option value="oldest" <?php selected($s, 'oldest'); ?>><?php esc_html_e('Oldest booking first', 'mbs-booking'); ?></option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-700"><?php esc_html_e('Status', 'mbs-booking'); ?></label>
                                    <?php $sel = is_array($selected_statuses ?? null) ? $selected_statuses : []; ?>
                                    <div class="space-y-1 mt-2 text-sm">
                                        <label><input type="checkbox" name="statuses[]" value="confirmed" <?php checked(in_array('confirmed', $sel)); ?> /> <?php esc_html_e('Confirmed', 'mbs-booking'); ?></label>
                                        <label><input type="checkbox" name="statuses[]" value="pending" <?php checked(in_array('pending', $sel)); ?> /> <?php esc_html_e('Pending', 'mbs-booking'); ?></label>
                                        <label><input type="checkbox" name="statuses[]" value="pending_payment" <?php checked(in_array('pending_payment', $sel)); ?> /> <?php esc_html_e('Pending Payment', 'mbs-booking'); ?></label>
                                        <label><input type="checkbox" name="statuses[]" value="cancelled_payment" <?php checked(in_array('cancelled_payment', $sel)); ?> /> <?php esc_html_e('Cancelled Payment', 'mbs-booking'); ?></label>
                                        <label><input type="checkbox" name="statuses[]" value="failed_payment" <?php checked(in_array('failed_payment', $sel)); ?> /> <?php esc_html_e('Failed Payment', 'mbs-booking'); ?></label>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded"><?php esc_html_e('Apply', 'mbs-booking'); ?></button>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=booking-app')); ?>" class="text-sm text-gray-600 hover:underline"><?php esc_html_e('Clear', 'mbs-booking'); ?></a>
                                </div>
                            </div>
                        </details>
                    </form>
                </div>

                <a href="<?php echo admin_url('admin.php?page=booking-app-create'); ?>" class="text-sm text-indigo-600 font-medium hover:text-indigo-800"><?php esc_html_e('Create New Booking', 'mbs-booking'); ?></a>
        </div>
        <div class="overflow-x-auto">
            <form id="mbs-bookings-bulk-form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=booking-app')); ?>">
                <?php wp_nonce_field('mbs_bookings_bulk_action', 'mbs_bookings_nonce'); ?>
                <div class="px-6 py-3 flex items-center justify-between border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <select name="mbs_bulk_action_type" class="text-sm border rounded px-2 py-1">
                            <option value=""><?php esc_html_e('Bulk Actions', 'mbs-booking'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete', 'mbs-booking'); ?></option>
                        </select>
                        <button type="submit" class="px-3 py-1 bg-gray-100 text-sm rounded" id="mbs-apply-bulk"><?php esc_html_e('Apply', 'mbs-booking'); ?></button>
                    </div>
                    <div class="text-sm text-gray-600"></div>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><input type="checkbox" id="mbs-select-all" /></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Customer', 'mbs-booking'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Consultation', 'mbs-booking'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Date & Time', 'mbs-booking'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Status', 'mbs-booking'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo esc_html__('Action', 'mbs-booking'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_bookings)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                <?php esc_html_e('No bookings found yet.', 'mbs-booking'); ?>
                            </td>
                        </tr>
                    <?php
else: ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr data-booking-row-id="<?php echo intval($booking->id); ?>">
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="booking_ids[]" value="<?php echo intval($booking->id); ?>" class="mbs-row-checkbox" />
                                </td>
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
                                <td class="px-6 py-4 whitespace-nowrap mbs-status-cell">
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
                                                <?php esc_html_e('Paid', 'mbs-booking'); ?>
                                            </span>
                                        <?php
        elseif ($booking->payment_status === 'pending'): ?>
                                            <span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-yellow-50 text-yellow-600 border border-yellow-100">
                                                <span class="material-icons-outlined text-[10px] mr-1">payments</span>
                                                <?php esc_html_e('Awaiting Payment', 'mbs-booking'); ?>
                                            </span>
                                        <?php
        endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button type="button" class="mbs-edit-btn button text-sm text-indigo-600 mr-3" data-booking-id="<?php echo intval($booking->id); ?>" data-booking="<?php echo esc_attr(wp_json_encode($booking)); ?>" onclick="(function(btn){try{var raw=btn.getAttribute('data-booking'); if(!raw) return; var b=JSON.parse(raw); document.getElementById('mbs-edit-booking-id').value=b.id||''; document.getElementById('mbs-edit-customer_name').value=b.customer_name||''; document.getElementById('mbs-edit-customer_email').value=b.customer_email||''; document.getElementById('mbs-edit-customer_phone').value=b.customer_phone||''; document.getElementById('mbs-edit-duration').value=b.duration||''; document.getElementById('mbs-edit-price_amount').value=(b.price_total||b.price_amount||''); document.getElementById('mbs-edit-service_name').value=''; document.getElementById('mbs-edit-payment_provider').value=b.payment_provider||''; document.getElementById('mbs-edit-currency').value=b.currency||''; document.getElementById('mbs-edit-status').value=b.status||'pending'; document.getElementById('mbs-booking-edit-modal').style.display='flex'; document.getElementById('mbs-booking-edit-modal').setAttribute('aria-hidden','false'); }catch(e){} })(this);">
                                        <?php esc_html_e('Edit', 'mbs-booking'); ?>
                                    </button>
                                    <button type="button" class="mbs-remove-btn text-sm text-red-600" data-id="<?php echo intval($booking->id); ?>"><?php esc_html_e('Remove', 'mbs-booking'); ?></button>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
            </form>
        </div>
        <!-- Booking Edit Modal -->
        <div id="mbs-booking-edit-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;padding:24px;">
            <div class="mbs-modal-card" style="background:#fff;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.15);max-width:900px;width:100%;max-height:90vh;overflow:auto;padding:22px;">
                <h2 style="margin-top:0;margin-bottom:6px;font-size:18px;font-weight:700;color:#2b3a42;"><?php esc_html_e('Booking Details','mbs-booking'); ?></h2>
                <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;">
                    <input type="hidden" id="mbs-edit-booking-id" value="">

                    <!-- Main info card (left) -->
                    <div style="flex:1 1 55%;background:#fafafa;border:1px solid #f1f1f1;border-radius:8px;padding:18px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                            <div>
                                <div style="font-size:11px;font-weight:700;color:#9aa6ae;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;"><?php esc_html_e('Booking Info','mbs-booking'); ?></div>
                                <div style="font-weight:700;color:#1f2937;margin-bottom:6px;" id="mbs-display-service_name"></div>
                                <div style="font-size:13px;color:#6b7280;margin-bottom:2px;" id="mbs-display-booking_key"></div>
                                <div style="font-size:13px;color:#6b7280;" id="mbs-display-datetime"></div>
                            </div>
                            <div style="text-align:right;min-width:140px;">
                                <div style="font-size:11px;font-weight:700;color:#9aa6ae;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;"><?php esc_html_e('Status','mbs-booking'); ?></div>
                                <!-- Keep the status UI exactly as-is: the select remains below for editing -->
                                <div id="mbs-status-display-wrapper"></div>
                            </div>
                        </div>

                        <hr style="border:none;border-top:1px solid #eee;margin:12px 0;" />

                        <div style="margin-top:6px;">
                            <div style="font-size:11px;font-weight:700;color:#9aa6ae;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;"><?php esc_html_e('Customer Info','mbs-booking'); ?></div>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                <div style="font-weight:600;color:#374151;" id="mbs-display-customer_name"></div>
                                <div style="font-size:13px;color:#6b7280;" id="mbs-display-customer_email"></div>
                                <div style="font-size:13px;color:#6b7280;" id="mbs-display-customer_phone"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Side card (right) -->
                    <div style="flex:1 1 40%;min-width:260px;">
                        <div style="background:#fff;border-radius:8px;border:1px solid #f3f4f6;padding:14px;">
                            <div style="font-size:11px;font-weight:700;color:#9aa6ae;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;"><?php esc_html_e('Payment Info','mbs-booking'); ?></div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <div style="font-weight:700;color:#111827;font-size:16px;" id="mbs-display-price"></div>
                                <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Payment Status:', 'mbs-booking'); ?> <span id="mbs-display-payment_status" style="font-weight:600;color:#374151;margin-left:6px;"></span></div>
                                <div style="font-size:13px;color:#6b7280;"><?php esc_html_e('Provider:', 'mbs-booking'); ?> <span id="mbs-display-payment_provider" style="font-weight:600;color:#374151;margin-left:6px;"></span></div>
                                <div style="font-size:12px;color:#8b949e;"><?php esc_html_e('Intent ID:', 'mbs-booking'); ?> <div id="mbs-display-payment_intent_id" style="font-size:12px;color:#8b949e;margin-top:4px;"></div></div>
                                <div style="font-size:12px;color:#8b949e;"><?php esc_html_e('Charge ID:', 'mbs-booking'); ?> <div id="mbs-display-payment_charge_id" style="font-size:12px;color:#8b949e;margin-top:4px;"></div></div>
                                <div style="font-size:12px;color:#8b949e;"><?php esc_html_e('Event ID:', 'mbs-booking'); ?> <div id="mbs-display-payment_event_id" style="font-size:12px;color:#8b949e;margin-top:4px;"></div></div>
                            </div>
                        </div>

                        <div style="margin-top:12px;background:#fff;border-radius:8px;border:1px solid #f3f4f6;padding:12px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><div style="font-size:11px;font-weight:700;color:#9aa6ae;letter-spacing:1px;text-transform:uppercase;"><?php esc_html_e('Notes','mbs-booking'); ?></div></div>
                            <div id="mbs-display-notes" style="font-size:13px;color:#374151;min-height:40px;"></div>
                        </div>

                        <div style="margin-top:12px;background:#fff;border-radius:8px;border:1px solid #f3f4f6;padding:12px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><div style="font-size:11px;font-weight:700;color:#9aa6ae;letter-spacing:1px;text-transform:uppercase;"><?php esc_html_e('Raw Payment Data','mbs-booking'); ?></div>
                            <button type="button" id="mbs-toggle-raw-payment" class="button" style="font-size:12px;padding:4px 8px;"><?php esc_html_e('View JSON','mbs-booking'); ?></button></div>
                            <pre id="mbs-raw-payment-json" style="display:none;overflow:auto;background:#f8fafc;border-radius:6px;padding:10px;font-size:12px;color:#374151;max-height:180px;"></pre>
                        </div>
                    </div>
                </div>

                <!-- Keep status select unchanged and available for editing -->
                <div style="margin-top:14px;display:flex;justify-content:flex-end;gap:10px;align-items:center;">
                    <label for="mbs-edit-status" style="font-weight:600;color:#374151;margin-right:8px;"><?php esc_html_e('Status','mbs-booking'); ?></label>
                    <select id="mbs-edit-status" class="regular-text">
                        <option value="confirmed"><?php esc_html_e('Confirmed','mbs-booking'); ?></option>
                        <option value="pending"><?php esc_html_e('Pending','mbs-booking'); ?></option>
                        <option value="pending_payment"><?php esc_html_e('Pending Payment','mbs-booking'); ?></option>
                        <option value="cancelled_payment"><?php esc_html_e('Cancelled Payment','mbs-booking'); ?></option>
                        <option value="failed_payment"><?php esc_html_e('Failed Payment','mbs-booking'); ?></option>
                    </select>
                    <button type="button" id="mbs-edit-cancel" class="button"><?php esc_html_e('Cancel','mbs-booking'); ?></button>
                    <button type="button" id="mbs-edit-save" class="button button-primary"><?php esc_html_e('Save Changes','mbs-booking'); ?></button>
                </div>
            </div>
        </div>
        <?php if (!empty($total_pages) && $total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-white">
                <nav class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        <?php printf(esc_html__('Showing page %d of %d', 'mbs-booking'), max(1, intval($paged)), intval($total_pages)); ?>
                    </div>
                    <div>
                        <ul class="inline-flex -space-x-px">
                            <?php
                            $base_url = admin_url('admin.php?page=booking-app');
                            for ($i = 1; $i <= $total_pages; $i++):
                                $args = array_merge($_GET ?? [], ['paged' => $i, 'page' => 'booking-app']);
                                $link = esc_url(add_query_arg($args, $base_url));
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
        <script>
            (function(){
                var selectAll = document.getElementById('mbs-select-all');
                var form = document.getElementById('mbs-bookings-bulk-form');
                if (selectAll) {
                    selectAll.addEventListener('change', function(){
                        var checked = this.checked;
                        document.querySelectorAll('.mbs-row-checkbox').forEach(function(cb){ cb.checked = checked; });
                    });
                }

                if (form) {
                    form.addEventListener('submit', function(e){
                        var action = (document.querySelector('select[name="mbs_bulk_action_type"]')||{}).value || '';
                        if (action === 'delete') {
                            var any = document.querySelectorAll('.mbs-row-checkbox:checked').length > 0;
                            if (!any) {
                                e.preventDefault();
                                alert('<?php echo esc_js(__('Please select at least one booking to delete.', 'mbs-booking')); ?>');
                                return;
                            }
                            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete the selected bookings? This action cannot be undone.', 'mbs-booking')); ?>')) {
                                e.preventDefault();
                                return;
                            }
                        }
                    });
                }

                document.querySelectorAll('.mbs-remove-btn').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var id = this.getAttribute('data-id');
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this booking? This action cannot be undone.', 'mbs-booking')); ?>')) return;
                        var cb = document.querySelector('.mbs-row-checkbox[value="'+id+'"]');
                        if (cb) {
                            // uncheck others
                            document.querySelectorAll('.mbs-row-checkbox').forEach(function(c){ c.checked = false; });
                            cb.checked = true;
                        } else if (form) {
                            // create hidden input
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'booking_ids[]';
                            input.value = id;
                            form.appendChild(input);
                        }
                        var select = document.querySelector('select[name="mbs_bulk_action_type"]');
                        if (select) select.value = 'delete';
                        if (form) form.submit();
                    });
                });
            })();
        </script>
    </div>
</div>
