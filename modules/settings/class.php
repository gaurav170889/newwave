<?php
Class Settings{

	public function __construct() {
      $this->modal = loadmodal("settings");
    }

	public function index(){
        $_SESSION['navurl'] = 'Settings'; // For Navbar highlighting
        
        // Ensure only Admin/Super Admin access?
        // Assuming any logged in user with access to this module (company admin).
        if (!isset($_SESSION['company_id'])) {
             echo "Access Denied"; return;
        }
        
        $company_id = $_SESSION['company_id'];
        $settings = $this->modal->getSettings($company_id);
        $rating_questions = $this->modal->getQuestions($company_id);

		include(INCLUDEPATH.'modules/common/campaignheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');
		
		include(__DIR__ . "/view/index.php");
		
		include(INCLUDEPATH.'modules/common/campaignfooter.php');
	}

    public function save() {
        if (!isset($_SESSION['company_id'])) {
             echo json_encode(['success' => false, 'message' => 'Session expired.']);
             return;
        }
        
        $company_id = $_SESSION['company_id'];
        $data = $_POST;
        
        // Handle Logo Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $uploadDir = 'asset/logos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $newFilename = 'company_' . $company_id . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                    $data['logo'] = $newFilename;
                } else {
                     // Log error? For now proceed without logo update
                }
            }
        }
        // Handle Auth Token Generation
        $pbxurl_raw = $data['pbxurl'] ?? '';
        $auth_method = $data['auth_method'] ?? '';
        
        $generated_token = null;
        $creds = [];
        
        if ($auth_method == 'oauth') {
            $creds['client_id'] = $data['pbxclientid'] ?? '';
            $creds['client_secret'] = $data['pbxsecret'] ?? '';
        } elseif ($auth_method == 'login') {
            $creds['username'] = $data['pbxloginid'] ?? '';
            $creds['password'] = $data['pbxloginpass'] ?? '';
        }
        
        if ($pbxurl_raw) {
            $generated_token = Generate3CXToken($pbxurl_raw, $auth_method, $creds);
        }
        
        if ($generated_token) {
            $data['auth_token'] = $generated_token;
            // auth_updated_at handles by modal's NOW()
        }
        
        $result = $this->modal->saveSettings($company_id, $data);
        
        echo json_encode($result);
    }
}
?>
