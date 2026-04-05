<?php
$rangeLabel = $rangeInfo['label'] ?? 'Today';
$rangeDisplay = $rangeInfo['display'] ?? date('M d, Y');
$totalCalls = intval($outboundSummary['total_calls'] ?? 0);
$uniqueNumbers = intval($outboundSummary['unique_numbers'] ?? 0);
$connectedCalls = intval($outboundSummary['connected_calls'] ?? 0);
$activeAgents = intval($outboundSummary['active_agents'] ?? 0);
$activeCampaigns = intval($outboundSummary['active_campaigns'] ?? 0);
$dispositionsLogged = intval($outboundSummary['dispositions_logged'] ?? 0);
$totalTalkTime = $outboundSummary['total_talk_time'] ?? '00:00:00';
$avgTalkTime = $outboundSummary['avg_talk_time'] ?? '00:00:00';
?>

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
                       class="btn btn-sm <?php echo $selectedRange === $rangeKey ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        <?php echo htmlspecialchars($rangeText, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="alert alert-light border d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <strong>Showing analytics for:</strong> <?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?>
                <span class="text-muted">(<?php echo htmlspecialchars($rangeDisplay, ENT_QUOTES, 'UTF-8'); ?>)</span>
            </div>
            <small class="text-muted mt-2 mt-md-0">Default filter: Today</small>
        </div>

        <?php if (!empty($fallbackNotice) || ($totalCalls === 0 && !empty($latestActivityAt))): ?>
            <div class="alert alert-warning border mb-4">
                <strong><?php echo htmlspecialchars($fallbackNotice ?: 'No outbound calls were found for the selected period.', ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($latestActivityAt)): ?>
                    <div class="small mt-1 text-muted">Latest recorded outbound call: <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($latestActivityAt)), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 col-xl-4 mb-3">
                <div class="card metric-card metric-card-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="metric-label">Total Calls</div>
                                <div class="metric-value"><?php echo number_format($totalCalls); ?></div>
                                <div class="metric-subtext"><?php echo htmlspecialchars($rangeLabel, ENT_QUOTES, 'UTF-8'); ?> outbound attempts</div>
                            </div>
                            <div class="metric-icon">📞</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 mb-3">
                <div class="card metric-card metric-card-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="metric-label">Unique Numbers Dialed</div>
                                <div class="metric-value"><?php echo number_format($uniqueNumbers); ?></div>
                                <div class="metric-subtext">Distinct leads reached in this period</div>
                            </div>
                            <div class="metric-icon">🎯</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 mb-3">
                <div class="card metric-card metric-card-warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="metric-label">Connected to Agents</div>
                                <div class="metric-value"><?php echo number_format($connectedCalls); ?></div>
                                <div class="metric-subtext">Calls that reached an agent conversation</div>
                            </div>
                            <div class="metric-icon">🤝</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 mb-3">
                <div class="card metric-card metric-card-danger h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="metric-label">Active Agents</div>
                                <div class="metric-value"><?php echo number_format($activeAgents); ?></div>
                                <div class="metric-subtext">Agents with connected outbound calls</div>
                            </div>
                            <div class="metric-icon">👥</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 mb-3">
                <div class="card metric-card metric-card-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="metric-label">Avg Talk Time</div>
                                <div class="metric-value"><?php echo htmlspecialchars($avgTalkTime, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="metric-subtext">Total talk time: <?php echo htmlspecialchars($totalTalkTime, ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="metric-icon">⏱️</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 mb-3">
                <div class="card metric-card metric-card-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="metric-label">Campaigns / Dispositions</div>
                                <div class="metric-value"><?php echo number_format($activeCampaigns); ?> / <?php echo number_format($dispositionsLogged); ?></div>
                                <div class="metric-subtext">Active campaigns and logged dispositions</div>
                            </div>
                            <div class="metric-icon">📊</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-5 d-flex">
                <div class="card flex-fill">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Call Status Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($statusBreakdown)): ?>
                            <?php $statusBase = max(1, $totalCalls); ?>
                            <?php foreach ($statusBreakdown as $statusRow): ?>
                                <?php $statusPercent = round((intval($statusRow['total']) * 100) / $statusBase, 1); ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-weight-semibold"><?php echo htmlspecialchars(ucwords(strtolower(str_replace('_', ' ', $statusRow['status']))), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="badge badge-primary badge-pill"><?php echo number_format(intval($statusRow['total'])); ?></span>
                                    </div>
                                    <div class="progress" style="height: 7px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, $statusPercent); ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0">No outbound call activity was found for this filter.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-7 d-flex">
                <div class="card flex-fill">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Top Campaign Activity</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm analytics-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Campaign</th>
                                    <th class="text-center">Calls</th>
                                    <th class="text-center">Unique Numbers</th>
                                    <th class="text-right">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($campaignActivity)): ?>
                                    <?php foreach ($campaignActivity as $campaignRow): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($campaignRow['campaign_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><?php echo number_format(intval($campaignRow['total_calls'])); ?></td>
                                            <td class="text-center"><?php echo number_format(intval($campaignRow['unique_numbers'])); ?></td>
                                            <td class="text-right text-muted">
                                                <?php echo !empty($campaignRow['last_call_at']) ? date('M d, Y h:i A', strtotime($campaignRow['last_call_at'])) : '-'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No campaign activity found for this filter.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">Agent Pickup Analytics</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover analytics-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Agent</th>
                                    <th class="text-center">Connected Calls</th>
                                    <th class="text-center">Unique Numbers</th>
                                    <th class="text-center">Total Talk Time</th>
                                    <th class="text-center">Avg Talk Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($agentPickupStats)): ?>
                                    <?php $pickupCounter = 1; ?>
                                    <?php foreach ($agentPickupStats as $pickupRow): ?>
                                        <tr>
                                            <td><?php echo $pickupCounter++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($pickupRow['agent_ext'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <?php if (!empty($pickupRow['agent_name'])): ?>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($pickupRow['agent_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo number_format(intval($pickupRow['connected_calls'])); ?></td>
                                            <td class="text-center"><?php echo number_format(intval($pickupRow['unique_numbers'])); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($pickupRow['total_talk_time'] ?: '00:00:00', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($pickupRow['avg_talk_time'] ?: '00:00:00', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No agent pickup data found for this date range.</td>
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
include(INCLUDEPATH . 'modules/common/footer_1.php');
?>