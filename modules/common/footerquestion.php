	</div>   
 </div>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <!-- Popper.JS -->
 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
 
 <script type="text/javascript">
        $(document).ready(function () {
			
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
				window.location = "http://localhost/lineman?type=logout";
				return true;
				}else{
					return false;
					}
			}
        });
		
</script>

</body>

</html>