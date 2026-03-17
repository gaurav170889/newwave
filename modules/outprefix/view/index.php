<main class="content">
	<div class="container-fluid p-0">
		<h1 class="h3 mb-3">Outbound Campaign Prefix</h1>
		<div class="row">
			<div class="col-12 col-xl-8">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Manage Prefixes</h5>
						<h6 class="card-subtitle text-muted">Add prefixes for outbound call caller ID rotation.</h6>
					</div>
					<div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Select Campaign</label>
                            <select class="form-control" id="campaignSelect">
                                <option value="">Select Campaign...</option>
                                <?php foreach ($campaigns as $camp): ?>
                                    <option value="<?php echo $camp['id']; ?>"><?php echo htmlspecialchars($camp['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="prefixSection" style="display:none;">
                            <hr>
                            <label class="form-label">Prefixes</label>
                            <div id="prefixContainer">
                                <!-- Dynamic Rows Here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary mt-2" id="addPrefixBtn"><i class="align-middle" data-feather="plus"></i> Add Prefix</button>
                            
                            <hr>
                            <button type="button" class="btn btn-primary" id="savePrefixesBtn">Save Changes</button>
                        </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<script>
$(document).ready(function() {
    
    // Template for a row
    function createRow(value = '') {
        return `
            <div class="input-group mb-2 prefix-row">
                <input type="text" class="form-control" name="prefixes[]" placeholder="Enter Prefix" value="${value}">
                <div class="input-group-append">
                    <button class="btn btn-danger remove-prefix" type="button"><i class="align-middle" data-feather="trash-2"></i></button>
                </div>
            </div>
        `;
    }

    // Load Prefixes when Campaign Selected
    $('#campaignSelect').on('change', function() {
        var campaignId = $(this).val();
        $('#prefixContainer').empty();
        
        if (campaignId) {
            $('#prefixSection').show();
            // Fetch existing
            $.ajax({
                url: 'outprefix/get_prefixes',
                type: 'GET',
                data: { campaign_id: campaignId },
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        data.forEach(function(item) {
                            $('#prefixContainer').append(createRow(item.prefix));
                        });
                    } else {
                        // Add one empty row if none
                         $('#prefixContainer').append(createRow());
                    }
                    feather.replace();
                }
            });
        } else {
            $('#prefixSection').hide();
        }
    });

    // Add Row
    $('#addPrefixBtn').on('click', function() {
        $('#prefixContainer').append(createRow());
        feather.replace();
    });

    // Remove Row
    $(document).on('click', '.remove-prefix', function() {
        $(this).closest('.prefix-row').remove();
    });

    // Save
    $('#savePrefixesBtn').on('click', function() {
        var campaignId = $('#campaignSelect').val();
        var prefixes = [];
        $('input[name="prefixes[]"]').each(function() {
            var val = $(this).val().trim();
            if(val) prefixes.push(val);
        });
        
        $.ajax({
            url: 'outprefix/save',
            type: 'POST',
            data: { 
                campaign_id: campaignId, 
                prefixes: prefixes 
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert(response.message);
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("Server Error");
            }
        });
    });
});
</script>
