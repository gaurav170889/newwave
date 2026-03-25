<?php
// Modulename
Class Agent{
	
	//private $pages;
	//public $select;
	//public $totalPages;
	function __construct() {
      $this->modal = loadmodal("agent");
    }
	public function index(){
		include(INCLUDEPATH.'modules/common/agentheader.php');
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
			
			include("view/notadmin.php");
		}
		else
		{
		//$page = (isset($_GET['page']) && is_numeric($_GET['page']) ) ? $_GET['page'] : 1;
		$data = $this->modal->select("agent");
		$group = $this->modal->groupassoc("agentgroup");
		//print_r($group);
		$counter = 1;
		include("view/index.php");
		//$this->record();
		}
		include('modules/common/agentfooter.php');
		 
	}		
	//public function record($keywords,$pages)
	/*public function record()
	{
		
		include("view/record.php");
	}*/
	
	public function updateprocess()
	
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'GET'){
				if(isset($_GET['checkid']) && $_GET['checkid'] > 0){

					$update_ch_id = $this->modal->htmlvalidation($_GET['checkid']);

					$condition0['agent_id'] = $update_ch_id;
					$select_pre = $this->modal->select_assoc("agent", $condition0);

					if($select_pre)
					{
						$selectid =$this->modal->getgrpid("agentgroup",$select_pre['agent_group']);
						$json['status'] = 0;
						$json['ext'] = $select_pre['agent_ext'];
						$json['name'] = $select_pre['agent_name'];								
						$json['group'] = $selectid;
						$json['msg'] = "Success";

					}
					else
					{

						$json['status'] = 1;
						$json['ext'] = "NULL";
						$json['name'] = "NULL";
						$json['role'] = "NULL";
						$json['group'] = "NULL";
						$json['msg'] = "Fail";

					}

				}
				else
				{
						$json['status'] = 2;
						$json['ext'] = "NULL";
						$json['name'] = "NULL";
						$json['role'] = "NULL";
						$json['group'] = "NULL";
						$json['msg'] = "Invalid Values Passed";
				}
			}
			else
			{
						$json['status'] = 3;
						$json['ext'] = "NULL";
						$json['name'] = "NULL";
						$json['role'] = "NULL";
						$json['group'] = "NULL";
						$json['msg'] = "Invalid Method Found";
			}


		echo json_encode($json);
	}
	
	public function updateprocess2()
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
			
				if(isset($_POST['username']) && isset($_POST['ext']) && isset($_POST['group'])&& isset($_POST['dataval']))
				{

					$username = $this->modal->htmlvalidation($_POST['username']);
					$ext = $this->modal->htmlvalidation($_POST['ext']);
					//$role = $this->modal->htmlvalidation($_POST['role']);
					$group = $this->modal->htmlvalidation($_POST['group']);
					$update_id = $this->modal->htmlvalidation($_POST['dataval']);

						if((!preg_match('/^[ ]*$/', $username)) && (!preg_match('/^[ ]*$/', $ext)) &&($group != NULL))
						{
			
							$condition['agent_id'] = $update_id;

							$field_val['agent_name'] = $username;
							$field_val['agent_ext'] = $ext;	
							//$field_val['agent_role'] = $role;
							$field_val['agent_grpid'] = $group;	
							$field_val['agent_group']= $this->modal->getgrpname("agentgroup",$group);
							$update = $this->modal->update("agent", $field_val, $condition);
			
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

							if(preg_match('/^[ ]*$/', $ext))
							{
							$json['status'] = 103;
							$json['msg'] = "Please Enter Extension";

							}
							if(preg_match('/^[ ]*$/', $username))
							{

							$json['status'] = 104;
							$json['msg'] = "Please Enter username";

							}
							if(preg_match('/^[ ]*$/', $role))
							{

								$json['status'] = 105;
								$json['msg'] = "Please Select role";

							}
			
							if($group == NULL)
							{

								$json['status'] = 107;
								$json['msg'] = "Please Enter group";

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

			if(isset($_POST['username']) && isset($_POST['ext']) && isset($_POST['group']))
			{

				$username = $this->modal->htmlvalidation($_POST['username']);
				$ext = $this->modal->htmlvalidation($_POST['ext']);
				$group = $this->modal->htmlvalidation($_POST['group']);

				if((!preg_match('/^[ ]*$/', $username)) && (!preg_match('/^[ ]*$/', $ext)) && (!preg_match('/^[ ]*$/', $group)))
				{
					$agentgrp = $this->modal->getgrpname("agentgroup",$group);

					$field_val['agent_name'] = $username;
					$field_val['agent_ext'] = $ext;
					$field_val['agent_group'] = $agentgrp;
					
					$insert = $this->modal->insert("agent", $field_val);

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

					if(preg_match('/^[ ]*$/', $username)){

						$json['status'] = 103;
						$json['msg'] = "Please Enter Agent name";

					}
					if(preg_match('/^[ ]*$/', $ext)){

						$json['status'] = 104;
						$json['msg'] = "Please Enter extension";

					}
					if(preg_match('/^[ ]*$/', $role)){

						$json['status'] = 105;
						$json['msg'] = "Please Select role";

					}
					if(preg_match('/^[ ]*$/', $group)){

						$json['status'] = 106;
						$json['msg'] = "Please Choice group";

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
	
	public function getgroup()
	{
		$json = array();
		if(isset($_POST['depart']))
		{
			//echo "goga";
			$select_grp = $this->modal->groupassoc("agentgroup");
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
	public function deleteprocess()
	{
		$json = array();

		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{

			if(isset($_POST['delete_id']) && $_POST['delete_id'] > 0)
			{

				$deleteid = $this->modal->htmlvalidation($_POST['delete_id']);

				$condition['agent_id'] = $deleteid;
				$delete_rec = $this->modal->delete("agent",$condition);
		
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

    public function sync3cx() {
        if(!function_exists('RefreshTokenIfNeeded')) {
             include_once INCLUDEPATH . 'includes/functions.php';
        }

        $json = ['status' => 0, 'msg' => ''];
        
        if (!isset($_SESSION['company_id'])) {
             $json['msg'] = "Session expired or Company ID missing";
             echo json_encode($json); return;
        }
        $company_id = $_SESSION['company_id'];

        // 1. Get Token
        $token = RefreshTokenIfNeeded($company_id);
        if (!$token) {
            $json['msg'] = "Could not retrieve valid 3CX Token. Please check Settings.";
            echo json_encode($json); return;
        }

        // 2. Get PBX URL
        $conn = ConnectDB();
        $set_sql = "SELECT pbxurl FROM pbxdetail WHERE company_id = $company_id";
        $set_res = mysqli_query($conn, $set_sql);
        $settings = mysqli_fetch_assoc($set_res);
        $pbxurl = $settings['pbxurl'] ?? '';

        if (!$pbxurl) {
             $json['msg'] = "PBX URL missing.";
             echo json_encode($json); return;
        }

        // Ensure HTTPS
        if (!preg_match("~^https?://~i", $pbxurl)) {
            $pbxurl = "https://" . $pbxurl;
        }

        // 3. Fetch Users from 3CX
        $url = rtrim($pbxurl, '/') . "/xapi/v1/Users";
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
          ),
          CURLOPT_SSL_VERIFYPEER => false
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $json['msg'] = "cURL Error: " . $err;
            echo json_encode($json); return;
        }

        $data = json_decode($response, true);
        if (!isset($data['value']) || !is_array($data['value'])) {
             $json['msg'] = "Invalid response from 3CX: " . substr($response, 0, 100);
             echo json_encode($json); return;
        }

        // 4. Update Database
        $count_new = 0;
        $count_up = 0;
        $count_skipped = 0;

        foreach ($data['value'] as $u) {
            $tid = intval($u['Id'] ?? 0);
            $ext = mysqli_real_escape_string($conn, $u['Number'] ?? '');
            $name = mysqli_real_escape_string($conn, $u['DisplayName'] ?? '');
            $email = mysqli_real_escape_string($conn, $u['EmailAddress'] ?? '');
            
            if (!$tid || !$ext) continue; // Skip invalid
            
            // Check existence by (company_id + agent_ext) OR (company_id + 3cx_id)
            // But User wants Unique on Ext. So let's match by Ext primarily for linking? 
            // Actually, 3CX ID is more stable than Ext if Ext changes? 
            // User requirement: "number not exist company_id to agent noumber is unique"
            // So we match by Extension (Number).
            
            $check = "SELECT agent_id, is_archived FROM agent WHERE company_id = $company_id AND agent_ext = '$ext'";
            $res = mysqli_query($conn, $check);
            
            if (mysqli_num_rows($res) > 0) {
                // Found
                $row = mysqli_fetch_assoc($res);
                if ($row['is_archived'] == 1) {
                    $count_skipped++;
                    continue; // Skip archived
                }
                
                // Update
                $aid = $row['agent_id'];
                // Update 3cx_id just in case it was linked by ext but didn't have ID yet
                $up_sql = "UPDATE agent SET agent_name='$name', email='$email', `3cx_id`=$tid WHERE agent_id=$aid";
                mysqli_query($conn, $up_sql);
                $count_up++;
            } else {
                // Insert
                $ins_sql = "INSERT INTO agent (company_id, `3cx_id`, agent_name, agent_ext, email, agent_group, is_archived) 
                            VALUES ($company_id, $tid, '$name', '$ext', '$email', '3CX Users', 0)";
                mysqli_query($conn, $ins_sql);
                $count_new++;
            }
        }

        $json['status'] = 101;
        $json['msg'] = "Synced: $count_new added, $count_up updated, $count_skipped archived/skipped.";
        echo json_encode($json);
    }
    
    public function bulk_archive() {
        $json = ['status' => 0, 'msg' => ''];
        
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ids'])) {
            $ids = $_POST['ids']; // Array of IDs
            if (is_array($ids) && count($ids) > 0) {
                $conn = ConnectDB();
                // Sanitize IDs
                $safe_ids = array_map('intval', $ids);
                $ids_str = implode(',', $safe_ids);
                $company_id = $_SESSION['company_id'] ?? 0;
                
                // Update
                $sql = "UPDATE agent SET is_archived = 1 WHERE agent_id IN ($ids_str) AND company_id = $company_id";
                if(mysqli_query($conn, $sql)) {
                     $json['status'] = 1;
                     $json['msg'] = "Archived " . mysqli_affected_rows($conn) . " agents.";
                } else {
                     $json['msg'] = "Database error: " . mysqli_error($conn);
                }
            } else {
                 $json['msg'] = "No agents selected.";
            }
        }
        echo json_encode($json);
    }

	public function check()
	{
		include("view/check.php");
	}
		
}
?>