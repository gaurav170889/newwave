	</div>
</div>

	<!--<script src="js/vendor.js"></script>-->
	<script src="modules/common/js_1/app.js"></script>
 <!--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>-->
<!-- <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>-->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
 

<script type="text/javascript">
$(document).ready(function (){
		var navclass= "<?php echo $_SESSION['navurl']; ?>";
		var check = $('.navul li.active').attr('class').split(' ')[1];;
		if(check!==null)
		{
			//alert(check);
			$('.'+check).removeClass('active');
			$('.'+navclass).addClass('active');
		}
		$('#sidebarCollapse').on('click', function () {
			$('#sidebar').toggleClass('active');
		});
	
		$("#myInput").on("keyup", function() 
		{
			var value = $(this).val().toLowerCase();
				$(".dropdown-menu li").filter(function()
			{
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		});
		
	$('#examplegroup').DataTable();
    $('#ins_rec').on("submit", function(e){
		e.preventDefault();
		$.ajax({

			type:'POST',
			url:'group/insprocess',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					$('#sc_msg').text(json.msg);
					$('#ins_rec').trigger('reset');
					//$('#close_click').trigger('click');
					
					setTimeout(function(){ location.reload(); }, 3000);
					//location.reload();
				}
				else if(json.status == 102){
					$('#er_msg').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 103){
					$('#msg_1').text(json.msg);
					console.log(json.msg);
				}				
				else
				{
					console.log(json.msg);
				}

			}

		});

	});
	
	$(document).on("click", "button.editdata", function(){	
		$('#umsg_5').text("");
		var check_id = $(this).data('dataid');
		$.getJSON("group/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#upd_2').val(json.groupname);							
				$('#upd_6').val(check_id);
				
			}
			else{
				console.log(json.msg);
			}
		});
	});
	
	$('#updata').on("submit", function(e){
		e.preventDefault();

		$.ajax({

			type:'POST',
			url:'group/updateprocess2',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					$('#sc_msg').text(json.msg);
					console.log(json.msg);
					//$('#exampleagent').load('agent/');					
					$('#ins_rec').trigger('reset');
					$('#up_cancle').trigger('click');
					location.reload();
				}
				else if(json.status == 102){
					$('#umsg_6').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 103){
					$('#umsg_1').text(json.msg);
					console.log(json.msg);
				}
				
				else{
					console.log(json.msg);
				}

			}

		});
	});
		
	var deleteid;

	$(document).on("click", "button.deletedata", function(){
		deleteid = $(this).data("dataid");
		
	});

	$('#deleterec').click(function (){
		$.ajax({
			type:'POST',
			url:'group/deleteprocess',
			data:{delete_id : deleteid},
			success:function(data){
				var json = JSON.parse(data);
				if(json.status == 0){					
					$('#de_cancle').trigger("click");
					console.log(json.msg);
					location.reload();
				}
				else{
					console.log(json.msg);
				}
			}
		});
	});
	
	
});
</script>
<script src="modules/common/js/jquery.dataTables.min.js"></script>
<script src="modules/common/js/dataTables.bootstrap4.min.js"></script>
<script src="modules/common/js/dataTables.buttons.min.js"></script>
<script src="modules/common/js/jszip.min.js"></script>
<script src="modules/common/js/pdfmake.min.js"></script>
<script src="modules/common/js/buttons.html5.min.js"></script>
<script src="modules/common/js/buttons.print.min.js"></script>
<script src="modules/common/js/buttons.colVis.min.js"></script>
<script src="modules/common/js/vfs_fonts.js"></script>