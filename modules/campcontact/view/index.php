
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
		



<!-- Disposition Modal -->
<div class="modal fade" id="dispositionModal" tabindex="-1" role="dialog" aria-labelledby="dispositionModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dispositionModalLabel">Update Call Disposition</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="dispositionForm">
          <input type="hidden" id="dispo_contact_id" name="contact_id">
          
          <div class="form-group">
            <label for="dispo_select">Disposition</label>
            <select class="form-control" id="dispo_select" name="disposition" required>
              <option value="">Select Disposition...</option>
              <!-- Populated via AJAX/JS -->
            </select>
          </div>

          <div class="form-group" id="dispo_schedule_div" style="display:none;">
            <label>Schedule Callback / Retry</label>
            <div class="row">
               <div class="col-md-6">
                 <input type="date" class="form-control" id="dispo_date" name="callback_date">
               </div>
               <div class="col-md-6">
                 <input type="time" class="form-control" id="dispo_time" name="callback_time">
               </div>
            </div>
          </div>

          <div class="form-group">
            <label>History</label>
            <textarea class="form-control" id="dispo_history" rows="3" readonly style="font-size: 0.85em; background-color: #f8f9fa;"></textarea>
          </div>

          <div class="form-group">
            <label for="dispo_notes">Notes / Reason</label>
            <textarea class="form-control" id="dispo_notes" name="notes" rows="3" placeholder="Enter reason or notes..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="submitDisposition()">Save changes</button>
      </div>
    </div>
  </div>
</div>


<main class="content">
	<div class="container-fluid p-0">
		<div class="card shadow-sm border-primary mb-3" id="campaignContactFilters"
             data-is-super-admin="<?php echo !empty($isSuperAdmin) ? '1' : '0'; ?>"
             data-company-id="<?php echo intval($selectedCompanyId ?? 0); ?>">
            <div class="card-body">
                <h5 class="text-uppercase text-primary mb-3" style="letter-spacing: 0.08em; font-size: 0.95rem;">Campaign Contact Filters</h5>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="contactCompanySelect">Select Company</label>
                        <select class="form-control" id="contactCompanySelect" <?php echo !empty($isSuperAdmin) ? '' : 'disabled'; ?>>
                            <option value="">Select Company</option>
                            <?php foreach (($companies ?? []) as $company): ?>
                                <option value="<?php echo intval($company['id']); ?>" <?php echo intval($selectedCompanyId ?? 0) === intval($company['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="contactCampaignSelect">Select Campaign</label>
                        <select class="form-control" id="contactCampaignSelect">
                            <option value="">Select Campaign</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="contactTypeSelect">Select Type</label>
                        <select class="form-control" id="contactTypeSelect">
                            <option value="">Select Type</option>
                            <option value="agent">Agent</option>
                            <option value="answered">Answered</option>
                            <option value="not_answered">Not Answered</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="contactValueSelect">Select Value</label>
                        <select class="form-control" id="contactValueSelect" disabled>
                            <option value="">Select Value</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="button" class="btn btn-outline-secondary btn-block" id="clearContactFilters">Clear Filters</button>
                    </div>
                </div>
                <div class="alert alert-info py-2 px-3 mb-0" id="contactFilterStatus">
                    Select a campaign to load today's contacts using the company PBX timezone. Answered / Not Answered is based on <strong>`last_call_status`</strong>.
                </div>
            </div>
        </div>

		<div class="d-flex justify-content-end mb-2">
          <button id="deleteAllBtn" class="btn btn-danger btn-sm" onclick="deleteAllContacts()">
            Delete All Contacts
          </button>
        </div>
			<table id="campaignTable" class="table table-striped table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th>Number</th>
                  <th>Name</th>
                  <th>Days Past Due</th>
                  <th>Type</th>
                  <th>Last Outcome</th>
                  <th>State</th>
                  <th>Attempts</th>
                  <th>Last Attempt</th>
                  <th>Agent</th>
                  <th>Disposition</th>
                  <th>Scheduled At</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
	</div>
</main>

