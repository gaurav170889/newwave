<?php
// Modulename
Class Group{
	
	//private $pages;
	//public $select;
	//public $totalPages;
	function __construct() {
      $this->modal = loadmodal("agent");
    }
	public function index(){
		include(INCLUDEPATH.'modules/common/groupheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');	
		
		// Access Control
		if(!isset($_SESSION['prole']) || ($_SESSION['prole'] != 'super_admin' && $_SESSION['prole'] != 'company_admin'))
		{
			echo "<script>window.location.href='".BASE_URL."?route=dashboard/index';</script>";
			exit;
		}

		if($_SESSION['role']== "uagent")
		{
			//$qagent = $this->getagent();
			
			include(__DIR__ . "/view/notadmin.php");
		}
		else
		{
			$data = $this->modal->select("agentgroup");
			$counter = 1;
			include(__DIR__ . "/view/index.php");
		}
		include('modules/common/groupfooter.php');
		 
	}		
	//public function record($keywords,$pages)
	/*public function record()
	{
		
		include(__DIR__ . "/view/record.php");
	}*/
	
	public function updateprocess()
	
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'GET'){
				if(isset($_GET['checkid']) && $_GET['checkid'] > 0){

					$update_ch_id = $this->modal->htmlvalidation($_GET['checkid']);

					$condition0['id'] = $update_ch_id;
					$select_pre = $this->modal->select_assoc("agentgroup", $condition0);

					if($select_pre)
					{

						$json['status'] = 0;
						$json['id'] = $select_pre['id'];
						$json['groupname'] = $select_pre['grpname'];
						$json['msg'] = "Success";

					}
					else
					{

						$json['status'] = 1;
						$json['id'] = "NULL";
						$json['groupname'] = "NULL";
						$json['msg'] = "Fail";

					}

				}
				else
				{
						$json['status'] = 2;
						$json['id'] = "NULL";
						$json['grpname'] = "NULL";						
						$json['msg'] = "Invalid Values Passed";
				}
			}
			else
			{
						$json['status'] = 3;
						$json['id'] = "NULL";
						$json['grpname'] = "NULL";
						$json['msg'] = "Invalid Method Found";
			}


		echo json_encode($json);
	}
	
	public function updateprocess2()
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
			
				if(isset($_POST['groupname'])&& isset($_POST['dataval']))
				{

					$username = $this->modal->htmlvalidation($_POST['groupname']);				
					$update_id = $this->modal->htmlvalidation($_POST['dataval']);

						if((!preg_match('/^[ ]*$/', $username)))
						{
			
							$condition['id'] = $update_id;

							$field_val['grpname'] = $username;
							$update = $this->modal->update("agentgroup", $field_val, $condition);
			
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

							if(preg_match('/^[ ]*$/', $username))
							{
							$json['status'] = 103;
							$json['msg'] = "Please Enter Groupname";

							}							

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

			if(isset($_POST['groupname']))
			{

				
				$group = $this->modal->htmlvalidation($_POST['groupname']);

				if((!preg_match('/^[ ]*$/', $group)))
				{
					$field_val['grpname'] = $group;
					

					$insert = $this->modal->insert("agentgroup", $field_val);

					if($insert){
						$json['status'] = 101;
						$json['msg'] = "Data Successfully Inserted";
						//
					}
					else{
						$json['status'] = 102;
						$json['msg'] = "Data Not Inserted";
						//"Data Not Inserted"
					}

				}
				else{

					
					if(preg_match('/^[ ]*$/', $group)){

						$json['status'] = 103;
						$json['msg'] = "Please Enter group";

					}
					/*if($bod == NULL){

						$json['status'] = 107;
						$json['msg'] = "Please Enter BOD";

					}*/

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
				$delete_rec = $this->modal->delete("agentgroup",$condition);
		
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
	public function check()
	{
		include("view/check.php");
	}
		
}
?>