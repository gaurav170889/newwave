<main class="content">
  <div class="container-fluid p-0">
    <div class="card shadow-sm border-info mb-3" id="scheduledCallsPanel"
         data-is-super-admin="<?php echo !empty($isSuperAdmin) ? '1' : '0'; ?>"
         data-company-id="<?php echo intval($selectedCompanyId ?? 0); ?>">
      <div class="card-body">
        <h5 class="text-uppercase text-info mb-3" style="letter-spacing: 0.08em; font-size: 0.95rem;">Scheduled Calls Status</h5>

        <div class="form-row align-items-end">
          <div class="form-group col-md-3">
            <label for="scheduledCompanySelect">Select Company</label>
            <select class="form-control" id="scheduledCompanySelect" <?php echo !empty($isSuperAdmin) ? '' : 'disabled'; ?>>
              <option value="">Select Company</option>
              <?php foreach (($companies ?? []) as $company): ?>
                <option value="<?php echo intval($company['id']); ?>" <?php echo intval($selectedCompanyId ?? 0) === intval($company['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label for="scheduledCampaignSelect">Select Campaign</label>
            <select class="form-control" id="scheduledCampaignSelect">
              <option value="">All Campaigns</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="scheduledStatusSelect">Status</label>
            <select class="form-control" id="scheduledStatusSelect">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="pending_agent">Pending Agent</option>
              <option value="dialing">Dialing</option>
              <option value="connected">Connected</option>
              <option value="completed">Completed</option>
              <option value="no_answer">No Answer</option>
              <option value="failed">Failed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="scheduledRouteTypeSelect">Route Type</label>
            <select class="form-control" id="scheduledRouteTypeSelect">
              <option value="Agent" selected>Agent</option>
            </select>
          </div>
          <div class="form-group col-md-2">
            <button type="button" class="btn btn-outline-secondary btn-block" id="clearScheduledFilters">Clear Filters</button>
          </div>
        </div>

        <div class="alert alert-info py-2 px-3 mb-0" id="scheduledCallsStatusMsg">
          This view shows single-contact scheduled callbacks from <strong>`Dialed Numbers`</strong> that are assigned to a specific agent destination.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <table id="scheduledCallsTable" class="table table-striped table-bordered w-100">
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Number</th>
              <th>Name</th>
              <th>Route</th>
              <th>Destination</th>
              <th>Scheduled For</th>
              <th>Status</th>
              <th>Disposition</th>
              <th>Last Attempt</th>
              <th>Started At</th>
              <th>Completed At</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script>
$(document).ready(function() {
  const panel = $('#scheduledCallsPanel');
  const isSuperAdmin = String(panel.data('is-super-admin') || '0') === '1';
  const defaultCompanyId = String(panel.data('company-id') || $('#scheduledCompanySelect').val() || '');

  function escapeHtml(value) {
    return $('<div>').text(value || '').html();
  }

  function selectedCompanyId() {
    return isSuperAdmin ? ($('#scheduledCompanySelect').val() || '') : (defaultCompanyId || $('#scheduledCompanySelect').val() || '');
  }

  function updateStatus(message, type) {
    const alertType = type || 'info';
    $('#scheduledCallsStatusMsg')
      .removeClass('alert-info alert-warning alert-success')
      .addClass('alert-' + alertType)
      .html(message || '');
  }

  function loadCampaigns(autoSelectFirst) {
    const companyId = selectedCompanyId();
    $('#scheduledCampaignSelect').html('<option value="">Loading campaigns...</option>');

    if (!companyId) {
      $('#scheduledCampaignSelect').html('<option value="">All Campaigns</option>');
      updateStatus('Select a company first to load scheduled call records.', 'warning');
      reloadTable();
      return;
    }

    $.ajax({
      url: 'scheduledcalls/getcampaigns',
      type: 'GET',
      dataType: 'json',
      data: { company_id: companyId }
    }).done(function(rows) {
      let html = '<option value="">All Campaigns</option>';
      rows = Array.isArray(rows) ? rows : [];

      rows.forEach(function(row) {
        html += '<option value="' + escapeHtml(row.id) + '">' + escapeHtml(row.name) + '</option>';
      });

      $('#scheduledCampaignSelect').html(html);
      if (autoSelectFirst && rows.length > 0) {
        $('#scheduledCampaignSelect').val('');
      }
      updateStatus('Scheduled call status loaded. Use filters to narrow the list.', 'info');
      reloadTable();
    }).fail(function() {
      $('#scheduledCampaignSelect').html('<option value="">All Campaigns</option>');
      updateStatus('Could not load campaigns right now.', 'warning');
      reloadTable();
    });
  }

  function reloadTable() {
    if ($.fn.DataTable.isDataTable('#scheduledCallsTable')) {
      $('#scheduledCallsTable').DataTable().ajax.reload();
    }
  }

  function statusBadge(status) {
    const value = String(status || '').trim();
    const lower = value.toLowerCase();
    let color = '#6c757d';

    if (lower === 'pending' || lower === 'pending_agent') color = '#ffc107';
    else if (lower === 'dialing') color = '#17a2b8';
    else if (lower === 'connected' || lower === 'completed') color = '#28a745';
    else if (lower === 'no_answer') color = '#fd7e14';
    else if (lower === 'failed' || lower === 'cancelled') color = '#dc3545';

    return '<span class="badge badge-pill" style="background-color:' + color + '; color:#fff;">' + escapeHtml(value || 'Unknown') + '</span>';
  }

  $('#scheduledCallsTable').DataTable({
    ajax: {
      url: 'scheduledcalls/getrecords',
      type: 'POST',
      dataSrc: '',
      data: function(payload) {
        payload.company_id = selectedCompanyId();
        payload.campaign_id = $('#scheduledCampaignSelect').val() || '';
        payload.status = $('#scheduledStatusSelect').val() || '';
        payload.route_type = $('#scheduledRouteTypeSelect').val() || '';
      }
    },
    columns: [
      { data: 'campaign_name' },
      { data: 'contact_number' },
      { data: 'contact_name' },
      { data: 'route_type' },
      { data: 'destination_label' },
      { data: 'scheduled_for' },
      {
        data: 'status',
        render: function(data) {
          return statusBadge(data);
        }
      },
      { data: 'disposition_label' },
      { data: 'last_attempt_at' },
      { data: 'started_at' },
      { data: 'completed_at' },
      {
        data: 'note_text',
        render: function(data) {
          const text = String(data || '');
          if (!text) return '';
          const shortText = text.length > 70 ? text.substring(0, 70) + '...' : text;
          return '<span title="' + escapeHtml(text).replace(/"/g, '&quot;') + '">' + escapeHtml(shortText) + '</span>';
        }
      }
    ],
    order: [[5, 'asc']],
    responsive: true,
    pageLength: 25,
    language: {
      search: '_INPUT_',
      searchPlaceholder: 'Search scheduled calls'
    }
  });

  $('#scheduledCompanySelect').on('change', function() {
    loadCampaigns(true);
  });

  $('#scheduledCampaignSelect, #scheduledStatusSelect, #scheduledRouteTypeSelect').on('change', function() {
    reloadTable();
  });

  $('#clearScheduledFilters').on('click', function() {
    $('#scheduledStatusSelect').val('');
    $('#scheduledRouteTypeSelect').val('');
    loadCampaigns(true);
  });

  if (!selectedCompanyId() && isSuperAdmin && $('#scheduledCompanySelect option').length > 1) {
    $('#scheduledCompanySelect').val($('#scheduledCompanySelect option').eq(1).val());
  }

  if (selectedCompanyId()) {
    loadCampaigns(true);
  } else {
    updateStatus('Select a company first to load scheduled call records.', 'warning');
    reloadTable();
  }
});
</script>