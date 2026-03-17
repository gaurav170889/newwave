<?php
class Admindashboard {
    
    function __construct() {
        $this->modal = loadmodal("admindashboard");
    }

    public function index() {
        // Fetch all companies via AJAX in the view
        include(MODULEPATH . "admindashboard/view/index.php");
    }

    public function get_companies() {
        header('Content-Type: application/json');
        $companies = $this->modal->getCompanies();
        echo json_encode($companies);
        exit;
    }

    public function add_company() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['company_name']);
            $status = trim($_POST['status']);
            // Add other fields as needed
            
            if ($this->modal->addCompany($name, $status)) {
                echo json_encode(['status' => 'success', 'message' => 'Company added successfully']);
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Error adding company']);
            }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
        }
        exit;
    }
    
    public function add_user() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Need company_id to add user to
            $user_email = trim($_POST['user_email']);
            $company_id = intval($_POST['company_id']);
            $password = trim($_POST['password']);
             
            if ($this->modal->addCompanyAdmin($company_id, $user_email, $password)) {
                 echo json_encode(['status' => 'success', 'message' => 'User added successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error adding user']);
            }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
        }
        exit;
    }

    public function reset_password() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $user_id = intval($_POST['user_id']);
             $new_password = trim($_POST['password']);
             
             if ($this->modal->updatePassword($user_id, $new_password)) {
                 echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
             } else {
                 echo json_encode(['status' => 'error', 'message' => 'Error updating password']);
             }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
        }
        exit;
    }
    public function get_company_settings() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['company_id'])) {
            $company_id = intval($_GET['company_id']);
            $settings = $this->modal->getCompanySettings($company_id);
            $questions = $this->modal->getCompanyQuestions($company_id);
            
            // Construct response
            $response = [
                'success' => true,
                'settings' => $settings ? $settings : [],
                'questions' => $questions,
                'webhook_base_url' => WEBHOOK_URL
            ];
            echo json_encode($response);
        } else {
             echo json_encode(['success' => false, 'message' => 'Invalid Request']);
        }
        exit;
    }

    public function save_company_settings() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $company_id = intval($_POST['company_id']);
             
             if ($this->modal->updateCompanySettings($company_id, $_POST)) {
                 echo json_encode(['status' => 'success', 'message' => 'Settings updated successfully']);
             } else {
                 echo json_encode(['status' => 'error', 'message' => 'Error updating settings']);
             }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
        }
        exit;
    }
}
?>
