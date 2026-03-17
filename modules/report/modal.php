<?php
/* Modulename_modal */
Class Report_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
	}
	
	public function htmlvalidation($form_data){
		$form_data = trim( stripslashes( htmlspecialchars( $form_data ) ) );
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}
	
	public function fetch()
    {
        $data = [];
		$id=1;
        $query = "SELECT `r_cfdname` AS Total, COUNT(*) AS Times, SEC_TO_TIME(SUM(TIME_TO_SEC(`r_duration`))) AS Minutes,SEC_TO_TIME(SUM(TIME_TO_SEC(`r_totaltime`))) AS TotalMinute,TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(`r_totaltime`)) / COUNT(*) ), '%H:%i:%s') AS AvgConversation FROM custdata WHERE `r_externalno` IS NOT NULL AND `r_externalno` <> '' AND `r_startdt`= CURDATE() AND (`r_cfdname` IS NOT NULL AND `r_cfdname` <> '') GROUP BY `r_cfdname`";
		//print_r($query);
        if ($sql = $this->conn->query($query)) {
            while ($row = mysqli_fetch_assoc($sql)) {
				$row['r_id']= $id;
				$id++;
                $data[] = $row;
            }
        }

        return $data;
    }

    public function date_range($start_date, $end_date)
    {
        $data = [];
		$id=1;
        if (isset($start_date) && isset($end_date)) {
			
            $query = "SELECT `r_cfdname` AS Total, COUNT(*) AS Times, SEC_TO_TIME(SUM(TIME_TO_SEC(`r_duration`))) AS Minutes,SEC_TO_TIME(SUM(TIME_TO_SEC(`r_totaltime`))) AS TotalMinute,TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(`r_totaltime`)) / COUNT(*) ), '%H:%i:%s') AS AvgConversation FROM custdata WHERE `r_externalno` IS NOT NULL AND `r_externalno` <> '' AND `r_startdt` >= '$start_date' AND `r_startdt` <= '$end_date' AND (`r_cfdname` IS NOT NULL AND `r_cfdname` <> '')  GROUP BY `r_cfdname`";
            //print_r($query);
			
			if ($sql = $this->conn->query($query)) {
                while ($row = mysqli_fetch_assoc($sql)) {
					$row['cx_id']= $id;
				$id++;
                    $data[] = $row;
                }
            }
        }

        return $data;
    }
	
	public function date_rangetype($start_date, $end_date)
    {
        $data = [];
		$id=1;
        if (isset($start_date) && isset($end_date)) {
			
            $query = "SELECT `r_cfdname` AS Total, COUNT(*) AS Times, SEC_TO_TIME(SUM(TIME_TO_SEC(`r_duration`))) AS Minutes,SEC_TO_TIME(SUM(TIME_TO_SEC(`r_totaltime`))) AS TotalMinute,TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(`r_totaltime`)) / COUNT(*) ), '%H:%i:%s') AS AvgConversation FROM custdata WHERE `r_externalno` IS NOT NULL AND `r_externalno` <> '' AND `r_startdt` >= '$start_date' AND `r_startdt` <= '$end_date' AND (`r_cfdname` IS NOT NULL AND `r_cfdname` <> '')  GROUP BY `r_cfdname`";
           // print_r($query);
			if ($sql = $this->conn->query($query)) {
                while ($row = mysqli_fetch_assoc($sql)) {
					$row['r_id']= $id;
				$id++;
                    $data[] = $row;
                }
            }
        }

        return $data;
    }
	
	public function daterangequeue($start_date, $end_date,$agentgrp)
    {
		$grpid = $agentgrp;
        $data = [];
		$id=1;
        if (isset($start_date) && isset($end_date)) {
			$grpquery= "SELECT queueno FROM queue WHERE q_id = $grpid";
			$queryresut = $this->conn->query($grpquery);
			$grpdata = mysqli_fetch_array($queryresut,MYSQLI_NUM);
			$grpname=$grpdata[0];
			
			// Fetch raw data for aggregation in PHP
            $query = "SELECT a.agent_ext, r.ratings_json FROM agent a LEFT JOIN rate r ON a.agent_ext = r.agentno WHERE r.created_at >= '$start_date 00:00:00' AND r.created_at <= '$end_date 23:59:59' AND r.queue='$grpname'";
            
			// Initialize aggregation array
			$agent_stats = [];

			if ($sql = $this->conn->query($query)) {
                while ($row = mysqli_fetch_assoc($sql)) {
					$ext = $row['agent_ext'];
					if (!isset($agent_stats[$ext])) {
						$agent_stats[$ext] = [
							'total_point' => 0,
							'total_calls' => 0, // Calls with ratings
							'total_records' => 0 // All records associated
						];
					}
					
					$agent_stats[$ext]['total_records']++;

					if (!empty($row['ratings_json'])) {
						$ratings = json_decode($row['ratings_json'], true);
						if (is_array($ratings)) {
							$call_points = 0;
							foreach ($ratings as $val) {
								$call_points += intval($val);
							}
							$agent_stats[$ext]['total_point'] += $call_points;
							$agent_stats[$ext]['total_calls']++;
						}
					}
                }
            }
			
			// Format output data
			foreach ($agent_stats as $ext => $stats) {
				$total_point = $stats['total_point'];
				$total_calls = $stats['total_calls']; // Number of rated calls
				$total_records = $stats['total_records']; // Total rows found (if needed for 'total' column)
				
				$avg_point = ($total_calls > 0) ? round($total_point / $total_calls) : 0;
				
				// Calculate Percentages (Grade vs Not Grade? Or Rated vs Not Rated?)
				// Legacy query had: COUNT(r.point) as total_calls, COUNT(r.agentno) as total
				// percent_grade = total_calls * 100 / total
				
				$percent_grade = ($total_records > 0) ? number_format(($total_calls * 100 / $total_records), 2) . '%' : '0.00%';
				$percent_not_grade = ($total_records > 0) ? number_format(100 - ($total_calls * 100 / $total_records), 2) . '%' : '0.00%';

				$data[] = [
					'cx_id' => $id++,
					'queue' => $grpname,
					'agent_ext' => $ext,
					'total_point' => $total_point,
					'avg_point' => $avg_point,
					'total_calls' => $total_calls,
					'total' => $total_records,
					'percent_grade' => $percent_grade,
					'percent_not_grade' => $percent_not_grade
				];
			}
        }

        return $data;
    }
	
	public function dateagent($start_date, $end_date,$agentgrp,$agent)
    {
		$grpid = $this->getqueueno($agentgrp);
		$agentid = $agent;
        $data = [];
		$id=1;
        if (isset($start_date) && isset($end_date)) {
			$agentquery= "SELECT agent_ext FROM agent WHERE agent_id = $agentid";
			$agentresut = $this->conn->query($agentquery);
			$agentdata = mysqli_fetch_array($agentresut,MYSQLI_NUM);
			$agentext=$agentdata[0];
			
            // Fetch raw data
             $query = "SELECT a.agent_ext, r.ratings_json FROM agent a LEFT JOIN rate r ON a.agent_ext = r.agentno WHERE r.created_at >= '$start_date 00:00:00' AND r.created_at <= '$end_date 23:59:59' AND r.queue='$grpid' AND r.agentid='$agentid'";

            $agent_stats = [];

			if ($sql = $this->conn->query($query)) {
                while ($row = mysqli_fetch_assoc($sql)) {
					$ext = $row['agent_ext'];
					if (!isset($agent_stats[$ext])) {
						$agent_stats[$ext] = [
							'total_point' => 0,
							'total_calls' => 0,
							'total_records' => 0
						];
					}
					
					$agent_stats[$ext]['total_records']++;
					
					if (!empty($row['ratings_json'])) {
						$ratings = json_decode($row['ratings_json'], true);
						if (is_array($ratings)) {
							$call_points = 0;
							foreach ($ratings as $val) {
								$call_points += intval($val);
							}
							$agent_stats[$ext]['total_point'] += $call_points;
							$agent_stats[$ext]['total_calls']++;
						}
					}
                }
            }
			
			foreach ($agent_stats as $ext => $stats) {
				$total_point = $stats['total_point'];
				$total_calls = $stats['total_calls'];
				$total_records = $stats['total_records'];
				
				$avg_point = ($total_calls > 0) ? round($total_point / $total_calls) : 0;
				$percent_grade = ($total_records > 0) ? number_format(($total_calls * 100 / $total_records), 2) . '%' : '0.00%';
				$percent_not_grade = ($total_records > 0) ? number_format(100 - ($total_calls * 100 / $total_records), 2) . '%' : '0.00%';

				$data[] = [
					'cx_id' => $id++,
					'agentno' => $ext, // Matching original key 'agentno' set from $agentext, but in previous code it was actually from $row['agentno'] which was aliased to valid extension
					'total_point' => $total_point,
					'avg_point' => $avg_point,
					'total_calls' => $total_calls,
					'total' => $total_records,
					'percent_grade' => $percent_grade,
					'percent_not_grade' => $percent_not_grade
				];
			}
        }

        return $data;
    }
	
	public function fetchgroup()
	{
			$query = "SELECT * FROM `queue` WHERE 1";
			$sql = $this->conn->query($query);
			//$statement->execute();
			$data = mysqli_fetch_all($sql, MYSQLI_ASSOC);;
			//print_r($query);
			//print_r($query);
			foreach($data as $row)
			{
				$output[] = array(
					'id'  => $row["q_id"],
					'name'  => $row["queuename"],
					'qno'  => $row["queueno"]
				);
			}
			//print_r($data);
		return $output;
	}
	
	public function fetchagent($id)
	{
		/*$query = "SELECT queueno FROM queue WHERE q_id = $id ";
		$sql = $this->conn->query($query);
			//$statement->execute();
			$data = mysqli_fetch_array($sql);
			
		$queueno = $data[0];
		//print_r($queueno);*/
		$getagentid = "SELECT `qagents` FROM `queue` WHERE `q_id`= $id";
		//$aidquery = "SELECT DISTINCT(agentid) FROM rate WHERE `queue`='$id'";
		$aidsql= $this->conn->query($getagentid);
		$dataaid = mysqli_fetch_all($aidsql, MYSQLI_ASSOC);;
		//print_r($dataaid[0]['qagents']);
		$arrayagentid=json_decode($dataaid[0]['qagents'],TRUE);
		//print_r($arrayagentid);
		if(!empty($dataaid))
		{
			foreach($arrayagentid as $row)
			{
				$agentquery="SELECT agent_id,agent_ext,agent_name FROM agent WHERE agent_id = $row ";
				//print_r($agentquery);
				$agentsql= $this->conn->query($agentquery);
				$agentdata= mysqli_fetch_assoc($agentsql);;
				$agentid = $agentdata['agent_id'];
				$agentext = $agentdata['agent_ext'];
				$agentname = $agentdata['agent_name'];
				$output[] = array(
					'id'  => $agentid,
					'name'  => $agentname,
					'qno'  => $agentext
				);
			}
			//print_r($output);
		}
		else
		{
			$output = "";
		}
		return $output;
	}
	
	public function getqueueno($queueid)
	{
			$qid=$queueid;
			$grpquery= "SELECT queueno FROM queue WHERE q_id = $qid";
			$queryresut = $this->conn->query($grpquery);
			$grpdata = mysqli_fetch_array($queryresut,MYSQLI_NUM);
			$grpname=$grpdata[0];
			return $grpname;
	}

	
	
	public function getdata($table)
	{
		// Default to today's date range
		$start_date = date('Y-m-d'); 
		$end_date = date('Y-m-d');

		// Fetch raw data
		$query = "SELECT a.agent_ext, r.ratings_json FROM agent a LEFT JOIN $table r ON a.agent_ext = r.agentno WHERE r.created_at >= '$start_date 00:00:00' AND r.created_at <= '$end_date 23:59:59'";
		
        $agent_stats = [];

		if ($result = mysqli_query($this->conn, $query)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$ext = $row['agent_ext'];
				if (!isset($agent_stats[$ext])) {
					$agent_stats[$ext] = [
						'total_point' => 0,
						'total_calls' => 0,
						'total_records' => 0 
					];
				}
				
				$agent_stats[$ext]['total_records']++;

				if (!empty($row['ratings_json'])) {
					$ratings = json_decode($row['ratings_json'], true);
					if (is_array($ratings)) {
						$call_points = 0;
						foreach ($ratings as $val) {
							$call_points += intval($val);
						}
						$agent_stats[$ext]['total_point'] += $call_points;
						$agent_stats[$ext]['total_calls']++;
					}
				}
			}
		} else {
			return false;
		}

		$select_fetch = [];
		foreach ($agent_stats as $ext => $stats) {
			$total_point = $stats['total_point'];
			$total_calls = $stats['total_calls'];
			$total_records = $stats['total_records'];
			
			$avg_point = ($total_calls > 0) ? round($total_point / $total_calls) : 0;
			$percent_grade = ($total_records > 0) ? number_format(($total_calls * 100 / $total_records), 2) . '%' : '0.00%';
			$percent_not_grade = ($total_records > 0) ? number_format(100 - ($total_calls * 100 / $total_records), 2) . '%' : '0.00%';

			$select_fetch[] = [
				'agent_ext' => $ext, // Matching original
				'total_point' => $total_point,
				'avg_point' => $avg_point,
				'total_calls' => $total_calls,
				'total' => $total_records,
				'percent_grade' => $percent_grade,
				'percent_not_grade' => $percent_not_grade
			];
		}
		
		if (!empty($select_fetch)) {
			return $select_fetch;
		} else {
			return false; // Or empty array
		}
	}
	
}	
?>