<!-- Disposition Modal -->
<div class="modal fade" id="dialedDispositionModal" tabindex="-1" role="dialog" aria-labelledby="dialedDispositionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dialedDispositionModalLabel">Update Call Disposition</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="dialedDispositionForm">
          <input type="hidden" id="dialed_dispo_contact_id" name="contact_id">

          <div class="form-group">
            <label for="dialed_dispo_select">Disposition</label>
            <select class="form-control" id="dialed_dispo_select" name="disposition" required>
              <option value="">Select Disposition...</option>
            </select>
          </div>

          <div class="form-group" id="dialed_dispo_schedule_div" style="display:none;">
            <label>Schedule Callback / Retry</label>
            <div class="row">
              <div class="col-md-6">
                <input type="date" class="form-control" id="dialed_dispo_date" name="callback_date">
              </div>
              <div class="col-md-6">
                <input type="time" class="form-control" id="dialed_dispo_time" name="callback_time">
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-md-12" id="dialed_dispo_agent_wrap">
                <input type="hidden" id="dialed_dispo_route_type" name="route_type" value="Agent">
                <label for="dialed_dispo_agent_id" class="mb-1">Select Agent Destination</label>
                <select class="form-control" id="dialed_dispo_agent_id" name="agent_id">
                  <option value="">Select Agent</option>
                </select>
              </div>
            </div>
            <small class="text-muted d-block mt-2">Scheduled calls from Dialed Numbers are one contact at a time and always transfer to the selected agent after the customer answers.</small>
          </div>

          <div class="form-group">
            <label>History</label>
            <textarea class="form-control" id="dialed_dispo_history" rows="3" readonly style="font-size: 0.85em; background-color: #f8f9fa;"></textarea>
          </div>

          <div class="form-group">
            <label for="dialed_dispo_notes">Notes / Reason</label>
            <textarea class="form-control" id="dialed_dispo_notes" name="notes" rows="3" placeholder="Enter reason or notes..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="submitDialedDisposition()">Save changes</button>
      </div>
    </div>
  </div>
</div>

