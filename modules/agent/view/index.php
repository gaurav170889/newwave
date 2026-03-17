<main class="content">
	<div class="container-fluid p-0">
		<div class="row mb-3">
			<div class="col-lg-12">
				<button type="button" class="btn btn-primary" id="add_agent" data-toggle="modal" data-target="#exampleModalCenter">Add Agent</button>
				<button type="button" class="btn btn-info ml-2" id="sync_3cx_btn">Sync with 3CX</button>
				<button type="button" class="btn btn-danger ml-2" id="archive_selected_btn" style="display:none;">Archive Selected</button>
			</div>
		</div>

			<div class="row">
				<div class="col-lg-12">
					<div class="table-responsive">
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th width="5%"><input type="checkbox" id="select_all"></th>
									<th>#</th>
									<th>Name</th>
									<th>Extension</th>
									<th>Group</th>
									<th>3CX ID</th>
									<th>Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								if($data) {
									$i = 1;
									foreach($data as $row) {
										// Skip archived (or check param to show?)
										if(isset($row['is_archived']) && $row['is_archived'] == 1) continue;
								?>
								<tr>
									<td><input type="checkbox" class="agent-checkbox" value="<?php echo $row['agent_id']; ?>"></td>
									<td><?php echo $i++; ?></td>
									<td><?php echo htmlspecialchars($row['agent_name']); ?></td>
									<td><?php echo htmlspecialchars($row['agent_ext']); ?></td>
									<td><?php echo htmlspecialchars($row['agent_group']); ?></td>
									<td><?php echo $row['3cx_id'] ?? '-'; ?></td>
									<td><span class="badge badge-success">Active</span></td>
									<td>
										<a href="javascript:void(0)" class="text-primary update_record" id="<?php echo $row['agent_id']; ?>" title="Edit"><i data-feather="edit"></i></a>
										<a href="javascript:void(0)" class="text-danger delete_record" id="<?php echo $row['agent_id']; ?>" title="Delete"><i data-feather="trash-2"></i></a>
									</td>
								</tr>
								<?php 
									} 
									if ($i == 1) { // No active agents found
										echo "<tr><td colspan='8' class='text-center'>No active agents found.</td></tr>";
									}
								} else {
									echo "<tr><td colspan='8' class='text-center'>No agents found. Please Sync with 3CX.</td></tr>";
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
	</div>
	
	<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalCenterTitle">Add New Agent</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
				</div>
				<form method="POST" id="ins_rec">
					<div class="modal-body">
		  
						<div class="form-group">
							<label><b>Agent Ext</b></label>
							<input type="text" name="ext" class="form-control" placeholder="Agent Ext no.">
							<span class="error-msg" id="msg_2"></span>
						</div>
						<div class="form-group">
							<label><b>Agent Name</b></label>
							<input type="text" name="username" class="form-control" placeholder="Agent Name">
							<span class="error-msg" id="msg_1"></span>
						</div>
						<div class="form-group">
							<label><b>Agent Group</b></label>
							<select class="custom-select" name="group" id="group">
								<option value="" selected>Choose...</option>
								<!--<option value="1">User</option>
								<option value="2">Driver</option>
								<option value="3">Restaurant</option>-->
							</select>
							<span class="error-msg" id="msg_3"></span>
						</div>
						
						<div class="form-group">
							<span class="success-msg" id="sc_msg"></span>
						</div>
						<div class="form-group">
							<span class="error-msg" id="er_msg"></span>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" id="close_click" data-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary" >Add Record</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
<div class="modal fade" id="updateModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="updateModalCenterTitle">Update Record</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
			</div>
			<form method="POST" id="updata">
				<div class="modal-body">
					<div class="form-group">
						<label><b>Agent Ext</b></label>
						<input type="text" name="ext" class="form-control" id="upd_1" placeholder="Agent Ext no.">
						<span class="error-msg" id="msg_2"></span>
					</div>
					<div class="form-group">
						<label><b>Agent Name</b></label>
						<input type="text" name="username" class="form-control" id="upd_2" placeholder="Username">
						<span class="error-msg" id="msg_1"></span>
					</div>
					<div class="form-group">
						<label><b>Agent Group</b></label>
						<select class="custom-select" name="group" id="upd_3" id="role">
							<!--<option value="" selected>Choose...</option>
							<option value="1">User</option>
							<option value="2">Driver</option>
							<option value="3">Restaurant</option>-->
						</select>
						<span class="error-msg" id="msg_3"></span>
					</div>
					<!--<div class="form-group">
						<label><b>Birth Date</b></label>
						<input type="date" name="bod" class="form-control">
						<span class="error-msg" id="msg_4"></span>
					</div>-->
					<div class="form-group">
						<input type="hidden" name="dataval" id="upd_6">
						<span class="success-msg" id="umsg_5"></span>
					</div>
				
					<div class="form-group">
						<span class="success-msg" id="sc_msg"></span>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal" id="up_cancle">Cancle</button>
					<button type="submit" class="btn btn-primary">Update Record</button>
				</div>
			</form>	
		</div>
	 </div>
</div>	
		
	<!-- End Update Design Modal -->
		
	<!-- Delete Design Modal -->
		
	<div class="modal fade" id="deleteModalCenter" tabindex="-1" role="dialog" aria-labelledby="deleteModalCenterTitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title" id="deleteModalCenterTitle">Are You Sure Delete This Record ?</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				  <span aria-hidden="true">&times;</span>
				</button>
			  </div>
			  <div class="modal-body">
				  <p>If You Click On Delete Button Record Will Be Deleted. We Don't have Backup So Be Carefull.</p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" id="de_cancle" data-dismiss="modal">Cancle</button>
				<button type="button" class="btn btn-primary" id="deleterec">Delete Now</button>
			  </div>
			</div>
		</div>
	</div>	
</main>

<script>
$(document).ready(function() {
    $('#sync_3cx_btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Syncing...');
        
        $.ajax({
            url: 'agent/sync3cx',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.status == 101 || response.status == 'success') {
                    alert('Sync Complete: ' + (response.msg || 'Agents updated.'));
                    location.reload();
                } else {
                    alert('Sync Failed: ' + (response.msg || 'Unknown error.'));
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('Error connecting to server.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Sync with 3CX');
            }
        });
    });

    // Checkbox Handling
    $('#select_all').on('click', function() {
        $('.agent-checkbox').prop('checked', this.checked);
        toggleArchiveBtn();
    });

    $(document).on('click', '.agent-checkbox', function() {
        toggleArchiveBtn();
    });

    function toggleArchiveBtn() {
        if($('.agent-checkbox:checked').length > 0) {
            $('#archive_selected_btn').show();
        } else {
            $('#archive_selected_btn').hide();
        }
    }

    // Bulk Archive
    $('#archive_selected_btn').on('click', function() {
        if(!confirm("Are you sure you want to archive selected agents? They will be hidden and excluded from sync updates.")) return;
        
        var ids = [];
        $('.agent-checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        $.ajax({
            url: 'agent/bulk_archive',
            type: 'POST',
            data: {ids: ids},
            dataType: 'json',
            success: function(response) {
                if(response.status == 1) {
                    alert(response.msg);
                    location.reload();
                } else {
                    alert("Error: " + response.msg);
                }
            },
            error: function() {
                alert("Server error.");
            }
        });
    });
});
</script>


