<?php
$rangeLabel = $rangeInfo['label'] ?? 'Today';
$rangeDisplay = $rangeInfo['display'] ?? date('M d, Y');
?>

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
                       class="btn btn-sm <?php echo $selectedRange === $rangeKey ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        <?php echo htmlspecialchars($rangeText, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="alert alert-light border d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <strong>Showing ratings for:</strong> <?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?>
                <span class="text-muted">(<?php echo htmlspecialchars($rangeDisplay, ENT_QUOTES, 'UTF-8'); ?>)</span>
            </div>
        </div>

        <?php if (!empty($fallbackNotice)): ?>
            <div class="alert alert-warning border mb-4">
                <strong><?php echo htmlspecialchars($fallbackNotice, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        <?php endif; ?>

        <div class="row mb-2">
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card rate-card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-2">Point 1</h6>
                        <h2><?php echo number_format(intval($point_1[0] ?? 0)); ?></h2>
                        <small><?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card rate-card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-2">Point 3</h6>
                        <h2><?php echo number_format(intval($point_3[0] ?? 0)); ?></h2>
                        <small><?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card rate-card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-2">Point 5</h6>
                        <h2><?php echo number_format(intval($point_5[0] ?? 0)); ?></h2>
                        <small><?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-3">
                <div class="card rate-card bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-2">Total Rated Calls</h6>
                        <h2><?php echo number_format(intval($point_all[0] ?? 0)); ?></h2>
                        <small><?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Rated Agent Performance</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Agent</th>
                                    <th class="text-center">Average Point</th>
                                    <th class="text-center">Total Point</th>
                                    <th class="text-center">Rated Calls</th>
                                    <th class="text-center">Records</th>
                                    <th class="text-center">Rating Coverage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($agentout)): ?>
                                    <?php foreach($agentout as $se_data): ?>
                                        <tr>
                                            <td><?php echo $counter; $counter++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($se_data['agent_ext'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <?php if (!empty($se_data['agent_name'])): ?>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($se_data['agent_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo htmlspecialchars((string) $se_data['avg_point'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><?php echo number_format(intval($se_data['total_point'])); ?></td>
                                            <td class="text-center"><?php echo number_format(intval($se_data['total_calls'])); ?></td>
                                            <td class="text-center"><?php echo number_format(intval($se_data['total'])); ?></td>
                                            <td class="text-center"><span class="badge badge-light"><?php echo htmlspecialchars($se_data['percent_grade'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No rating data found for this filter.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include('modules/common/footer_1.php');
?>