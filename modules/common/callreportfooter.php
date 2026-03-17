
         </div>   
 </div>
  <!-- jQuery, Popper.js, Bootstrap JS -->
	<!--<script type="https://code.jquery.com/jquery-3.5.1.js"></script>-->
   <!--<script src="jquery/jquery-3.3.1.min.js"></script>-->
   
    <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
    <!--<script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>-->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
        integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
    </script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <!-- Datepicker -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Datatables -->
    <!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>-->
	<script type="text/javascript" src="modules/common/callreportdatatable/DataTable/datatables.min.js"></script>
    <!--<script type="text/javascript"
        src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.20/b-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/r-2.2.3/datatables.min.js">
    </script> -->
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
			
	        function logout(){
			if (confirm('Are you sure you want to logout?')){
				window.location = "http://localhost/rateagent?type=logout";
				return true;
				}else{
					return false;
					}
			}
		
		
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
				  url:"callreport/option",
				  method:"POST",
				  data:{type:type, category_id:category_id},
				  dataType:"json",
				  success:function(data)
				{
					var html = '';
					for(var count = 0; count < data.length; count++)
					{
					  html += '<option value="'+data[count].id+'">'+data[count].name+'</option>';
					}
					if(type == 'category_data')
					{
					  $('#repgrp').html(html);
					  $('#repgrp').selectpicker('refresh');
					}
					else
					{
					  $('#repagt').html(html);
					  $('#repagt').selectpicker('refresh');
					}
				}
			})
		}

		$(document).on('change', '#repgrp', function(){
			var category_id = $('#repgrp').val();
			load_data('repagt', category_id);
		});
});
</script>

 <script>
    // Fetch records

    function fetch(start_date, end_date,group='',agent='',type='') {
        $.ajax({
            url: "callreport/records",
            type: "POST",
            data: {
                start_date: start_date,
                end_date: end_date,
				sgroup:group,
				sagent:agent,
				stype:type
            },
            dataType: "json",
            success: function(data) {
                // Datatables
               // var i = "1";

                $('#records').DataTable({
                    "data": data,
                    // buttons
                    "dom": "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'B><'col-sm-12 col-md-4'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    "buttons": [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ],
                    // responsive
                    "responsive": true,
                     "columns": [{
                            "data": "cx_id",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_id}`;
                            }
                        },
                       /* {
                            "data": "ag_grp"
                        },*/
                        {
                            "data": "cx_agent",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_agent}`;
                            }
                        },
                        {
                            "data": "cx_number",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_number}`;
                            }
                        },
						{
                            "data": "cx_type",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_type}`;
                            }
                        },
						{
                            "data": "cx_duration",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_duration}`;
                            }
                        },
						{
                            "data": "cx_totaltime",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_totaltime}`;
                            }
                        },
						{
                            "data": "cx_waittime",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_waittime}`;
                            }
                        },
						{
                            "data": "cx_reason",
                            "render": function(data, type, row, meta) {
                                return `${row.cx_reason}`;
                            }
                        },
						{
                           "data": "cx_stdate",
                            "render": function(data, type, row, meta) {
                                return moment(row.cx_stdate).format('DD-MM-YYYY');
                            }
                        },
						
                        {
                            "data": "cx_sttime"
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
		var reptype = $("#reptype").val();
        if (start_date == "" || end_date == "") {
            alert("both date required");
        } else {
			//alert(repgrp);
			//alert(repagt);
            $('#records').DataTable().destroy();
            fetch(start_date, end_date,repgrp,repagt,reptype);
        }
    });

    // Reset

    $(document).on("click", "#reset", function(e) {
        e.preventDefault();

        $("#start_date").val(''); // empty value
        $("#end_date").val('');	
		$('#repgrp').selectpicker('val', '');
		$('#repagt').selectpicker('val', '');
		$('#reptype').val('all');
        $('#records').DataTable().destroy();
        fetch();
    });
</script>

</body>
</html>