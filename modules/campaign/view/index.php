
	<!--<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
		<div class="container">
			
			<div  class="row justify-content-center">
				<div class="col-lg-12">
				<button type="button" class="btn btn-lg btn-primary" id="add_agent" data-toggle="modal" data-target="#exampleModalCenter" >Add Agent</button>	
				</div>
			</div>
		</div>
	</div>-->
	
	
	
		
	<!-- End Update Design Modal -->
		
	<!-- Delete Design Modal -->
		


<!-- Modal -->
<div class="modal fade" id="addCampaignModal" tabindex="-1" role="dialog" aria-labelledby="addCampaignModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="campaignForm" class="modal-content needs-validation" novalidate>
      <div class="modal-header">
        <h5 class="modal-title" id="addCampaignModalLabel">Add Campaign</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <!-- Name -->
        <div class="form-group">
          <label for="campaignName">Name</label>
          <input type="text" class="form-control" id="campaignName" name="name" required>
        </div>

        <?php if (!empty($companies)): ?>
        <div class="form-group">
          <label for="companyId">Select Company</label>
          <select class="form-control" id="companyId" name="company_id" required>
            <option value="">Select Company</option>
            <?php foreach ($companies as $company): ?>
              <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Dialer Mode -->
        <div class="form-group">
            <label for="dialerMode">Dialer Mode</label>
            <select class="form-control" id="dialerMode" name="dialer_mode" required>
                <option value="Predictive Dialer" selected>Predictive Dialer</option>
                <option value="Power Dialer">Power Dialer</option>
            </select>
        </div>

        <!-- Route To Type -->
        <div class="form-group">
            <label for="routeType">Route Type</label>
            <select class="form-control" id="routeType" name="route_type" required>
                <option value="Queue">Queue</option>
                <option value="Extension">Extension</option>
                <option value="IVR">IVR</option>
            </select>
        </div>

        <!-- Route To (Value) -->
        <div class="form-group">
          <label for="routeto">Route To (Number/ID)</label>
          <input type="number" class="form-control" id="routeto" name="routeto" required>
        </div>

        <!-- DN Number (Dialer Extension) -->
        <div class="form-group">
            <label for="dnNumber">DN Number (Dialer Extension)</label>
            <input type="text" class="form-control" id="dnNumber" name="dn_number" placeholder="e.g. 802">
        </div>

        <!-- Concurrent Calls -->
        <div class="form-group">
            <label for="concurrentCalls">Concurrent Calls (Half of 3CX license recommended)</label>
            <input type="number" class="form-control" id="concurrentCalls" name="concurrent_calls" value="1" min="1" required>
        </div>

        <div class="form-group" id="emptyDialNotifyWrapper">
            <label class="d-block mb-2">Empty List Notification</label>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="notifyNoLeadsEmail" name="notify_no_leads_email" value="1">
                <label class="form-check-label" for="notifyNoLeadsEmail">
                    Send one email when no PST/DPD numbers are left to dial
                </label>
            </div>
            <input type="email" class="form-control" id="notifyEmail" name="notify_email" placeholder="alerts@example.com" disabled>
            <small class="form-text text-muted">The predictive dialer will send this only once per campaign run and then mark it as sent.</small>
        </div>

        <!-- Return Call (1, 2, or 3 only) -->
        <div class="form-group">
          <label for="returncall">Return Call</label>
          <select class="form-control" id="returncall" name="returncall" required>
            <option value="">Select</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
          </select>
        </div>

        <!-- Weekdays -->
        <div class="form-group">
          <label>Weekdays</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Monday" id="monday">
            <label class="form-check-label" for="monday">Monday</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Tuesday" id="tuesday">
            <label class="form-check-label" for="tuesday">Tuesday</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Wednesday" id="wednesday">
            <label class="form-check-label" for="wednesday">Wednesday</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Thursday" id="thursday">
            <label class="form-check-label" for="thursday">Thursday</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Friday" id="friday">
            <label class="form-check-label" for="friday">Friday</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Saturday" id="saturday">
            <label class="form-check-label" for="saturday">Saturday</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="Sunday" id="sunday">
            <label class="form-check-label" for="sunday">Sunday</label>
          </div>
        </div>
        
        <input type="hidden" name="id" id="campaignId">
        <input type="hidden" name="webhook_token" id="webhookToken">
        
        <!-- Start Time -->
        <div class="form-group">
          <label for="starttime">Start Time</label>
          <input type="time" class="form-control" id="starttime" name="starttime" required>
        </div>

        <!-- Stop Time -->
        <div class="form-group">
          <label for="stoptime">Stop Time</label>
          <input type="time" class="form-control" id="stoptime" name="stoptime" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="saveCampaignBtn">Save Campaign</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="importNumbersModal" tabindex="-1" role="dialog" aria-labelledby="importNumbersModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="importNumbersForm" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="importNumbersModalLabel">Import Numbers for Campaign</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        
        <?php if (isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin'): ?>
        <div class="form-group">
          <label for="importCompanySelect">Select Company</label>
          <select id="importCompanySelect" name="company_id" class="form-control" style="width: 100%" required>
            <option value="">Select Company</option>
            <?php foreach ($companies as $company): ?>
               <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
          <label for="campaignSelect">Select Campaign</label>
          <select id="campaignSelect" name="campaignid" class="form-control" style="width: 100%" required>
            <option></option>
            <!-- Dynamically load campaign options -->
          </select>
        </div>
        <div class="form-group">
          <label for="csvFile">Upload CSV File</label>
          <input type="file" class="form-control-file" id="csvFile" name="csvFile" accept=".csv" required>
          <small class="form-text text-muted">
              Supported columns: <b>number, fname, lname, type, feedback</b>. Any other columns will be saved as extra data.
              <a href="campaign/download_sample" target="_blank" class="ml-2"><i class="fas fa-file-csv"></i> Download Sample CSV</a>
          </small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Import</button>
      </div>
    </form>
  </div>
</div>
<style>
/* Fix Select2 in Bootstrap Modal */
.select2-container {
    z-index: 100000 !important; /* Higher than Modal */
}
.select2-dropdown {
    z-index: 100001 !important; /* Higher than Container */
}
</style>
<main class="content">
	<div class="container-fluid p-0">
		<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
			<div class="container">
			
				<div  class="row justify-content-end">
					<div class="col-lg-12 text-right">
                    
                    <?php if (isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin'): ?>
                    <div class="form-group d-inline-block mr-2" style="max-width: 200px; text-align: left;">
                        <select class="form-control" id="companyFilter">
                            <option value="">All Companies</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

					<button type="button" class="btn btn-lg btn-primary" id="add_agent" data-toggle="modal" data-target="#addCampaignModal" >Add Campaign</button>	
					
					<button class="btn btn-secondary" data-toggle="modal" data-target="#importNumbersModal">Import Numbers</button>
					</div>
				</div>
			</div>
		</div>
			<table id="campaignTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Route To</th>
                  <th>DN Number</th>
                  <th>Return Call</th>
                  <th>Weekdays</th>
                  <th>Start Time</th>
                  <th>Stop Time</th>
                  <th>Status</th>
                  <th>Dialer Mode</th>
                  <th>Route Type</th>
                  <th>Concurrent Calls</th>
                  <th style="display:none;">Webhook Token</th>
                  <?php if (isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin'): ?>
                  <th>Created By</th>
                  <th>Updated By</th>
                  <?php endif; ?>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
	</div>
</main>

<!-- Start Campaign DPD Filter Modal -->
<div class="modal fade" id="startCampaignDpdModal" tabindex="-1" role="dialog" aria-labelledby="startCampaignDpdModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="startCampaignDpdForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="startCampaignDpdModalLabel">Start Campaign</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <p>Optionally filter which numbers to dial based on Days Past Due.</p>
        <div class="row">
            <div class="col-md-6 form-group">
              <label for="dpdFrom">Days Past Due (From)</label>
              <input type="number" class="form-control" id="dpdFrom" name="dpd_from" placeholder="e.g. 84">
            </div>
            <div class="col-md-6 form-group">
              <label for="dpdTo">Days Past Due (To)</label>
              <input type="number" class="form-control" id="dpdTo" name="dpd_to" placeholder="e.g. 144">
            </div>
        </div>
        <small class="text-muted">Leave blank to dial all numbers.</small>
        <input type="hidden" id="startCampaignId" name="id">
        <input type="hidden" name="status" value="1">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success" id="confirmStartCampaignBtn">Start Campaign</button>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function() {
    
    const isSuperAdmin = <?php echo (isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin') ? 'true' : 'false'; ?>;
    const QUEUE_WEBHOOK_URL = "<?php echo QUEUE_WEBHOOK_URL; ?>";
    
    const columns = [
          { data: 'id' },
          { data: 'name', className: 'all' }, // Always visible
          { data: 'routeto' },
          { data: 'dn_number', defaultContent: '' },
          { data: 'returncall' },
          { data: 'weekdays' },
          { data: 'starttime' },
          { data: 'stoptime' },
          {
            data: 'status',
            render: function (data, type, row) {
              const isRunning = data === 'Running';
              const btnClass = isRunning ? 'btn-success' : 'btn-danger';
              const label = isRunning ? 'Running' : 'Stopped';
              return `<button class="btn btn-sm ${btnClass} toggle-status" data-id="${row.campaignid}" data-status="${isRunning ? '1' : '0'}">${label}</button>`;
            }
          },
          { data: 'dialer_mode', defaultContent: 'Power Dialer' },
          { data: 'route_type', defaultContent: 'Queue' },
          { data: 'concurrent_calls', defaultContent: '1' },
          { data: 'webhook_token', visible: false }
    ];

    if (isSuperAdmin) {
        columns.push(
            { data: 'created_by' },
            { data: 'updated_by' }
        );
    }

    columns.push({
            data: null,
            className: 'all', // Always visible
            render: function (data, type, row) {
              let buttons = `
                <button class="btn btn-sm btn-info edit-campaign" data-id="${row.campaignid}">Edit</button>
                <button class="btn btn-sm btn-danger delete-campaign" data-id="${row.campaignid}">Delete</button>
              `;
              
              if (row.dialer_mode === 'Predictive Dialer' && row.webhook_token) {
                  const webhookUrl = `${QUEUE_WEBHOOK_URL}?token=${row.webhook_token}`;
                  buttons += ` <button class="btn btn-sm btn-secondary copy-webhook" data-url="${webhookUrl}" title="Copy Webhook URL">Webhook</button>`;
              }
              
              return buttons;
            }
    });

    // --- DataTable Initialization ---
    const table = $('#campaignTable').DataTable({
        responsive: true,
        ajax: {
          url: 'campaign/get_campaigns',
          type: 'GET',
          data: function(d) {
              d.company_id = $('#companyFilter').val(); // Send selected company ID
          },
          dataSrc: ''
        },
        columns: columns,
        "drawCallback": function(settings) {
            // feather.replace(); // If using feather icons
        }
    });
    
    // Reload table on filter change
    $('#companyFilter').on('change', function() {
        table.ajax.reload();
    });

    // --- Add/Edit Campaign Logic ---

    // Open Modal for ADD
    $('#add_agent').on('click', function () {
      $('#addCampaignModalLabel').text('Add Campaign');
      $('#campaignForm')[0].reset();
      $('#campaignForm').removeClass('was-validated');
      $('#campaignName').prop('readonly', false);
      
      // FIX: Do not remove the hidden input, just clear it.
      // Ensure the input exists. It is in HTML: <input type="hidden" name="id" id="campaignId">
      $('#campaignId').val(''); 
      
      // Clear checkboxes
      $('input[name="weekdays[]"]').prop('checked', false);
      $('#notifyNoLeadsEmail').prop('checked', false);
      $('#notifyEmail').val('');
      $('#webhookToken').val('');
      
      // Trigger change to update UI state based on default value
      $('#dialerMode').trigger('change');
      syncEmptyDialNotifyUi();
      
      $('#addCampaignModal').modal('show');
    });

    // Open Modal for EDIT
    $('#campaignTable tbody').on('click', '.edit-campaign', function () {
      const campaignId = $(this).data('id'); 
      const rowData = table.row($(this).closest('tr')).data();
    
      $('#addCampaignModalLabel').text('Edit Campaign');
      $('#campaignName').val(rowData.name).prop('readonly', true); // Name is unique/readonly on edit? User choice. keeping readonly as per old code.
      $('#routeto').val(rowData.routeto);
      $('#dnNumber').val(rowData.dn_number);
      $('#returncall').val(rowData.returncall);
      $('#starttime').val(rowData.starttime);
      $('#stoptime').val(rowData.stoptime);
      $('#campaignId').val(rowData.campaignid);
      if(rowData.company_id) {
          $('#companyId').val(rowData.company_id);
      }

      // New Fields Population
      $('#dialerMode').val(rowData.dialer_mode || 'Power Dialer').trigger('change');
      $('#routeType').val(rowData.route_type || 'Queue');
      $('#concurrentCalls').val(rowData.concurrent_calls || 1);
      $('#webhookToken').val(rowData.webhook_token || '');
      $('#notifyNoLeadsEmail').prop('checked', String(rowData.notify_no_leads_email || '0') === '1');
      $('#notifyEmail').val(rowData.notify_email || '');
      syncEmptyDialNotifyUi();
    
      // Handle weekdays
      $('input[name="weekdays[]"]').prop('checked', false); 
      if (rowData.weekdays) {
          // If weekdays is already a string "Monday, Tuesday"
          const selectedDays = rowData.weekdays.split(',').map(day => day.trim());
          selectedDays.forEach(day => {
            $(`input[name="weekdays[]"][value="${day}"]`).prop('checked', true);
          });
      }
    
      $('#addCampaignModal').modal('show');
    });

    // Form Submission
    // Form Submission (Click Handler)
    // Form Submission (Click Handler)
    // Form Submission (Click Handler)
    $('#saveCampaignBtn').on('click', function (e) {
      const form = $('#campaignForm')[0];
    
      // Basic Validation
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        // Force browser to show the validation message
        if (typeof form.reportValidity === "function") {
            form.reportValidity();
        }
        return;
      }
    
      // Collect checked weekdays manually if needed, or serialize handles it?
      // serialize() handles checkboxes with same name as multiple entries.
      // But creating a clean JSON string might be better if backend expects it.
      // The backend expects array or JSON? 
      // Existing backend code: $weekdays = $_POST['weekdays'] ?? []; (Array)
      // So serialize() is fine, it sends weekdays[]=Monday&weekdays[]=Tuesday...
      // BUT current code in footer was doing JSON.stringify.
      // Let's stick to standard form submission if backend handles it.
      // Backend: $weekdays = $_POST['weekdays'] ?? []; ... json_encode($weekdays);
      // So standard submit is fine. 
    
      const formData = $('#campaignForm').serialize();
      
      // Determine Add or Update
      const campaignId = $('#campaignId').val();
      const isEdit = campaignId && campaignId !== '';
      const ajaxUrl = isEdit ? 'campaign/update_campaign' : 'campaign/addcampaign';
    
      $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
          if (response.success) {
            alert(isEdit ? 'Campaign updated successfully!' : 'Campaign added successfully!');
            $('#addCampaignModal').modal('hide');
            table.ajax.reload();
          } else {
            alert('Error: ' + response.error);
          }
        },
        error: function () {
          alert('An unexpected error occurred.');
        }
      });
    });

    // --- Toggle Status ---
    $('#campaignTable tbody').on('click', '.toggle-status', function () {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status'); // 1 or 0
        const newStatus = currentStatus == '1' ? '0' : '1';
    
        if (newStatus === '1') {
            // Starting campaign: Open Modal
            $('#startCampaignId').val(id);
            $('#dpdFrom').val('');
            $('#dpdTo').val('');
            $('#startCampaignDpdModal').modal('show');
        } else {
            // Stopping campaign: Send immediately
            $.post('campaign/toggle_campaign_status', { id: id, status: newStatus }, function (response) {
              if (response.success) {
                table.ajax.reload(null, false);
              } else {
                alert('Failed to update status');
              }
            }, 'json');
        }
    });

    // Handle Start Campaign with DPD Form Submission
    $('#startCampaignDpdForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const btn = $('#confirmStartCampaignBtn');
        btn.prop('disabled', true).text('Starting...');

        $.post('campaign/toggle_campaign_status', formData, function(response) {
            btn.prop('disabled', false).text('Start Campaign');
            if (response.success) {
                $('#startCampaignDpdModal').modal('hide');
                table.ajax.reload(null, false);
            } else {
                alert('Failed to start campaign');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).text('Start Campaign');
            alert('Server error.');
        });
    });

    // --- Delete Campaign ---
    $('#campaignTable tbody').on('click', '.delete-campaign', function() {
        if (!confirm('Are you sure you want to delete this campaign?')) return;
        
        var id = $(this).data('id');
        $.ajax({
            url: 'campaign/delete_campaign', 
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Campaign deleted.');
                    table.ajax.reload();
                } else {
                    alert('Error deleting campaign: ' + (response.error || 'Unknown error'));
                }
            }
        });
    });

    // --- Copy Webhook Logic ---
    $('#campaignTable tbody').on('click', '.copy-webhook', function() {
        const url = $(this).data('url');
        const example = url + '&queue_dn=800&availableagent=5';
        
        // Copy to clipboard
        navigator.clipboard.writeText(example).then(function() {
            alert('Webhook URL copied to clipboard!\n\nExample:\n' + example);
        }, function(err) {
            alert('Could not copy text: ', err);
            prompt("Copy this URL:", example);
        });
    });

    // Fix Select2 Input focus in Bootstrap Modal
    $.fn.modal.Constructor.prototype.enforceFocus = function() {};

    // --- Import Numbers Logic ---
    $('#importNumbersModal').on('shown.bs.modal', function () {
        // Re-initialize Select2
        if ($('#campaignSelect').data('select2')) {
            $('#campaignSelect').select2('destroy');
        }
        $('#campaignSelect').select2({
            dropdownParent: $('#importNumbersModal'),
            theme: 'bootstrap4',
            placeholder: "Select Campaign",
            allowClear: true,
            width: '100%'
        });
        
        if ($('#importCompanySelect').length > 0) {
             // Use Standard Select to match Add Campaign Modal (proven to work)
             // No Select2 initialization for Company Select
            
            // Cascading Logic: Load campaigns when Company changes
            $('#importCompanySelect').off('change').on('change', function() {
                const companyId = $(this).val();
                loadCampaignsForImport(companyId);
            });
            
            // Sync with Main Filter if set
            const mainFilterVal = $('#companyFilter').val();
            if (mainFilterVal) {
                 $('#importCompanySelect').val(mainFilterVal).trigger('change');
            } else {
                 // Trigger change to load (empty) campaigns even if no company selected
                 if (!$('#importCompanySelect').val()) {
                     // trigger change? No, just clear campaign select
                     $('#campaignSelect').empty().trigger('change');
                 }
            }
        } else {
             // Company Admin: Load immediately
             loadCampaignsForImport();
        }
    });
    
    function loadCampaignsForImport(companyId = null) {
         let url = 'campaign/get_campaigns';
         if (companyId) {
             url += '?company_id=' + companyId;
         } else if (isSuperAdmin) {
             // If Super Admin and no company selected, clear campaigns
             $('#campaignSelect').empty().trigger('change');
             return; 
         }
         
         $.getJSON(url, function (data) {
          let options = '<option></option>';
          $.each(data, function(i, item) {
             // Filter: Only allow if status is NOT Running
             if (item.status !== 'Running') {
                options += `<option value="${item.campaignid}">${item.name}</option>`;
             }
          });
          $('#campaignSelect').html(options).trigger('change');
        });
    }
    
    $('#importNumbersForm').on('submit', function (e) {
        e.preventDefault();
        
        // Validation check for Select2 fields which might not trigger HTML5 validation visually well
        if (isSuperAdmin && !$('#importCompanySelect').val()) {
            alert("Please select a company.");
            return;
        }
        if (!$('#campaignSelect').val()) {
             alert("Please select a campaign.");
             return;
        }

        const formData = new FormData(this);
        $.ajax({
          url: 'campaign/import_numbers',
          type: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function (response) {
            if(response.success){
                 alert(response.message || 'Import complete!');
                 $('#importNumbersModal').modal('hide');
                 $('#importNumbersForm')[0].reset();
                 $('#campaignSelect').val(null).trigger('change');
                 if(isSuperAdmin) $('#importCompanySelect').val(null).trigger('change');
            } else {
                 alert('Import failed: ' + response.message);
            }
          },
          error: function () {
            alert('Error uploading numbers.');
          }
        });
    });

    function syncEmptyDialNotifyUi() {
        const isPredictive = $('#dialerMode').val() === 'Predictive Dialer';
        const enabled = $('#notifyNoLeadsEmail').is(':checked');

        $('#emptyDialNotifyWrapper').toggle(isPredictive);
        $('#notifyNoLeadsEmail').prop('disabled', !isPredictive);
        $('#notifyEmail')
            .prop('disabled', !isPredictive || !enabled)
            .prop('required', isPredictive && enabled);
    }

    $('#notifyNoLeadsEmail').on('change', function() {
        syncEmptyDialNotifyUi();
    });

    // --- Dialer Mode Logic ---
    $('#dialerMode').on('change', function() {
        const mode = $(this).val();
        const routeTypeSelect = $('#routeType');
        
        // Reset all first
        routeTypeSelect.find('option').prop('disabled', false).prop('hidden', false).css('display', '');

        if (mode === 'Predictive Dialer') {
            // Predictive: Only Queue
            routeTypeSelect.find('option[value="Extension"]').prop('disabled', true).prop('hidden', true).css('display', 'none');
            routeTypeSelect.find('option[value="IVR"]').prop('disabled', true).prop('hidden', true).css('display', 'none');
            
            // Force select Queue
            routeTypeSelect.val('Queue');
        } else {
             // Power Dialer: Only Extension and IVR (Hide Queue)
             routeTypeSelect.find('option[value="Queue"]').prop('disabled', true).prop('hidden', true).css('display', 'none');
             
             // If Queue was selected (or nothing), switch to Extension default
             if (routeTypeSelect.val() === 'Queue' || !routeTypeSelect.val()) {
                 routeTypeSelect.val('Extension');
             }
        }

        syncEmptyDialNotifyUi();
    });

    syncEmptyDialNotifyUi();

});
</script>

