jQuery(document).ready(function ($) {
    // Open modal when row is clicked - fetch full booking data from server
    $(document).on('click', '.mbs-booking-row', function (e) {
        // Do not open if clicking on a button or checkbox
        if ($(e.target).closest('input[type="checkbox"], button, .mbs-remove-btn').length > 0) {
            return;
        }

        e.preventDefault();
        var id = $(this).attr('data-booking-row-id') || null;
        if (!id) {
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
        }, function (resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.booking) {
                alert((resp && resp.data && resp.data.message) || 'Failed to load booking');
                return;
            }
            var b = resp.data.booking;

            // Simple Booking info mapping
            $('#mbs-final-service-name').text(b.service_name || '');
            var dt = '';
            if (b.booking_datetime_utc) {
                // In a real scenario we might format the date string, but we'll show the provided format directly
                dt = b.booking_datetime_utc;
            }
            $('#mbs-final-datetime').text(dt);

            // Customer info
            $('#mbs-final-customer-name').text(b.customer_name || '');
            $('#mbs-final-customer-email').text(b.customer_email || '');

            // Render status display based on user's exact required output
            function renderStatusDisplay(bk) {
                var paymentStatusLabel = '';
                var statusText = '';

                if ((bk.payment_status || '') === 'paid' || bk.price_amount <= 0 || (bk.payment_type && bk.payment_type !== '')) {
                    paymentStatusLabel = 'مدفوع';
                } else {
                    paymentStatusLabel = 'غير مدفوع';
                }

                if (bk.status === 'confirmed') {
                    statusText = ' ومؤكد';
                } else if (bk.status === 'pending') {
                    statusText = ' وغير مؤكد';
                } else {
                    statusText = ' ' + bk.status;
                }

                // fallback UI using the emerald theme they provided
                return '<div class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-black uppercase">' + paymentStatusLabel + statusText + '</div>';
            }

            $('#mbs-status-display-wrapper').html(renderStatusDisplay(b));

            // open modal
            $('#mbs-booking-edit-modal').fadeIn(200);
        }).fail(function () {
            alert('AJAX error while loading booking');
        });
    });

    // Close modal
    $(document).on('click', '#mbs-edit-close-x', function (e) {
        if (e) e.preventDefault();
        $('#mbs-booking-edit-modal').fadeOut(150);
    });

    // Toggle raw payment JSON viewer (registered once)
    $(document).on('click', '#mbs-toggle-raw-payment', function () {
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
    $(document).on('keydown', function (e) { if (e.key === 'Escape') { $('#mbs-booking-edit-modal').fadeOut(150); } });
});
