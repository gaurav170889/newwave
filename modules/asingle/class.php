<?php
// Modulename
Class Asingle{
	function __construct() {
      $this->modal = loadmodal("asingle");;
    }
	public function index(){
        $_SESSION['navurl'] = 'Asingle';
		include('modules/common/header.php');
		include('modules/common/navbar_1.php');
		include(__DIR__ . "/view/index.php");
		include('modules/common/footer_1.php');
	}
	
	public function get_ratings() {
        header('Content-Type: application/json');
        
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $company_id = $_SESSION['company_id'] ?? 0;

        $data = $this->modal->getRatings($company_id, $start_date, $end_date);
        echo json_encode(['data' => $data]);
        exit;
    }

    public function get_rating_details() {
        header('Content-Type: application/json');
        $rid = intval($_GET['rid']);
        $company_id = $_SESSION['company_id'] ?? 0;
        
        $data = $this->modal->getRatingDetails($rid, $company_id);
        echo json_encode($data);
        exit;
    }
}
?>