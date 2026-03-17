<?php
// Modulename
Class Report{
	function __construct() {
      $this->modal = loadmodal("report");;
    }
	public function index(){
        $_SESSION['navurl'] = 'Report';
		include('modules/common/reportheader.php');
		include('modules/common/navbar_1.php');
		
		//$data = $this->modal->getdata("rate");
		//$counter = 1 ;
		include("view/newindex.php");
		//$this->record();
		include('modules/common/reportfooter.php');
		
		//include("view/record.php");
	}
	public function option()
	{
		if($_POST["type"] == "category_data")
		{
			 $rows = $this->modal->fetchgroup();
			
			echo json_encode($rows);
		}
		else
		{
			$id=$_POST['category_id'];
			$rows = $this->modal->fetchagent($id);
			
			echo json_encode($rows);
		}
	}
	
	public function records()
	{
		if (isset($_POST['start_date']) && isset($_POST['end_date'])) 
		{
			$start_date = $_POST['start_date'];
			$end_date = $_POST['end_date'];
			//$agentgrp=$_POST['sgroup'];
			//$agent=$_POST['sagent'];
			
			if(!empty($start_date) && !empty($end_date))
			{
		   // $rows = $model->date_range($start_date, $end_date,$agentgrp,$agent);
			 $rows = $this->modal->date_rangetype($start_date, $end_date);
			}
			/*elseif(empty($agentgrp) && empty($agent) && $type)
			{
				
		   // $rows = $model->date_range($start_date, $end_date,$agentgrp,$agent);
			 $rows = $this->modal->date_rangetype($start_date, $end_date,$type);
			}*/
			/*elseif(empty($agent))
			{
				$rows = $this->modal->daterangequeue($start_date, $end_date,$agentgrp);
			}
			else
			{
				$rows = $this->modal->dateagent($start_date, $end_date,$agentgrp,$agent);
			}*/	
		} 
		else 
		{
			$rows = $this->modal->fetch();
		}
		
		echo json_encode($rows);
	}
	
}
?>