<main class="content">
  <div class="container-fluid p-0">
    <div class="card shadow-sm border-success mb-3" id="dialedNumbersPanel"
         data-is-super-admin="<?php echo !empty($isSuperAdmin) ? '1' : '0'; ?>"
         data-is-agent-user="<?php echo !empty($isAgentUser) ? '1' : '0'; ?>"
         data-can-manage="<?php echo !empty($canManageDialedContacts) ? '1' : '0'; ?>"
         data-company-id="<?php echo intval($selectedCompanyId ?? 0); ?>">
      <div class="card-body">
        <h5 class="text-uppercase text-success mb-3" style="letter-spacing: 0.08em; font-size: 0.95rem;">Dialed Numbers Filters</h5>

        <div class="form-row align-items-end dialed-compact-row">
          <div class="form-group col-lg-3 col-md-6">
            <label for="dialedCompanySelect">Company</label>
            <select class="form-control form-control-sm" id="dialedCompanySelect" <?php echo !empty($isSuperAdmin) ? '' : 'disabled'; ?>>
              <option value="">Select Company</option>
              <?php foreach (($companies ?? []) as $company): ?>
                <option value="<?php echo intval($company['id']); ?>" <?php echo intval($selectedCompanyId ?? 0) === intval($company['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-lg-3 col-md-6">
            <label for="dialedCampaignSelect">Campaign</label>
            <select class="form-control form-control-sm" id="dialedCampaignSelect">
              <option value="">Select Campaign</option>
            </select>
          </div>
          <div class="form-group col-lg-2 col-md-4">
            <label for="dialedFilterTypeSelect">Type</label>
            <select class="form-control form-control-sm" id="dialedFilterTypeSelect">
              <option value="">All Connected</option>
              <option value="agent">Agent</option>
              <option value="answered">Answered</option>
            </select>
          </div>
          <div class="form-group col-lg-2 col-md-4">
            <label for="dialedFilterValueSelect">Value</label>
            <select class="form-control form-control-sm" id="dialedFilterValueSelect" disabled>
              <option value="">All Values</option>
            </select>
          </div>
          <div class="form-group col-lg-2 col-md-4">
            <button type="button" class="btn btn-outline-secondary btn-sm btn-block" id="clearDialedFilters">Clear</button>
          </div>

          <div class="form-group col-lg-4 col-md-8">
            <label for="dialedDpdSelect">
              DPD
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
            <select class="form-control form-control-sm" id="dialedDpdSelect" multiple="multiple"></select>
          </div>
        </div>

        <?php if (!empty($canManageDialedContacts)): ?>
        <div class="form-group mb-2">
          <label class="d-block mb-1">Schedule selected numbers</label>
          <div class="form-row align-items-end dialed-compact-row">
            <div class="form-group col-lg-3 col-md-4 mb-2">
              <label for="dialedScheduleDate">Date</label>
              <input type="date" class="form-control form-control-sm" id="dialedScheduleDate">
            </div>
            <div class="form-group col-lg-2 col-md-3 mb-2">
              <label for="dialedScheduleTime">Time</label>
              <input type="time" class="form-control form-control-sm" id="dialedScheduleTime" value="09:00" min="09:00" max="18:00" step="1800">
            </div>
            <div class="form-group col-lg-5 col-md-5 mb-2">
              <small class="text-muted d-block pt-md-4" id="dialedScheduleMeta">Pick a future date using the selected campaign's weekdays. Time must stay within that campaign's start/stop window in the PBX timezone.</small>
            </div>
            <div class="form-group col-lg-2 col-md-12 mb-2">
              <button type="button" class="btn btn-success btn-sm btn-block" id="moveDialedBtn">Move To Contacts</button>
            </div>
          </div>
          <small class="text-muted">Leave the schedule date empty to move the numbers as <strong>READY</strong>.</small>
        </div>
        <?php else: ?>
        <div class="alert alert-light border mb-2">
          You are viewing only the calls you answered. Use the <strong>Disposition</strong> action to update callback, retry, DNC, or closed outcomes.
        </div>
        <?php endif; ?>

        <div class="alert alert-info py-2 px-3 mb-0" id="dialedStatus">
          This table shows all numbers whose calls were <strong>connected to agents</strong>, including <strong>today's calls</strong>, sorted by the latest system call first.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table id="dialedNumbersTable" class="table table-striped table-bordered w-100">
          <thead>
            <tr>
              <th style="width: 40px;"><?php if (!empty($canManageDialedContacts)): ?><input type="checkbox" id="selectAllDialed"><?php endif; ?></th>
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
              <th>Disposition</th>
              <th>Next Call At</th>
              <th>Created At</th>
              <th>Action</th>
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
#dialedNumbersPanel .dialed-compact-row .form-group {
  margin-bottom: .6rem;
}
#dialedNumbersPanel label {
  font-size: .82rem;
  margin-bottom: .25rem;
}
#dialedNumbersPanel .form-control-sm,
#dialedNumbersPanel .btn-sm {
  font-size: .82rem;
}
#dialedNumbersPanel .select2-container--default .select2-selection--multiple,
#dialedNumbersPanel .select2-container--default .select2-selection--single {
  min-height: calc(1.8rem + 2px);
  border: 1px solid #ced4da;
  border-radius: 0.2rem;
}
#dialedNumbersPanel .select2-container--default .select2-selection--multiple .select2-selection__choice {
  background-color: #28a745;
  border: 0;
  color: #fff;
  padding: 1px 6px;
  font-size: .75rem;
}
#dialedNumbersPanel .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
  color: rgba(255, 255, 255, 0.85);
  margin-right: 6px;
}
#dialedNumbersPanel .dialedInfoBtn {
  text-decoration: none;
  box-shadow: none !important;
}
#dialedScheduleMeta {
  line-height: 1.35;
  font-size: .78rem;
}
</style>

