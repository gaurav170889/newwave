<?php
// Modulename
Class Campcontact{
	
	//private $pages;
	//public $select;
	//public $totalPages;
	public function __construct() {
      $this->modal = loadmodal("campcontact");
    }
	public function index(){
        $_SESSION['navurl'] = 'Campcontact';
	    //echo "abcd";
	   // exit();
		include(INCLUDEPATH.'modules/common/campaignheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');	
		//echo "abcd";
	//	exit();
		if($_SESSION['role']== "uagent")
		{
			//$qagent = $this->getagent();
			
			include("view/notadmin.php");
		}
		else
		{
		//$page = (isset($_GET['page']) && is_numeric($_GET['page']) ) ? $_GET['page'] : 1;
		//$data = $this->modal->select("agent");
		//$group = $this->modal->groupassoc("agentgroup");
		//print_r($group);
	//	$counter = 1;
		include("view/index.php");
		//$this->record();
		}
		
		include('modules/common/campcontactfooter.php');
		 
	}		
	//public function record($keywords,$pages)
	/*public function record()
	{
		
		include("view/record.php");
	}*/
	
	
    public function getallcontact() 
    {
        
        $data = $this->modal->getallcontact() ;
        header('Content-Type: application/json');
        echo $data ;
        
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