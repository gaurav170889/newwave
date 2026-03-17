
	
		<!--<div class="container-fluid" style="margin-top:50px;">
			<div class="container">
				
				<div  class="row justify-content-center">
					<div class="col-lg-12">
					<button type="button" class="btn btn-lg btn-primary" id="add_user" data-toggle="modal" data-target="#exampleModalCenter" >Add User</button>	
					</div>
					
				</div>
				<div class="row mt-5" id="user_tbl_rec">
			
				</div>
			</div>
		</div>-->
	
		<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalCenterTitle">Add New User</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
					</div>
					<form method="POST" id="user_ins_rec">
						<div class="modal-body">
				
							<div class="form-group">
								<label><b>Select Role</b></label>
								<select class="custom-select" name="role" id="role">
									<option>Choose...</option>
									<option value="uagent">Agent</option>
									<option value="admin">Admin</option>						
								</select>
							<span class="error-msg" id="msg_3"></span>
							</div>
				
							<div class="form-group" id="roleagent" class="uagent sagent">
								<label><b>Agent</b></label>
								<select class="custom-select" name="agent" id="agent">
								<option value="" selected>Choose...</option>
								<!--<option value="1">User</option>
								<option value="2">Driver</option>
								<option value="3">Restaurant</option>-->
								</select>
								<span class="error-msg" id="msg_4"></span>
							</div>
				
							<div class="form-group">
								<label><b>Username</b></label>
								<input type="text" name="loginname" class="form-control" placeholder="Enter Usernamme">
								<span class="error-msg" id="msg_1"></span>
							</div>
				
				
				
							<div class="form-group">
								<label><b>Password</b></label>
								<input type="text" name="loginpass" class="form-control" placeholder="Enter Password">
								<span class="error-msg" id="msg_2"></span>
							</div>
							
							<div class="form-group">
								<input type="hidden" name="dataval">
								<span class="error-msg" id="umsg_5"></span>
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

	
<!-- End Insert Modal -->
		
<!-- Update Design Modal -->
	
<div class="modal fade" id="updateModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="updateModalCenterTitle">Update Record</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			  <span aria-hidden="true">&times;</span>
			</button>
		</div>
		<form method="POST" id="temp_update">
			<div class="modal-body">
				<div class="form-group">
					<label><b>Username</b></label>
					<input type="text" name="loginname" class="form-control" id="upd_1" placeholder="Username">
					<span class="error-msg" id="umsg_1"></span>
			  	</div>
			  	<div class="form-group">
					<label><b>Password</b></label>
					<input type="text" name="loginpass" class="form-control" id="upd_2" placeholder="Password">
					<span class="error-msg" id="umsg_2"></span>
			  	</div>
				<div class="form-group">
					<input type="hidden" name="dataval" id="upd_6">
					<span class="error-msg" id="umsg_5"></span>
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


<div class="modal fade" id="viewModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewModalCenterTitle">Record View</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="POST" id="userview_updata">
      <div class="modal-body">
				<div class="form-group">
					<label><b>Role</b></label>
					<input type="text" name="loginname" class="form-control" id="vpd_1" readonly ">
					
			  	</div>
			  	<div class="form-group">
					<label><b>Agent Ext</b></label>
					<input type="text" name="loginpass" class="form-control" id="vpd_2" readonly ">
					
			  	</div>
				<div class="form-group">
					<label><b>Username</b></label>
					<input type="text" name="loginpass" class="form-control" id="vpd_3" readonly ">
					
			  	</div>					
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="view_cancle">Cancle</button>      
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

<!-- End Delete Design Modal -->


<main class="content">
	<div class="container-fluid p-0">
		<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<button type="button" class="btn btn-lg btn-primary" id="add_user" data-toggle="modal" data-target="#exampleModalCenter" >Add User</button>
						</div>
							<!--<div class="col-lg-12">
					<button type="button" class="btn btn-lg btn-primary" id="add_user" data-toggle="modal" data-target="#exampleModalCenter" >Add User</button>	
					</div>-->

						<!--<div class="col-auto ml-auto text-right mt-n1">
							<nav aria-label="breadcrumb">
								<ol class="breadcrumb bg-transparent p-0 mt-1 mb-0">
									<li class="breadcrumb-item"><a href="#">AdminKit</a></li>
									<li class="breadcrumb-item"><a href="#">Dashboards</a></li>
									<li class="breadcrumb-item active" aria-current="page">Analytics</li>
								</ol>
							</nav>
						</div>-->
		</div>
		<div class="row">
				<table id="exampleuser" class="table table-striped table-bordered" style="width:100%">
					<thead>
						<tr>
							<th scope="col">#</th>
							<th scope="col" style="text-align:center">Username</th>
							<th scope="col" style="text-align:center">Role</th>						
							<th scope="col" style="text-align:center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php if(!empty($data)): ?>

						<?php foreach($data as $userdata): ?>

						<tr>
							<td style="text-align:center;vertical-align:center"><?php echo $counter; $counter++; ?></td>
							<td style="text-align:center;vertical-align:center"><?php echo $userdata['email']; ?></td>
							<td style="text-align:center;vertical-align:center"><?php echo $userdata['role']; ?></td>
					  
							<td style="text-align:center;vertical-align:center"><button type="button" class="btn btn-warning agtuserview viewdata" data-dataid="<?php echo $userdata['id']; ?>" data-toggle="modal" data-target="#viewModalCenter">View</button>
							<button type="button" class="btn btn-info agtgrp editdata" data-dataid="<?php echo $userdata['id']; ?>" data-toggle="modal" data-target="#updateModalCenter">Update</button>
							<button type="button" class="btn btn-danger deletedata" data-dataid="<?php echo $userdata['id']; ?>" data-toggle="modal" data-target="#deleteModalCenter">Delete</button></td>
						</tr>

					<?php endforeach;?>

					<?php endif; ?> 
					</tbody>
				   
				</table>
		</div>

	</div>
	
</div>