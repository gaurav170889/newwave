
				<table class="table" style="vertical-align: middle; text-align: center; margin-top:30px ">
				  <thead class="thead-dark">
					<tr>
					  	<th scope="col">#</th>
					  	<th scope="col">Ext</th>
					  	<th scope="col">Name</th>
						<th scope="col">Role</th>
					  	<th scope="col">Group</th>
						<!--<th scope="col">DOB</th>-->
						<th scope="col">Action</th>
					</tr>
				  </thead>
				  <tbody>
				  	<?php if($select){ foreach($select as $se_data){ ?>
					<tr>
					  <th scope="row"><?php echo $counter; $counter++; ?></th>
					  	<td><?php echo $se_data['agent_ext']; ?></td>
					  	<td><?php echo $se_data['agent_name']; ?></td>
					  	<td><?php echo $se_data['agent_role']; ?></td>
						<td><?php echo $se_data['agent_group']; ?></td>
						<!--<td> //echo $se_data['u_bod']; ?></td>-->
						<td>
							<button type="button" class="btn btn-info editdata" data-dataid="<?php echo $se_data['agent_id']; ?>" data-toggle="modal" data-target="#updateModalCenter">Update</button>
							<button type="button" class="btn btn-danger deletedata" data-dataid="<?php echo $se_data['agent_id']; ?>" data-toggle="modal" data-target="#deleteModalCenter">Delete</button>
						</td>
					</tr>
					<?php }}else{ echo "<tr><td colspan='7'><h2>No Result Found</h2></td></tr>"; } ?>
				  </tbody>
				</table>
					
				<nav aria-label="Page navigation example mt-5">
					<ul class="pagination justify-content-center">
						<li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
							<a class="page-link"
								href="<?php if($page <= 1){ echo '#'; } else { echo "agent/page=" . $prev; } ?>">Previous</a>
						</li>

						<?php for($i = 1; $i <= $totalPages; $i++ ): ?>
						<li class="page-item <?php if($page == $i) {echo 'active'; } ?>">
							<a class="page-link" href="#<?= $i; ?>"> <?= $i; ?> </a>
						</li>
						<?php endfor; ?>

						<li class="page-item <?php if($page >= $totalPages) { echo 'disabled'; } ?>">
							<a class="page-link"
								href="<?php if($page >= $totalPages){ echo '#'; } else {echo "agent/record?page=". $next; } ?>">Next</a>
						</li>
					</ul>
				</nav>
				<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
