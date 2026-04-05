<?php
// Modulename
Class Users{
	function __construct() {
      $this->modal = loadmodal("users");;
    }
	public function index(){
		include(INCLUDEPATH.'modules/common/header.php');
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
		$data = $this->modal->select("users");
		//$group = $this->modal->groupassoc("agentgroup");
		//print_r($group);
		$counter = 1;
		include(__DIR__ . "/view/index.php");
		}
		include('modules/common/footer_1.php');
		
		//include("view/record.php");
	}
	
	public function record()
	{
		$counter = 1;

		if(isset($_POST['keyword']) && !empty(trim($_POST['keyword']))){

		$keyword = $this->modal->htmlvalidation($_POST['keyword']);

		$match_field['agent_ext'] = $keyword;
		$match_field['agent_name'] = $keyword;
		$select = $this->modal->search("users", $match_field, "OR");

		}
		else
		{

		$select = $this->modal->select('users');
		

		}
		include(__DIR__ . "/view/record.php");
	}
	
	public function getagent()
	{
		$json = array();
		if(isset($_POST['depart']))
		{
			//echo "goga";
			$select_grp = $this->modal->agentassoc("agent");
			//print_r($select_grp);
			if($select_grp)
			{
				$json =  json_encode($select_grp);
				echo $json;
			}
			else
			{
				$json['status'] = 102;
				$json['msg'] = "Data Not Inserted";
				//"Data Not Inserted"
			}
		}
		else
		{
			$json['status'] = 103;
				$json['msg'] = "Error in Request";
		}
	}
	
	public function updateprocess()
	
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'GET'){
				if(isset($_GET['checkid']) && $_GET['checkid'] > 0){

					$update_ch_id = $this->modal->htmlvalidation($_GET['checkid']);

					$condition0['id'] = $update_ch_id;
					$select_pre = $this->modal->select_assoc("users", $condition0);

					if($select_pre)
					{

						$json['status'] = 0;
						$json['name'] = $select_pre['email'];
						$json['urole'] = $select_pre['role'];						
						$json['uagent'] = $select_pre['agentid'];						
						$json['msg'] = "Success";

					}
					else
					{

						$json['status'] = 1;
						$json['name'] = "NULL";
						$json['password'] = "NULL";
						$json['role'] = "NULL";
						$json['group'] = "NULL";
						$json['msg'] = "Fail";

					}

				}
				else
				{
						$json['status'] = 2;
						$json['name'] = "NULL";
						$json['password'] = "NULL";						
						$json['msg'] = "Invalid Values Passed";
				}
			}
			else
			{
						$json['status'] = 3;
						$json['name'] = "NULL";
						$json['password'] = "NULL";	
						$json['msg'] = "Invalid Method Found";
			}


		echo json_encode($json);
	}
	
	public function updateprocess2()
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
			
				if(isset($_POST['loginname']) && isset($_POST['loginpass']))
				{

					$username = $this->modal->htmlvalidation($_POST['loginname']);
					$pass = $this->modal->htmlvalidation($_POST['loginpass']);
					$update_id = $this->modal->htmlvalidation($_POST['dataval']);					
					
						if((!preg_match('/^[ ]*$/', $username)) && (!preg_match('/^[ ]*$/', $pass)) )
						{
			
							$condition['id'] = $update_id;

							$field_val['email'] = $username;
							$field_val['password'] = password_hash($pass,PASSWORD_DEFAULT);
							
							$update = $this->modal->update("users", $field_val, $condition);
			
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
							$json['msg'] = "Please Enter Username";

							}
							if(preg_match('/^[ ]*$/', $pass))
							{

							$json['status'] = 104;
							$json['msg'] = "Please Enter Password";

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

			if(isset($_POST['loginname']) && isset($_POST['loginpass']) && isset($_POST['role']) ){

				$username = $this->modal->htmlvalidation($_POST['loginname']);
				$pass = $this->modal->htmlvalidation($_POST['loginpass']);
				$urole=$this->modal->htmlvalidation($_POST['role']);

				if((!preg_match('/^[ ]*$/', $username)) && (!preg_match('/^[ ]*$/', $pass)) && (!preg_match('/^[ ]*$/', $urole)) )
				{
					if($urole=="admin")
					{
						$field_val['email'] = $username;
						$field_val['password'] = password_hash($pass,PASSWORD_DEFAULT);
						$field_val['role'] = $urole;

						$insert = $this->modal->insert("users", $field_val);

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
					else
					{
						$agent = $_POST['agent'];
						$field_val['email'] = $username;
						$field_val['password'] = password_hash($pass,PASSWORD_DEFAULT);
						$field_val['role'] = $urole;
						$field_val['agentid']=$agent;
						$insert = $this->modal->insert("users", $field_val);

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

				}
				else{

					if(preg_match('/^[ ]*$/', $username)){

						$json['status'] = 103;
						$json['msg'] = "Please Enter usersname";

					}
					if(preg_match('/^[ ]*$/', $pass)){

						$json['status'] = 104;
						$json['msg'] = "Please Enter password";

					}
					

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
				$delete_rec = $this->modal->delete("users",$condition);
		
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