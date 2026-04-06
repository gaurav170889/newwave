<main class="content">
  <div class="container-fluid p-0">
    <div class="card shadow-sm border-success mb-3" id="dialedNumbersPanel"
         data-is-super-admin="<?php echo !empty($isSuperAdmin) ? '1' : '0'; ?>"
         data-company-id="<?php echo intval($selectedCompanyId ?? 0); ?>">
      <div class="card-body">
        <h5 class="text-uppercase text-success mb-3" style="letter-spacing: 0.08em; font-size: 0.95rem;">Dialed Numbers Filters</h5>

        <div class="form-row align-items-end">
          <div class="form-group col-md-3">
            <label for="dialedCompanySelect">Select Company</label>
            <select class="form-control" id="dialedCompanySelect" <?php echo !empty($isSuperAdmin) ? '' : 'disabled'; ?>>
              <option value="">Select Company</option>
              <?php foreach (($companies ?? []) as $company): ?>
                <option value="<?php echo intval($company['id']); ?>" <?php echo intval($selectedCompanyId ?? 0) === intval($company['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label for="dialedCampaignSelect">Select Campaign</label>
            <select class="form-control" id="dialedCampaignSelect">
              <option value="">Select Campaign</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="dialedFilterTypeSelect">Select Type</label>
            <select class="form-control" id="dialedFilterTypeSelect">
              <option value="">All Dialed</option>
              <option value="agent">Agent</option>
              <option value="answered">Answered</option>
              <option value="not_answered">Not Answered</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="dialedFilterValueSelect">Select Value</label>
            <select class="form-control" id="dialedFilterValueSelect" disabled>
              <option value="">All Values</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <button type="button" class="btn btn-outline-secondary btn-block" id="clearDialedFilters">Clear Filters</button>
          </div>
        </div>

        <div class="form-row align-items-end">
          <div class="form-group col-md-4">
            <label for="dialedDpdSelect">
              Days Past Due (search + multi-select)
              <button type="button"
                      class="btn btn-link btn-sm text-info p-0 ml-1 align-baseline dialedInfoBtn"
                      data-toggle="popover"
                      data-trigger="focus"
                      data-placement="top"
                      data-html="true"
                      data-content="Search and select one or more DPD values, or leave empty to show all dialed numbers."
                      aria-label="Days Past Due help">
                <i class="fas fa-info-circle"></i>
              </button>
            </label>
            <select class="form-control" id="dialedDpdSelect" multiple="multiple"></select>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="d-block mb-2">Schedule the selected numbers for dialing</label>
          <div class="d-flex flex-wrap schedule-days-group mb-2">
            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
              <div class="form-check mr-3 mb-2">
                <input class="form-check-input dialed-schedule-day" type="checkbox" name="schedule_days[]" value="<?php echo $day; ?>" id="dialed_day_<?php echo strtolower($day); ?>">
                <label class="form-check-label" for="dialed_day_<?php echo strtolower($day); ?>"><?php echo $day; ?></label>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="form-row align-items-end">
            <div class="form-group col-md-3 mb-2">
              <label for="dialedScheduleTime">Preferred Time</label>
              <input type="time" class="form-control" id="dialedScheduleTime" value="09:00">
            </div>
            <div class="form-group col-md-3 mb-2">
              <button type="button" class="btn btn-success btn-block" id="moveDialedBtn">Move Selected To Contacts</button>
            </div>
          </div>
          <small class="text-muted">Answered / Not Answered is based on <strong>last_call_status</strong>. If you select weekday(s), the earliest upcoming selected day/time becomes the next dial slot.</small>
        </div>

        <div class="alert alert-info py-2 px-3 mb-0" id="dialedStatus">
          This table shows numbers that already have a <strong>dialer attempt</strong> or <strong>call status history</strong> and are not already part of today's Contacts batch.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table id="dialedNumbersTable" class="table table-striped table-bordered w-100">
          <thead>
            <tr>
              <th style="width: 40px;"><input type="checkbox" id="selectAllDialed"></th>
              <th>ID</th>
              <th>Campaign</th>
              <th>Number</th>
              <th>Name</th>
              <th>Days Past Due</th>
              <th>Type</th>
              <th>Feedback</th>
              <th>State</th>
              <th>Attempts</th>
              <th>Last Try At</th>
              <th>Agent</th>
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
#dialedNumbersPanel .select2-container {
  width: 100% !important;
}
#dialedNumbersPanel .select2-container--default .select2-selection--multiple,
#dialedNumbersPanel .select2-container--default .select2-selection--single {
  min-height: calc(2.25rem + 2px);
  border: 1px solid #ced4da;
  border-radius: 0.2rem;
}
#dialedNumbersPanel .select2-container--default .select2-selection--multiple .select2-selection__choice {
  background-color: #28a745;
  border: 0;
  color: #fff;
  padding: 2px 8px;
}
#dialedNumbersPanel .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
  color: rgba(255, 255, 255, 0.85);
  margin-right: 6px;
}
#dialedNumbersPanel .dialedInfoBtn {
  text-decoration: none;
  box-shadow: none !important;
}
.schedule-days-group .form-check {
  min-width: 110px;
}
</style>

