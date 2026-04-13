<main class="content">
  <div class="container-fluid p-0">
    <div class="card shadow-sm border-primary mb-3" id="notDialedPanel"
         data-is-super-admin="<?php echo !empty($isSuperAdmin) ? '1' : '0'; ?>"
         data-company-id="<?php echo intval($selectedCompanyId ?? 0); ?>">
      <div class="card-body">
        <h5 class="text-uppercase text-primary mb-3" style="letter-spacing: 0.08em; font-size: 0.95rem;">Not Dialed Filters</h5>
        <div class="form-row align-items-end">
          <div class="form-group col-md-3">
            <label for="notDialedCompanySelect">Select Company</label>
            <select class="form-control" id="notDialedCompanySelect" <?php echo !empty($isSuperAdmin) ? '' : 'disabled'; ?>>
              <option value="">Select Company</option>
              <?php foreach (($companies ?? []) as $company): ?>
                <option value="<?php echo intval($company['id']); ?>" <?php echo intval($selectedCompanyId ?? 0) === intval($company['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label for="notDialedCampaignSelect">Select Campaign</label>
            <select class="form-control" id="notDialedCampaignSelect">
              <option value="">Select Campaign</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="notDialedDpdSelect">
              Days Past Due (search + multi-select)
              <button type="button"
                      class="btn btn-link btn-sm text-info p-0 ml-1 align-baseline notDialedInfoBtn"
                      data-toggle="popover"
                      data-trigger="focus"
                      data-placement="top"
                      data-html="true"
                      data-content="Search and select one or more DPD values, or leave empty to show all."
                      aria-label="Days Past Due help">
                <i class="fas fa-info-circle"></i>
              </button>
            </label>
            <select class="form-control" id="notDialedDpdSelect" multiple="multiple"></select>
          </div>
          <div class="form-group col-md-2">
            <button type="button" class="btn btn-outline-secondary btn-block" id="clearNotDialedFilters">Clear Filters</button>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="d-block mb-2">Schedule the selected numbers for dialing</label>
          <div class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
              <label for="notDialedScheduleDate">Schedule Date</label>
              <input type="date" class="form-control" id="notDialedScheduleDate">
            </div>
            <div class="form-group col-md-3 mb-2">
              <label for="notDialedScheduleTime">Preferred Time</label>
              <input type="time" class="form-control" id="notDialedScheduleTime" value="09:00" min="09:00" max="18:00" step="1800">
            </div>
            <div class="form-group col-md-4 mb-2">
              <small class="text-muted d-block" id="notDialedScheduleMeta">Pick a future date using the selected campaign's weekdays. Time must stay within that campaign's start/stop window in the PBX timezone.</small>
            </div>
            <div class="form-group col-md-2 mb-2">
              <button type="button" class="btn btn-primary btn-block" id="moveNotDialedBtn">Move Selected To Contacts</button>
            </div>
          </div>
          <small class="text-muted">Leave the schedule date empty to move the numbers as <strong>READY</strong> for immediate dialing.</small>
        </div>

        <div class="alert alert-info py-2 px-3 mb-0" id="notDialedStatus">
          This table shows numbers the system already attempted but that were <strong>not received by an agent</strong>, including current-date records, ordered by the latest system call first.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table id="notDialedTable" class="table table-striped table-bordered w-100">
          <thead>
            <tr>
              <th style="width: 40px;"><input type="checkbox" id="selectAllNotDialed"></th>
              <th>ID</th>
              <th>Campaign</th>
              <th>Number</th>
              <th>Name</th>
              <th>Days Past Due</th>
              <th>State</th>
              <th>Attempts</th>
              <th>Last Outcome</th>
              <th>Next Call At</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<style>
#notDialedPanel .select2-container {
  width: 100% !important;
}
#notDialedPanel .select2-container--default .select2-selection--multiple {
  min-height: calc(2.25rem + 2px);
  border: 1px solid #ced4da;
  border-radius: 0.2rem;
}
#notDialedPanel .select2-container--default .select2-selection--multiple .select2-selection__choice {
  background-color: #3b7ddd;
  border: 0;
  color: #fff;
  padding: 2px 8px;
}
#notDialedPanel .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
  color: rgba(255, 255, 255, 0.85);
  margin-right: 6px;
}
#notDialedPanel .notDialedInfoBtn {
  text-decoration: none;
  box-shadow: none !important;
}
#notDialedScheduleMeta {
  line-height: 1.45;
}
</style>

