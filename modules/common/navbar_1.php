
	<div class="wrapper">
		<nav id="sidebar" class="sidebar">
			<div class="sidebar-content js-simplebar">
				<a class="sidebar-brand" href="index.html">
                    <?php
                    $nav_logo = "";
                    $show_outprefix = false;
                    if (isset($_SESSION['company_id'])) {
                        $cid = intval($_SESSION['company_id']);
                        // Ensure DB connection
                        if (!isset($conn) || !$conn) {
                             if(function_exists('ConnectDB')) $conn = ConnectDB();
                        }
                        
                        if ($conn) {
								$lq = mysqli_query($conn, "SELECT logo, outbound_prefix, simultaneous_calls, pbxurl, pbxclientid, pbxsecret, pbxloginid, pbxloginpass FROM pbxdetail WHERE company_id = $cid");
								if ($lq && mysqli_num_rows($lq) > 0) {
									$lrow = mysqli_fetch_assoc($lq);
									if (!empty($lrow['logo']) && file_exists('asset/logos/'.$lrow['logo'])) {
										$nav_logo = $lrow['logo'];
									}
									if (isset($lrow['outbound_prefix']) && $lrow['outbound_prefix'] == 'Yes') {
										$show_outprefix = true;
									}
									
									$missing_config = [];
									if (empty($lrow['simultaneous_calls'])) $missing_config[] = "Simultaneous Calls";
									if (empty($lrow['pbxurl'])) $missing_config[] = "PBX URL";
									
									$has_oauth = !empty($lrow['pbxclientid']) && !empty($lrow['pbxsecret']);
									$has_login = !empty($lrow['pbxloginid']) && !empty($lrow['pbxloginpass']);
									
									if (!$has_oauth && !$has_login) {
										$missing_config[] = "Authentication (Client ID/Secret)";
									}

									if (!empty($missing_config)) {
										$show_config_error = true;
										$config_error_msg = implode(", ", $missing_config);
									}
								} else {
									$show_config_error = true; // No pbxdetail record
									$config_error_msg = "All Configuration";
								}
                                }
                        }

                    ?>
                    
                    <?php if($nav_logo): ?>
					    <img src="asset/logos/<?php echo $nav_logo; ?>" alt="Logo" class="img-fluid" style="max-height: 40px; max-width: 100%;">
                    <?php else: ?>
					    <span class="align-middle">3cx Addons</span>
                    <?php endif; ?>
				</a>

				<ul class="sidebar-nav navul">
					<li class="sidebar-header">
						All Dashboard
					</li>
                    
                    <?php if(isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin'): ?>
                    <li class="sidebar-item Admindashboard">
						<a class="sidebar-link" href="<?php echo BASE_URL;?>?route=admindashboard/index">
							<i class="align-middle" data-feather="monitor"></i> <span class="align-middle">Admin Dashboard</span>
						</a>
					</li>
                    <?php endif; ?>

					<li class="sidebar-item Dashboard">
						<a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=dashboard/index">
							<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
						</a>
					</li>
                    <li class="sidebar-item Ratedashboard">
						<a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=dashboard/rates">
							<i class="align-middle" data-feather="star"></i> <span class="align-middle">Rate Dashboard</span>
						</a>
					</li>
					<!--<li class="sidebar-item Wallboard">
						<a class="sidebar-link" href="<echo NAVURL;?>wallboard/">
							<i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Wallboard</span>
						</a>
					</li>-->

					<li class="sidebar-header">
						Configs
					</li>

					
					
					<?php 
					// Check for Admin privileges
					$isAdmin = (isset($_SESSION['prole']) && ($_SESSION['prole'] == 'super_admin' || $_SESSION['prole'] == 'company_admin'));
					?>

					<?php if($isAdmin): ?>
					<li class="sidebar-item Users">
						<a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=users/index">
							<i class="align-middle" data-feather="user"></i> <span class="align-middle">Users</span>
						</a>
					</li>

					<li class="sidebar-item Group">
						<a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=group/index">
							<i class="align-middle" data-feather="users"></i> <span class="align-middle">Group</span>
						</a>
					</li>

					<li class="sidebar-item Agent">
						<a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=agent/index">
							<i class="align-middle" data-feather="credit-card"></i> <span class="align-middle">Agents</span>
						</a>
					</li>
					<?php endif; ?>
                    
					
					<li class="sidebar-item Campaign">
						<a href="#campaign" data-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="monitor"></i> <span class="align-middle">Campaign</span>
						</a>
						<ul id="campaign" class="sidebar-dropdown list-unstyled collapse " data-parent="#sidebar">
							<!-- Ideally Module should also be restricted if it's administration -->
                            <?php if($isAdmin): ?>
							<li class="sidebar-item Module"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=campaign/index">Module</a></li>
                            <?php endif; ?>
                            
                            <li class="sidebar-item Skipnum"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=campaign/skipped">Skipnum</a></li>
                            
                            <?php if(isset($show_outprefix) && $show_outprefix): ?>
                            <li class="sidebar-item Outprefix"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=outprefix/index">Outprefix</a></li>
							<li class="sidebar-item Did"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=did/index">Did</a></li>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['prole']) && $_SESSION['prole'] == 'super_admin'): ?>
							<li class="sidebar-item Importnum"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=campaign/importlog">Importnum</a></li>
                            <?php endif; ?>
                            
                            <li class="sidebar-item Campcontact Contacts"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=campcontact/index">Contacts</a></li>
                            <li class="sidebar-item Disposition"><a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=disposition/index">Disposition</a></li>
						</ul>
					</li>
					
					<!--<li class="sidebar-item Vip">
						<a class="sidebar-link" href="echo NAVURL;?>vip/">
							<i class="align-middle" data-feather="star"></i> <span class="align-middle">Vip</span>
						</a>
					</li>

					<li class="sidebar-item Queue">
						<a class="sidebar-link" href=" echo NAVURL;?>queue/">
							<i class="align-middle" data-feather="headphones"></i> <span class="align-middle">Queues</span>
						</a>
					</li>-->

					<!--<li class="sidebar-header">
						Reports
					</li>-->
                    
                    <li class="sidebar-item RatingReport">
						<a href="#ratingreport" data-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Rating Report</span>
						</a>
						<ul id="ratingreport" class="sidebar-dropdown list-unstyled collapse " data-parent="#sidebar">
                            <li class="sidebar-item Report">
                                <a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=report/index">
                                    <span class="align-middle">Agent Summary</span>
                                </a>
                            </li>
                            <li class="sidebar-item Asingle">
                                <a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=asingle/index">
                                    <span class="align-middle">Agent Survey</span>
                                </a>
                            </li>
						</ul>
					</li>
					<!--<li class="sidebar-item Callreport">
						<a  class="sidebar-link" href="< echo NAVURL;?>callreport/">
							<i class="align-middle" data-feather="phone"></i> <span class="align-middle">Call Report</span>
						</a>
					</li>-->
					<!--<li class="sidebar-item Recording">
						<a class="sidebar-link" href="< echo NAVURL;?>recording/">
							<i class="align-middle" data-feather="mic"></i> <span class="align-middle">Recording</span>
						</a>
					</li>-->
					
					<!--<li class="sidebar-item">
						<a href="#forms" data-toggle="collapse" class="sidebar-link collapsed">
							<i class="align-middle" data-feather="check-circle"></i> <span class="align-middle">Forms</span>
						</a>
						<ul id="forms" class="sidebar-dropdown list-unstyled collapse " data-parent="#sidebar">
							<li class="sidebar-item"><a class="sidebar-link" href="forms-layouts.html">Form Layouts</a></li>
							<li class="sidebar-item"><a class="sidebar-link" href="forms-basic-inputs.html">Basic Inputs</a></li>
						</ul>
					</li>

					<li class="sidebar-item">
						<a class="sidebar-link" href="tables-bootstrap.html">
							<i class="align-middle" data-feather="list"></i> <span class="align-middle">Tables</span>
						</a>
					</li>-->

	<li class="sidebar-item Settings">
						<a class="sidebar-link" href="<?php echo BASE_URL; ?>?route=settings/index">
							<i class="align-middle" data-feather="settings"></i> <span class="align-middle">Settings</span>
						</a>
					</li>

					<li class="sidebar-header">
						Support & Help
					</li>

					<li class="sidebar-item">
						<a class="sidebar-link" href="https://smartlifetech.zohosites.com/contact-us-sipTrunkServicesThailand-VoipServices" target="_blank">
							<i class="align-middle" data-feather="help-circle"></i> <span class="align-middle">Contact</span>
						</a>
					</li>

					<!--<li class="sidebar-item">
						<a class="sidebar-link" href="maps-google.html">
							<i class="align-middle" data-feather="map"></i> <span class="align-middle">Maps</span>
						</a>
					</li>-->
				</ul>
					
			</div>
		</nav>

		<div class="main">
			<nav class="navbar navbar-expand navbar-light navbar-bg">
				<a class="sidebar-toggle d-flex">
					<i class="hamburger align-self-center"></i>
				</a>

				<!--<form class="form-inline d-none d-sm-inline-block">
					<div class="input-group input-group-navbar">
						<input type="text" class="form-control" placeholder="Search…" aria-label="Search">
						<div class="input-group-append">
							<button class="btn" type="button">
								<i class="align-middle" data-feather="search"></i>
							</button>
						</div>
					</div>
				</form>-->

				<div class="navbar-collapse collapse">
					<ul class="navbar-nav navbar-align">
						<!--<li class="nav-item dropdown">
							<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-toggle="dropdown">
								<div class="position-relative">
									<i class="align-middle" data-feather="bell"></i>
									<span class="indicator">4</span>
								</div>
							</a>
							<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right py-0" aria-labelledby="alertsDropdown">
								<div class="dropdown-menu-header">
									4 New Notifications
								</div>
								<div class="list-group">
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<i class="text-danger" data-feather="alert-circle"></i>
											</div>
											<div class="col-10">
												<div class="text-dark">Update completed</div>
												<div class="text-muted small mt-1">Restart server 12 to complete the update.</div>
												<div class="text-muted small mt-1">30m ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<i class="text-warning" data-feather="bell"></i>
											</div>
											<div class="col-10">
												<div class="text-dark">Lorem ipsum</div>
												<div class="text-muted small mt-1">Aliquam ex eros, imperdiet vulputate hendrerit et.</div>
												<div class="text-muted small mt-1">2h ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<i class="text-primary" data-feather="home"></i>
											</div>
											<div class="col-10">
												<div class="text-dark">Login from 192.186.1.8</div>
												<div class="text-muted small mt-1">5h ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<i class="text-success" data-feather="user-plus"></i>
											</div>
											<div class="col-10">
												<div class="text-dark">New connection</div>
												<div class="text-muted small mt-1">Christina accepted your request.</div>
												<div class="text-muted small mt-1">14h ago</div>
											</div>
										</div>
									</a>
								</div>
								<div class="dropdown-menu-footer">
									<a href="#" class="text-muted">Show all notifications</a>
								</div>
							</div>
						</li>-->
						<!--<li class="nav-item dropdown">
							<a class="nav-icon dropdown-toggle" href="#" id="messagesDropdown" data-toggle="dropdown">
								<div class="position-relative">
									<i class="align-middle" data-feather="message-square"></i>
								</div>
							</a>
							<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right py-0" aria-labelledby="messagesDropdown">
								<div class="dropdown-menu-header">
									<div class="position-relative">
										4 New Messages
									</div>
								</div>
								<div class="list-group">
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-5.jpg" class="avatar img-fluid rounded-circle" alt="Vanessa Tucker">
											</div>
											<div class="col-10 pl-2">
												<div class="text-dark">Vanessa Tucker</div>
												<div class="text-muted small mt-1">Nam pretium turpis et arcu. Duis arcu tortor.</div>
												<div class="text-muted small mt-1">15m ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-2.jpg" class="avatar img-fluid rounded-circle" alt="William Harris">
											</div>
											<div class="col-10 pl-2">
												<div class="text-dark">William Harris</div>
												<div class="text-muted small mt-1">Curabitur ligula sapien euismod vitae.</div>
												<div class="text-muted small mt-1">2h ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-4.jpg" class="avatar img-fluid rounded-circle" alt="Christina Mason">
											</div>
											<div class="col-10 pl-2">
												<div class="text-dark">Christina Mason</div>
												<div class="text-muted small mt-1">Pellentesque auctor neque nec urna.</div>
												<div class="text-muted small mt-1">4h ago</div>
											</div>
										</div>
									</a>
									<a href="#" class="list-group-item">
										<div class="row no-gutters align-items-center">
											<div class="col-2">
												<img src="img/avatars/avatar-3.jpg" class="avatar img-fluid rounded-circle" alt="Sharon Lessman">
											</div>
											<div class="col-10 pl-2">
												<div class="text-dark">Sharon Lessman</div>
												<div class="text-muted small mt-1">Aenean tellus metus, bibendum sed, posuere ac, mattis non.</div>
												<div class="text-muted small mt-1">5h ago</div>
											</div>
										</div>
									</a>
								</div>
								<div class="dropdown-menu-footer">
									<a href="#" class="text-muted">Show all messages</a>
								</div>
							</div>
						</li>-->
						<li class="nav-item dropdown">
							<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-toggle="dropdown">
								<i class="align-middle" data-feather="settings"></i>
							</a>

							<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-toggle="dropdown">
								<img src="modules/common/img/avatars/avatar.jpg" class="avatar img-fluid rounded mr-1" alt="Charles Hall" /> <span class="text-dark">Admin</span>
							</a>
							<div class="dropdown-menu dropdown-menu-right">
								<!--<a class="dropdown-item" href="pages-profile.html"><i class="align-middle mr-1" data-feather="user"></i> Profile</a>
								<a class="dropdown-item" href="#"><i class="align-middle mr-1" data-feather="pie-chart"></i> Analytics</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="pages-settings.html"><i class="align-middle mr-1" data-feather="settings"></i> Settings & Privacy</a>
								<a class="dropdown-item" href="#"><i class="align-middle mr-1" data-feather="help-circle"></i> Help Center</a>
								<div class="dropdown-divider"></div>-->
								<a class="dropdown-item" href="<?php echo LOGOUT;?>?type=logout">Log out</a>
							</div>
						</li>
						<!--<li class="nav-item dropdown">
							<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-toggle="dropdown">
								<i class="align-middle" data-feather="settings"></i>
							</a>

							<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-toggle="dropdown">
								<img src="modules/common/img/avatars/avatar.jpg" class="avatar img-fluid rounded mr-1" alt="Charles Hall" /> <span class="text-dark">Admin</span>
							</a>
							<div class="dropdown-menu dropdown-menu-right">
								<!--<a class="dropdown-item" href="pages-profile.html"><i class="align-middle mr-1" data-feather="user"></i> Profile</a>
								<a class="dropdown-item" href="#"><i class="align-middle mr-1" data-feather="pie-chart"></i> Analytics</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="pages-settings.html"><i class="align-middle mr-1" data-feather="settings"></i> Settings & Privacy</a>
								<a class="dropdown-item" href="#"><i class="align-middle mr-1" data-feather="help-circle"></i> Help Center</a>
								<div class="dropdown-divider"></div>-->
								<!--<a class="dropdown-item" href="<?php echo LOGOUT;?>?type=logout">Log out</a>
							</div>
						</li>-->
					</ul>
				</div>
			</nav>
            <?php if(isset($show_config_error) && $show_config_error && isset($_SESSION['company_id'])): ?>
            <div class="alert alert-danger fade show mb-0 text-center d-flex justify-content-center align-items-center" role="alert" style="width: 100%;">
                <strong>Config Required!</strong> &nbsp; Please set the missing configuration (<?php echo $config_error_msg; ?>) in &nbsp; <a href="<?php echo NAVURL;?>settings/" class="alert-link">Settings</a>.
            </div>
            <?php endif; ?>

			<!--<main class="content">
				<div class="container-fluid p-0">

					<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<h3><strong>Analytics</strong> Dashboard</h3>
						</div>

						<div class="col-auto ml-auto text-right mt-n1">
							<nav aria-label="breadcrumb">
								<ol class="breadcrumb bg-transparent p-0 mt-1 mb-0">
									<li class="breadcrumb-item"><a href="#">AdminKit</a></li>
									<li class="breadcrumb-item"><a href="#">Dashboards</a></li>
									<li class="breadcrumb-item active" aria-current="page">Analytics</li>
								</ol>
							</nav>
						</div>
					</div>
					<div class="row">
						<div class="col-xl-6 col-xxl-5 d-flex">
							<div class="w-100">
								<div class="row">
									<div class="col-sm-6">
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4">Sales</h5>
												<h1 class="display-5 mt-1 mb-3">2.382</h1>
												<div class="mb-1">
													<span class="text-danger"> <i class="mdi mdi-arrow-bottom-right"></i> -3.65% </span>
													<span class="text-muted">Since last week</span>
												</div>
											</div>
										</div>
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4">Visitors</h5>
												<h1 class="display-5 mt-1 mb-3">14.212</h1>
												<div class="mb-1">
													<span class="text-success"> <i class="mdi mdi-arrow-bottom-right"></i> 5.25% </span>
													<span class="text-muted">Since last week</span>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4">Earnings</h5>
												<h1 class="display-5 mt-1 mb-3">$21.300</h1>
												<div class="mb-1">
													<span class="text-success"> <i class="mdi mdi-arrow-bottom-right"></i> 6.65% </span>
													<span class="text-muted">Since last week</span>
												</div>
											</div>
										</div>
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4">Orders</h5>
												<h1 class="display-5 mt-1 mb-3">64</h1>
												<div class="mb-1">
													<span class="text-danger"> <i class="mdi mdi-arrow-bottom-right"></i> -2.25% </span>
													<span class="text-muted">Since last week</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="col-xl-6 col-xxl-7">
							<div class="card flex-fill w-100">
								<div class="card-header">

									<h5 class="card-title mb-0">Recent Movement</h5>
								</div>
								<div class="card-body py-3">
									<div class="chart chart-sm">
										<canvas id="chartjs-dashboard-line"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12 col-md-6 col-xxl-3 d-flex order-2 order-xxl-3">
							<div class="card flex-fill w-100">
								<div class="card-header">

									<h5 class="card-title mb-0">Browser Usage</h5>
								</div>
								<div class="card-body d-flex">
									<div class="align-self-center w-100">
										<div class="py-3">
											<div class="chart chart-xs">
												<canvas id="chartjs-dashboard-pie"></canvas>
											</div>
										</div>

										<table class="table mb-0">
											<tbody>
												<tr>
													<td>Chrome</td>
													<td class="text-right">4306</td>
												</tr>
												<tr>
													<td>Firefox</td>
													<td class="text-right">3801</td>
												</tr>
												<tr>
													<td>IE</td>
													<td class="text-right">1689</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
						<div class="col-12 col-md-12 col-xxl-6 d-flex order-3 order-xxl-2">
							<div class="card flex-fill w-100">
								<div class="card-header">

									<h5 class="card-title mb-0">Real-Time</h5>
								</div>
								<div class="card-body px-4">
									<div id="world_map" style="height:350px;"></div>
								</div>
							</div>
						</div>
						<div class="col-12 col-md-6 col-xxl-3 d-flex order-1 order-xxl-1">
							<div class="card flex-fill">
								<div class="card-header">

									<h5 class="card-title mb-0">Calendar</h5>
								</div>
								<div class="card-body d-flex">
									<div class="align-self-center w-100">
										<div class="chart">
											<div id="datetimepicker-dashboard"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12 col-lg-8 col-xxl-9 d-flex">
							<div class="card flex-fill">
								<div class="card-header">

									<h5 class="card-title mb-0">Latest Projects</h5>
								</div>
								<table class="table table-hover my-0">
									<thead>
										<tr>
											<th>Name</th>
											<th class="d-none d-xl-table-cell">Start Date</th>
											<th class="d-none d-xl-table-cell">End Date</th>
											<th>Status</th>
											<th class="d-none d-md-table-cell">Assignee</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Project Apollo</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-success">Done</span></td>
											<td class="d-none d-md-table-cell">Vanessa Tucker</td>
										</tr>
										<tr>
											<td>Project Fireball</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-danger">Cancelled</span></td>
											<td class="d-none d-md-table-cell">William Harris</td>
										</tr>
										<tr>
											<td>Project Hades</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-success">Done</span></td>
											<td class="d-none d-md-table-cell">Sharon Lessman</td>
										</tr>
										<tr>
											<td>Project Nitro</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-warning">In progress</span></td>
											<td class="d-none d-md-table-cell">Vanessa Tucker</td>
										</tr>
										<tr>
											<td>Project Phoenix</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-success">Done</span></td>
											<td class="d-none d-md-table-cell">William Harris</td>
										</tr>
										<tr>
											<td>Project X</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-success">Done</span></td>
											<td class="d-none d-md-table-cell">Sharon Lessman</td>
										</tr>
										<tr>
											<td>Project Romeo</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-success">Done</span></td>
											<td class="d-none d-md-table-cell">Christina Mason</td>
										</tr>
										<tr>
											<td>Project Wombat</td>
											<td class="d-none d-xl-table-cell">01/01/2020</td>
											<td class="d-none d-xl-table-cell">31/06/2020</td>
											<td><span class="badge badge-warning">In progress</span></td>
											<td class="d-none d-md-table-cell">William Harris</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="col-12 col-lg-4 col-xxl-3 d-flex">
							<div class="card flex-fill w-100">
								<div class="card-header">

									<h5 class="card-title mb-0">Monthly Sales</h5>
								</div>
								<div class="card-body d-flex w-100">
									<div class="align-self-center chart chart-lg">
										<canvas id="chartjs-dashboard-bar"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
			</main>-->

			<!--<footer class="footer">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-left">
							<p class="mb-0">
								<a href="index.html" class="text-muted"><strong>AdminKit Demo</strong></a> &copy;
							</p>
						</div>
						<div class="col-6 text-right">
							<ul class="list-inline">
								<li class="list-inline-item">
									<a class="text-muted" href="#">Support</a>
								</li>
								<li class="list-inline-item">
									<a class="text-muted" href="#">Help Center</a>
								</li>
								<li class="list-inline-item">
									<a class="text-muted" href="#">Privacy</a>
								</li>
								<li class="list-inline-item">
									<a class="text-muted" href="#">Terms</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</footer>-->
		
	