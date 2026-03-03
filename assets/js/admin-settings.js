jQuery(document).ready(function ($) {
  // Tab switching logic
  $('.tab-panel').hide();
  $('#tab-general').show();
  $('[data-tab-target="#tab-general"]').addClass('border-blue-600 text-blue-600').removeClass('border-transparent');

  $('[data-tab-target]').on('click', function (e) {
    e.preventDefault();
    const target = $(this).data('tab-target');

    $('.tab-panel').hide();
    $(target).show();

    $('[data-tab-target]').removeClass('border-blue-600 text-blue-600').addClass('border-transparent');
    $(this).addClass('border-blue-600 text-blue-600').removeClass('border-transparent');
  });

  // Stripe Connection Test
  $('#mbs-test-stripe').on('click', function () {
    const $btn = $(this);
    const $result = $('#mbs-stripe-test-result');

    $btn.prop('disabled', true).text('Testing...');
    $result.hide().removeClass('bg-green-100 text-green-800 bg-red-100 text-red-800');

    $.ajax({
      url: BookingAppSettings.ajax_url,
      type: 'POST',
      data: {
        action: 'mbs_test_stripe_connection',
        nonce: BookingAppSettings.nonce
      },
      success: function (response) {
        if (response.success) {
          $result.addClass('bg-green-100 text-green-800').text(response.data.message).show();
        } else {
          $result.addClass('bg-red-100 text-red-800').text(response.data.message).show();
        }
      },
      error: function () {
        $result.addClass('bg-red-100 text-red-800').text('An unexpected error occurred.').show();
      },
      complete: function () {
        $btn.prop('disabled', false).text('Test Connection');
      }
    });
  });

  // Add Break Logic (existing)
  $('.add-break-btn').on('click', function () {
    const dayIndex = $(this).data('day-index');
    const $list = $(`.breaks-list[data-day-index="${dayIndex}"]`);
    const template = $('#booking-app-break-row-template').html();

    // Generate a unique ID or index for the new break
    const breakIndex = $list.children().length;

    let html = template
      .replace(/__start__/g, `booking_app_settings[availability][${dayIndex}][breaks][${breakIndex}][start]`)
      .replace(/__end__/g, `booking_app_settings[availability][${dayIndex}][breaks][${breakIndex}][end]`);

    $list.append(html);
  });

  $(document).on('click', '.remove-break-btn', function () {
    $(this).closest('.break-row').remove();
  });
});
