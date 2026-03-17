<?php
class Outprefix {
    public $modal;

    public function __construct() {
        $this->modal = loadmodal("outprefix");
    }

    public function index() {
        $_SESSION['navurl'] = 'Outprefix'; // For Navbar highlighting
        
        if (!isset($_SESSION['company_id'])) {
             echo "Access Denied"; return;
        }
        
        $company_id = $_SESSION['company_id'];
        $campaigns = $this->modal->getCampaigns($company_id);

        include(INCLUDEPATH.'modules/common/campaignheader.php');
        include(INCLUDEPATH.'modules/common/navbar_1.php');
        
        include("view/index.php");
        
        include(INCLUDEPATH.'modules/common/campaignfooter.php');
    }

    public function get_prefixes() {
        if (!isset($_GET['campaign_id'])) {
            echo json_encode([]); return;
        }
        $company_id = $_SESSION['company_id'];
        $campaign_id = intval($_GET['campaign_id']);
        $prefixes = $this->modal->getPrefixes($company_id, $campaign_id);
        echo json_encode($prefixes);
    }

    public function save() {
        if (!isset($_SESSION['company_id'])) {
             echo json_encode(['success' => false, 'message' => 'Session expired.']);
             return;
        }
        
        $data = $_POST;
        $company_id = $_SESSION['company_id'];
        $result = $this->modal->savePrefixes($company_id, $data);
        
        echo json_encode($result);
    }
}
?>
