$(document).ready(function () {
    $('#companiesTable').DataTable({
        "ajax": {
            "url": "admindashboard/get_companies",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id" },
            { "data": "name" },
            {
                "data": "admin_email",
                "render": function (data, type, row) {
                    return data ? data : '<span class="text-muted">No Admin</span>';
                }
            },
            {
                "data": "status",
                "render": function (data, type, row) {
                    if (data === 'active') {
                        return '<span class="badge badge-success">Active</span>';
                    } else {
                        return '<span class="badge badge-secondary">Inactive</span>';
                    }
                }
            },
            { "data": "created_at" },
            {
                "data": "id",
                "render": function (data, type, row) {
                    // BASE_URL is defined in the PHP header or layout, usually available globally if output properly.
                    // However, it's safer to rely on relative paths or a global JS variable if BASE_URL php variable was used.
                    // In index.php we used <?php echo BASE_URL; ?>. In a separate JS file we can't use PHP tags.
                    // We must assume BASE_URL is available or use a relative path.
                    // The previous code used clean URLs like ?route=admindashboard...
                    // Let's assume the base href handles the root.

                    let baseUrl = $('base').attr('href'); // Trying to get it from <base> tag if exists, or just empty.
                    // Actually, the original code injected the PHP variable. 
                    // To support this in an external file, we should define a global JS config object in the view, or pass it.
                    // For now, I'll use a relative path assuming index.php is the entry point.

                    // Reconstructing the link:
                    // href="?route=admindashboard/add_user&company_id=${row.id}"

                    let actions = `
                        <button class="btn btn-sm btn-info btn-add-admin" data-id="${row.id}" title="Add New Admin">
                            <i data-feather="user-plus"></i> 
                        </button>
                        <button class="btn btn-sm btn-secondary btn-provision" data-id="${row.id}" title="Provision Features">
                            <i data-feather="settings"></i> 
                        </button>
                    `;

                    if (row.admin_id) {
                        actions += `
                            <button class="btn btn-sm btn-warning btn-reset-password" data-id="${row.admin_id}" title="Reset Admin Password">
                                <i data-feather="key"></i> 
                            </button>
                        `;
                    }
                    return actions;
                }
            }
        ],
        "order": [[4, "desc"]], // Sort by Created At desc by default
        "drawCallback": function (settings) {
            feather.replace();
        }
    });

    // Handle Save Company Click
    $('#saveCompanyBtn').click(function () {
        var formData = $('#addCompanyForm').serialize();
        $('#addCompanyMessage').html('');

        $.ajax({
            url: 'admindashboard/add_company', // Relative path
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#addCompanyModal').modal('hide');
                    $('#addCompanyForm')[0].reset();
                    $('#companiesTable').DataTable().ajax.reload();
                    alert(response.message);
                } else {
                    $('#addCompanyMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#addCompanyMessage').html('<div class="alert alert-danger">Error processing request.</div>');
            }
        });
    });

    // Handle Add Admin Click (Open Modal)
    $(document).on('click', '.btn-add-admin', function () {
        var companyId = $(this).data('id');
        $('#admin_company_id').val(companyId);
        $('#addAdminModal').modal('show');
    });

    // Handle Save Admin Click (AJAX)
    $('#saveAdminBtn').click(function () {
        var formData = $('#addAdminForm').serialize();
        $('#addAdminMessage').html('');

        $.ajax({
            url: 'admindashboard/add_user',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#addAdminModal').modal('hide');
                    $('#addAdminForm')[0].reset();
                    $('#companiesTable').DataTable().ajax.reload();
                    alert(response.message);
                } else {
                    $('#addAdminMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#addAdminMessage').html('<div class="alert alert-danger">Error processing request.</div>');
            }
        });
    });

    // Handle Reset Password Click (Open Modal)
    $(document).on('click', '.btn-reset-password', function () {
        var userId = $(this).data('id');
        $('#reset_user_id').val(userId);
        $('#resetPasswordModal').modal('show');
    });

    // Handle Save Password Click (AJAX)
    $('#savePasswordBtn').click(function () {
        var formData = $('#resetPasswordForm').serialize();
        $('#resetPasswordMessage').html('');

        $.ajax({
            url: 'admindashboard/reset_password',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    $('#resetPasswordModal').modal('hide');
                    $('#resetPasswordForm')[0].reset();
                    // No need to reload table as password change doesn't affect list view
                    alert(response.message);
                } else {
                    $('#resetPasswordMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#resetPasswordMessage').html('<div class="alert alert-danger">Error processing request.</div>');
            }
        });
    });

    // Handle Provision Click (Open Modal & Fetch Settings)
    $(document).on('click', '.btn-provision', function () {
        var companyId = $(this).data('id');
        $('#prov_company_id').val(companyId);
        $('#provisionMessage').html('');
        $('#webhookList').html('<p class="text-muted">Loading...</p>');
        $('#webhookSection').show();

        // Fetch settings
        $.ajax({
            url: 'admindashboard/get_company_settings',
            type: 'GET',
            data: { company_id: companyId },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const s = response.settings;
                    const q = response.questions;

                    // Set Checkboxes
                    $('#prov_outbound_prefix').prop('checked', (s.outbound_prefix === 'Yes'));
                    $('#prov_rating_recording').prop('checked', (s.enable_rating_recording == 1));
                    $('#prov_sentiment').prop('checked', (s.enable_sentiment == 1));
                    $('#prov_questions_count').val(s.rating_questions_count);

                    // Webhooks List
                    let html = '';
                    if (q && q.length > 0) {
                        q.forEach(function (item) {
                            let url = response.webhook_base_url + "?token=" + item.webhook_token;
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between mb-2">
                                        <h6 class="mb-1">Question ${item.question_number}</h6>
                                        <div class="form-group mb-0" style="width: 50%;">
                                            <input type="text" class="form-control form-control-sm" name="question_labels[${item.id}]" placeholder="Label (e.g., Agent Knowledge)" value="${item.label || ''}">
                                        </div>
                                    </div>
                                    <code style="font-size: smaller;">${url}</code>
                                </div>
                               `;
                        });
                    } else {
                        html = '<div class="list-group-item text-muted">No questions configured. Set count and save to generate.</div>';
                    }
                    $('#webhookList').html(html);

                    $('#provisionModal').modal('show');
                } else {
                    alert("Error fetching settings: " + response.message);
                }
            },
            error: function () {
                alert("Error connecting to server.");
            }
        });
    });

    // Handle Save Provision Click
    $('#saveProvisionBtn').click(function () {
        var formData = $('#provisionForm').serialize();
        $('#provisionMessage').html('');

        $.ajax({
            url: 'admindashboard/save_company_settings',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    // $('#provisionModal').modal('hide'); // Maybe keep open to see new webhooks?
                    alert(response.message);
                    // Reload modal data to show new webhooks if generated
                    // Trigger click again logic or separate function. For now simple reload.
                    var cid = $('#prov_company_id').val();
                    $('.btn-provision[data-id="' + cid + '"]').trigger('click');
                } else {
                    $('#provisionMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#provisionMessage').html('<div class="alert alert-danger">Error processing request.</div>');
            }
        });
    });
});
