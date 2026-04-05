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

    function setRateLoading() {
        $('#rateDashboardAjaxContent').html('<div class="alert alert-light border mb-4">Loading rate dashboard data...</div>');
    }

    function refreshRateDashboard(range, fallbackUrl) {
        setRateLoading();

        $.ajax({
            url: '<?php echo BASE_URL; ?>?route=dashboard/rateanalytics',
            type: 'GET',
            dataType: 'json',
            data: { range: range }
        }).done(function(response) {
            if (!response || parseInt(response.status, 10) !== 101) {
                window.location.href = fallbackUrl;
                return;
            }

            $('#rateDashboardAjaxContent').html(response.html || '');
            $('.js-rate-range').removeClass('btn-primary').addClass('btn-outline-secondary');
            $('.js-rate-range[data-range="' + (response.selectedRange || range) + '"]')
                .removeClass('btn-outline-secondary')
                .addClass('btn-primary');
        }).fail(function() {
            window.location.href = fallbackUrl;
        });
    }

    $(document).on('click', '.js-rate-range', function(event) {
        event.preventDefault();
        refreshRateDashboard($(this).data('range'), $(this).attr('href'));
    });
})(window.jQuery);
</script>

<?php
include(INCLUDEPATH . 'modules/common/footer_1.php');
?>