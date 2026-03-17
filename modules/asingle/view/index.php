<?php
// Access Control
$allowed_roles = ['super_admin', 'company_admin', 'manager'];
$user_role = $_SESSION['erole'] ?? ''; // Assuming 'erole' stores the role slug
if (!in_array($user_role, $allowed_roles)) {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this report.</div>";
    include(MODULEPATH."common/footer_1.php");
    exit;
}
?>

<main class="content">
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3">Agent Survey Report</h1>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Start Date</label>
                        <input type="date" id="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label>End Date</label>
                        <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button id="btnSearch" class="btn btn-primary btn-block">Search</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="surveyTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Call Date</th>
                            <th>Caller</th>
                            <th>Agent</th>
                            <th>Avg Score</th>
                            <th>Sentiment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Survey Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="loadingDetails" class="text-center"><div class="spinner-border"></div></div>
                <div id="detailsContent" style="display:none;">
                    <h6>Rating Breakdown</h6>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Question</th><th>Score</th></tr></thead>
                        <tbody id="qTableBody"></tbody>
                    </table>
                    
                    <hr>
                    <div id="mediaSection">
                        <h6>Recording & Transcript</h6>
                        <!-- Content injected via JS -->
                        <div id="mediaContent"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="modules/asingle/view/script.js?v=<?php echo time(); ?>"></script>