<script>
$(document).ready(function() {
  const panel = $('#dialedNumbersPanel');
  const isSuperAdmin = String(panel.data('is-super-admin') || '0') === '1';
  const isAgentUser = String(panel.data('is-agent-user') || '0') === '1';
  const canManageDialedContacts = String(panel.data('can-manage') || '0') === '1';
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

  function selectedCampaignMeta() {
    const $selected = $('#dialedCampaignSelect option:selected');
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
    const $date = $('#dialedScheduleDate');
    const $time = $('#dialedScheduleTime');
    const allowedDaysText = meta.weekdays.length ? meta.weekdays.join(', ') : 'All days';

    if (meta.today) {
      $date.attr('min', meta.today);
    }

    $time.attr('min', meta.minTime).attr('max', meta.maxTime);
    if (!$time.val() || $time.val() < meta.minTime || $time.val() > meta.maxTime) {
      $time.val(meta.minTime);
    }

    $('#dialedScheduleMeta').html(
      'PBX timezone: <strong>' + escapeHtml(meta.timezone) + '</strong><br>' +
      'Allowed weekdays: <strong>' + escapeHtml(allowedDaysText) + '</strong><br>' +
      'Allowed time: <strong>' + escapeHtml(meta.minTime) + ' to ' + escapeHtml(meta.maxTime) + '</strong>'
    );
  }

  function validateScheduleInputs(showMessage) {
    const meta = selectedCampaignMeta();
    const scheduleDate = $('#dialedScheduleDate').val() || '';
    const rawScheduleTime = $('#dialedScheduleTime').val() || '';
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

  function applyDispositionConstraints() {
    const meta = selectedCampaignMeta();
    const $date = $('#dialed_dispo_date');
    const $time = $('#dialed_dispo_time');

    if (meta.today) {
      $date.attr('min', meta.today);
    }

    $time.attr('min', meta.minTime).attr('max', meta.maxTime);
    if (!$time.val() || $time.val() < meta.minTime || $time.val() > meta.maxTime) {
      $time.val(meta.minTime);
    }
  }

  function validateDispositionSchedule(showMessage) {
    const meta = selectedCampaignMeta();
    const scheduleDate = $('#dialed_dispo_date').val() || '';
    const rawScheduleTime = $('#dialed_dispo_time').val() || '';
    const scheduleTime = rawScheduleTime || meta.minTime || '09:00';

    if (!scheduleDate || !rawScheduleTime) {
      if (showMessage !== false) {
        updateStatus('Please select both callback date and callback time.', 'warning');
      }
      return false;
    }

    if (scheduleTime < meta.minTime || scheduleTime > meta.maxTime) {
      if (showMessage !== false) {
        updateStatus('Please select a callback time between ' + meta.minTime + ' and ' + meta.maxTime + ' in the PBX timezone.', 'warning');
      }
      return false;
    }

    const selectedDate = new Date(scheduleDate + 'T12:00:00');
    if (isNaN(selectedDate.getTime())) {
      if (showMessage !== false) {
        updateStatus('Please select a valid callback date.', 'warning');
      }
      return false;
    }

    const weekdayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][selectedDate.getDay()];
    if (meta.weekdays.length && meta.weekdays.indexOf(weekdayName) === -1) {
      if (showMessage !== false) {
        updateStatus('The selected callback date falls on ' + weekdayName + '. Allowed campaign weekdays: ' + meta.weekdays.join(', ') + '.', 'warning');
      }
      return false;
    }

    return true;
  }

  function toggleDialedRouteFields() {
    $('#dialed_dispo_agent_wrap').show();
    $('#dialed_dispo_agent_id').prop('disabled', false);
  }

  function loadDispositionAgents(preselectedAgentId) {
    const companyId = selectedCompanyId();
    const $agentSelect = $('#dialed_dispo_agent_id');
    $agentSelect.html('<option value="">Loading agents...</option>');

    if (!companyId) {
      $agentSelect.html('<option value="">Select Agent</option>');
      return;
    }

    $.ajax({
      url: 'dialednumbers/getagents',
      type: 'GET',
      dataType: 'json',
      data: { company_id: companyId }
    }).done(function(rows) {
      let html = '<option value="">Select Agent</option>';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        const agentId = String(row.agent_id || '');
        if (!agentId) {
          return;
        }
        let label = row.agent_name || ('Agent ' + agentId);
        if (row.agent_ext) {
          label += ' (' + row.agent_ext + ')';
        }
        html += '<option value="' + escapeHtml(agentId) + '">' + escapeHtml(label) + '</option>';
      });

      $agentSelect.html(html);
      if (preselectedAgentId) {
        $agentSelect.val(String(preselectedAgentId));
      }
    }).fail(function() {
      $agentSelect.html('<option value="">Select Agent</option>');
      updateStatus('Could not load the agent list right now.', 'warning');
    });
  }

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
        const weekdaysJson = encodeURIComponent(JSON.stringify(Array.isArray(row.weekdays) ? row.weekdays : []));
        html += '<option value="' + escapeHtml(row.id) + '"' +
          ' data-weekdays="' + weekdaysJson + '"' +
          ' data-timezone="' + escapeHtml(row.timezone || '') + '"' +
          ' data-today="' + escapeHtml(row.today_label || '') + '"' +
          ' data-min-time="' + escapeHtml(row.min_time || '09:00') + '"' +
          ' data-max-time="' + escapeHtml(row.max_time || '18:00') + '">' +
          escapeHtml(row.name) + '</option>';
      });

      $('#dialedCampaignSelect').html(html);
      applyScheduleConstraints();

      if (autoSelectFirst && rows.length > 0) {
        $('#dialedCampaignSelect').val(String(rows[0].id));
        applyScheduleConstraints();
        updateStatus('Campaign selected. You can now filter dialed numbers and choose a valid schedule date/time.', 'info');
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
          return canManageDialedContacts ? '<input type="checkbox" class="dialed-row" value="' + row.id + '">' : '';
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
      {
        data: 'disposition',
        render: function(data, type, row) {
          if (data && type === 'display') {
            var color = row.color_code || '#808080';
            return '<span class="badge badge-pill" style="background-color:' + color + '; color:#fff; font-size:100%;">' + escapeHtml(data) + '</span>';
          }
          return data || '';
        }
      },
      { data: 'next_call_at' },
      { data: 'created_at' },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
          if (parseInt(row.attempts_used || 0, 10) <= 0) {
            return '';
          }

          var safeNotes = encodeURIComponent(row.notes || '');
          var tooltipTitle = '';

          if (row.notes) {
            try {
              var parsed = JSON.parse(row.notes);
              if (Array.isArray(parsed) && parsed.length > 0) {
                var last = parsed[parsed.length - 1];
                tooltipTitle = (last.date || '') + '<br>' + (last.note || '') + '<br>By: ' + (last.user || 'Unknown');
              }
            } catch (e) {
              var lines = String(row.notes).split('\n');
              for (var i = lines.length - 1; i >= 0; i--) {
                if (lines[i].trim() !== '') {
                  tooltipTitle = lines[i];
                  break;
                }
              }
            }
          }

          var tooltipAttr = tooltipTitle ? 'data-toggle="popover" data-trigger="hover" data-html="true" data-content="' + tooltipTitle.replace(/"/g, '&quot;') + '"' : '';
          var iconHtml = tooltipTitle ? '<i class="fas fa-sticky-note text-primary mr-2" style="cursor:pointer; font-size:1.1em;" ' + tooltipAttr + '></i>' : '<span class="mr-4"></span>';
          var nextCall = row.next_call_at || '';

          return '<div class="d-flex align-items-center justify-content-center">'
                 + iconHtml
                 + '<button class="btn btn-sm btn-info open-dialed-dispo" data-id="' + row.id + '" data-notes="' + safeNotes + '" data-disposition="' + escapeHtml(row.disposition || '') + '" data-schedule="' + escapeHtml(nextCall) + '">Disposition</button>'
                 + '</div>';
        }
      }
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
    applyScheduleConstraints();
    applyDispositionConstraints();
    loadFilterValues();
  });

  $('#dialedScheduleDate, #dialedScheduleTime').on('change', function() {
    validateScheduleInputs(true);
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
    if (!isAgentUser) {
      $('#dialedFilterTypeSelect').val('');
    }
    $('#dialedFilterValueSelect').prop('disabled', true).html('<option value="">All Values</option>');
    $('#dialedDpdSelect').val([]);
    refreshValueSelect('');
    refreshDpdSelect([]);
    $('#dialedScheduleDate').val('');
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
    const scheduleDate = $('#dialedScheduleDate').val() || '';
    const scheduleTime = $('#dialedScheduleTime').val() || (selectedCampaignMeta().minTime || '09:00');
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
      url: 'dialednumbers/move_to_contacts',
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
      $('#selectAllDialed').prop('checked', false);
      $('#dialedScheduleDate').val('');
      reloadTable();
    }).fail(function() {
      updateStatus('Failed to move the selected numbers right now.', 'warning');
    });
  });

  $.ajax({
    url: 'disposition/getdisposition',
    type: 'POST',
    dataType: 'json',
    data: { action: 'getdisposition' }
  }).done(function(response) {
    var options = '<option value="">Select Disposition...</option>';
    $.each(response || [], function(index, item) {
      options += '<option value="' + escapeHtml(item.label) + '" data-type="' + escapeHtml(item.action_type || '') + '">' + escapeHtml(item.label) + '</option>';
    });
    $('#dialed_dispo_select').html(options);
  });

  $('#dialed_dispo_select').on('change', function() {
    var selectedType = String($(this).find(':selected').data('type') || '').toLowerCase();
    if (selectedType === 'callback' || selectedType === 'retry') {
      $('#dialed_dispo_schedule_div').show();
      applyDispositionConstraints();
      toggleDialedRouteFields();
      loadDispositionAgents($('#dialed_dispo_agent_id').val() || '');
    } else {
      $('#dialed_dispo_schedule_div').hide();
      $('#dialed_dispo_date').val('');
      $('#dialed_dispo_time').val('');
      $('#dialed_dispo_agent_id').html('<option value="">Select Agent</option>').val('');
      toggleDialedRouteFields();
    }
  });

  $('#dialedNumbersTable').on('click', '.open-dialed-dispo', function() {
    var id = $(this).data('id');
    var currentDispo = $(this).data('disposition') || '';
    var schedule = $(this).data('schedule') || '';
    var notes = '';

    try {
      notes = decodeURIComponent($(this).data('notes') || '');
    } catch (e) {
      notes = $(this).data('notes') || '';
    }

    $('#dialed_dispo_contact_id').val(id);
    $('#dialed_dispo_route_type').val('Agent');
    $('#dialed_dispo_agent_id').html('<option value="">Select Agent</option>').val('');
    toggleDialedRouteFields();
    applyDispositionConstraints();
    $('#dialed_dispo_select').val(currentDispo).trigger('change');
    $('#dialed_dispo_notes').val('');

    if (schedule && String(schedule).length >= 16) {
      $('#dialed_dispo_date').val(String(schedule).substring(0, 10));
      $('#dialed_dispo_time').val(String(schedule).substring(11, 16));
    } else {
      $('#dialed_dispo_date').val('');
      $('#dialed_dispo_time').val(selectedCampaignMeta().minTime || '09:00');
    }

    var displayHistory = '';
    if (notes) {
      try {
        var parsed = JSON.parse(notes);
        if (Array.isArray(parsed)) {
          for (var i = parsed.length - 1; i >= 0; i--) {
            var item = parsed[i];
            if (item.note) {
              displayHistory += '[' + (item.date || '') + '] ' + (item.user || 'Unknown') + ': ' + item.note + '\n';
            }
          }
        }
      } catch (e) {
        displayHistory = String(notes).split('\n').filter(function(line) {
          return line.trim() !== '';
        }).reverse().join('\n');
      }
    }
    $('#dialed_dispo_history').val(displayHistory);
    $('#dialedDispositionModal').modal('show');
  });

  window.submitDialedDisposition = function() {
    var selectedType = String($('#dialed_dispo_select').find(':selected').data('type') || '').toLowerCase();

    if (selectedType === 'callback' || selectedType === 'retry') {
      if (!validateDispositionSchedule(true)) {
        return;
      }

      if (!($('#dialed_dispo_agent_id').val() || '')) {
        updateStatus('Please select the agent who should receive this scheduled call.', 'warning');
        return;
      }
    }

    var formData = $('#dialedDispositionForm').serialize();

    $.ajax({
      url: 'dialednumbers/updateDispositionSql',
      type: 'POST',
      dataType: 'json',
      data: formData
    }).done(function(response) {
      if (response && response.success) {
        $('#dialedDispositionModal').modal('hide');
        updateStatus(response.message || 'Disposition updated successfully.', 'success');
        reloadTable();
      } else {
        updateStatus((response && (response.message || response.error)) ? (response.message || response.error) : 'Failed to update disposition.', 'warning');
      }
    }).fail(function() {
      updateStatus('Failed to update disposition right now.', 'warning');
    });
  };

  if (isAgentUser) {
    $('#dialedFilterTypeSelect').val('answered').prop('disabled', true);
    updateStatus('Showing only the answered calls connected to you. Use the Action button to submit disposition.', 'info');
  }

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
