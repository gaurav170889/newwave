<div class="modal fade" id="logoutreasonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalCenterTitle">Mention Logout Reason</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
				</div>
				<form method="POST" id="reason_rec">
					<div class="modal-body">						
						<div class="form-group">
							<label><b>Logout Reason</b></label>
							<select class="custom-select" name="reason" id="reason">
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
						<button type="button" class="btn btn-secondary" id="nclose_click" data-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary" >LogoutQueue</button>
					</div>
				</form>
			</div>
		</div>
	</div>



<div class="wrapper" id="myDIV">
        <!-- Sidebar  -->		
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>Agent Rating</h3>
                <strong>AR</strong>
            </div>

            <ul class="list-unstyled components navul">
                <li class="active Dashboard">'
                    <a class="nav-link" href="<?php echo NAVURL;?>dashboard/" ><!--data-toggle="collapse" <!--aria-expanded="false"-->
                        <i class="fas fa-home"></i>
                        Home
                    </a>
                    
                </li>
                <li class="Users">
                    <a  class="nav-link" href="<?php echo NAVURL;?>users/">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
				</li>
				<li class="Group">
                    <a  class="nav-link" href="<?php echo NAVURL;?>group/">
                        <i class="fas fa-users-cog"></i>
                        Group
                    </a>
					
				</li>
				<li class="Deletelog">
                    <a  class="nav-link" href="<?php echo NAVURL;?>deletelog/">
                        <i class="fas fa-user-alt-slash"></i>
                        Deletelog
                    </a>
					
				</li>
				<li class="Campaign">
                    <a  class="nav-link" href="<?php echo NAVURL;?>campaign/">
                        <i class="fas fa-campground"></i>
                        Campaign
                    </a>
					
				</li>
				<li class="Rcoc">
                    <a  class="nav-link" href="<?php echo NAVURL;?>rcoc/">
                        <i class="fas fa-sync"></i>
                        Rcoc
                    </a>
					
				</li>
				<li class="Queue">
                    <a  class="nav-link" href="<?php echo NAVURL;?>queue/">
                        <i class="fas fa-chalkboard-teacher"></i>
                        Queue
                    </a>
					
				</li>
				<li class="Agent">
                    <a  class="nav-link" href="<?php echo NAVURL;?>agent/">
                       <i class="fas fa-child"></i>
                        Agent
                    </a>
				</li>				
				<li>
				 <a href="#Submenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle navreport">
					<i class="fas fa-calculator"></i>
					Reports
				</a>
				</li>
                    <!--<ul class="collapse list-unstyled" id="Submenu">-->
				<li class="Report">
					<a href="<?php echo NAVURL;?>report/">Agent All</a>
				</li>
				<li Class="Asingle">
					<a href="<?php echo NAVURL;?>asingle/">Agent Single</a>
				</li>
				 <li class="Callreport">
					<a href="<?php echo NAVURL;?>callreport/">Call Report</a>
				</li>
				<li class="Recording">
					<a href="<?php echo NAVURL;?>recording/">Recording</a>
				</li> 				
                    <!--</ul>-->
                <!--</li>-->               
                <li>
                    <a href="http://www.smartlifetech.co.th/contact.php" target="_blank" >
                        <i class="fas fa-paper-plane"></i>
                        Contact
                    </a>
                </li>
            </ul>           
        </nav>
	
        <!-- Page Content  -->
        <div id="content">

            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">

                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-align-left"></i>
                        <span>Toggle Sidebar</span>
                    </button>
					 <button type="button" id="agentoutin" class="btn btn-success">
                        <i class="fas fa-user-alt"></i>
                        <span></span>
                    </button>
                    <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="fas fa-align-justify"></i>
                    </button>
					<button type="button" id="signout" class="btn btn-danger">
						<a href="<?php echo LOGOUT;?>?type=logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Signout</span>
						</a>
                    </button>                   
                </div>
            </nav>
		
		

