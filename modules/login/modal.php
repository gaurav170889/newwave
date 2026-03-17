<?php

Class Login_modal{
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
	}
	
	
		public function getAllRecords($tableName, $fields='*', $cond='')
    {
       
        $sql =("SELECT $fields FROM $tableName WHERE ".$cond);        
        $result = mysqli_query($this->conn, $sql);
        $rows = mysqli_fetch_assoc($result);
        return $rows;
    }
	
}