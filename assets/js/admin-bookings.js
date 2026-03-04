jQuery(document).ready(function($){
    // Open modal when Edit action clicked - fetch full booking data from server
    $(document).on('click', '.mbs-edit-btn', function(e){
        e.preventDefault();
        var id = $(this).attr('data-booking-id') || null;
        if (!id) {
            // fallback: try to parse small data-booking JSON
            var raw = $(this).attr('data-booking');
            if (raw) {
                try { var tmp = JSON.parse(raw); id = tmp.id; } catch (err) { id = null; }
            }
        }
        if (!id) return;

        // request full booking details
        $.post(bookingAppBookings.ajaxUrl, {
            action: 'booking_app_get_booking',
            booking_id: id,
            _wpnonce: bookingAppBookings.nonce
        }, function(resp){
            if (!resp || !resp.success || !resp.data || !resp.data.booking) {
                alert((resp && resp.data && resp.data.message) || 'Failed to load booking');
                return;
            }
            var b = resp.data.booking;

            // Hidden id
            $('#mbs-edit-booking-id').val(b.id || '');

            // Booking info
            $('#mbs-display-service_name').text(b.service_name || '');
            $('#mbs-display-booking_key').text(b.booking_key ? b.booking_key : '');
            var dt = b.booking_datetime_utc ? (b.booking_datetime_utc + (b.time_zone ? ' ('+b.time_zone+')' : '')) : '';
            $('#mbs-display-datetime').text(dt);

            // Customer info
            $('#mbs-display-customer_name').text(b.customer_name || '');
            $('#mbs-display-customer_email').text(b.customer_email || '');
            $('#mbs-display-customer_phone').text(b.customer_phone || '');

            // Payment info
            var price = '';
            if (typeof b.price_amount === 'number' || !isNaN(parseFloat(b.price_amount))) {
                price = parseFloat(b.price_amount).toFixed(2) + ' ' + (b.currency || '');
            } else {
                price = (b.price_amount || '') + ' ' + (b.currency || '');
            }
            $('#mbs-display-price').text(price.trim());
            $('#mbs-display-payment_status').text(b.payment_status || '');
            $('#mbs-display-payment_provider').text(b.payment_provider || '');
            $('#mbs-display-payment_intent_id').text(b.payment_intent_id || '');
            $('#mbs-display-payment_charge_id').text(b.payment_charge_id || '');
            $('#mbs-display-payment_event_id').text(b.payment_event_id || '');

            // Notes
            $('#mbs-display-notes').text(b.notes || '');

            // raw payment data viewer (do not render fully by default)
            try {
                var raw = b.raw_payment_data || '';
                var rawJson = (typeof raw === 'string') ? raw : JSON.stringify(raw, null, 2);
                $('#mbs-raw-payment-json').text(rawJson);
            } catch (e) {
                $('#mbs-raw-payment-json').text('');
            }

            // Set underlying form fields where present (kept for compatibility)
            $('#mbs-edit-service_name').val(b.service_name || '');
            $('#mbs-edit-customer_name').val(b.customer_name || '');
            $('#mbs-edit-customer_email').val(b.customer_email || '');
            $('#mbs-edit-customer_phone').val(b.customer_phone || '');
            $('#mbs-edit-duration').val(b.duration || '');
            $('#mbs-edit-price_amount').val(b.price_amount || '');
            $('#mbs-edit-payment_provider').val(b.payment_provider || '');
            $('#mbs-edit-currency').val(b.currency || '');
            $('#mbs-edit-status').val(b.status || 'pending');

            // Render status display (keep visual style exactly as on table rows)
            function renderStatusDisplay(bk) {
                var status = bk.status || 'pending';
                var status_class = 'bg-gray-100 text-gray-800';
                if (status === 'confirmed') status_class = 'bg-green-100 text-green-800';
                if (status === 'pending') status_class = 'bg-yellow-100 text-yellow-800';
                var html = '<div class="flex flex-col gap-1">';
                html += '<span class="px-2 inline-flex text-[10px] leading-4 font-bold uppercase rounded-full '+status_class+'">'+status+'</span>';
                    if ((bk.payment_status||'') === 'paid') {
                    html += '<span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-blue-50 text-blue-600 border border-blue-100"><span class="material-icons-outlined text-[10px] mr-1">check_circle</span>Paid</span>';
                } else if ((bk.payment_status||'') === 'pending') {
                    html += '<span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-yellow-50 text-yellow-600 border border-yellow-100"><span class="material-icons-outlined text-[10px] mr-1">payments</span>Awaiting Payment</span>';
                }
                html += '</div>';
                return html;
            }

            $('#mbs-status-display-wrapper').html(renderStatusDisplay(b));

            // open modal
            $('#mbs-booking-edit-modal').addClass('open').attr('aria-hidden','false').css('display','flex');
        }).fail(function(){
            alert('AJAX error while loading booking');
        });
    });

    // Close modal
    $(document).on('click', '#mbs-edit-cancel', function(){
        $('#mbs-booking-edit-modal').removeClass('open').attr('aria-hidden','true').hide();
    });

    // Save changes
    $(document).on('click', '#mbs-edit-save', function(){
        var bookingId = $('#mbs-edit-booking-id').val();
        var status = $('#mbs-edit-status').val();
        var allowed = ['confirmed','pending','pending_payment','cancelled_payment','failed_payment'];
        if (!bookingId || allowed.indexOf(status) === -1) {
            alert(bookingAppBookings.i18n_invalid || 'Invalid input');
            return;
        }

        var $btn = $(this).prop('disabled', true).text(bookingAppBookings.i18n_saving || 'Saving...');

        $.post(bookingAppBookings.ajaxUrl, {
            action: 'booking_app_update_booking',
            booking_id: bookingId,
            status: status,
            _wpnonce: bookingAppBookings.nonce
        }, function(resp){
            if (resp && resp.success) {
                // update status cell with same visual structure as server render
                var row = $('tr[data-booking-row-id="'+bookingId+'"]');
                if (row.length && resp.data && resp.data.updated) {
                    var b = resp.data.updated;
                    var st = b.status || status;
                    var status_class = 'bg-gray-100 text-gray-800';
                    if (st === 'confirmed') status_class = 'bg-green-100 text-green-800';
                    if (st === 'pending') status_class = 'bg-yellow-100 text-yellow-800';
                    var html = '<div class="flex flex-col gap-1">';
                    html += '<span class="px-2 inline-flex text-[10px] leading-4 font-bold uppercase rounded-full '+status_class+'">'+st+'</span>';
                    if ((b.payment_status||'') === 'paid') {
                        html += '<span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-blue-50 text-blue-600 border border-blue-100"><span class="material-icons-outlined text-[10px] mr-1">check_circle</span>Paid</span>';
                    } else if ((b.payment_status||'') === 'pending') {
                        html += '<span class="px-2 inline-flex text-[9px] leading-3 font-medium rounded-full bg-yellow-50 text-yellow-600 border border-yellow-100"><span class="material-icons-outlined text-[10px] mr-1">payments</span>Awaiting Payment</span>';
                    }
                    html += '</div>';
                    row.find('.mbs-status-cell').html(html);
                }

                $('#mbs-booking-edit-modal').removeClass('open').attr('aria-hidden','true').hide();
            } else {
                alert((resp && resp.data && resp.data.message) || 'Save failed');
            }
        }).fail(function(){
            alert('AJAX error');
        }).always(function(){
            $btn.prop('disabled', false).text(bookingAppBookings.i18n_save || 'Save Changes');
        });

    });

    // Toggle raw payment JSON viewer (registered once)
    $(document).on('click', '#mbs-toggle-raw-payment', function(){
        var $p = $('#mbs-raw-payment-json');
        if ($p.is(':visible')) {
            $p.hide();
            $(this).text('View JSON');
        } else {
            $p.show();
            $(this).text('Hide JSON');
        }
    });

    // Close on ESC
    $(document).on('keydown', function(e){ if (e.key === 'Escape') { $('#mbs-booking-edit-modal').removeClass('open').attr('aria-hidden','true').hide(); } });
});
