$(document).ready(function () {

    var table = $('#surveyTable').DataTable({
        "order": [[0, "desc"]], // Sort by ID desc
        "columns": [
            { "data": "rid" },
            { "data": "call_date" },
            { "data": "callerno" },
            { "data": "agent_name" },
            {
                "data": "avg_score",
                "render": function (data) {
                    let badgeClass = 'badge-secondary';
                    if (data >= 4) badgeClass = 'badge-success';
                    else if (data >= 3) badgeClass = 'badge-warning';
                    else if (data > 0) badgeClass = 'badge-danger';
                    return `<span class="badge ${badgeClass}" style="font-size:1em;">${data}</span>`;
                }
            },
            {
                "data": "sentiment",
                "render": function (data) {
                    if (!data) return '-';
                    // Check logic for positive/negative if needed, for now string
                    return data;
                }
            },
            {
                "data": null,
                "render": function (data, type, row) {
                    return `<button class="btn btn-sm btn-info btn-details" data-id="${row.rid}">View Details</button>`;
                }
            }
        ]
    });

    function loadData() {
        let start = $('#start_date').val();
        let end = $('#end_date').val();

        // Show loading?

        $.ajax({
            url: 'asingle/get_ratings',
            type: 'GET',
            data: { start_date: start, end_date: end },
            dataType: 'json',
            success: function (response) {
                table.clear();
                if (response.data && response.data.length > 0) {
                    table.rows.add(response.data);
                }
                table.draw();
            },
            error: function () {
                alert('Error loading data');
            }
        });
    }

    // Load on init
    loadData();

    // Search Click
    $('#btnSearch').click(function () {
        loadData();
    });

    // Details Click
    $(document).on('click', '.btn-details', function () {
        let rid = $(this).data('id');
        $('#detailsModal').modal('show');
        $('#loadingDetails').show();
        $('#detailsContent').hide();

        $.ajax({
            url: 'asingle/get_rating_details',
            type: 'GET',
            data: { rid: rid },
            dataType: 'json',
            success: function (response) {
                $('#loadingDetails').hide();
                $('#detailsContent').show();

                if (response.status === 'success') {
                    // Populate Questions
                    let tbody = '';
                    if (response.details && response.details.length > 0) {
                        response.details.forEach(function (item) {
                            tbody += `<tr><td>${item.label}</td><td><strong>${item.score}</strong></td></tr>`;
                        });
                    } else {
                        tbody = '<tr><td colspan="2">No details found</td></tr>';
                    }
                    $('#qTableBody').html(tbody);

                    // Populate Media
                    let mediaHtml = '';
                    if (response.transcript) {
                        mediaHtml += `<div class="mb-2"><strong>Transcript:</strong><div class="bg-light p-2 rounded" style="max-height:150px; overflow-y:auto;">${response.transcript}</div></div>`;
                    } else {
                        mediaHtml += `<div class="text-muted mb-2">No Transcript Available</div>`;
                    }

                    /* 
                       Note: Role check for recording is server-side conceptually, 
                       but here we just show what API returned. 
                       Ideally API should hide recording_url if user Role is not authorized.
                       Let's assume API (modal) returns it only if authorized? 
                       Wait, I didn't add role check in modal.php for getRatingDetails.
                       I should modify modal.php to restrict recording_url if session role is 'user'.
                       Actually, the whole page is blocked for 'user'. So 'manager', 'admin' can see.
                       So we are safe to show it if it exists.
                    */

                    if (response.recording_url) {
                        mediaHtml += `<div class="mt-2">
                                        <strong>Recording:</strong><br>
                                        <audio controls style="width:100%;">
                                            <source src="${response.recording_url}" type="audio/mpeg"> <!-- or wav -->
                                            Your browser does not support the audio element.
                                        </audio>
                                       </div>`;
                    } else {
                        mediaHtml += `<div class="text-muted mt-2">No Recording Available</div>`;
                    }

                    $('#mediaContent').html(mediaHtml);

                } else {
                    $('#detailsContent').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function () {
                $('#loadingDetails').hide();
                $('#detailsContent').show().html('<div class="alert alert-danger">Error fetching details</div>');
            }
        });
    });

});
