<table id="example" class="table table-striped table-bordered" style="width:100%">
         <thead>
			<tr>
			   <th>ID</th>
			   <th>Agent No</th>
			   <th>Total Point</th>
			   <th>Avg Point</th>
			   <th>Call Points</th>
			   <th>Transfer_Rate</th>
			   <th>% People Grade</th>
			   <th>% People NotGrade</th>
			</tr>
     </thead>
       <tbody>
      <?php if(!empty($data)): ?>

      <?php foreach($data as $pointdata): ?>

      <tr>
          <td><?php echo $counter; $counter++; ?></td>
          <td><?php echo $pointdata['agent_ext']; ?></td>
          <td><?php echo $pointdata['total_point']; ?></td>
          <td><?php echo $pointdata['avg_point']; ?></td>
          <td><?php echo $pointdata['total_calls']; ?></td>
          <td><?php echo $pointdata['total']; ?></td>
          <td><?php echo $pointdata['percent_grade']; ?></td>
          <td><?php echo $pointdata['percent_not_grade']; ?></td>
      </tr>

<?php endforeach;?>

<?php endif; ?> 
</tbody>
           
    </table>