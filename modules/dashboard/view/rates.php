<main class="content">
    <div class="container-fluid p-0">
        <style>
            .dashboard-filter-group .btn {
                margin: 0.15rem;
                border-radius: 999px;
            }
            .rate-card {
                border: 0;
                border-radius: 14px;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            }
            .rate-card h2 {
                font-weight: 700;
                margin-bottom: 0.25rem;
            }
        </style>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
            <div>
                <h3 class="mb-1"><strong>Rate</strong> Dashboard</h3>
                <p class="text-muted mb-0">Separate customer rating analytics with the same date filters.</p>
            </div>
            <div class="dashboard-filter-group mt-3 mt-lg-0">
                <?php foreach ($rangeOptions as $rangeKey => $rangeText): ?>
                    <a href="<?php echo BASE_URL; ?>?route=dashboard/rates&amp;range=<?php echo urlencode($rangeKey); ?>"
                       data-range="<?php echo htmlspecialchars($rangeKey, ENT_QUOTES, 'UTF-8'); ?>"
                       class="btn btn-sm js-rate-range <?php echo $selectedRange === $rangeKey ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        <?php echo htmlspecialchars($rangeText, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="rateDashboardRequestState"></div>

        <div id="rateDashboardAjaxContent">
            <?php include(__DIR__ . '/rates_content.php'); ?>
        </div>
    </div>
</main>

<script>
(function($) {
    if (!$) {
        return;
    }

    var rateDashboardStaticHtml = $('#rateDashboardAjaxContent').html();

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function setActiveRateRange(range) {
        $('.js-rate-range').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('.js-rate-range[data-range="' + range + '"]')
            .removeClass('btn-outline-secondary')
            .addClass('btn-primary');
    }

    function setRateDashboardState(message, type) {
        if (!message) {
            $('#rateDashboardRequestState').html('');
            return;
        }

        $('#rateDashboardRequestState').html(
            '<div class="alert alert-' + type + ' border mb-3"><strong>' + escapeHtml(message) + '</strong></div>'
        );
    }

    function showRateFallback(range, message) {
        setActiveRateRange(range);
        setRateDashboardState(message || 'Live rate data could not be loaded right now. Showing 0 values instead.', 'warning');
        $('#rateDashboardAjaxContent').html(rateDashboardStaticHtml);
    }

    function refreshRateDashboard(range) {
        setActiveRateRange(range);
        setRateDashboardState('Loading rate dashboard data...', 'light');

        $.ajax({
            url: '<?php echo BASE_URL; ?>?route=dashboard/rateanalytics',
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: { range: range }
        }).done(function(response) {
            if (!response || parseInt(response.status, 10) !== 101 || typeof response.html !== 'string') {
                showRateFallback(range);
                return;
            }

            $('#rateDashboardAjaxContent').html(response.html || rateDashboardStaticHtml);
            setActiveRateRange(response.selectedRange || range);
            setRateDashboardState('', 'light');
        }).fail(function() {
            showRateFallback(range);
        });
    }

    $(function() {
        refreshRateDashboard('<?php echo htmlspecialchars($selectedRange, ENT_QUOTES, 'UTF-8'); ?>');
    });

    $(document).on('click', '.js-rate-range', function(event) {
        event.preventDefault();
        refreshRateDashboard($(this).data('range'));
    });
})(window.jQuery);
</script>

<?php
include(INCLUDEPATH . 'modules/common/footer_1.php');
?>