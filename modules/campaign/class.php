<?php
// Modulename
Class Campaign{
	
	//private $pages;
	//public $select;
	//public $totalPages;
	public function __construct() {
      $this->modal = loadmodal("campaign");
    }
	public function index(){
        $_SESSION['navurl'] = 'Module';
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
	    
	    $companies = [];
        if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') {
            $companies = $this->modal->getCompanies();
        }
        
		include("view/index.php");
		//$this->record();
		}
		
		include('modules/common/campaignfooter.php');
		 
	}		
	//public function record($keywords,$pages)
	/*public function record()
	{
		
		include("view/record.php");
	}*/
	public function toggle_campaign_status()
	{
	    $input = $_POST;

        if (isset($input['id'], $input['status'])) {
            $dpd_from = isset($input['dpd_from']) && $input['dpd_from'] !== '' ? intval($input['dpd_from']) : null;
            $dpd_to = isset($input['dpd_to']) && $input['dpd_to'] !== '' ? intval($input['dpd_to']) : null;
            
            $success = $this->modal->updatestatus($input['id'], $input['status'], $dpd_from, $dpd_to);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false]);
        }
	}
	
	
	public function get_campaigns()
	{
	    $company_id = null;

        // If Super Admin, check for filter
        if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') {
            if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
                $company_id = intval($_GET['company_id']);
            }
            // else remain null (fetch all)
        } 
        // If Company Admin, force session company ID
        elseif (isset($_SESSION['company_id'])) {
            $company_id = $_SESSION['company_id'];
        }
        
	    $data = $this->modal->getcampaign($company_id);
	    echo $data;
	}
	
	public function addcampaign()
	{
	    $name        = $_POST['name'] ?? '';
        $routeto     = $_POST['routeto'] ?? '';
        $dn_number   = $_POST['dn_number'] ?? '';
        $returncall  = $_POST['returncall'] ?? '';
        $weekdays    = $_POST['weekdays'] ?? [];
        $starttime   = $_POST['starttime'] ?? '';
        $stoptime    = $_POST['stoptime'] ?? '';
        $dialer_mode = $_POST['dialer_mode'] ?? 'Power Dialer';
        $route_type  = $_POST['route_type'] ?? 'Queue';
        $concurrent_calls = $_POST['concurrent_calls'] ?? 1;
        
        $created_by = $_SESSION['zid'] ?? 0;
        $company_id = 0;

        if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') {
            // Super Admin must select a company
            $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
            if ($company_id === 0) {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'error' => 'Super Admin must select a company']);
                 return;
            }
        } else {
            // Regular Admin uses their session company ID
            $company_id = isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;
        }

        if ($company_id === 0) {
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'error' => 'Invalid Company ID']);
             return;
        }
        
        if ($this->modal->checkDuplicateCampaign($name, $company_id)) {
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'error' => 'Campaign name already exists for this company.']);
             return;
        }
        
        // Generate Webhook Token for Predictive Dialer
        $webhook_token = null;
        if ($dialer_mode === 'Predictive Dialer') {
            $webhook_token = md5(uniqid(rand(), true));
        }
    
        $result = $this->modal->addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime, $company_id, $created_by, $dialer_mode, $route_type, $concurrent_calls, $webhook_token, $dn_number);
    
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
    
        // Check Campaign Status & Existence
        $campaignStatus = $this->modal->getCampaignStatus($campaignId);
        
        if ($campaignStatus === false) {
             echo json_encode(['success' => false, 'message' => 'Campaign or Company not found.']);
             return;
        }
        
        if ($campaignStatus === 'Running') {
             echo json_encode(['success' => false, 'message' => 'Cannot import numbers to a running campaign. Please stop it first.']);
             return;
        }
        
        // Prepare File Paths
        $originalFileName = $fileInfo['name'];
        $fileExt = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        
        if ($fileExt !== 'csv') {
             echo json_encode(['success' => false, 'message' => 'Only CSV files are allowed.']);
             return;
        }

        $tempFileName = 'import_' . time() . '_' . rand(1000, 9999) . '.csv';
        // Ensure UPLOAD constant is used (defined in variables.php as absolute path)
        $targetPath = UPLOAD . $tempFileName;
        
        // Log to Database (importnum)
        // Need Company ID. Fetch it again or assume modal does it? 
        // Best to fetch it here to log it correctly.
        // We can reuse getCampaignStatus if it returned array, but it returns string status.
        // Let's do a quick query via modal or just assume modal function handles extraction?
        // User wants log table populated. Let's add a helper in modal to log this.
        
        if (!is_dir(UPLOAD)) {
            mkdir(UPLOAD, 0777, true);
        }
    
        // Move uploaded file
        if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file. Permission denied or path error. Path: ' . $targetPath]);
            return;
        }
        
        $userId = $_SESSION['zid'] ?? 0;
        
        // Log Import
        $this->modal->logImport($campaignId, $originalFileName, $tempFileName, $userId);
    
        // Call model function to import
        $result = $this->modal->importnumbersql($campaignId, $targetPath);
    
        echo json_encode($result);
    }
    
    public function delete_campaign()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             echo json_encode(['success' => false, 'error' => 'Invalid Request']);
             return;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id > 0) {
            // Check Status
            $currentStatus = $this->modal->getCampaignStatus($id);
            if ($currentStatus === 'Running') {
                echo json_encode(['success' => false, 'error' => 'Cannot delete a running campaign. Please stop it first.']);
                return;
            }

            if ($this->modal->deleteCampaign($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete campaign']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
    }
    
    public function update_campaign()
    {
        // Ensure it's a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
    
        // Collect and sanitize input
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $routeto = isset($_POST['routeto']) ? trim($_POST['routeto']) : '';
        $dn_number = isset($_POST['dn_number']) ? trim($_POST['dn_number']) : '';
        $returncall = isset($_POST['returncall']) ? trim($_POST['returncall']) : '';
        $weekdays = isset($_POST['weekdays']) ? $_POST['weekdays'] : '[]';
        $starttime = isset($_POST['starttime']) ? $_POST['starttime'] : '';
        $stoptime = isset($_POST['stoptime']) ? $_POST['stoptime'] : '';
        $dialer_mode = isset($_POST['dialer_mode']) ? $_POST['dialer_mode'] : 'Power Dialer';
        $route_type = isset($_POST['route_type']) ? $_POST['route_type'] : 'Queue';
        $concurrent_calls = isset($_POST['concurrent_calls']) ? intval($_POST['concurrent_calls']) : 1;
        $webhook_token = $_POST['webhook_token'] ?? '';
    
        if ($id <= 0 || $name === '' || $routeto === '' || $returncall === '' || $starttime === '' || $stoptime === '') {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }
    
        // Check Status
        $currentStatus = $this->modal->getCampaignStatus($id);
        if ($currentStatus === 'Running') {
            echo json_encode(['success' => false, 'error' => 'Cannot edit a running campaign. Please stop it first.']);
            return;
        }

        // Prepare data for update
        $weekdays = isset($_POST['weekdays']) ? $_POST['weekdays'] : [];
        if (is_array($weekdays)) {
            $weekdays = json_encode($weekdays);
        }
        
        // Generate Token if missing and Predictive
        if ($dialer_mode === 'Predictive Dialer' && empty($webhook_token)) {
             $webhook_token = md5(uniqid(rand(), true));
        }
        
        $updated_by = $_SESSION['zid'] ?? 0;
    
        $data = [
            'name' => $name,
            'routeto' => $routeto,
            'dn_number' => $dn_number,
            'returncall' => $returncall,
            'weekdays' => $weekdays, 
            'starttime' => $starttime,
            'stoptime' => $stoptime,
            'dialer_mode' => $dialer_mode,
            'route_type' => $route_type,
            'concurrent_calls' => $concurrent_calls,
            'webhook_token' => $webhook_token,
            'updated_by' => $updated_by
        ];
    
        // Call model function
        $success = $this->modal->updatecampaign($id, $data);
    
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update campaign']);
        }
    }
    
    public function download_sample()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sample_campaign_numbers.csv"');
        
        $output = fopen('php://output', 'w');
        // Fixed headers + example extra headers
        fputcsv($output, ['number', 'fname', 'lname', 'type', 'feedback', 'scheduled_date', 'scheduled_time', 'custom_field_1']);
        
        // Sample data
        fputcsv($output, ['1234567890', 'John', 'Doe', 'Lead', 'Interested', '2025-12-31', '14:30', 'Value1']);
        fputcsv($output, ['9876543210', 'Jane', 'Smith', 'Customer', 'CallBack', '', '', 'DataA']);
        
        fclose($output);
        exit;
    }


    // --- SKIPPED NUMBERS ---
    public function skipped()
    {
        $_SESSION['navurl'] = 'Skipnum'; // Highlight 'Skipnum' submenu
        include(INCLUDEPATH.'modules/common/campaignheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');
        
        $companies = [];
        if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') {
            $companies = $this->modal->getCompanies();
        }
        
        include("view/skipped.php");
        include('modules/common/campaignfooter.php');
    }
    
    public function get_skipped_numbers_list()
    {
        $company_id = null;
        if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') {
             $company_id = isset($_GET['company_id']) && !empty($_GET['company_id']) ? intval($_GET['company_id']) : null;
        } elseif (isset($_SESSION['company_id'])) {
            $company_id = $_SESSION['company_id'];
        }
        
        $data = $this->modal->getSkippedNumbers($company_id);
	    echo json_encode($data);
    }
    
    // --- IMPORT LOGS ---
    public function importlog()
    {
        $_SESSION['navurl'] = 'Importnum';
        // Only Super Admin should access? Or allow company admin to see their logs?
        // User request: "Importnum(only for super admin)"
        if (!isset($_SESSION['erole']) || $_SESSION['erole'] != 'super_admin') {
            echo "Access Denied";
            return;
        }

        include(INCLUDEPATH.'modules/common/campaignheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');
        
        $companies = $this->modal->getCompanies();
        
        include("view/importlog.php");
        include('modules/common/campaignfooter.php');
    }
    
    public function get_import_logs_list()
    {
        // Super admin only
        if (!isset($_SESSION['erole']) || $_SESSION['erole'] != 'super_admin') {
             echo json_encode([]);
             return;
        }
        $company_id = isset($_GET['company_id']) && !empty($_GET['company_id']) ? intval($_GET['company_id']) : null;
        
        $data = $this->modal->getImportLogs($company_id);
	    echo json_encode($data);
    }
    
    public function download_import_file()
    {
        // ... Logic to download preserved file
    }

}
?>