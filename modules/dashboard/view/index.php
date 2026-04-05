<main class="content">
	<div class="container-fluid p-0">
        <style>
            .dashboard-filter-group .btn {
                margin: 0.15rem;
                border-radius: 999px;
            }
            .metric-card {
                border: 0;
                color: #fff;
                overflow: hidden;
                position: relative;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }
            .metric-card::after {
                content: "";
                position: absolute;
                right: -25px;
                top: -25px;
                width: 110px;
                height: 110px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.12);
            }
            .metric-card-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
            .metric-card-success { background: linear-gradient(135deg, #059669, #047857); }
            .metric-card-warning { background: linear-gradient(135deg, #d97706, #b45309); }
            .metric-card-danger { background: linear-gradient(135deg, #dc2626, #b91c1c); }
            .metric-card-info { background: linear-gradient(135deg, #0891b2, #0e7490); }
            .metric-card-dark { background: linear-gradient(135deg, #475569, #334155); }
            .metric-label {
                font-size: 0.92rem;
                font-weight: 600;
                opacity: 0.92;
            }
            .metric-value {
                font-size: 2rem;
                font-weight: 700;
                line-height: 1.1;
                margin: 0.35rem 0;
            }
            .metric-subtext {
                opacity: 0.9;
                font-size: 0.85rem;
            }
            .metric-icon {
                font-size: 1.85rem;
            }
            .analytics-table td, .analytics-table th {
                vertical-align: middle;
            }
        </style>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
            <div>
                <h3 class="mb-1"><strong>Outbound Dialer</strong> Dashboard</h3>
                <p class="text-muted mb-0">Track dialer activity, agent connects, and campaign outcomes.</p>
            </div>
            <div class="dashboard-filter-group mt-3 mt-lg-0">
                <?php foreach ($rangeOptions as $rangeKey => $rangeText): ?>
                    <a href="<?php echo BASE_URL; ?>?route=dashboard/index&amp;range=<?php echo urlencode($rangeKey); ?>"
                       data-range="<?php echo htmlspecialchars($rangeKey, ENT_QUOTES, 'UTF-8'); ?>"
                       class="btn btn-sm js-dashboard-range <?php echo $selectedRange === $rangeKey ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        <?php echo htmlspecialchars($rangeText, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="dashboardRequestState"></div>

        <div id="dashboardAjaxContent">
            <?php include(__DIR__ . '/analytics_content.php'); ?>
        </div>
	</div>
</main>

<script>
(function($) {
    if (!$) {
        return;
    }

    var dashboardStaticHtml = $('#dashboardAjaxContent').html();

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function setActiveDashboardRange(range) {
        $('.js-dashboard-range').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('.js-dashboard-range[data-range="' + range + '"]')
            .removeClass('btn-outline-secondary')
            .addClass('btn-primary');
    }

    function setDashboardState(message, type) {
        if (!message) {
            $('#dashboardRequestState').html('');
            return;
        }

        $('#dashboardRequestState').html(
            '<div class="alert alert-' + type + ' border mb-3"><strong>' + escapeHtml(message) + '</strong></div>'
        );
    }

    function showDashboardFallback(range, message) {
        setActiveDashboardRange(range);
        setDashboardState(message || 'Live dashboard data could not be loaded right now. Showing 0 values instead.', 'warning');
        $('#dashboardAjaxContent').html(dashboardStaticHtml);
    }

    function refreshDashboard(range) {
        setActiveDashboardRange(range);
        setDashboardState('Loading dashboard data...', 'light');

        $.ajax({
            url: '<?php echo BASE_URL; ?>?route=dashboard/analytics',
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: { range: range }
        }).done(function(response) {
            if (!response || parseInt(response.status, 10) !== 101 || typeof response.html !== 'string') {
                showDashboardFallback(range);
                return;
            }

            $('#dashboardAjaxContent').html(response.html || dashboardStaticHtml);
            setActiveDashboardRange(response.selectedRange || range);
            setDashboardState('', 'light');
        }).fail(function() {
            showDashboardFallback(range);
        });
    }

    $(function() {
        refreshDashboard('<?php echo htmlspecialchars($selectedRange, ENT_QUOTES, 'UTF-8'); ?>');
    });

    $(document).on('click', '.js-dashboard-range', function(event) {
        event.preventDefault();
        refreshDashboard($(this).data('range'));
    });
})(window.jQuery);
</script>

<?php
include(INCLUDEPATH . 'modules/common/footer_1.php');
?>