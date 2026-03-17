
         </div>   
 </div>
  <!-- jQuery, Popper.js, Bootstrap JS -->
	<!--<script type="https://code.jquery.com/jquery-3.5.1.js"></script>-->
   <!--<script src="jquery/jquery-3.3.1.min.js"></script>-->
    <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-migrate-1.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
    </script>
    <!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
        integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
    </script>-->
	<script src="modules/common/js_1/app.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <!-- Datepicker -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Datatables -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
	<script type="text/javascript" src="modules/common/callreportdatatable/DataTable/datatables.min.js"></script>
    <!--<script type="text/javascript"
        src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.20/b-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/r-2.2.3/datatables.min.js">
    </script>-->
    <!-- Momentjs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
	
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
				
		/*$('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
		
		    $("#myInput").on("keyup", function() 
			{
				var value = $(this).val().toLowerCase();
					$(".dropdown-menu li").filter(function()
				{
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
			});*/
			
	        /*function logout(){
			if (confirm('Are you sure you want to logout?')){
				window.location = "http://localhost/rateagent?type=logout";
				return true;
				}else{
					return false;
					}
			}*/
		
		
		$("#start_date").datepicker({
            "dateFormat": "yy-mm-dd"
        });
        $("#end_date").datepicker({
            "dateFormat": "yy-mm-dd"
        });
		
		$('#repgrp').selectpicker();

		$('#repagt').selectpicker();
		
		
		load_data('category_data');
		function load_data(type, category_id = '')
		{
			$.ajax({
				  url:"asingle/option",
				  method:"POST",
				  data:{type:type, category_id:category_id},
				  dataType:"json",
				  success:function(data)
				{
					//alert(data);
					var html = '';
					for(var count = 0; count < data.length; count++)
					{
					  html += '<option value="'+data[count].id+'">'+data[count].name+"("+data[count].qno+")"+'</option>';
					}
					if(type == 'category_data')
					{
					  $('#repagt').html(html);
					  $('#repagt').selectpicker('refresh');
					 // $('#repgrp').html(html);
					  //$('#repgrp').selectpicker('refresh');
					}
					else
					{
					  //$('#repagt').html(html);
					  //$('#repagt').selectpicker('refresh');
					}
				}
			})
		}
		
		/*$(document).on('change', '#repgrp', function(){
			var category_id = $('#repgrp').val();
			load_data('repagt', category_id);
		});*/
});
</script>

 <script>
    // Fetch records

    function fetch(start_date, end_date,agent='') {
        $.ajax({
            url: "asingle/records",
            type: "POST",
            data: {
                start_date: start_date,
                end_date: end_date,
				//sgroup:group,
				sagent:agent,
				
            },
            dataType: "json",
            success: function(data) {
				
                $('#asinglerecords').DataTable({
                    "data": data,
                    // buttons
                    "dom": "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'B><'col-sm-12 col-md-4'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    "buttons": [
									{
										extend: 'excelHtml5',
										title: start_date+'_to_'+end_date,
										//messageTop:'Queue is :'+data[0].queue
										
									},
									{
										extend: 'csv',
										title: start_date+'_to_'+end_date,
									}
                    ],
                    // responsive
                    "responsive": true,
                     "columns": [{
                            "data": "r_id",
                            "render": function(data, type, row, meta) {
                                return `${row.r_id}`;
                            }
                        },
                        {
                            "data": "r_ext"
                        },
                       
                        {
                            "data": "r_caller",
                            "render": function(data, type, row, meta) {
                                return `${row.r_caller}`;
                            }
                        },
						{
                            "data": "r_externalno",
                            "render": function(data, type, row, meta) {
                                return `${row.r_externalno}`;
                            }
                        },
						{
                            "data": "r_cfdname",
                            "render": function(data, type, row, meta) {
                                return `${row.r_cfdname}`;
                            }
                        },
                        {
                            "data": "r_totaltime",
                            "render": function(data, type, row, meta) {
                                return `${row.r_totaltime}`;
                            }
                        },
                        
                        {
                            "data": "r_duration",
                            "render": function(data, type, row, meta) {
                                return `${row.r_duration}`;
                            }
                        },
						{
                           "data": "r_startdt",
                            "render": function(data, type, row, meta) {
                                return `${row.r_startdt}`;
                            }
                        },
                        {
                           "data": "r_starttime",
                            "render": function(data, type, row, meta) {
                                return `${row.r_starttime}`;
                            }
                        }
                    ]
                });
            }
        });
    }
    fetch();

    // Filter

    $(document).on("click", "#filter", function(e) {
        e.preventDefault();

        var start_date = $("#start_date").val();
        var end_date = $("#end_date").val();
		var repgrp = $("#repgrp").val();
		var repagt = $("#repagt").val();
		
        if (start_date == "" || end_date == "") {
            alert("both date required");
        } 
		else 
		{		
           $('#asinglerecords').DataTable().destroy();
            fetch(start_date,end_date,repgrp,repagt);
        }
    });

    // Reset

    $(document).on("click", "#reset", function(e) {
        e.preventDefault();

        $("#start_date").val(''); // empty value
        $("#end_date").val('');	
		$('#repgrp').selectpicker('val', '');
		$('#repagt').selectpicker('val', '');		
        $('#asinglerecords').DataTable().destroy();
        fetch();
    });
</script>

</body>
</html>