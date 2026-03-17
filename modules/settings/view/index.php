<main class="content">
	<div class="container-fluid p-0">
		<h1 class="h3 mb-3">Settings</h1>
		<div class="row">
			<div class="col-12 col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">PBX Details</h5>
						<h6 class="card-subtitle text-muted">Configure your PBX connection details.</h6>
					</div>
					<div class="card-body">
						<form id="settingsForm" enctype="multipart/form-data">
							<div class="form-group">
								<label class="form-label">PBX URL</label>
								<input type="text" class="form-control" name="pbxurl" placeholder="smartall.3cx.us" value="<?php echo htmlspecialchars($settings['pbxurl'] ?? ''); ?>">
                                <small class="form-text text-muted">(If your PBX does not use port 443, please mention port example: fqdn.3cx.com:5001)</small>
							</div>
                            
                            <hr>
                            <label class="form-label">Authentication Method</label>
                            <div class="form-group">
                                <label class="custom-control custom-radio">
                                    <input name="auth_method" type="radio" class="custom-control-input" value="login" <?php echo (!empty($settings['pbxloginid']) || empty($settings['pbxclientid'])) ? 'checked' : ''; ?>>
                                    <span class="custom-control-label">System Owner Extension & Password</span>
                                </label>
                                <label class="custom-control custom-radio">
                                    <input name="auth_method" type="radio" class="custom-control-input" value="oauth" <?php echo (!empty($settings['pbxclientid'])) ? 'checked' : ''; ?>>
                                    <span class="custom-control-label">Client ID & Secret</span>
                                </label>
                            </div>

							<div class="form-group auth-section" id="loginSection">
								<label class="form-label">Extension Number (Login ID)</label>
								<input type="text" class="form-control" name="pbxloginid" value="<?php echo htmlspecialchars($settings['pbxloginid'] ?? ''); ?>">
                                <label class="form-label mt-2">Password</label>
								<input type="password" class="form-control" name="pbxloginpass" value="<?php echo htmlspecialchars($settings['pbxloginpass'] ?? ''); ?>">
							</div>

                            <div class="form-group auth-section" id="oauthSection" style="display:none;">
								<label class="form-label">Client ID</label>
								<input type="text" class="form-control" name="pbxclientid" value="<?php echo htmlspecialchars($settings['pbxclientid'] ?? ''); ?>">
                                <label class="form-label mt-2">Client Secret</label>
								<input type="password" class="form-control" name="pbxsecret" value="<?php echo htmlspecialchars($settings['pbxsecret'] ?? ''); ?>">
							</div>
                            
                            <hr>

                            <div class="form-group">
                                <label class="form-label">Timezone</label>
                                <select class="form-control" name="timezone" id="timezoneSelect">
                                    <option value="">Select Timezone</option>
                                    <?php 
                                    $identifiers = DateTimeZone::listIdentifiers();
                                    foreach ($identifiers as $tz) {
                                        $z = new DateTimeZone($tz);
                                        $c = new DateTime(null, $z);
                                        $offset = $z->getOffset($c);
                                        $offset_hours = floor($offset / 3600);
                                        $offset_minutes = floor(($offset % 3600) / 60);
                                        $offset_string = sprintf("(UTC%s%02d:%02d)", ($offset >= 0 ? '+' : ''), $offset_hours, abs($offset_minutes));
                                        
                                        $selected = (isset($settings['timezone']) && $settings['timezone'] == $tz) ? 'selected' : '';
                                        echo "<option value=\"$tz\" $selected>$tz $offset_string</option>";
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Required if PBX connection details are missing.</small>
                            </div>
                            
                            <hr>
                            
                            <div class="form-group">
                                <label class="form-label">Simultaneous Calls (License Limit)</label>
                                <select class="form-control" name="simultaneous_calls" required>
                                    <option value="">Select Limit</option>
                                    <?php 
                                    $limits = [4,8,16,24,32,48,64,96,128,192,256,512];
                                    $current_limit = $settings['simultaneous_calls'] ?? 0;
                                    foreach ($limits as $limit) {
                                        $selected = ($current_limit == $limit) ? 'selected' : '';
                                        echo "<option value=\"$limit\" $selected>$limit</option>";
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Select your Simultaneous Call License number (Required).</small>
                            </div>

                            
                            <hr>
                            
                            <div class="form-group">
                                <label class="form-label">Company Logo</label>
                                <?php if (!empty($settings['logo'])): ?>
                                    <div class="mb-2">
                                        <img src="asset/logos/<?php echo $settings['logo']; ?>" alt="Company Logo" style="max-height: 50px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control-file" name="logo" accept="image/*">
                                <small class="form-text text-muted">Upload a logo to display in the navigation bar. Max 2MB.</small>
                            </div>

							<div class="mt-3">
								<button type="submit" class="btn btn-primary">Save Settings</button>
							</div>


						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<script>
$(document).ready(function() {
    
    // Initialize Select2 for Timezone
    $('#timezoneSelect').select2({
        theme: 'bootstrap4',
        placeholder: "Select Timezone",
        allowClear: true,
        width: '100%'
    });
    
    function toggleAuthMethod() {
        const method = $('input[name="auth_method"]:checked').val();
        if (method === 'login') {
            $('#loginSection').show();
            $('#oauthSection').hide();
        } else {
            $('#loginSection').hide();
            $('#oauthSection').show();
        }
    }
    
    toggleAuthMethod(); // Run on load
    
    $('input[name="auth_method"]').on('change', function() {
        toggleAuthMethod();
        const method = $(this).val();
        if (method === 'login') {
            alert("Please provide system owner extension id and password.");
        } else {
             alert("For this method please provide client id and pass.");
        }
    });

    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        // Strict Validation Logic
        const pbxurl = $('input[name="pbxurl"]').val().trim();
        const timezone = $('select[name="timezone"]').val();
        
        if (!pbxurl) {
            alert("PBX URL is required.");
            return;
        }
        
        if (!timezone) {
            alert("Timezone is required.");
            return;
        }

        const method = $('input[name="auth_method"]:checked').val(); // 'login' or 'oauth'
        let hasAuth = false;
        
        if (method === 'login') {
            const id = $('input[name="pbxloginid"]').val().trim();
            const pass = $('input[name="pbxloginpass"]').val().trim();
            if (id && pass) hasAuth = true;
        } else {
            const id = $('input[name="pbxclientid"]').val().trim();
            const pass = $('input[name="pbxsecret"]').val().trim();
            if (id && pass) hasAuth = true;
        }
        
        if (!hasAuth) {
             alert("Please provide the authentication details (Extension/Password or Client ID/Secret).");
             return;
        }
        
        // AJAX Submit with File Upload
        var formData = new FormData(this);
        
        $.ajax({
            url: 'settings/save',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    alert(response.message);
                    location.reload(); // Reload to show new logo/values
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("Server error occurred.");
            }
        });
    });
});
</script>
