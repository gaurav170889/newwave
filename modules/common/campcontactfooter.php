</div>   
 </div>
 <!--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>-->
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

 



<script>
    
var navclass= "<?php echo $_SESSION['navurl']; ?>";
console.log('NavURL:', navclass);

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

</script>

<script>
$(document).ready(function () {
    const filterPanel = $('#campaignContactFilters');
    const isSuperAdmin = String(filterPanel.data('is-super-admin') || '0') === '1';
    const defaultCompanyId = String(filterPanel.data('company-id') || $('#contactCompanySelect').val() || '');

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function selectedCompanyId() {
        return isSuperAdmin ? ($('#contactCompanySelect').val() || '') : (defaultCompanyId || $('#contactCompanySelect').val() || '');
    }

    function selectedCampaignId() {
        return $('#contactCampaignSelect').val() || '';
    }

    function selectedFilterType() {
        return $('#contactTypeSelect').val() || '';
    }

    function updateFilterNotice(message, type) {
        const alertType = type || 'info';
        $('#contactFilterStatus')
            .removeClass('alert-info alert-warning alert-success')
            .addClass('alert-' + alertType)
            .html(message || '');
    }

    function resetValueSelect(placeholder) {
        $('#contactValueSelect')
            .prop('disabled', true)
            .html('<option value="">' + escapeHtml(placeholder || 'Select Value') + '</option>');
    }

    function reloadCampaignTable() {
        if ($.fn.DataTable.isDataTable('#campaignTable')) {
            $('#campaignTable').DataTable().ajax.reload();
        }
    }

    function loadCampaigns(autoSelectFirst) {
        const companyId = selectedCompanyId();
        $('#contactCampaignSelect').html('<option value="">Loading campaigns...</option>');
        $('#contactTypeSelect').val('');
        resetValueSelect('Select Value');

        if (!companyId) {
            $('#contactCampaignSelect').html('<option value="">Select Campaign</option>');
            updateFilterNotice('Select a company first to load campaigns.', 'warning');
            reloadCampaignTable();
            return;
        }

        $.ajax({
            url: 'campcontact/getcampaigns',
            type: 'GET',
            dataType: 'json',
            data: { company_id: companyId }
        }).done(function(rows) {
            let html = '<option value="">Select Campaign</option>';
            rows = Array.isArray(rows) ? rows : [];

            rows.forEach(function(row) {
                html += '<option value="' + escapeHtml(row.id) + '">' + escapeHtml(row.name) + '</option>';
            });

            $('#contactCampaignSelect').html(html);

            if (autoSelectFirst && rows.length > 0) {
                $('#contactCampaignSelect').val(String(rows[0].id));
                updateFilterNotice('Campaign selected. You can now choose Type (Agent, Answered, Not Answered).', 'info');
            } else if (rows.length === 0) {
                updateFilterNotice('No campaigns were found for the selected company.', 'warning');
            }

            reloadCampaignTable();
        }).fail(function() {
            $('#contactCampaignSelect').html('<option value="">Select Campaign</option>');
            updateFilterNotice('Could not load campaigns right now.', 'warning');
            reloadCampaignTable();
        });
    }

    function loadFilterValues() {
        const companyId = selectedCompanyId();
        const campaignId = selectedCampaignId();
        const filterType = selectedFilterType();

        if (!campaignId) {
            resetValueSelect('Select Value');
            updateFilterNotice('Select a campaign to load today\'s contacts using the company PBX timezone.', 'info');
            reloadCampaignTable();
            return;
        }

        if (!filterType) {
            resetValueSelect('Select Value');
            updateFilterNotice('Campaign selected. You can now choose Type (Agent, Answered, Not Answered).', 'info');
            reloadCampaignTable();
            return;
        }

        $('#contactValueSelect')
            .prop('disabled', true)
            .html('<option value="">Loading values...</option>');

        $.ajax({
            url: 'campcontact/getfiltervalues',
            type: 'GET',
            dataType: 'json',
            data: {
                company_id: companyId,
                campaign_id: campaignId,
                filter_type: filterType
            }
        }).done(function(rows) {
            let html = '<option value="">Select Value</option>';
            rows = Array.isArray(rows) ? rows : [];

            rows.forEach(function(row) {
                html += '<option value="' + escapeHtml(row.value) + '">' + escapeHtml(row.label) + '</option>';
            });

            $('#contactValueSelect').html(html).prop('disabled', false);
            reloadCampaignTable();
        }).fail(function() {
            resetValueSelect('Select Value');
            updateFilterNotice('Could not load filter values right now.', 'warning');
            reloadCampaignTable();
        });
    }

    // JIT Popover Initialization (Avoids jQuery UI Tooltip conflict)
    $('body').on('mouseenter', '[data-toggle="tooltip"]', function() {
        if (!$(this).data('bs.popover')) {
            $(this).popover({
                html: true,
                container: 'body',
                trigger: 'hover',
                placement: 'top',
                content: function() {
                    return $(this).data('tooltip-content');
                }
            }).popover('show');
        }
    });

    const contactTable = $('#campaignTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'campcontact/getallcontact',
            type: 'POST',
            dataSrc: function(json) {
                return (json && Array.isArray(json.data)) ? json.data : [];
            },
            data: function(payload) {
                payload.company_id = selectedCompanyId();
                payload.campaign_id = selectedCampaignId();
                payload.filter_type = selectedFilterType();
                payload.filter_value = $('#contactValueSelect').val() || '';
            }
        },
        columns: [
            { data: 'number' },
            { data: 'name' },
            { data: 'days_past_due' },
            { data: 'type' },
            { data: 'feedback' },
            { data: 'call_status' },
            { data: 'last_try' },
            { data: 'last_try_dt' },
            { data: 'agent_name' },
            {
                data: 'disposition',
                render: function(data, type, row) {
                    if(data && type === 'display') {
                        var color = row.color_code || '#808080';
                        return '<span class="badge badge-pill" style="background-color: '+color+'; color: #fff; font-size: 100%;">'+data+'</span>';
                    }
                    return data || '';
                }
            },
            {
                data: 'scheduled_at',
                render: function(data, type, row) {
                    return data || row.next_call_at || '';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    if (parseInt(row.attempts_used) > 0) {
                        var safeNotes = encodeURIComponent(row.notes || '');
                        var lastNote = '';
                        var tooltipTitle = '';

                        if(row.notes) {
                            try {
                                var parsed = JSON.parse(row.notes);
                                if(Array.isArray(parsed) && parsed.length > 0) {
                                    var last = parsed[parsed.length - 1];
                                    tooltipTitle = (last.date || '') + '<br>' + (last.note || '') + '<br>By: ' + (last.user || 'Unknown');
                                } else {
                                    throw 'Not Array';
                                }
                            } catch(e) {
                                var lines = row.notes.split('\n');
                                for(var i=lines.length-1; i>=0; i--) {
                                     if(lines[i].trim() !== '') {
                                         lastNote = lines[i];
                                         break;
                                     }
                                }
                                if(lastNote) {
                                    var match = lastNote.match(/^\[(.*?)\] (.*?): (.*)$/);
                                    if(match) {
                                        tooltipTitle = match[1] + '<br>' + match[3] + '<br>By: ' + match[2];
                                    } else {
                                        tooltipTitle = lastNote;
                                    }
                                }
                            }
                        }

                        var tooltipAttr = tooltipTitle ? 'data-toggle="tooltip" data-html="true" data-tooltip-content="'+tooltipTitle.replace(/"/g, '&quot;')+'"' : '';
                        var iconHtml = tooltipTitle ? '<i class="fas fa-sticky-note text-primary mr-2" style="cursor:pointer; font-size: 1.2em;" '+tooltipAttr+'></i>' : '<span class="mr-4"></span>';
                        var nextCall = row.next_call_at || '';

                        return '<div class="d-flex align-items-center justify-content-center">'
                               + iconHtml
                               + '<button class="btn btn-sm btn-info open-dispo" data-id="'+row.id+'" data-notes="'+safeNotes+'" data-disposition="'+row.disposition+'" data-schedule="'+nextCall+'">Disposition</button>'
                               + '</div>';
                    }
                    return '';
                }
            }
        ],
        responsive: true,
        search: {
            return: true
        },
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search all columns'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[10, 'asc']]
    });

    $('#contactCompanySelect').on('change', function() {
        loadCampaigns(true);
    });

    $('#contactCampaignSelect').on('change', function() {
        $('#contactTypeSelect').val('');
        resetValueSelect('Select Value');
        if ($(this).val()) {
            updateFilterNotice('Campaign selected. You can now choose Type (Agent, Answered, Not Answered).', 'info');
        } else {
            updateFilterNotice('Select a campaign to load today\'s contacts using the company PBX timezone.', 'info');
        }
        reloadCampaignTable();
    });

    $('#contactTypeSelect').on('change', function() {
        loadFilterValues();
    });

    $('#contactValueSelect').on('change', function() {
        reloadCampaignTable();
    });

    $('#clearContactFilters').on('click', function() {
        $('#contactTypeSelect').val('');
        resetValueSelect('Select Value');
        if (selectedCompanyId()) {
            loadCampaigns(true);
        } else {
            $('#contactCampaignSelect').html('<option value="">Select Campaign</option>');
            updateFilterNotice('Select a company first to load campaigns.', 'warning');
            reloadCampaignTable();
        }
    });

    if (!selectedCompanyId() && isSuperAdmin && $('#contactCompanySelect option').length > 1) {
        $('#contactCompanySelect').val($('#contactCompanySelect option').eq(1).val());
    }

    if (selectedCompanyId()) {
        loadCampaigns(true);
    } else {
        resetValueSelect('Select Value');
        updateFilterNotice('Select a company first to load campaigns.', 'warning');
        contactTable.ajax.reload();
    }

    // Populate Disposition Dropdown on Load
    $.ajax({
        url: "disposition/getdisposition",
        type: "POST",
        data: { action: "getdisposition" },
        dataType: "json",
        success: function(response) {
            var options = '<option value="">Select Disposition...</option>';
            $.each(response, function(index, item) {
                options += '<option value="' + item.label + '" data-type="' + item.action_type + '">' + item.label + '</option>';
            });
            $('#dispo_select').html(options);
        }
    });

    // Open Modal
    $('#campaignTable').on('click', '.open-dispo', function() {
        var id = $(this).data('id');
        var notes = "";
        try {
            var rawNotes = $(this).data('notes');
            notes = decodeURIComponent(rawNotes || "");
        } catch(e) { 
            notes = $(this).data('notes') || ""; // Fallback
        }
        var currentDispo = $(this).data('disposition');
        var schedule = $(this).data('schedule');

        $('#dispo_contact_id').val(id);
        
        // History: Reverse order (Newest First)
        if(notes) {
            var displayHistory = "";
            try {
                var parsed = JSON.parse(notes);
                if(Array.isArray(parsed)) {
                    // Reverse for history display
                    for(var i=parsed.length-1; i>=0; i--) {
                        var item = parsed[i];
                        if(item.note) {
                            displayHistory += "[" + (item.date||"") + "] " + (item.user||"Unknown") + ": " + item.note + "\n";
                        }
                    }
                } else {
                    throw "Not Array";
                }
            } catch(e) {
                // Legacy String
                 var lines = notes.split('\n');
                 displayHistory = lines.filter(line => line.trim() !== "").reverse().join('\n');
            }
            $('#dispo_history').val(displayHistory);
        } else {
            $('#dispo_history').val('');
        }

        // Clear New Note
        $('#dispo_notes').val('');
        
        // Select Current Disposition
        $('#dispo_select').val(currentDispo); 
        $('#dispo_select').trigger('change'); // To show/hide schedule

        // Pre-fill schedule if exists
        if(schedule && schedule !== 'null') {
             var parts = schedule.split(' ');
             if(parts.length >= 1) $('#dispo_date').val(parts[0]);
             if(parts.length >= 2) $('#dispo_time').val(parts[1].substring(0, 5));
        } else {
             $('#dispo_date').val('');
             $('#dispo_time').val('');
        }
        
        if(!currentDispo) {
             $('#dispo_schedule_div').hide();
             $('#dispo_date').val('');
             $('#dispo_time').val('');
        }

        $('#dispositionModal').modal('show');
    });

    // Show/Hide Schedule inputs based on selection
    $('#dispo_select').change(function() {
        var selected = $(this).find(':selected');
        var type = String(selected.data('type') || "").toLowerCase();
        if (type === 'callback' || type === 'retry') {
            $('#dispo_schedule_div').show();
        } else {
            $('#dispo_schedule_div').hide();
        }
    });
});

