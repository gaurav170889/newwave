<?php
// Modulename
Class Dashboard{
	function __construct() {
      $this->name = loadmodal("dashboard");;
    }
	public function index(){
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');
		
		$date=date("m/d/Y");
		$point_1=$this->name->pointone("rate","1");
		//echo $point_1[0];
		$point_3=$this->name->pointthree("rate","3");
		//echo $point_3[0];
		$point_5=$this->name->pointfive("rate","5");
		$point_all=$this->name->totalcallpoint("rate");
		
		//echo $point_1[0];
		//echo $point_3[0];
		//echo $point_5[0];
		//echo $po[0];
		$agentout = $this->name->averagescore("rate");
		//print_r($agentout);
		$counter = 1;
		include("view/index.php");
		//$agentout = $this->name->agentoutcall("calldetail",'cx_agent');
		//echo $inbound[0];
		//print_r($agentout);
		
	}
	
	public function goga(){
		echo "This is goga";
	}
}
?>