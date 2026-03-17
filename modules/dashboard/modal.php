<?php
/* Modulename_modal */
Class Dashboard_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
	}
	
	public function htmlvalidation($form_data){
		$form_data = trim( stripslashes( htmlspecialchars( $form_data ) ) );
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}
	
	public function insert($tblname, $filed_data){

		$query_data = "";

		foreach ($filed_data as $q_key => $q_value) {
			$query_data = $query_data."$q_key='$q_value',";
		}
		$query_data = rtrim($query_data,",");

		$query = "INSERT INTO $tblname SET $query_data";
		$insert_fire = mysqli_query($this->conn, $query);
		if($insert_fire){
			return $query; 
			//$insert_fire;
		}
		else{
			return false;
		}

	}

	
	public function agentoutcall($tblname,$column)
	{
		$sql= "SELECT `$column`,COUNT(cx_type)'Total',COUNT(IF(cx_type='Outbound',1,NULL))'Outbound',COUNT(IF(cx_type='Notanswered',1,NULL))'Notanswered' FROM $tblname where (cx_type='Outbound' OR cx_type='Notanswered') && cx_stdate= curdate() GROUP BY cx_agent ORDER BY  COUNT(cx_type) DESC";
		//print_r($sql);
		$total_query = mysqli_query($this->conn, $sql);
		if(mysqli_num_rows($total_query) > 0){
			$total_fetch = mysqli_fetch_all($total_query, MYSQLI_ASSOC);;
			return $total_fetch;
		}
		elseif(mysqli_num_rows($total_query) == 0){
			$nodata ="";
			return $nodata;
		}
		else{
			return false;
		}
	}
	
	public function calltotal($tblname,$countattr1,$countattr2){

		$search = "SELECT COUNT(cx_type) FROM $tblname WHERE (cx_type='$countattr1' OR cx_type='$countattr2') && cx_stdate = curdate()";
		$search_query = mysqli_query($this->conn, $search);
		if(mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_array($search_query);
			return $search_fetch;
		}
		elseif(mysqli_num_rows($search_query) == 0){
			//$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			return $search_query;
		}
		else{
			return false;
		}

	}
	public function pointone($tablename,$point)
	{
		$sql = "SELECT COUNT(point) FROM $tablename WHERE point = '$point' && start_date = curdate()";
		$search_query = mysqli_query($this->conn, $sql);
		if(mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_array($search_query);
			return $search_fetch;
		}
		elseif(mysqli_num_rows($search_query) == 0){
			//$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			return $search_query;
		}
		else{
			return false;
		}

	}
	public function pointthree($tablename,$point)
	{
		$sql = "SELECT COUNT(point) FROM $tablename WHERE point = '$point' && start_date = curdate()";
		$search_query = mysqli_query($this->conn, $sql);
		if(mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_array($search_query);
			return $search_fetch;
		}
		elseif(mysqli_num_rows($search_query) == 0){
			//$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			return $search_query;
		}
		else{
			return false;
		}

	}
	public function pointfive($tablename,$point)
	{
		$sql = "SELECT COUNT(point) FROM $tablename WHERE point = '$point' && start_date = curdate()";
		$search_query = mysqli_query($this->conn, $sql);
		if(mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_array($search_query);
			return $search_fetch;
		}
		elseif(mysqli_num_rows($search_query) == 0){
			//$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			return $search_query;
		}
		else{
			return false;
		}

	}
	public function totalcallpoint($tablename)
	{
		$sql= "SELECT COUNT(point) FROM $tablename WHERE start_date = curdate()";
		$search_query = mysqli_query($this->conn, $sql);
		if(mysqli_num_rows($search_query) > 0){
			$search_fetch = mysqli_fetch_array($search_query);
			return $search_fetch;
		}
		elseif(mysqli_num_rows($search_query) == 0){
			//$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			return $search_query;
		}
		else{
			return false;
		}

	}
	public function averagescore($tablename)
	{
		$data = [];
		$id=1;
        $query = "SELECT a.agent_ext,sum(CAST(r.point AS UNSIGNED))'total_point',ROUND(AVG(CAST(r.point AS UNSIGNED)))'avg_point',COUNT(r.point)'total_calls',COUNT(r.agentno)'total',CONCAT(FORMAT(COUNT(r.point)*100/COUNT(r.agentno),2), '%') AS `percent_grade`,CONCAT(FORMAT(100-COUNT(r.point)*100/COUNT(r.agentno),2), '%') AS `percent_not_grade` FROM agent a LEFT JOIN $tablename r ON a.agent_ext = r.agentno AND r.start_date WHERE r.start_date>= CURDATE() AND r.start_date<= CURDATE() GROUP BY a.agent_ext ORDER BY ROUND(AVG(CAST(r.point AS UNSIGNED))) DESC";
		//print_r($query);
        if ($sql = $this->conn->query($query)) {
            while ($row = mysqli_fetch_assoc($sql)) {
				$row['cx_id']= $id;
				$id++;
                $data[] = $row;
            }
        }

        return $data;
	}
	
	
}	
?>