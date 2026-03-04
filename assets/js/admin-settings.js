document.addEventListener('DOMContentLoaded', function () {
  // Simple tab switching
  var tabButtons = document.querySelectorAll('[data-tab-target]');
  tabButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-tab-target'));
      document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.add('hidden'); });
      if (target) target.classList.remove('hidden');
      // update active styles
      tabButtons.forEach(function (b) { b.classList.remove('border-b-2', 'border-indigo-600'); });
      btn.classList.add('border-b-2', 'border-indigo-600');
    });
  });

  // Activate first tab by default
  if (tabButtons.length) tabButtons[0].click();

  // Add/Remove Break dynamic handlers
  function addBreakRow(dayIndex, startVal, endVal) {
    var tpl = document.getElementById('booking-app-break-row-template');
    if (!tpl) return null;
    var clone = tpl.content.firstElementChild.cloneNode(true);
    // compute next index
    var list = document.querySelector('.breaks-list[data-day-index="' + dayIndex + '"]');
    var nextIndex = 0;
    if (list) {
      nextIndex = list.querySelectorAll('.break-row').length;
    }
    // update inputs' name attributes
    var start = clone.querySelector('input[data-name-start]');
    var end = clone.querySelector('input[data-name-end]');
    if (start) {
      start.name = 'booking_app_settings[availability][' + dayIndex + '][breaks][' + nextIndex + '][start]';
      if (startVal) start.value = startVal;
    }
    if (end) {
      end.name = 'booking_app_settings[availability][' + dayIndex + '][breaks][' + nextIndex + '][end]';
      if (endVal) end.value = endVal;
    }
    // remove handler
    var remove = clone.querySelector('.remove-break-btn');
    if (remove) {
      remove.addEventListener('click', function () {
        clone.remove();
        // reindex remaining rows
        reindexBreaks(dayIndex);
      });
    }
    if (list) list.appendChild(clone);
    return clone;
  }

  function reindexBreaks(dayIndex) {
    var list = document.querySelector('.breaks-list[data-day-index="' + dayIndex + '"]');
    if (!list) return;
    var rows = list.querySelectorAll('.break-row');
    rows.forEach(function (row, idx) {
      var start = row.querySelector('input[data-name-start]');
      var end = row.querySelector('input[data-name-end]');
      if (start) start.name = 'booking_app_settings[availability][' + dayIndex + '][breaks][' + idx + '][start]';
      if (end) end.name = 'booking_app_settings[availability][' + dayIndex + '][breaks][' + idx + '][end]';
    });
  }

  document.querySelectorAll('.add-break-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var day = btn.getAttribute('data-day-index');
      addBreakRow(day);
    });
  });

  // Attach remove handlers for any existing rows (none on first load)
  document.querySelectorAll('.remove-break-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = btn.closest('.break-row');
      if (row) {
        var list = row.parentElement;
        var day = list ? list.getAttribute('data-day-index') : null;
        row.remove();
        if (day !== null) reindexBreaks(day);
      }
    });
  });

  // Simple form submit UX
  var form = document.getElementById('booking-app-settings-form');
  if (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('input[type=submit], button[type=submit]');
      if (btn) {
        btn.disabled = true;
        btn.value = 'Saving...';
      }
    });
  }
});
