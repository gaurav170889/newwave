<main class="content">
	<div class="container-fluid p-0">
			<div class="container">
				
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-6">
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text bg-info text-white" id="basic-addon1"><i
												class="fas fa-calendar-alt"></i></span>
									</div>
									<input type="text" class="form-control" id="start_date" placeholder="Start Date" readonly>
								</div>
							</div>
							<div class="col-md-6">
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text bg-info text-white" id="basic-addon1"><i
												class="fas fa-calendar-alt"></i></span>
									</div>
									<input type="text" class="form-control" id="end_date" placeholder="End Date" readonly>
								</div>
							</div>
						</div>
						
						<div class="row">
							<!--<div class="col-md-4">
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text bg-info text-white" id="basic-addon1"><i
												class="fas fa-users-cog"></i></span>
									</div>
									<select name="repgrp" id="repgrp" class="form-control" data-live-search="true" title="Select Queue"></select>
								</div>
							</div>-->
							<div class="col-md-4">
								<div class="input-group mb-3">
									<div class="input-group-prepend">
										<span class="input-group-text bg-info text-white" id="basic-addon1"><i
												class="fas fa-user"></i></span>
									</div>
									 <select name="repagt" id="repagt" class="form-control" data-live-search="true" title="Select Agent"></select>
								</div>
							</div>
							
						</div>
						
						<div>
							<button id="filter" class="btn btn-outline-info btn-sm">Filter</button>
							<button id="reset" class="btn btn-outline-warning btn-sm">Reset</button>
						</div>
						<div class="row mt-3">
							<div class="col-md-12">
								<!-- Table -->
								<div class="table-responsive">
									<table class="table table-bordered display nowrap" id="asinglerecords" style="width:100%">
										<thead>
											<tr>
												<th>ID</th>
												<th>Agent No</th>
												<!--<th>Queue</th>-->
												<th>Caller</th>
												<th>Customer</th>
												<th>Name</th>
												<th>TotalTime</th>
												<th>Duration</th>
												<th>Date</th>
												<th>Time</th>
											</tr>
										</thead>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
	</div>
</main>