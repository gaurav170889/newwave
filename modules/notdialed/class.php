<?php
class Notdialed {
    public $modal;

    public function __construct() {
        $this->modal = loadmodal("notdialed");
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
        $_SESSION['navurl'] = 'Notdialed';

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

    public function getdpdvalues()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $this->json($this->modal->getDaysPastDueOptions($companyId, $campaignId));
    }

    public function getrecords()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $daysPastDue = isset($_REQUEST['days_past_due']) ? (array) $_REQUEST['days_past_due'] : [];

        $this->json($this->modal->getNotDialedRows($companyId, $campaignId, $daysPastDue));
    }

    public function move_to_contacts()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $contactIds = isset($_POST['contact_ids']) ? (array) $_POST['contact_ids'] : [];
        $scheduleDate = isset($_POST['schedule_date']) ? (string) $_POST['schedule_date'] : '';
        $scheduleTime = isset($_POST['schedule_time']) ? (string) $_POST['schedule_time'] : '';

        $this->json($this->modal->moveToContacts($companyId, $campaignId, $contactIds, $scheduleDate, $scheduleTime));
    }
}
?>