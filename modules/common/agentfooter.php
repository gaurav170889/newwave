
         </div>   
 </div>
 <!--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>-->
 <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>
<script src="modules/common/js_1/app.js"></script>
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
		$('#exampleagent').DataTable({			
		});
		
		
		
	        function logout(){
			if (confirm('Are you sure you want to logout?')){
				window.location = "http://localhost/rateagent?type=logout";
				return true;
				}else{
					return false;
					}
			}
			
		$('#ins_rec').on("submit", function(e){
		e.preventDefault();
		$.ajax({

			type:'POST',
			url:'agent/insprocess',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					console.log(json.msg);
					$('#ins_rec').trigger('reset');
					$('#close_click').trigger('click');
					location.reload();
				}
				else if(json.status == 102){
					$('#er_msg').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 103){
					$('#msg_1').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 104){
					$('#msg_2').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 105){
					$('#msg_3').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 106){
					$('#msg_4').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 107){
					$('#msg_5').text(json.msg);
					console.log(json.msg);
				}
				else{
					console.log(json.msg);
				}

			}

		});

	});
	
	
	$(document).on("click", "#add_agent", function(){
		var groupname = "grpname";
		$.ajax({
            type:'POST',
			url:'agent/getgroup',
            data: {depart:groupname},
            dataType: 'json',
            success:function(response){
                var len = response.length;

                $("#group").empty();
				
                for( var i = 0; i<len; i++){
                    var id = response[i]['id'];
                    var grpname = response[i]['grpname'];
                    
                    $("#group").append("<option value='"+id+"'>"+grpname+"</option>");

                }
            }
        });
	
	});

	$(document).on("click", ".agtgrp", function(){
		var groupname = "grpname";
		$.ajax({
            type:'POST',
			url:'agent/getgroup',
            data: {depart:groupname},
            dataType: 'json',
            success:function(response){
                var len = response.length;

                $("#upd_3").empty();
				
                for( var i = 0; i<len; i++){
                    var id = response[i]['id'];
                    var grpname = response[i]['grpname'];
                    
                    $("#upd_3").append("<option value='"+id+"'>"+grpname+"</option>");

                }
            }
        });
	
	});
	//select data

	$(document).on("click", "button.editdata", function(){
		$('#umsg_1').text("");
		$('#umsg_2').text("");
		$('#umsg_3').text("");
		$('#umsg_4').text("");
		$('#umsg_5').text("");
		var check_id = $(this).data('dataid');
		$.getJSON("agent/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#upd_1').val(json.ext);
				$('#upd_2').val(json.name);					
				$('#upd_6').val(check_id);
				$('#upd_3').val(json.group).change();
				/*if(json.group=="3")
					{
						var grpid = 3 ;
						$('#upd_3').val("3").change();
					}*/
			}
			else{
				console.log(json.msg);
			}
		});
	});

	//Update Record

	$('#updata').on("submit", function(e){
		e.preventDefault();

		$.ajax({

			type:'POST',
			url:'agent/updateprocess2',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
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
				else if(json.status == 104){
					$('#umsg_2').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 105){
					$('#umsg_3').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 107){
					$('#umsg_4').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 106){
					$('#umsg_5').text(json.msg);
					console.log(json.msg);
				}

				else{
					console.log(json.msg);
				}

			}

		});
		

	});

	//delete record

	var deleteid;

	$(document).on("click", "button.deletedata", function(){
		deleteid = $(this).data("dataid");
	});

	$('#deleterec').click(function (){
		$.ajax({
			type:'POST',
			url:'agent/deleteprocess',
			data:{delete_id : deleteid},
			success:function(data){
				var json = JSON.parse(data);
				if(json.status == 0){
					$('#tbl_rec').load('agent/record');
					$('#de_cancle').trigger("click");
					console.log(json.msg);
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