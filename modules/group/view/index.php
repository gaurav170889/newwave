
	<!--<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
		<div class="container">
			
			<div  class="row justify-content-center">
				<div class="col-lg-12">
				<button type="button" class="btn btn-lg btn-primary" data-toggle="modal" data-target="#exampleModalCenter" >Add Group</button>	
				</div>
			</div>
		</div>
	</div>-->
	
	
	<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalCenterTitle">Add New Group</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
				</div>
				<form method="POST" id="ins_rec">
					<div class="modal-body">		  						
						<div class="form-group">
							<label><b>Group Name</b></label>
							<input type="text" name="groupname" class="form-control" placeholder="Group Name">
							<span class="error-msg" id="msg_1"></span>
						</div>						
						<div class="form-group">
							<span class="success-msg alert-success" id="sc_msg"></span>
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
						<label><b>Agent Name</b></label>
						<input type="text" name="groupname" class="form-control" id="upd_2" placeholder="Group Name">
						<span class="error-msg" id="umsg_1"></span>
					</div>					
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
				  <p style="color:red"><strong>If You Click On Delete Button Group Record Will Be Deleted. We Don't have Backup So Be Carefull.</strong></p>
			  </div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" id="de_cancle" data-dismiss="modal">Cancle</button>
				<button type="button" class="btn btn-primary" id="deleterec">Delete Now</button>
			  </div>
			</div>
		</div>
	</div>	

<main class="content">
	<div class="container-fluid p-0">
			
		<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
			<div class="container">
			
				<div  class="row justify-content-center">
					<div class="col-lg-12">
					<button type="button" class="btn btn-lg btn-primary" data-toggle="modal" data-target="#exampleModalCenter" >Add Group</button>	
					</div>
				</div>
			</div>
		</div>
		<table id="examplegroup" class="table table-striped table-bordered" style="width:100%">
			<thead>
				<tr>
				   <th>Id</th>
				   <th>Group Name</th>
				   <th>Edit</th>
				   <th>Delete</th>
				   
				</tr>
			</thead>
			<tbody>
				<?php if(!empty($data)): ?>

				<?php foreach($data as $groupdata): ?>

					<tr>
						<td><?php echo $counter; $counter++; ?></td>         
						<td><?php echo $groupdata['grpname']; ?></td>
						<td><button type="button" class="btn btn-info editdata" data-dataid="<?php echo $groupdata['id']; ?>" data-toggle="modal" data-target="#updateModalCenter">Update</button></td>
						<td><button type="button" class="btn btn-danger deletedata" data-dataid="<?php echo $groupdata['id']; ?>" data-toggle="modal" data-target="#deleteModalCenter">Delete</button></td>
					</tr>

				<?php endforeach;?>

				<?php endif; ?> 
			</tbody>
           
		</table>
	</div>
</main>

