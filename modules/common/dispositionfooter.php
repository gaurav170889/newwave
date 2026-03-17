</div>   
 </div>
  <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
 
    <script src="https://code.jquery.com/jquery-migrate-1.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
    </script>
	<script src="modules/common/js_1/app.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <!-- Datepicker -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Datatables -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
	<script type="text/javascript" src="modules/common/callreportdatatable/DataTable/datatables.min.js"></script>
    <!-- Momentjs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>

<script>
    var navclass= "<?php echo $_SESSION['navurl']; ?>";
    
    // Remove active from any existing active items in sidebar
    $('.navul li.active').removeClass('active');
    
    // Add active to current target
    if(navclass) {
        $('.'+navclass).addClass('active');
    
        // Auto-expand Dropdown if child is active
        var activeItem = $('.'+navclass);
        if(activeItem.length > 0) {
            var parentUl = activeItem.closest('ul.sidebar-dropdown');
            if(parentUl.length > 0) {
                parentUl.addClass('show'); // Expand submenu
                parentUl.parent('li').addClass('active'); // Highlight parent menu
                parentUl.parent('li').find('a[data-toggle="collapse"]').attr('aria-expanded', 'true');
                parentUl.parent('li').find('a[data-toggle="collapse"]').removeClass('collapsed');
            }
        }
    }

    $(document).ready(function() {
        $('#dispositionTable').DataTable();

        // Add Disposition
        $('#addDispositionForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '<?php echo BASE_URL;?>?route=disposition/insprocess',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    var res = JSON.parse(response);
                    if(res.status == 101) {
                        alert(res.msg);
                        location.reload();
                    } else {
                        alert(res.msg);
                    }
                }
            });
        });

        // Edit Button Click (Event Delegation)
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            var code = $(this).data('code');
            var name = $(this).data('name');
            var action = $(this).data('action');
            var color = $(this).data('color');

            $('#edit_id').val(id);
            $('#edit_code').val(code);
            $('#edit_name').val(name);
            $('#edit_action').val(action);
            $('#edit_color').val(color);

            $('#editDispositionModal').modal('show');
        });

        // Edit Submit
        $('#editDispositionForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '<?php echo BASE_URL;?>?route=disposition/updateprocess2', // Function name in class.php
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    var res = JSON.parse(response);
                    if(res.status == 101) {
                        alert(res.msg);
                        location.reload();
                    } else {
                        alert(res.msg);
                    }
                }
            });
        });

        // Delete Button Click (Event Delegation)
        $(document).on('click', '.delete-btn', function() {
            if(confirm('Are you sure you want to delete this disposition?')) {
                var id = $(this).data('id');
                $.ajax({
                    url: '<?php echo BASE_URL;?>?route=disposition/deleteprocess',
                    type: 'POST',
                    data: {delete_id: id},
                    success: function(response) {
                        var res = JSON.parse(response);
                        if(res.status == 0) {
                            alert(res.msg);
                            location.reload();
                        } else {
                            alert(res.msg);
                        }
                    }
                });
            }
        });
    });
</script>
