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
            <label for="notDialedDpdSelect">Days Past Due (search + multi-select)</label>
            <select class="form-control" id="notDialedDpdSelect" multiple="multiple"></select>
            <small class="form-text text-muted">Search and select one or more DPD values, or leave empty to show all.</small>
          </div>
          <div class="form-group col-md-2">
            <button type="button" class="btn btn-outline-secondary btn-block" id="clearNotDialedFilters">Clear Filters</button>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="d-block mb-2">Schedule the selected numbers for dialing</label>
          <div class="d-flex flex-wrap schedule-days-group mb-2">
            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
              <div class="form-check mr-3 mb-2">
                <input class="form-check-input notdialed-schedule-day" type="checkbox" name="schedule_days[]" value="<?php echo $day; ?>" id="day_<?php echo strtolower($day); ?>">
                <label class="form-check-label" for="day_<?php echo strtolower($day); ?>"><?php echo $day; ?></label>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
              <label for="notDialedScheduleTime">Preferred Time</label>
              <input type="time" class="form-control" id="notDialedScheduleTime" value="09:00">
            </div>
            <div class="form-group col-md-3 mb-2">
              <button type="button" class="btn btn-primary btn-block" id="moveNotDialedBtn">Move Selected To Contacts</button>
            </div>
          </div>
          <small class="text-muted">If you select weekday(s), the earliest upcoming selected day/time becomes the next dial slot. If no day is selected, the numbers are moved as <strong>READY</strong> for immediate dialing.</small>
        </div>

        <div class="alert alert-info py-2 px-3 mb-0" id="notDialedStatus">
          This table shows numbers with <strong>0 attempts</strong> and <strong>no dialer attempt log</strong> that are not already part of today's Contacts batch.
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
.schedule-days-group .form-check {
  min-width: 110px;
}
</style>

<script>
$(document).ready(function() {
  const panel = $('#notDialedPanel');
  const isSuperAdmin = String(panel.data('is-super-admin') || '0') === '1';
  const defaultCompanyId = String(panel.data('company-id') || $('#notDialedCompanySelect').val() || '');

  function escapeHtml(value) {
    return $('<div>').text(value || '').html();
  }

  function selectedCompanyId() {
    return isSuperAdmin ? ($('#notDialedCompanySelect').val() || '') : (defaultCompanyId || $('#notDialedCompanySelect').val() || '');
  }

  function selectedCampaignId() {
    return $('#notDialedCampaignSelect').val() || '';
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
        html += '<option value="' + escapeHtml(row.id) + '">' + escapeHtml(row.name) + '</option>';
      });

      $('#notDialedCampaignSelect').html(html);

      if (autoSelectFirst && rows.length > 0) {
        $('#notDialedCampaignSelect').val(String(rows[0].id));
        updateStatus('Campaign selected. You can now filter by one or more Days Past Due values.', 'info');
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
    order: [[1, 'desc']],
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
    loadDpdValues();
  });

  $('#notDialedDpdSelect').on('change', function() {
    reloadTable();
  });

  $('#clearNotDialedFilters').on('click', function() {
    $('#notDialedDpdSelect').val([]);
    refreshDpdSelect([]);
    $('.notdialed-schedule-day').prop('checked', false);
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
    const scheduleDays = $('.notdialed-schedule-day:checked').map(function() {
      return $(this).val();
    }).get();
    const scheduleTime = $('#notDialedScheduleTime').val() || '09:00';
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

    $.ajax({
      url: 'notdialed/move_to_contacts',
      type: 'POST',
      dataType: 'json',
      data: {
        company_id: companyId,
        campaign_id: campaignId,
        contact_ids: selectedIds,
        schedule_days: scheduleDays,
        schedule_time: scheduleTime
      }
    }).done(function(response) {
      if (!response || !response.success) {
        updateStatus((response && response.message) ? response.message : 'Could not move the selected numbers.', 'warning');
        return;
      }

      updateStatus(response.message || 'Selected numbers moved to Contacts.', 'success');
      $('#selectAllNotDialed').prop('checked', false);
      $('.notdialed-schedule-day').prop('checked', false);
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