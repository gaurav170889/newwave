<?php
// Modulename
Class Campcontact{
	
	//private $pages;
	//public $select;
	//public $totalPages;
	public function __construct() {
      $this->modal = loadmodal("campcontact");
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

	public function index(){
        $_SESSION['navurl'] = 'Campcontact';
        $sessionRole = $this->getSessionRole();
        $isSuperAdmin = ($sessionRole === 'super_admin');
        $selectedCompanyId = $this->resolveCompanyIdFromRequest();
        $companies = $this->modal->getCompanies($isSuperAdmin ? 0 : $selectedCompanyId);

		include(INCLUDEPATH.'modules/common/campaignheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');	
		if($sessionRole == "uagent")
		{
			include(__DIR__ . "/view/notadmin.php");
		}
		else
		{
			include(__DIR__ . "/view/index.php");
		}
		
		include('modules/common/campcontactfooter.php');
		 
	}		
	//public function record($keywords,$pages)
	/*public function record()
	{
		
		include(__DIR__ . "/view/record.php");
	}*/
	
	
    public function getallcontact() 
    {
        header('Content-Type: application/json');
        echo json_encode($this->modal->getContactDataTableResponse());
    }

    public function getcampaigns()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        header('Content-Type: application/json');
        echo json_encode($companyId > 0 ? $this->modal->getCampaignsByCompany($companyId) : []);
    }

    public function getfiltervalues()
    {
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
        $filterType = isset($_REQUEST['filter_type']) ? (string) $_REQUEST['filter_type'] : '';

        header('Content-Type: application/json');
        echo json_encode($this->modal->getFilterValues($companyId, $campaignId, $filterType));
    }
	
	public function addcampaign()
	{
	   
	    $name        = $_POST['name'] ?? '';
        $routeto     = $_POST['routeto'] ?? '';
        $returncall  = $_POST['returncall'] ?? '';
        $weekdays    = $_POST['weekdays'] ?? [];
        $starttime   = $_POST['starttime'] ?? '';
        $stoptime    = $_POST['stoptime'] ?? '';
    
        $result = $this->modal->addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime);
    
        header('Content-Type: application/json');
        echo json_encode($result);
	}
	
	public function import_numbers()
    {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== 0) {
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            return;
        }
    
        $fileInfo = $_FILES['csvFile'];
        $campaignId = isset($_POST['campaignid']) ? intval($_POST['campaignid']) : 0;
    
        // Validate CSV extension
        $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            echo json_encode(['success' => false, 'message' => 'Only CSV files are allowed.']);
            return;
        }
    
        // Generate temporary filename
        $tempName = 'campaign_' . time() . '_' . rand(1000, 9999) . '.csv';
        $targetPath = UPLOAD . $tempName;
    
        // Move uploaded file
        if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
            return;
        }
    
        // Call model function to import
        $result = $this->modal->importnumbersql($campaignId, $targetPath);
    
        echo json_encode($result);
    }
	
	public function delete_all_contacts()
	{
	    $data = $this->modal->deletecontacts();
	}
    
    public function updateDispositionSql()
    {
        $id = $_POST['contact_id'] ?? 0;
        $disposition = $_POST['disposition'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $date = $_POST['callback_date'] ?? '';
        $time = $_POST['callback_time'] ?? '';

        $result = $this->modal->updateDispositionSql($id, $disposition, $notes, $date, $time);
        echo json_encode($result);
    }
		
}
?>
