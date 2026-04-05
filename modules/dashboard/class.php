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

    private function buildStaticDashboardViewData($requestedRange)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange);

        return [
            'selectedRange' => $rangeInfo['key'],
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => 'Static preview mode is enabled right now so the Dashboard page loads without pulling live analytics data.',
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

    private function buildStaticRateViewData($requestedRange)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange);

        return [
            'selectedRange' => $rangeInfo['key'],
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => 'Static preview mode is enabled right now so the Rate Dashboard page loads without pulling live rating data.',
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
        $rangeInfo = $this->name->resolveDateRange($requestedRange);
        $selectedRange = $rangeInfo['key'];

        return [
            'selectedRange' => $selectedRange,
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => '',
            'outboundSummary' => $this->name->getOutboundSummary($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'statusBreakdown' => $this->name->getCallStatusBreakdown($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'campaignActivity' => $this->name->getCampaignActivity($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'agentPickupStats' => $this->name->getAgentPickupAnalytics($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'latestActivityAt' => $this->name->getLatestOutboundActivity($companyId),
            'rangeOptions' => $this->getRangeOptions(),
        ];
    }

    private function buildRateViewData($requestedRange, $companyId)
    {
        $rangeInfo = $this->name->resolveDateRange($requestedRange);
        $selectedRange = $rangeInfo['key'];

        return [
            'selectedRange' => $selectedRange,
            'rangeInfo' => $rangeInfo,
            'fallbackNotice' => '',
            'point_1' => $this->name->pointone("rate", "1", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'point_3' => $this->name->pointthree("rate", "3", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'point_5' => $this->name->pointfive("rate", "5", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'point_all' => $this->name->totalcallpoint("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'agentout' => $this->name->averagescore("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId),
            'rangeOptions' => $this->getRangeOptions(),
            'counter' => 1,
        ];
    }

	public function index(){
        $_SESSION['navurl'] = 'Dashboard';
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');

        extract($this->buildStaticDashboardViewData($this->getSelectedRange()));
		include(__DIR__ . "/view/index.php");
	}

    public function analytics(){
        header('Content-Type: application/json');
        extract($this->buildStaticDashboardViewData($this->getSelectedRange()));

        ob_start();
        include(__DIR__ . "/view/analytics_content.php");
        $html = ob_get_clean();

        echo json_encode([
            'status' => 101,
            'selectedRange' => $selectedRange,
            'html' => $html,
        ]);
        exit;
    }

    public function rates(){
        $_SESSION['navurl'] = 'Ratedashboard';
        include(INCLUDEPATH.'modules/common/header.php');
        include(INCLUDEPATH.'modules/common/navbar_1.php');

        extract($this->buildStaticRateViewData($this->getSelectedRange()));
        include(__DIR__ . "/view/rates.php");
    }

    public function rateanalytics(){
        header('Content-Type: application/json');
        extract($this->buildStaticRateViewData($this->getSelectedRange()));

        ob_start();
        include(__DIR__ . "/view/rates_content.php");
        $html = ob_get_clean();

        echo json_encode([
            'status' => 101,
            'selectedRange' => $selectedRange,
            'html' => $html,
        ]);
        exit;
    }
	
	public function goga(){
		echo "This is goga";
	}
}
?>