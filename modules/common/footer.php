
         </div>   
 </div>
  <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>

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
	/*$('#tbl_rec').load('agent/record');	
	$('#search').keyup(function (){
		var search_data = $(this).val();
		$('#tbl_rec').load('agent/record', {keyword:search_data});
	});*/
	/*function getresult(url) {
		var page = url;
		$.ajax({
			url:'agent/record',
			type: "GET",
			data:  {page:page},
			//beforeSend: function(){$("#overlay").show();},
			success: function(data){
			$("#pagination-result").html(data);
			setInterval(function() {$("#overlay").hide(); },500);
			},
			error: function() 
			{} 	        
	   });
	}*/
	
	// show all agent 
	
	$(document).on("click", "#add_user", function(){
		
		var groupname = "grpname";
		$.ajax({
            type:'POST',
			url:'users/getagent',
            data: {depart:groupname},
            dataType: 'json',
            success:function(response){
                var len = response.length;

                $("#agent").empty();
				
                for( var i = 0; i<len; i++){
                    var id = response[i]['agent_id'];
                    var agentname = response[i]['agent_name'];
                    var agentext= response[i]['agent_ext'];
                    $("#agent").append("<option value='"+id+"'>"+agentname+"("+agentext+")</option>");

                }
            }
        });		
	
	});
	
	$('#exampleuser').DataTable({			
		});
	
	/*$("#add_user").click( function(e)
	{*/
		$("#role").change(function(){
			console.clear();
			$(this).find("option:selected").each(function(){
				var optionValue = $(this).attr("value");
					if(optionValue == "uagent")
					{
						$("#roleagent").show();
						//console.log(optionValue);
						//$("." + optionValue).hide();
					} 
					else
					{
						$("#roleagent").hide();
					}
			});
		}).change();	
	//});

	//insert Record

	$('#ins_rec').on("submit", function(e){
		e.preventDefault();
		$.ajax({

			type:'POST',
			url:'users/insprocess',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					console.log(json.msg);
					//$('#tbl_rec').load('agent/record');
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
	
	//view data
	
	$(document).on("click", "button.viewdata", function(){
		
		var check_id = $(this).data('dataid');
		$.getJSON("users/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#vpd_1').val(json.urole);
				$('#vpd_2').val(json.uagent);							
				$('#vpd_3').val(json.name);		
			}
			else{
				console.log(json.msg);
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
		$.getJSON("users/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#upd_1').val(json.name);
										
				$('#upd_6').val(check_id);
					
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
			url:'users/updateprocess2',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					console.log(json.msg);
					//$('#tbl_rec').load('agent/record');
					$('#ins_rec').trigger('reset');
					$('#up_cancle').trigger('click');
					location.reload();
				}
				else if(json.status == 102){
					$('#umsg_5').text(json.msg);
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
			url:'users/deleteprocess',
			data:{delete_id : deleteid},
			success:function(data){
				var json = JSON.parse(data);
				if(json.status == 0){
					//$('#tbl_rec').load('agent/record');
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
	
	$('#user_ins_rec').on("submit", function(e){
		e.preventDefault();
		$.ajax({

			type:'POST',
			url:'users/insprocess',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					console.log(json.msg);
					//$('#user_tbl_rec').load('users/index');
					$('#user_ins_rec').trigger('reset');
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
				else{
					console.log(json.msg);
				}

			}

		});

	});

	//select data

	$(document).on("click", "button.usereditdata", function(){
		$('#umsg_1').text("");
		$('#umsg_2').text("");
		$('#umsg_3').text("");
		$('#umsg_4').text("");
		$('#umsg_5').text("");
		var check_id = $(this).data('dataid');
		$.getJSON("users/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#upd_1').val(json.name);
				$('#upd_2').val(json.password);							
				$('#upd_6').val(check_id);				
			}
			else{
				console.log(json.msg);
			}
		});
	});

	//Update Record

	$('#user_updata').on("submit", function(e){
		e.preventDefault();

		$.ajax({

			type:'POST',
			url:'users/updateprocess2',
			data:$(this).serialize(),
			success:function(vardata){

				var json = JSON.parse(vardata);

				if(json.status == 101){
					console.log(json.msg);
					//$('#user_tbl_rec').load('users/record');
					$('#user_ins_rec').trigger('reset');
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
				else{
					console.log(json.msg);
				}

			}

		});
		

	});

	//delete record

	var deleteid;

	$(document).on("click", "button.userdeletedata", function(){
		deleteid = $(this).data("dataid");
	});

	$('#deleterec').click(function (){
		$.ajax({
			type:'POST',
			url:'users/deleteprocess',
			data:{delete_id : deleteid},
			success:function(data){
				var json = JSON.parse(data);
				if(json.status == 0){
					$('#user_tbl_rec').load('user/index');
					$('#de_cancle').trigger("click");
					console.log(json.msg);
				}
				else{
					console.log(json.msg);
				}
			}
		});
	});
	
	$('#sidebarCollapse').on('click', function () 
	{
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
			
	function logout(){
	if (confirm('Are you sure you want to logout?')){
		window.location = "http://localhost/rateagent?type=logout";
		return true;
		}else{
			return false;
			}
	}

});
</script>
<script src="modules/common/js/jquery.dataTables.min.js"></script>
<script src="modules/common/js/dataTables.bootstrap4.min.js"></script>
<script src="modules/common/js/dataTables.buttons.min.js"></script>

</body>

</html>