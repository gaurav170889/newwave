<main class="content">
	<div class="container-fluid p-0">
		<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
			<div class="container">
			
				<div  class="row justify-content-end">
					<div class="col-lg-12 text-right">
                    
                    <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                    <div class="form-group d-inline-block mr-2" style="max-width: 200px; text-align: left;">
                        <select class="form-control" id="companyFilter">
                            <option value="">All Companies</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

					</div>
				</div>
			</div>
			
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">Skipped Duplicate Numbers Log</h5>
                    <h6 class="card-subtitle text-muted">Numbers skipped during import because they already existed in the campaign.</h6>
				</div>
                <div class="card-body">
                    <table id="skippedTable" class="table table-striped table-hover" style="width:100%">
                      <thead>
                        <tr>
                          <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                          <th>Company</th>
                          <?php endif; ?>
                          <th>Campaign</th>
                          <th>Number</th>
                          <th>Name</th>
                          <th>Feedback</th>
                          <th>Extra Data</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                </div>
			</div>
		</div>
	</div>
</main>

<script>
$(document).ready(function() {
    const isSuperAdmin = <?php echo (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') ? 'true' : 'false'; ?>;
    
    const columns = [
          { data: 'campaign_name' },
          { data: 'number' },
          { 
              data: null,
              render: function(data, type, row) {
                  return (row.fname || '') + ' ' + (row.lname || '');
              }
          },
          { data: 'feedback' },
          { 
              data: 'exdata',
              render: function(data) {
                  if(!data || data === '[]') return '-';
                  // Truncate logic can be added
                  return `<span title='${data}'>json data</span>`;
              }
          }
    ];

    if (isSuperAdmin) {
        columns.unshift({ data: 'company_name' });
    }

    const table = $('#skippedTable').DataTable({
        responsive: true,
        ajax: {
          url: 'campaign/get_skipped_numbers_list',
          type: 'GET',
          data: function(d) {
              d.company_id = $('#companyFilter').val(); 
          },
          dataSrc: ''
        },
        columns: columns
    });
    
    $('#companyFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
