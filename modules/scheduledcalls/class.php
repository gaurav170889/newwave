<?php
class Scheduledcalls {
    public $modal;

    public function __construct() {
        $this->modal = loadmodal("scheduledcalls");
    }

    private function getSessionRole()
    {
        return strtolower(trim((string) ($_SESSION['prole'] ?? ($_SESSION['role'] ?? ''))));
    }

    private function resolveCompanyIdFromRequest()
    {
        if ($this->getSessionRole() === 'super_admin') {
            $requestCompanyId = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
            if ($requestCompanyId > 0) {
                return $requestCompanyId;
            }
        }

        return isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;
    }

    private function json($payload)
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    public function index()
    {
        $_SESSION['navurl'] = 'Scheduledcalls';

        $sessionRole = $this->getSessionRole();
        $isAllowed = in_array($sessionRole, ['super_admin', 'company_admin'], true);
        if (!$isAllowed) {
            echo "<script>window.location.href='" . BASE_URL . "?route=dashboard/index';</script>";
            exit;
        }

        $isSuperAdmin = ($sessionRole === 'super_admin');
        $selectedCompanyId = $this->resolveCompanyIdFromRequest();
        $companies = $this->modal->getCompanies($isSuperAdmin ? 0 : $selectedCompanyId);

        include(INCLUDEPATH . 'modules/common/campaignheader.php');
        include(INCLUDEPATH . 'modules/common/navbar_1.php');
        include(__DIR__ . '/view/index.php');
        include(INCLUDEPATH . 'modules/common/campaignfooter.php');
    }

    public function getcampaigns()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $this->json($companyId > 0 ? $this->modal->getCampaignsByCompany($companyId) : []);
    }

    public function getrecords()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $status = isset($_REQUEST['status']) ? (string) $_REQUEST['status'] : '';
        $routeType = isset($_REQUEST['route_type']) ? (string) $_REQUEST['route_type'] : '';

        $this->json($this->modal->getRows($companyId, $campaignId, $status, $routeType));
    }
}
?>