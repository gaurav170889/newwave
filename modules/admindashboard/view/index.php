<?php include(MODULEPATH."common/header.php"); ?>
<?php include(MODULEPATH."common/navbar_1.php"); ?>

<main class="content">
    <div class="container-fluid p-0">
        <div class="row mb-2 mb-xl-3">
            <div class="col-auto d-none d-sm-block">
                <h3><strong>Super Admin</strong> Dashboard</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Companies
                             <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#addCompanyModal">
                                 <i class="align-middle" data-feather="plus"></i> Add Company
                             </button>
                        </h5>
                    </div>
                    <div class="card-body">
                        <table id="companiesTable" class="table table-bordered table-hover" style="width:100%">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Admin</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                            <th style="width:15%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated by DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" role="dialog" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCompanyModalLabel">Add New Company</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addCompanyForm">
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" id="company_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </form>
         <div id="addCompanyMessage" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveCompanyBtn">Save Company</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog" aria-labelledby="addAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addAdminModalLabel">Add Company Admin</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addAdminForm">
            <input type="hidden" name="company_id" id="admin_company_id">
            <div class="form-group">
                <label>User Email (Username)</label>
                <input type="email" name="user_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
        </form>
         <div id="addAdminMessage" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveAdminBtn">Create Admin</button>
      </div>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="resetPasswordForm">
            <input type="hidden" name="user_id" id="reset_user_id">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
        </form>
         <div id="resetPasswordMessage" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" id="savePasswordBtn">Update Password</button>
      </div>
    </div>
  </div>
</div>

<!-- Company Provisioning Modal -->
<div class="modal fade" id="provisionModal" tabindex="-1" role="dialog" aria-labelledby="provisionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="provisionModalLabel">Company Provisioning & Settings</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="provisionForm">
            <input type="hidden" name="company_id" id="prov_company_id">
            
            <h6 class="font-weight-bold">Feature Toggles</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="prov_outbound_prefix" name="outbound_prefix" value="Yes">
                        <label class="custom-control-label" for="prov_outbound_prefix">Outbound Prefix</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="prov_rating_recording" name="enable_rating_recording" value="1">
                        <label class="custom-control-label" for="prov_rating_recording">Play Rating Recording</label>
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="prov_sentiment" name="enable_sentiment" value="1">
                        <label class="custom-control-label" for="prov_sentiment">Enable Sentiment 3CX</label>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <h6 class="font-weight-bold">Rating Configuration</h6>
            <div class="form-group">
                <label>Number of Rating Questions (Max 10)</label>
                <input type="number" class="form-control" name="rating_questions_count" id="prov_questions_count" min="0" max="10" placeholder="0">
                <small class="text-muted">Save to generate new webhooks if increasing count.</small>
            </div>
            
            <div id="webhookSection" style="display:none;">
                <label>Active Webhooks</label>
                <div class="list-group" id="webhookList">
                    <!-- Populated via JS -->
                </div>
            </div>

        </form>
         <div id="provisionMessage" class="mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="saveProvisionBtn">Save Settings</button>
      </div>
    </div>
  </div>
</div>

<?php include(MODULEPATH."common/footer_1.php"); ?>

<script src="modules/admindashboard/view/script.js?v=<?php echo time(); ?>"></script>
