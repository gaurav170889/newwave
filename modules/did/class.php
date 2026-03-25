<?php
class Did {
    public $modal;

    public function __construct() {
        $this->modal = loadmodal("did");
    }

    public function index() {
        $_SESSION['navurl'] = 'Did';

        $isAdmin = isset($_SESSION['prole']) && in_array($_SESSION['prole'], ['super_admin', 'company_admin'], true);
        if (!$isAdmin) {
            echo "Access Denied";
            return;
        }

        $companies = [];
        if (isset($_SESSION['prole']) && $_SESSION['prole'] === 'super_admin') {
            $companies = $this->modal->getCompanies();
        }

        include(INCLUDEPATH.'modules/common/campaignheader.php');
        include(INCLUDEPATH.'modules/common/navbar_1.php');
        include("view/index.php");
        include(INCLUDEPATH.'modules/common/campaignfooter.php');
    }

    private function resolveCompanyIdFromRequest() {
        if (isset($_SESSION['prole']) && $_SESSION['prole'] === 'super_admin') {
            $cid = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
            return $cid > 0 ? $cid : 0;
        }
        return isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;
    }

    private function json($payload) {
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function apiGet($url, $token) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return [false, $err, null];
        }

        if ($code < 200 || $code >= 300) {
            return [false, 'HTTP ' . $code . ': ' . $body, null];
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return [false, 'Invalid JSON response from PBX API', null];
        }

        return [true, null, $json];
    }

    public function get_campaigns() {
        $company_id = $this->resolveCompanyIdFromRequest();
        if ($company_id <= 0) {
            $this->json([]);
            return;
        }

        $rows = $this->modal->getCampaigns($company_id);
        $this->json($rows);
    }

    public function get_synced_dids() {
        $company_id = $this->resolveCompanyIdFromRequest();
        if ($company_id <= 0) {
            $this->json([]);
            return;
        }

        $rows = $this->modal->getSyncedDids($company_id);
        $this->json($rows);
    }

    public function get_campaign_mapping() {
        $company_id = $this->resolveCompanyIdFromRequest();
        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

        if ($company_id <= 0 || $campaign_id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid company or campaign']);
            return;
        }

        $data = $this->modal->getCampaignMapping($company_id, $campaign_id);
        $this->json(['success' => true, 'data' => $data]);
    }

    public function save_campaign_mapping() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        $company_id = $this->resolveCompanyIdFromRequest();
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $outbound_rule_id = isset($_POST['outbound_rule_id']) ? intval($_POST['outbound_rule_id']) : 0;
        $did_ids = isset($_POST['did_ids']) && is_array($_POST['did_ids']) ? $_POST['did_ids'] : [];

        if ($company_id <= 0 || $campaign_id <= 0 || $outbound_rule_id <= 0) {
            $this->json(['success' => false, 'message' => 'Campaign and outbound rule are required']);
            return;
        }

        $result = $this->modal->saveCampaignMapping($company_id, $campaign_id, $outbound_rule_id, $did_ids);
        $this->json($result);
    }

    public function sync_dids() {
        $company_id = $this->resolveCompanyIdFromRequest();
        if ($company_id <= 0) {
            $this->json(['success' => false, 'message' => 'Company is required']);
            return;
        }

        $pbxurl = $this->modal->getPbxUrl($company_id);
        if (!$pbxurl) {
            $this->json(['success' => false, 'message' => 'PBX URL not configured']);
            return;
        }

        $token = RefreshTokenIfNeeded($company_id);
        if (!$token) {
            $this->json(['success' => false, 'message' => 'Failed to generate PBX token']);
            return;
        }

        $base = rtrim($pbxurl, '/');
        if (!preg_match('~^https?://~i', $base)) {
            $base = 'https://' . $base;
        }

        $url = $base . '/xapi/v1/ReportInboundRules/Pbx.GetInboundRulesData()?$top=100&$skip=0';
        list($ok, $err, $data) = $this->apiGet($url, $token);
        if (!$ok) {
            $this->json(['success' => false, 'message' => $err]);
            return;
        }

        $items = isset($data['value']) && is_array($data['value']) ? $data['value'] : [];
        $saved = $this->modal->syncDids($company_id, $items);

        $this->json([
            'success' => true,
            'message' => 'DID sync completed',
            'synced_count' => $saved,
        ]);
    }

    public function get_outbound_rules() {
        $company_id = $this->resolveCompanyIdFromRequest();
        if ($company_id <= 0) {
            $this->json(['success' => false, 'message' => 'Company is required']);
            return;
        }

        $pbxurl = $this->modal->getPbxUrl($company_id);
        if (!$pbxurl) {
            $this->json(['success' => false, 'message' => 'PBX URL not configured']);
            return;
        }

        $token = RefreshTokenIfNeeded($company_id);
        if (!$token) {
            $this->json(['success' => false, 'message' => 'Failed to generate PBX token']);
            return;
        }

        $base = rtrim($pbxurl, '/');
        if (!preg_match('~^https?://~i', $base)) {
            $base = 'https://' . $base;
        }

        $url = $base . '/xapi/v1/OutboundRules?$top=50&$skip=0&$orderby=Priority&$select=Name,Id,Routes,Priority,NumberLengthRanges,GroupIds,GroupNames,Prefix,DNRanges,Priority';
        list($ok, $err, $data) = $this->apiGet($url, $token);
        if (!$ok) {
            $this->json(['success' => false, 'message' => $err]);
            return;
        }

        $rules = [];
        $items = isset($data['value']) && is_array($data['value']) ? $data['value'] : [];
        foreach ($items as $item) {
            $rules[] = [
                'Id' => isset($item['Id']) ? intval($item['Id']) : 0,
                'Name' => isset($item['Name']) ? $item['Name'] : '',
                'Priority' => isset($item['Priority']) ? $item['Priority'] : null,
                'Prefix' => isset($item['Prefix']) ? $item['Prefix'] : ''
            ];
        }

        $this->json(['success' => true, 'data' => $rules]);
    }
}
?>