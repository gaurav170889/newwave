<main class="content">
	<div class="container-fluid p-0">

					<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<h3><strong>Analytics</strong> Dashboard</h3>
						</div>

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
						<div class="col-xl-12 col-xxl-12 d-flex">
							<div class="w-100">
								<div class="row">
									<div class="col-sm-3">
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4" style="font-size:15px"><b>Total Point 1</b></h5>
												<h1 class="display-5 mt-1 mb-3" style="width:18rem;"><?php echo $point_1[0] ?></h1>
												<div class="mb-1">
													<!--<span class="text-danger"> <i class="mdi mdi-arrow-bottom-right"></i> -3.65% </span>-->
													<span class="text-muted">Today</span>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-3">
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4" style="font-size:15px"><b>Total Point 3</b></h5>
												<h1 class="display-5 mt-1 mb-3"><?php echo $point_3[0] ?></h1>
												<div class="mb-1">
													<!--<span class="text-success"> <i class="mdi mdi-arrow-bottom-right"></i></span>-->
													<span class="text-muted">Today</span>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-3">
										<!--</div>
										<div class="col-sm-6">-->
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4" style="font-size:15px"><b>Total Point 5</b></h5>
												<h1 class="display-5 mt-1 mb-3"><?php echo $point_5[0] ?></h1>
												<div class="mb-1">
													<!--<span class="text-success"> <i class="mdi mdi-arrow-bottom-right"></i> 6.65% </span>-->
													<span class="text-muted">Today</span>
												</div>
											</div>
										</div>
									</div>
									<div class="col-sm-3">
										<div class="card">
											<div class="card-body">
												<h5 class="card-title mb-4" style="font-size:15px"><b>Total Customer Point</b></h5>
												<h1 class="display-5 mt-1 mb-3"><?php echo $point_all[0] ?></h1>
												<div class="mb-1">
													<!--<span class="text-danger"> <i class="mdi mdi-arrow-bottom-right"></i></span>-->
													<span class="text-muted">Today</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
	
					<div class="row">
						<div class="col-xl-12 col-lg-8 col-xxl-12 d-flex">
							<div class="card flex-fill">
								<div class="card-header">

									<h5 class="card-title mb-0">Individual Agent Analytics</h5>
								</div>
									<!--<div class="container scroltable table-responsive">
									<h2 style="margin-top:10px;text-align:center;color:blue">Individual Agent Call Details</h2>-->
							  <!--<p>Combine .table-dark and .table-striped to create a dark, striped table:</p>-->            
								<table class="table table-striped table-responsive' id="scrollt">
										<thead  class="table-warning table-bordered scrolltb scrollth">
										  <tr class="scrolltb">
											<th class="scrolltb" style="width: 15%">#</th>
											<th class="scrolltb" style="width: 25%">Agent Ext.</th>
											<th class="scrolltb" style="width: 15%"><!--OutTotal-->Average Point</th>
											<th class="scrolltb" style="width: 15%"><!--Out Ans-->Total Point</th>
											<th class="scrolltb" style="width: 15%"><!--Out NotAns-->Call Point</th>
											<th class="scrolltb" style="width: 15%"><!--Out NotAns-->Total Transfer</th>
										  </tr>
										</thead>
										<tbody class="table-dark scrolltb scrolltbody">
										  <?php if($agentout){ foreach($agentout as $se_data){ ?>
										  <tr class="scrolltb">
											<td class="scrolltb" style="width: 15%;color:black"><?php echo $counter; $counter++; ?></td>
											<td class="scrolltb" style="width: 25%;color:black"><?php echo $se_data['agent_ext']; ?></td>
											 <td class="scrolltb" style="width: 15%;color:black"><?php echo $se_data['avg_point']; ?></td>
											<td class="scrolltb" style="width: 15%;color:black"><?php echo $se_data['total_point']; ?></td>        
											<td class="scrolltb" style="width: 15%;color:black"><?php echo $se_data['total_calls']; ?></td>
											<td class="scrolltb" style="width: 15%;color:black"><?php echo $se_data['total']; ?></td>
										  </tr>
										 <?php }}else{ echo "<tr><td colspan='2' style='color:red'><h3>No Result Found</h3></td></tr>"; } ?>
										</tbody>
								</table>
							</div>
						</div>
					</div>
		</div>
</main>

<?php
include('modules/common/footer_1.php');
?>