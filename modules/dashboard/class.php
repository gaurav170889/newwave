<?php
// Modulename
Class Dashboard{
	function __construct() {
      $this->name = loadmodal("dashboard");
    }
	public function index(){
        $_SESSION['navurl'] = 'Dashboard';
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');

        $selectedRange = isset($_GET['range']) ? trim($_GET['range']) : 'today';
        $rangeInfo = $this->name->resolveDateRange($selectedRange);
        $selectedRange = $rangeInfo['key'];
        $companyId = isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : 0;
        $fallbackNotice = '';

        $outboundSummary = $this->name->getOutboundSummary($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);

        if (intval($outboundSummary['total_calls'] ?? 0) === 0) {
            foreach (['this_week', 'last_week', 'this_month', 'last_month'] as $fallbackKey) {
                if ($fallbackKey === $selectedRange) {
                    continue;
                }

                $candidateRange = $this->name->resolveDateRange($fallbackKey);
                $candidateSummary = $this->name->getOutboundSummary($candidateRange['start_date'], $candidateRange['end_date'], $companyId);

                if (intval($candidateSummary['total_calls'] ?? 0) > 0) {
                    $fallbackNotice = 'No outbound activity was found for ' . $rangeInfo['label'] . '. Showing ' . $candidateRange['label'] . ' instead.';
                    $rangeInfo = $candidateRange;
                    $selectedRange = $candidateRange['key'];
                    $outboundSummary = $candidateSummary;
                    break;
                }
            }
        }

        $statusBreakdown = $this->name->getCallStatusBreakdown($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $campaignActivity = $this->name->getCampaignActivity($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $agentPickupStats = $this->name->getAgentPickupAnalytics($rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $latestActivityAt = $this->name->getLatestOutboundActivity($companyId);
        $rangeOptions = [
            'today' => 'Today',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
        ];

		include(__DIR__ . "/view/index.php");
	}

    public function rates(){
        $_SESSION['navurl'] = 'Ratedashboard';
        include(INCLUDEPATH.'modules/common/header.php');
        include(INCLUDEPATH.'modules/common/navbar_1.php');

        $selectedRange = isset($_GET['range']) ? trim($_GET['range']) : 'today';
        $rangeInfo = $this->name->resolveDateRange($selectedRange);
        $selectedRange = $rangeInfo['key'];
        $companyId = isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : 0;
        $fallbackNotice = '';

        $point_1 = $this->name->pointone("rate", "1", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $point_3 = $this->name->pointthree("rate", "3", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $point_5 = $this->name->pointfive("rate", "5", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $point_all = $this->name->totalcallpoint("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
        $agentout = $this->name->averagescore("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);

        if (intval($point_all[0] ?? 0) === 0) {
            foreach (['this_week', 'last_week', 'this_month', 'last_month'] as $fallbackKey) {
                if ($fallbackKey === $selectedRange) {
                    continue;
                }

                $candidateRange = $this->name->resolveDateRange($fallbackKey);
                $candidateTotal = $this->name->totalcallpoint("rate", $candidateRange['start_date'], $candidateRange['end_date'], $companyId);

                if (intval($candidateTotal[0] ?? 0) > 0) {
                    $fallbackNotice = 'No rating data was found for ' . $rangeInfo['label'] . '. Showing ' . $candidateRange['label'] . ' instead.';
                    $rangeInfo = $candidateRange;
                    $selectedRange = $candidateRange['key'];
                    $point_1 = $this->name->pointone("rate", "1", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
                    $point_3 = $this->name->pointthree("rate", "3", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
                    $point_5 = $this->name->pointfive("rate", "5", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
                    $point_all = $candidateTotal;
                    $agentout = $this->name->averagescore("rate", $rangeInfo['start_date'], $rangeInfo['end_date'], $companyId);
                    break;
                }
            }
        }
        $rangeOptions = [
            'today' => 'Today',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
        ];
        $counter = 1;

        include(__DIR__ . "/view/rates.php");
    }
	
	public function goga(){
		echo "This is goga";
	}
}
?>