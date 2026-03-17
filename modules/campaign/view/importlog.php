<main class="content">
	<div class="container-fluid p-0">
		<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
			<div class="container">
			
				<div  class="row justify-content-end">
					<div class="col-lg-12 text-right">
                    
                    <div class="form-group d-inline-block mr-2" style="max-width: 200px; text-align: left;">
                        <select class="form-control" id="companyFilter">
                            <option value="">All Companies</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

					</div>
				</div>
			</div>
			
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">Import History Log</h5>
				</div>
                <div class="card-body">
                    <table id="importLogTable" class="table table-striped table-hover" style="width:100%">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Company</th>
                          <th>Campaign</th>
                          <th>Filename</th>
                          <th>Imported By</th>
                          <th>Action</th>
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
    
    const table = $('#importLogTable').DataTable({
        responsive: true,
        order: [[ 0, "desc" ]],
        ajax: {
          url: 'campaign/get_import_logs_list',
          type: 'GET',
          data: function(d) {
              d.company_id = $('#companyFilter').val(); 
          },
          dataSrc: ''
        },
        columns: [
            { data: 'import_at' },
            { data: 'company_name' },
            { data: 'campaign_name' },
            { data: 'importfilename' },
            { data: 'imported_by_name', defaultContent: 'Unknown' },
            { 
               data: null,
               render: function(data, type, row) {
                   // Ensure path is correct. Assuming 'vantrap/asset/importnum/' is publicly accessible relative to root.
                   // Convert tempname to URL.
                   // The app seems to be in /vantrap/.
                   const url = `asset/importnum/${row.tempname}`;
                   return `<a href="${url}" class="btn btn-sm btn-info" download="${row.importfilename}"><i class="align-middle" data-feather="download"></i> Download</a>`;
               }
            }
        ],
        drawCallback: function() {
            feather.replace();
        }
    });
    
    $('#companyFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
