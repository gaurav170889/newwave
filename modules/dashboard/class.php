<?php
// Modulename
Class Dashboard{
	function __construct() {
      $this->name = loadmodal("dashboard");
    }

    private function getSelectedRange()
    {
        return isset($_GET['range']) ? trim((string) $_GET['range']) : 'today';
    }

    private function getCompanyId()
    {
        return isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : 0;
    }

    private function getSessionRole()
    {
        return strtolower(trim((string) ($_SESSION['prole'] ?? ($_SESSION['role'] ?? ''))));
    }

    private function canUseAllCompanyFallback()
    {
        return in_array($this->getSessionRole(), ['super_admin', 'superadmin', 'admin'], true);
    }

    private function safeDataCall($label, callable $callback, $default)
    {
        try {
            $result = $callback();
            return $result !== null ? $result : $default;
        } catch (\Throwable $exception) {
            error_log('Dashboard data error (' . $label . '): ' . $exception->getMessage());
            return $default;
        }
    }

    private function getRangeOptions()
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
        ];
    }

    private function buildStaticDashboardViewData($requestedRange, $companyId = 0)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange, $companyId);

        return [
            'selectedRange' => $rangeInfo['key'],
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => 'Static preview is shown first while live dashboard data loads in the background. If loading fails, 0 values stay visible here.',
            'outboundSummary' => [
                'total_calls' => 0,
                'unique_numbers' => 0,
                'connected_calls' => 0,
                'active_agents' => 0,
                'active_campaigns' => 0,
                'dispositions_logged' => 0,
                'total_talk_time' => '00:00:00',
                'avg_talk_time' => '00:00:00',
            ],
            'statusBreakdown' => [],
            'campaignActivity' => [],
            'agentPickupStats' => [],
            'latestActivityAt' => null,
            'rangeOptions' => $this->getRangeOptions(),
        ];
    }

    private function buildStaticRateViewData($requestedRange, $companyId = 0)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange, $companyId);

        return [
            'selectedRange' => $rangeInfo['key'],
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => 'Static preview is shown first while live rate data loads in the background. If loading fails, 0 values stay visible here.',
            'point_1' => [0],
            'point_3' => [0],
            'point_5' => [0],
            'point_all' => [0],
            'agentout' => [],
            'rangeOptions' => $this->getRangeOptions(),
            'counter' => 1,
        ];
    }

    private function buildDashboardViewData($requestedRange, $companyId)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange, $companyId);
        $selectedRange = $rangeInfo['key'];
        $fallbackNotice = '';
        $defaultSummary = [
            'total_calls' => 0,
            'unique_numbers' => 0,
            'connected_calls' => 0,
            'active_agents' => 0,
            'active_campaigns' => 0,
            'dispositions_logged' => 0,
            'total_talk_time' => '00:00:00',
            'avg_talk_time' => '00:00:00',
        ];

        $outboundSummary = $this->safeDataCall('outbound summary', function () use ($rangeInfo, $companyId) {
            return $this->name->getOutboundSummary($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, $defaultSummary);
        $statusBreakdown = $this->safeDataCall('call status breakdown', function () use ($rangeInfo, $companyId) {
            return $this->name->getCallStatusBreakdown($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, []);
        $campaignActivity = $this->safeDataCall('campaign activity', function () use ($rangeInfo, $companyId) {
            return $this->name->getCampaignActivity($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, []);
        $agentPickupStats = $this->safeDataCall('agent pickup analytics', function () use ($rangeInfo, $companyId) {
            return $this->name->getAgentPickupAnalytics($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, []);
        $latestActivityAt = $this->safeDataCall('latest outbound activity', function () use ($companyId) {
            return $this->name->getLatestOutboundActivity($companyId);
        }, null);

        if (($outboundSummary['total_calls'] ?? 0) === 0 && $companyId > 0 && $this->canUseAllCompanyFallback()) {
            $allCompanySummary = $this->safeDataCall('all-company outbound summary', function () use ($rangeInfo) {
                return $this->name->getOutboundSummary($rangeInfo['start_date'], $rangeInfo['end_date'], 0);
            }, $defaultSummary);

            if (($allCompanySummary['total_calls'] ?? 0) > 0) {
                $outboundSummary = $allCompanySummary;
                $statusBreakdown = $this->safeDataCall('all-company call status breakdown', function () use ($rangeInfo) {
                    return $this->name->getCallStatusBreakdown($rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, []);
                $campaignActivity = $this->safeDataCall('all-company campaign activity', function () use ($rangeInfo) {
                    return $this->name->getCampaignActivity($rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, []);
                $agentPickupStats = $this->safeDataCall('all-company agent pickup analytics', function () use ($rangeInfo) {
                    return $this->name->getAgentPickupAnalytics($rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, []);
                $latestActivityAt = $this->safeDataCall('all-company latest outbound activity', function () {
                    return $this->name->getLatestOutboundActivity(0);
                }, $latestActivityAt);
                $fallbackNotice = 'Showing overall dialer activity because the current company filter did not match the available records for this range.';
            }
        }

        return [
            'selectedRange' => $selectedRange,
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => $fallbackNotice,
            'outboundSummary' => $outboundSummary,
            'statusBreakdown' => $statusBreakdown,
            'campaignActivity' => $campaignActivity,
            'agentPickupStats' => $agentPickupStats,
            'latestActivityAt' => $latestActivityAt,
            'rangeOptions' => $this->getRangeOptions(),
        ];
    }

    private function buildRateViewData($requestedRange, $companyId)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange, $companyId);
        $selectedRange = $rangeInfo['key'];
        $fallbackNotice = '';

        $point1 = $this->safeDataCall('rate point 1', function () use ($rangeInfo, $companyId) {
            return $this->name->pointone("rate", "1", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, [0]);
        $point3 = $this->safeDataCall('rate point 3', function () use ($rangeInfo, $companyId) {
            return $this->name->pointthree("rate", "3", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, [0]);
        $point5 = $this->safeDataCall('rate point 5', function () use ($rangeInfo, $companyId) {
            return $this->name->pointfive("rate", "5", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, [0]);
        $pointAll = $this->safeDataCall('total rated calls', function () use ($rangeInfo, $companyId) {
            return $this->name->totalcallpoint("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, [0]);
        $agentOut = $this->safeDataCall('rated agent performance', function () use ($rangeInfo, $companyId) {
            return $this->name->averagescore("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        }, []);

        if (intval($pointAll[0] ?? 0) === 0 && $companyId > 0 && $this->canUseAllCompanyFallback()) {
            $allPointAll = $this->safeDataCall('all-company total rated calls', function () use ($rangeInfo) {
                return $this->name->totalcallpoint("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], 0);
            }, [0]);

            if (intval($allPointAll[0] ?? 0) > 0) {
                $point1 = $this->safeDataCall('all-company rate point 1', function () use ($rangeInfo) {
                    return $this->name->pointone("rate", "1", $rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, [0]);
                $point3 = $this->safeDataCall('all-company rate point 3', function () use ($rangeInfo) {
                    return $this->name->pointthree("rate", "3", $rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, [0]);
                $point5 = $this->safeDataCall('all-company rate point 5', function () use ($rangeInfo) {
                    return $this->name->pointfive("rate", "5", $rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, [0]);
                $pointAll = $allPointAll;
                $agentOut = $this->safeDataCall('all-company rated agent performance', function () use ($rangeInfo) {
                    return $this->name->averagescore("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], 0);
                }, []);
                $fallbackNotice = 'Showing overall rating activity because the current company filter did not match the available records for this range.';
            }
        }

        return [
            'selectedRange' => $selectedRange,
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => $fallbackNotice,
            'point_1' => $point1,
            'point_3' => $point3,
            'point_5' => $point5,
            'point_all' => $pointAll,
            'agentout' => $agentOut,
            'rangeOptions' => $this->getRangeOptions(),
            'counter' => 1,
        ];
    }

    private function buildDashboardAjaxViewData($requestedRange, $companyId)
    {
        try {
            return $this->buildDashboardViewData($requestedRange, $companyId);
        } catch (\Throwable $exception) {
            error_log('Dashboard AJAX error: ' . $exception->getMessage());
        }

        $viewData = $this->buildStaticDashboardViewData($requestedRange, $companyId);
        $viewData['fallbackNotice'] = 'Live dashboard data could not be loaded right now, so 0 values are being shown.';

        return $viewData;
    }

    private function buildRateAjaxViewData($requestedRange, $companyId)
    {
        try {
            return $this->buildRateViewData($requestedRange, $companyId);
        } catch (\Throwable $exception) {
            error_log('Rate dashboard AJAX error: ' . $exception->getMessage());
        }

        $viewData = $this->buildStaticRateViewData($requestedRange, $companyId);
        $viewData['fallbackNotice'] = 'Live rate data could not be loaded right now, so 0 values are being shown.';

        return $viewData;
    }

    private function renderAnalyticsResponse($viewFile, array $viewData)
    {
        extract($viewData);

        ob_start();
        include(__DIR__ . $viewFile);
        $html = ob_get_clean();

        echo json_encode([
            'status' => 101,
            'selectedRange' => $viewData['selectedRange'] ?? 'today',
            'message' => $viewData['fallbackNotice'] ?? '',
            'html' => $html,
        ]);
        exit;
    }

	public function index(){
        $_SESSION['navurl'] = 'Dashboard';
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');

        extract($this->buildStaticDashboardViewData($this->getSelectedRange(), $this->getCompanyId()));
		include(__DIR__ . "/view/index.php");
	}

    public function analytics(){
        header('Content-Type: application/json');
        $this->renderAnalyticsResponse(
            "/view/analytics_content.php",
            $this->buildDashboardAjaxViewData($this->getSelectedRange(), $this->getCompanyId())
        );
    }

    public function rates(){
        $_SESSION['navurl'] = 'Ratedashboard';
        include(INCLUDEPATH.'modules/common/header.php');
        include(INCLUDEPATH.'modules/common/navbar_1.php');

        extract($this->buildStaticRateViewData($this->getSelectedRange(), $this->getCompanyId()));
        include(__DIR__ . "/view/rates.php");
    }

    public function rateanalytics(){
        header('Content-Type: application/json');
        $this->renderAnalyticsResponse(
            "/view/rates_content.php",
            $this->buildRateAjaxViewData($this->getSelectedRange(), $this->getCompanyId())
        );
    }
	
	public function goga(){
		echo "This is goga";
	}
}
?>
