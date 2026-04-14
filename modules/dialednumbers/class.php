<?php
class Dialednumbers {
    public $modal;

    public function __construct() {
        $this->modal = loadmodal("dialednumbers");
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
        $_SESSION['navurl'] = 'Dialednumbers';

        $sessionRole = $this->getSessionRole();
        $isAllowed = in_array($sessionRole, ['super_admin', 'company_admin', 'uagent'], true);
        if (!$isAllowed) {
            echo "<script>window.location.href='" . BASE_URL . "?route=dashboard/index';</script>";
            exit;
        }

        $isSuperAdmin = ($sessionRole === 'super_admin');
        $isAgentUser = ($sessionRole === 'uagent');
        $canManageDialedContacts = in_array($sessionRole, ['super_admin', 'company_admin'], true);
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

    public function getfiltervalues()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $filterType = isset($_REQUEST['filter_type']) ? (string) $_REQUEST['filter_type'] : '';

        $this->json($this->modal->getFilterValues($companyId, $campaignId, $filterType));
    }

    public function getagents()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $this->json($companyId > 0 ? $this->modal->getAgentsByCompany($companyId) : []);
    }

    public function getdpdvalues()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $filterType = isset($_REQUEST['filter_type']) ? (string) $_REQUEST['filter_type'] : '';
        $filterValue = isset($_REQUEST['filter_value']) ? (string) $_REQUEST['filter_value'] : '';

        $this->json($this->modal->getDaysPastDueOptions($companyId, $campaignId, $filterType, $filterValue));
    }

    public function getrecords()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $filterType = isset($_REQUEST['filter_type']) ? (string) $_REQUEST['filter_type'] : '';
        $filterValue = isset($_REQUEST['filter_value']) ? (string) $_REQUEST['filter_value'] : '';
        $daysPastDue = isset($_REQUEST['days_past_due']) ? (array) $_REQUEST['days_past_due'] : [];

        $this->json($this->modal->getDialedDataTableResponse($companyId, $campaignId, $filterType, $filterValue, $daysPastDue));
    }

    public function move_to_contacts()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (!in_array($this->getSessionRole(), ['super_admin', 'company_admin'], true)) {
            $this->json(['success' => false, 'message' => 'You are not allowed to move dialed numbers back to Contacts.']);
            return;
        }

        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $contactIds = isset($_POST['contact_ids']) ? (array) $_POST['contact_ids'] : [];
        $scheduleDate = isset($_POST['schedule_date']) ? (string) $_POST['schedule_date'] : '';
        $scheduleTime = isset($_POST['schedule_time']) ? (string) $_POST['schedule_time'] : '';

        $this->json($this->modal->moveToContacts($companyId, $campaignId, $contactIds, $scheduleDate, $scheduleTime));
    }

    public function updateDispositionSql()
    {
        $id = $_POST['contact_id'] ?? 0;
        $disposition = $_POST['disposition'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $date = $_POST['callback_date'] ?? '';
        $time = $_POST['callback_time'] ?? '';
        $routeType = $_POST['route_type'] ?? 'Agent';
        $agentId = $_POST['agent_id'] ?? 0;

        $this->json($this->modal->updateDispositionSql($id, $disposition, $notes, $date, $time, $routeType, $agentId));
    }
}
?>