function submitDisposition() {
    var formData = $('#dispositionForm').serialize();
    formData += '&action=updateDispositionSql'; 
    // Wait... modal.php functions are called via class.php "action". 
    // We need to check if 'updateDispositionSql' is exposed in class.php?
    // It's in modal.php. Usually class.php routes to modal.php.
    // Let's assume standard pattern: action=something -> class.php -> modal.php
    
    $.ajax({
        url: "campcontact/updateDispositionSql",
        type: "POST",
        data: formData,
        dataType: "json",
        success: function(response) {
            if (response.success) {
                $('#dispositionModal').modal('hide');
                $('#campaignTable').DataTable().ajax.reload();
                alert("Disposition Updated!");
            } else {
                alert("Error: " + (response.error || "Unknown error"));
            }
        },
        error: function() {
            alert("Failed to update disposition (AJAX Error).");
        }
    });
}
</script>

<script>
function deleteAllContacts() {
  if (!confirm("Are you sure you want to delete all contacts for today? This action cannot be undone.")) {
    return;
  }

  const btn = document.getElementById('deleteAllBtn');
  const filterPanel = document.getElementById('campaignContactFilters');
  const companySelect = document.getElementById('contactCompanySelect');
  const campaignSelect = document.getElementById('contactCampaignSelect');
  const isSuperAdmin = String((filterPanel && filterPanel.getAttribute('data-is-super-admin')) || '0') === '1';
  const companyId = isSuperAdmin ? (companySelect ? companySelect.value : '') : ((filterPanel && filterPanel.getAttribute('data-company-id')) || (companySelect ? companySelect.value : ''));
  const campaignId = campaignSelect ? campaignSelect.value : '';

  btn.disabled = true;
  btn.textContent = 'Deleting...';

  fetch('campcontact/delete_all_contacts?company_id=' + encodeURIComponent(companyId || '') + '&campaign_id=' + encodeURIComponent(campaignId || ''))
    .then(response => response.text())
    .then(data => {
      alert('All contacts deleted successfully.');
      $('#campaignTable').DataTable().ajax.reload();
    })
    .catch(error => {
      alert('Error deleting contacts.');
      console.error(error);
    })
    .finally(() => {
      btn.disabled = false;
      btn.textContent = 'Delete All Contacts';
    });
}
</script>