<script>
$(document).ready(function() {
  const panel = $('#notDialedPanel');
  const isSuperAdmin = String(panel.data('is-super-admin') || '0') === '1';
  const defaultCompanyId = String(panel.data('company-id') || $('#notDialedCompanySelect').val() || '');

  $('[data-toggle="popover"]').popover();

  function escapeHtml(value) {
    return $('<div>').text(value || '').html();
  }

  function selectedCompanyId() {
    return isSuperAdmin ? ($('#notDialedCompanySelect').val() || '') : (defaultCompanyId || $('#notDialedCompanySelect').val() || '');
  }

  function selectedCampaignId() {
    return $('#notDialedCampaignSelect').val() || '';
  }

  function selectedCampaignMeta() {
    const $selected = $('#notDialedCampaignSelect option:selected');
    let weekdays = [];
    const rawWeekdays = $selected.attr('data-weekdays') || '%5B%5D';

    try {
      weekdays = JSON.parse(decodeURIComponent(rawWeekdays));
    } catch (error) {
      try {
        weekdays = JSON.parse(rawWeekdays || '[]');
      } catch (innerError) {
        weekdays = [];
      }
    }

    if (!Array.isArray(weekdays)) {
      weekdays = [];
    }

    return {
      timezone: $selected.attr('data-timezone') || 'PBX timezone',
      today: $selected.attr('data-today') || '',
      minTime: $selected.attr('data-min-time') || '09:00',
      maxTime: $selected.attr('data-max-time') || '18:00',
      weekdays: weekdays
    };
  }

  function applyScheduleConstraints() {
    const meta = selectedCampaignMeta();
    const $date = $('#notDialedScheduleDate');
    const $time = $('#notDialedScheduleTime');
    const allowedDaysText = meta.weekdays.length ? meta.weekdays.join(', ') : 'All days';

    if (meta.today) {
      $date.attr('min', meta.today);
    }

    $time.attr('min', meta.minTime).attr('max', meta.maxTime);
    if (!$time.val() || $time.val() < meta.minTime || $time.val() > meta.maxTime) {
      $time.val(meta.minTime);
    }

    $('#notDialedScheduleMeta').html(
      'PBX timezone: <strong>' + escapeHtml(meta.timezone) + '</strong><br>' +
      'Allowed weekdays: <strong>' + escapeHtml(allowedDaysText) + '</strong><br>' +
      'Allowed time: <strong>' + escapeHtml(meta.minTime) + ' to ' + escapeHtml(meta.maxTime) + '</strong>'
    );
  }

  function validateScheduleInputs(showMessage) {
    const meta = selectedCampaignMeta();
    const scheduleDate = $('#notDialedScheduleDate').val() || '';
    const rawScheduleTime = $('#notDialedScheduleTime').val() || '';
    const scheduleTime = rawScheduleTime || meta.minTime || '09:00';

    if (!scheduleDate && !rawScheduleTime) {
      return true;
    }

    if (!scheduleDate || !rawScheduleTime) {
      if (showMessage !== false) {
        updateStatus('Please select both schedule date and time, or leave both empty.', 'warning');
      }
      return false;
    }

    if (scheduleTime < meta.minTime || scheduleTime > meta.maxTime) {
      if (showMessage !== false) {
        updateStatus('Please select a time between ' + meta.minTime + ' and ' + meta.maxTime + ' in the PBX timezone.', 'warning');
      }
      return false;
    }

    const selectedDate = new Date(scheduleDate + 'T12:00:00');
    if (isNaN(selectedDate.getTime())) {
      if (showMessage !== false) {
        updateStatus('Please select a valid schedule date.', 'warning');
      }
      return false;
    }

    const weekdayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][selectedDate.getDay()];
    if (meta.weekdays.length && meta.weekdays.indexOf(weekdayName) === -1) {
      if (showMessage !== false) {
        updateStatus('The selected date falls on ' + weekdayName + '. Allowed campaign weekdays: ' + meta.weekdays.join(', ') + '.', 'warning');
      }
      return false;
    }

    return true;
  }

  function refreshDpdSelect(selectedValues) {
    const $select = $('#notDialedDpdSelect');
    const values = Array.isArray(selectedValues) ? selectedValues : ($select.val() || []);

    if ($.fn.select2) {
      if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
      }

      $select.select2({
        width: '100%',
        placeholder: 'Search and select Days Past Due',
        allowClear: true,
        closeOnSelect: false
      });
    }

    $select.val(values).trigger('change');
  }

  refreshDpdSelect([]);

  function selectedDpdValues() {
    return $('#notDialedDpdSelect').val() || [];
  }

  function updateStatus(message, type) {
    const alertType = type || 'info';
    $('#notDialedStatus')
      .removeClass('alert-info alert-warning alert-success')
      .addClass('alert-' + alertType)
      .html(message || '');
  }

  function reloadTable() {
    if ($.fn.DataTable.isDataTable('#notDialedTable')) {
      $('#notDialedTable').DataTable().ajax.reload();
    }
  }

  function loadCampaigns(autoSelectFirst) {
    const companyId = selectedCompanyId();
    $('#notDialedCampaignSelect').html('<option value="">Loading campaigns...</option>');
    $('#notDialedDpdSelect').html('');
    refreshDpdSelect([]);

    if (!companyId) {
      $('#notDialedCampaignSelect').html('<option value="">Select Campaign</option>');
      updateStatus('Select a company first to load campaigns.', 'warning');
      reloadTable();
      return;
    }

    $.ajax({
      url: 'notdialed/getcampaigns',
      type: 'GET',
      dataType: 'json',
      data: { company_id: companyId }
    }).done(function(rows) {
      let html = '<option value="">Select Campaign</option>';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        const weekdaysJson = encodeURIComponent(JSON.stringify(Array.isArray(row.weekdays) ? row.weekdays : []));
        html += '<option value="' + escapeHtml(row.id) + '"' +
          ' data-weekdays="' + weekdaysJson + '"' +
          ' data-timezone="' + escapeHtml(row.timezone || '') + '"' +
          ' data-today="' + escapeHtml(row.today_label || '') + '"' +
          ' data-min-time="' + escapeHtml(row.min_time || '09:00') + '"' +
          ' data-max-time="' + escapeHtml(row.max_time || '18:00') + '">' +
          escapeHtml(row.name) + '</option>';
      });

      $('#notDialedCampaignSelect').html(html);
      applyScheduleConstraints();

      if (autoSelectFirst && rows.length > 0) {
        $('#notDialedCampaignSelect').val(String(rows[0].id));
        applyScheduleConstraints();
        updateStatus('Campaign selected. You can now filter by DPD and choose a valid schedule date/time.', 'info');
        loadDpdValues();
      } else if (rows.length === 0) {
        updateStatus('No campaigns were found for the selected company.', 'warning');
        reloadTable();
      }
    }).fail(function() {
      $('#notDialedCampaignSelect').html('<option value="">Select Campaign</option>');
      updateStatus('Could not load campaigns right now.', 'warning');
      reloadTable();
    });
  }

  function loadDpdValues() {
    const companyId = selectedCompanyId();
    const campaignId = selectedCampaignId();

    if (!companyId || !campaignId) {
      $('#notDialedDpdSelect').html('');
      refreshDpdSelect([]);
      reloadTable();
      return;
    }

    $.ajax({
      url: 'notdialed/getdpdvalues',
      type: 'GET',
      dataType: 'json',
      data: {
        company_id: companyId,
        campaign_id: campaignId
      }
    }).done(function(rows) {
      let html = '';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        html += '<option value="' + escapeHtml(row.value) + '">' + escapeHtml(row.label) + '</option>';
      });

      $('#notDialedDpdSelect').html(html);
      refreshDpdSelect([]);
      reloadTable();
    }).fail(function() {
      $('#notDialedDpdSelect').html('');
      refreshDpdSelect([]);
      updateStatus('Could not load Days Past Due values right now.', 'warning');
      reloadTable();
    });
  }

  const notDialedTable = $('#notDialedTable').DataTable({
    ajax: {
      url: 'notdialed/getrecords',
      type: 'POST',
      dataSrc: '',
      data: function(payload) {
        payload.company_id = selectedCompanyId();
        payload.campaign_id = selectedCampaignId();
        payload.days_past_due = selectedDpdValues();
      }
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
          return '<input type="checkbox" class="notdialed-row" value="' + row.id + '">';
        }
      },
      { data: 'id' },
      { data: 'campaign_name' },
      { data: 'number' },
      { data: 'name' },
      { data: 'days_past_due' },
      { data: 'state' },
      { data: 'attempts' },
      { data: 'last_call_status' },
      { data: 'next_call_at' },
      { data: 'created_at' }
    ],
    order: [[8, 'desc'], [1, 'desc']],
    responsive: true,
    search: {
      return: true
    },
    language: {
      search: '_INPUT_',
      searchPlaceholder: 'Search not dialed numbers'
    }
  });

  $('#notDialedCompanySelect').on('change', function() {
    loadCampaigns(true);
  });

  $('#notDialedCampaignSelect').on('change', function() {
    applyScheduleConstraints();
    loadDpdValues();
  });

  $('#notDialedScheduleDate, #notDialedScheduleTime').on('change', function() {
    validateScheduleInputs(true);
  });

  $('#notDialedDpdSelect').on('change', function() {
    reloadTable();
  });

  $('#clearNotDialedFilters').on('click', function() {
    $('#notDialedDpdSelect').val([]);
    refreshDpdSelect([]);
    $('#notDialedScheduleDate').val('');
    $('#notDialedScheduleTime').val('09:00');
    loadCampaigns(true);
  });

  $('#selectAllNotDialed').on('change', function() {
    $('.notdialed-row').prop('checked', $(this).is(':checked'));
  });

  $('#notDialedTable').on('draw.dt', function() {
    $('#selectAllNotDialed').prop('checked', false);
  });

  $('#moveNotDialedBtn').on('click', function() {
    const selectedIds = $('.notdialed-row:checked').map(function() {
      return $(this).val();
    }).get();
    const scheduleDate = $('#notDialedScheduleDate').val() || '';
    const scheduleTime = $('#notDialedScheduleTime').val() || (selectedCampaignMeta().minTime || '09:00');
    const companyId = selectedCompanyId();
    const campaignId = selectedCampaignId();

    if (!companyId || !campaignId) {
      updateStatus('Please select company and campaign first.', 'warning');
      return;
    }

    if (!selectedIds.length) {
      updateStatus('Please select at least one number to move.', 'warning');
      return;
    }

    if (scheduleDate && !validateScheduleInputs(true)) {
      return;
    }

    $.ajax({
      url: 'notdialed/move_to_contacts',
      type: 'POST',
      dataType: 'json',
      data: {
        company_id: companyId,
        campaign_id: campaignId,
        contact_ids: selectedIds,
        schedule_date: scheduleDate,
        schedule_time: scheduleTime
      }
    }).done(function(response) {
      if (!response || !response.success) {
        updateStatus((response && response.message) ? response.message : 'Could not move the selected numbers.', 'warning');
        return;
      }

      updateStatus(response.message || 'Selected numbers moved to Contacts.', 'success');
      $('#selectAllNotDialed').prop('checked', false);
      $('#notDialedScheduleDate').val('');
      reloadTable();
    }).fail(function() {
      updateStatus('Failed to move the selected numbers right now.', 'warning');
    });
  });

  if (!selectedCompanyId() && isSuperAdmin && $('#notDialedCompanySelect option').length > 1) {
    $('#notDialedCompanySelect').val($('#notDialedCompanySelect option').eq(1).val());
  }

  if (selectedCompanyId()) {
    loadCampaigns(true);
  } else {
    updateStatus('Select a company first to load campaigns.', 'warning');
    reloadTable();
  }
});
</script>