<script>
$(document).ready(function() {
  const panel = $('#dialedNumbersPanel');
  const isSuperAdmin = String(panel.data('is-super-admin') || '0') === '1';
  const defaultCompanyId = String(panel.data('company-id') || $('#dialedCompanySelect').val() || '');

  $('[data-toggle="popover"]').popover();

  function escapeHtml(value) {
    return $('<div>').text(value || '').html();
  }

  function selectedCompanyId() {
    return isSuperAdmin ? ($('#dialedCompanySelect').val() || '') : (defaultCompanyId || $('#dialedCompanySelect').val() || '');
  }

  function selectedCampaignId() {
    return $('#dialedCampaignSelect').val() || '';
  }

  function selectedFilterType() {
    return $('#dialedFilterTypeSelect').val() || '';
  }

  function selectedFilterValue() {
    return $('#dialedFilterValueSelect').val() || '';
  }

  function selectedDpdValues() {
    return $('#dialedDpdSelect').val() || [];
  }

  function refreshDpdSelect(selectedValues) {
    const $select = $('#dialedDpdSelect');
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

    $select.val(values).trigger('change.select2');
  }

  function refreshValueSelect(selectedValue) {
    const $select = $('#dialedFilterValueSelect');
    const value = selectedValue || '';

    if ($.fn.select2) {
      if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
      }

      $select.select2({
        width: '100%',
        placeholder: 'Select Value',
        allowClear: true
      });
    }

    $select.val(value).trigger('change.select2');
  }

  refreshDpdSelect([]);
  refreshValueSelect('');

  function updateStatus(message, type) {
    const alertType = type || 'info';
    $('#dialedStatus')
      .removeClass('alert-info alert-warning alert-success')
      .addClass('alert-' + alertType)
      .html(message || '');
  }

  function reloadTable() {
    if ($.fn.DataTable.isDataTable('#dialedNumbersTable')) {
      $('#dialedNumbersTable').DataTable().ajax.reload();
    }
  }

  function loadCampaigns(autoSelectFirst) {
    const companyId = selectedCompanyId();
    $('#dialedCampaignSelect').html('<option value="">Loading campaigns...</option>');
    $('#dialedFilterValueSelect').prop('disabled', true).html('<option value="">All Values</option>');
    $('#dialedDpdSelect').html('');
    refreshValueSelect('');
    refreshDpdSelect([]);

    if (!companyId) {
      $('#dialedCampaignSelect').html('<option value="">Select Campaign</option>');
      updateStatus('Select a company first to load campaigns.', 'warning');
      reloadTable();
      return;
    }

    $.ajax({
      url: 'dialednumbers/getcampaigns',
      type: 'GET',
      dataType: 'json',
      data: { company_id: companyId }
    }).done(function(rows) {
      let html = '<option value="">Select Campaign</option>';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        html += '<option value="' + escapeHtml(row.id) + '">' + escapeHtml(row.name) + '</option>';
      });

      $('#dialedCampaignSelect').html(html);

      if (autoSelectFirst && rows.length > 0) {
        $('#dialedCampaignSelect').val(String(rows[0].id));
        updateStatus('Campaign selected. You can now filter dialed numbers by type, value, and DPD.', 'info');
        loadFilterValues();
      } else if (rows.length === 0) {
        updateStatus('No campaigns were found for the selected company.', 'warning');
        reloadTable();
      } else {
        loadFilterValues();
      }
    }).fail(function() {
      $('#dialedCampaignSelect').html('<option value="">Select Campaign</option>');
      updateStatus('Could not load campaigns right now.', 'warning');
      reloadTable();
    });
  }

  function loadFilterValues() {
    const companyId = selectedCompanyId();
    const campaignId = selectedCampaignId();
    const filterType = selectedFilterType();
    const $valueSelect = $('#dialedFilterValueSelect');

    $valueSelect.prop('disabled', true).html('<option value="">All Values</option>');
    refreshValueSelect('');

    if (!companyId || !campaignId) {
      loadDpdValues();
      return;
    }

    if (!filterType) {
      loadDpdValues();
      return;
    }

    $.ajax({
      url: 'dialednumbers/getfiltervalues',
      type: 'GET',
      dataType: 'json',
      data: {
        company_id: companyId,
        campaign_id: campaignId,
        filter_type: filterType
      }
    }).done(function(rows) {
      let html = '<option value="">All Values</option>';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        html += '<option value="' + escapeHtml(row.value) + '">' + escapeHtml(row.label) + '</option>';
      });

      $valueSelect.html(html);

      if (rows.length > 0) {
        const defaultValue = rows[0].value ? String(rows[0].value) : '';
        $valueSelect.prop('disabled', false);
        refreshValueSelect(defaultValue);
      } else {
        refreshValueSelect('');
      }

      loadDpdValues();
    }).fail(function() {
      updateStatus('Could not load filter values right now.', 'warning');
      loadDpdValues();
    });
  }

  function loadDpdValues() {
    const companyId = selectedCompanyId();
    const campaignId = selectedCampaignId();

    if (!companyId || !campaignId) {
      $('#dialedDpdSelect').html('');
      refreshDpdSelect([]);
      reloadTable();
      return;
    }

    $.ajax({
      url: 'dialednumbers/getdpdvalues',
      type: 'GET',
      dataType: 'json',
      data: {
        company_id: companyId,
        campaign_id: campaignId,
        filter_type: selectedFilterType(),
        filter_value: selectedFilterValue()
      }
    }).done(function(rows) {
      let html = '';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        html += '<option value="' + escapeHtml(row.value) + '">' + escapeHtml(row.label) + '</option>';
      });

      $('#dialedDpdSelect').html(html);
      refreshDpdSelect([]);
      reloadTable();
    }).fail(function() {
      $('#dialedDpdSelect').html('');
      refreshDpdSelect([]);
      updateStatus('Could not load Days Past Due values right now.', 'warning');
      reloadTable();
    });
  }

  const dialedTable = $('#dialedNumbersTable').DataTable({
    ajax: {
      url: 'dialednumbers/getrecords',
      type: 'POST',
      dataSrc: '',
      data: function(payload) {
        payload.company_id = selectedCompanyId();
        payload.campaign_id = selectedCampaignId();
        payload.filter_type = selectedFilterType();
        payload.filter_value = selectedFilterValue();
        payload.days_past_due = selectedDpdValues();
      }
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
          return '<input type="checkbox" class="dialed-row" value="' + row.id + '">';
        }
      },
      { data: 'id' },
      { data: 'campaign_name' },
      { data: 'number' },
      { data: 'name' },
      { data: 'days_past_due' },
      { data: 'type' },
      { data: 'feedback' },
      { data: 'state' },
      { data: 'attempts' },
      { data: 'last_try_dt' },
      { data: 'agent_name' },
      { data: 'next_call_at' },
      { data: 'created_at' }
    ],
    order: [[10, 'desc']],
    responsive: true,
    search: {
      return: true
    },
    language: {
      search: '_INPUT_',
      searchPlaceholder: 'Search dialed numbers'
    }
  });

  $('#dialedCompanySelect').on('change', function() {
    loadCampaigns(true);
  });

  $('#dialedCampaignSelect').on('change', function() {
    loadFilterValues();
  });

  $('#dialedFilterTypeSelect').on('change', function() {
    loadFilterValues();
  });

  $('#dialedFilterValueSelect').on('change', function() {
    loadDpdValues();
  });

  $('#dialedDpdSelect').on('change', function() {
    reloadTable();
  });

  $('#clearDialedFilters').on('click', function() {
    $('#dialedFilterTypeSelect').val('');
    $('#dialedFilterValueSelect').prop('disabled', true).html('<option value="">All Values</option>');
    $('#dialedDpdSelect').val([]);
    refreshValueSelect('');
    refreshDpdSelect([]);
    $('.dialed-schedule-day').prop('checked', false);
    $('#dialedScheduleTime').val('09:00');
    loadCampaigns(true);
  });

  $('#selectAllDialed').on('change', function() {
    $('.dialed-row').prop('checked', $(this).is(':checked'));
  });

  $('#dialedNumbersTable').on('draw.dt', function() {
    $('#selectAllDialed').prop('checked', false);
  });

  $('#moveDialedBtn').on('click', function() {
    const selectedIds = $('.dialed-row:checked').map(function() {
      return $(this).val();
    }).get();
    const scheduleDays = $('.dialed-schedule-day:checked').map(function() {
      return $(this).val();
    }).get();
    const scheduleTime = $('#dialedScheduleTime').val() || '09:00';
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
      url: 'dialednumbers/move_to_contacts',
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
      $('#selectAllDialed').prop('checked', false);
      $('.dialed-schedule-day').prop('checked', false);
      reloadTable();
    }).fail(function() {
      updateStatus('Failed to move the selected numbers right now.', 'warning');
    });
  });

  if (!selectedCompanyId() && isSuperAdmin && $('#dialedCompanySelect option').length > 1) {
    $('#dialedCompanySelect').val($('#dialedCompanySelect option').eq(1).val());
  }

  if (selectedCompanyId()) {
    loadCampaigns(true);
  } else {
    updateStatus('Select a company first to load campaigns.', 'warning');
    reloadTable();
  }
});
</script>