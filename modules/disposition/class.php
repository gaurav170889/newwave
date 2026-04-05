<?php
// Modulename
Class Disposition{
	
	function __construct() {
      $this->modal = loadmodal("disposition");
    }

	public function index(){
        $_SESSION['navurl'] = 'Disposition';
		include(INCLUDEPATH.'modules/common/groupheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');	
		
		// Access Control: Only Super Admin and Company Admin
		if(!isset($_SESSION['prole']) || ($_SESSION['prole'] != 'super_admin' && $_SESSION['prole'] != 'company_admin'))
		{
			echo "<script>window.location.href='".BASE_URL."?route=dashboard/index';</script>";
			exit;
		}

        $data = $this->modal->select("dialer_disposition_master");
        include(__DIR__ . "/view/index.php");
		
		include('modules/common/dispositionfooter.php');
	}

    public function getdisposition()
    {
        $company_id = $_SESSION['company_id'];
        $condition['company_id'] = $company_id;
        $data = $this->modal->select_filter("dialer_disposition_master", $condition);
        echo json_encode($data);
    }		

	
	public function updateprocess()
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'GET'){
				if(isset($_GET['checkid']) && $_GET['checkid'] > 0){

					$update_ch_id = $this->modal->htmlvalidation($_GET['checkid']);

					$condition0['id'] = $update_ch_id;
					$select_pre = $this->modal->select_assoc("dialer_disposition_master", $condition0);

					if($select_pre)
					{

						$json['status'] = 0;
						$json['id'] = $select_pre['id'];
						$json['code'] = $select_pre['code'];
						$json['name'] = $select_pre['label'];
						$json['name'] = $select_pre['label'];
						$json['action_type'] = $select_pre['action_type'];
                        $json['color_code'] = $select_pre['color_code'];
						$json['msg'] = "Success";

					}
					else
					{

						$json['status'] = 1;
						$json['msg'] = "Fail";

					}

				}
				else
				{
						$json['status'] = 2;					
						$json['msg'] = "Invalid Values Passed";
				}
			}
			else
			{
						$json['status'] = 3;
						$json['msg'] = "Invalid Method Found";
			}


		echo json_encode($json);
	}
	
	public function updateprocess2()
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
			
				if(isset($_POST['name']) && isset($_POST['dataval']) && isset($_POST['code']) && isset($_POST['action_type']))
				{
                    // Validation
					$name = $this->modal->htmlvalidation($_POST['name']);
					$code = $this->modal->htmlvalidation($_POST['code']);
					$action_type = $this->modal->htmlvalidation($_POST['action_type']);
					$action_type = $this->modal->htmlvalidation($_POST['action_type']);
                    $color_code = $this->modal->htmlvalidation($_POST['color_code'] ?? '#808080');
					$update_id = $this->modal->htmlvalidation($_POST['dataval']);

						if((!preg_match('/^[ ]*$/', $name)) && (!preg_match('/^[ ]*$/', $code)))
						{
			
							$condition['id'] = $update_id;

							$field_val['label'] = $name;
							$field_val['code'] = $code;
							$field_val['code'] = $code;
							$field_val['action_type'] = $action_type;
                            $field_val['color_code'] = $color_code;
							
							$update = $this->modal->update("dialer_disposition_master", $field_val, $condition);
			
							if($update)
							{
								$json['status'] = 101;
								$json['msg'] = "Data Successfully Updated";
							}
							else
							{
								$json['status'] = 102;
								$json['msg'] = "Data Not Updated";
							}

						}
						else
						{
							$json['status'] = 103;
							$json['msg'] = "Please Enter Name and Code";
						}

				}
				else
				{

				$json['status'] = 108;
				$json['msg'] = "Invalid Values Passed";

				}

			}
			else
			{

				$json['status'] = 109;
				$json['msg'] = "Invalid Method Found";

			}
			echo json_encode($json);

	}
	
	public function insprocess()
	{
		$json = array();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){

			if(isset($_POST['name']) && isset($_POST['code']) && isset($_POST['action_type']))
			{
				$name = $this->modal->htmlvalidation($_POST['name']);
				$code = $this->modal->htmlvalidation($_POST['code']);
				$action_type = $this->modal->htmlvalidation($_POST['action_type']);
                $color_code = $this->modal->htmlvalidation($_POST['color_code'] ?? '#808080');

				if((!preg_match('/^[ ]*$/', $name)) && (!preg_match('/^[ ]*$/', $code)))
				{
					$field_val['label'] = $name;
					$field_val['code'] = $code;
					$field_val['code'] = $code;
					$field_val['action_type'] = $action_type;
                    $field_val['color_code'] = $color_code;
					$field_val['company_id'] = $_SESSION['company_id']; // From Session

					$insert = $this->modal->insert("dialer_disposition_master", $field_val);

					if($insert){
						$json['status'] = 101;
						$json['msg'] = "Data Successfully Inserted";
					}
					else{
						$json['status'] = 102;
						$json['msg'] = "Data Not Inserted";
					}

				}
				else{
                    $json['status'] = 103;
                    $json['msg'] = "Please Enter Name and Code";
				}

			}
			else{

				$json['status'] = 108;
				$json['msg'] = "Invalid Values Passed";

			}

		}
		else{

			$json['status'] = 109;
			$json['msg'] = "Invalid Method Found";

		}


		echo json_encode($json);
	}
	
	public function deleteprocess()
	{
		$json = array();

		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{

			if(isset($_POST['delete_id']) && $_POST['delete_id'] > 0)
			{

				$deleteid = $this->modal->htmlvalidation($_POST['delete_id']);

				$condition['id'] = $deleteid;
				$delete_rec = $this->modal->delete("dialer_disposition_master",$condition);
		
				if($delete_rec)
				{
					$json['status'] = 0;
					$json['msg'] = "SuccessFully Deleted";
				}
				else
				{
					$json['status'] = 1;
					$json['msg'] = "Data Not Deleted";
				}

			}
			else
			{
				$json['status'] = 2;
				$json['msg'] = "Invalid Value Passed";
			}

		}
		else
		{
			$json['status'] = 3;
			$json['msg'] = "Invalid Method Found";
		}

		echo json_encode($json);
	}
		
}
?>
