<?php
require_once('webapi.php');
require_once('database.php');
date_default_timezone_set("Europe/Amsterdam");

$tests = file_get_contents('php://input');
$rawDataDirectory = __DIR__ . '/rawdata/';
$sqlErrorDirectory = __DIR__ . '/sqlerror/';

if (!is_dir($rawDataDirectory)) {
    mkdir($rawDataDirectory, 0775, true);
}

if (!is_dir($sqlErrorDirectory)) {
    mkdir($sqlErrorDirectory, 0775, true);
}

$get = file_put_contents($rawDataDirectory . date('Y-m-d') . '_test12.txt', $tests . PHP_EOL, FILE_APPEND);
$gdata=json_decode($tests,true);
$jdata=explode(",",$gdata['data']);
//print_r($jdata);
$callid=$jdata[1];
$starttime=$jdata[3];
$endtime=$jdata[5];
$chain=$jdata[17];
$caller=$jdata[7];
$reasonterminate=$jdata[6];
$sdate = convertDateTime($jdata[3]);
$edate = convertDateTime($jdata[5]);
$finalstdate = date("Y-m-d", strtotime($sdate));
$finalsttime = date("H:i:s", strtotime($sdate));
$finalendtime = date("H:i:s", strtotime($edate));
$newchain=str_replace(['Chain: ','Ext.'],"",$chain);
$chainarray= explode(';',$newchain);
$ttime = gettotaltime($finalsttime,$finalendtime);
$calltype=$jdata[15];
$gets = file_put_contents($rawDataDirectory . date('Y-m-d') . '_test123.txt', $callid . " " . $starttime . " " . $endtime . " " . $calltype . " " . $newchain . PHP_EOL, FILE_APPEND);

$countarray=count($chainarray);

$checktrans = strlen($chainarray[$countarray-2]);

if($calltype=='Line')
{
        if($checktrans== 3 AND  substr($chainarray[$countarray-2], 0, 1) =='9' )
        {
            $agent=$chainarray[$countarray-3];
            $cfdno= $chainarray[$countarray-2];
        	$cfdname=getcustname($cfdno);
        	$externalno="";
            $duration='00:00:00';
            $insertdata= inserdata($callid,$agent,$caller,$externalno,$finalstdate,$finalsttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime);
            if($insertdata)
            {
                echo "DataInserted";
                http_response_code(200);
            }
            else
            {
                echo "DataNotInserted";
                http_response_code(200);
            }
        }
        elseif($checktrans > 3 AND $countarray > 5 AND $reasonterminate !='Failed')
        {
            $agent=$chainarray[$countarray-4];
            $externalno=$chainarray[$countarray-2];
            $getcontdur = getconnecttime($caller,$externalno);
        				
        	file_put_contents("connect.txt",$getcontdur.PHP_EOL,FILE_APPEND);
        	$datadcode = (array) json_decode($getcontdur);
        	$redata = (array)$datadcode["CallLogRows"][0];
        	$duration = date("H:i:s", strtotime($redata['Duration']));
        	$cfdno= $chainarray[$countarray-3];
        	$cfdname=getcustname($cfdno);
        	if($cfdname=="No")
        	{
        	    $cfdname="";
        	}
            $insertdata= inserdata($callid,$agent,$caller,$externalno,$finalstdate,$finalsttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime);
            if($insertdata)
            {
                echo "DataInserted";
                http_response_code(200);
            }
            else
            {
                echo "DataNotInserted";
                http_response_code(200);
            }
            
        }
        elseif($checktrans > 3 AND $countarray > 5 AND $reasonterminate =='Failed')
        {
            $agent=$chainarray[$countarray-4];
            $externalno=$chainarray[$countarray-2];
            //$getcontdur = getconnecttime($caller,$externalno);
        				
        	//file_put_contents("connect.txt",$getcontdur.PHP_EOL,FILE_APPEND);
        	//$datadcode = (array) json_decode($getcontdur);
        	//$redata = (array)$datadcode["CallLogRows"][0];
        	$duration = '00:00:00';
        	$cfdno= $chainarray[$countarray-3];
        	$cfdname=getcustname($cfdno);
        	if($cfdname=="No")
        	{
        	    $cfdname="";
        	}
            $insertdata= inserdata($callid,$agent,$caller,$externalno,$finalstdate,$finalsttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime);
            if($insertdata)
            {
                echo "DataInserted";
                http_response_code(200);
            }
            else
            {
                echo "DataNotInserted";
                http_response_code(200);
            }
            
        }
        elseif($checktrans== 3 AND  substr($chainarray[$countarray-2], 0, 1) =='1' )
        {
            $agent=$chainarray[$countarray-2];
            $cfdno= "";
        	$cfdname="";
        	$externalno="";
            $duration='00:00:00';
            $insertdata= inserdata($callid,$agent,$caller,$externalno,$finalstdate,$finalsttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime);
            if($insertdata)
            {
                echo "DataInserted";
                http_response_code(200);
            }
            else
            {
                echo "DataNotInserted";
                http_response_code(200);
            }
        }
        elseif($checktrans== 3 AND  substr($chainarray[$countarray-2], 0, 1) =='8' )
        {
            $agent="";
            $cfdno= "";
        	$cfdname="";
        	$externalno="";
            $duration='00:00:00';
            $insertdata= inserdata($callid,$agent,$caller,$externalno,$finalstdate,$finalsttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime);
            if($insertdata)
            {
                echo "DataInserted";
                http_response_code(200);
            }
            else
            {
                echo "DataNotInserted";
                http_response_code(200);
            }
        }
}
elseif($checktrans > 3)
{
    
            $agent=$jdata[9];
            $cfdno= "";
        	$cfdname="";
        	$externalno="";
            $duration='00:00:00';
            $caller=$chainarray[$countarray-2];
            $insertdata= inserdata($callid,$agent,$caller,$externalno,$finalstdate,$finalsttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime);
            if($insertdata)
            {
                echo "DataInserted";
                http_response_code(200);
            }
            else
            {
                echo "DataNotInserted";
                http_response_code(200);
            }
}
http_response_code(200);

//Chain: +449827180054;Ext.703;Ext.801;Ext.201;Ext.921;0207544444;\r\n"}


function convertDateTime($date, $format = 'Y-m-d H:i:s')
{
    $tz1 = 'UTC';
    $tz2 = 'Europe/Amsterdam'; // UTC +7

    $d = new DateTime($date, new DateTimeZone($tz1));
    $d->setTimeZone(new DateTimeZone($tz2));

    return $d->format($format);
}

function getcustname($cfdno)
{
	global $conn;
	$sql=mysqli_query($conn,"SELECT `c_name` FROM `customer` WHERE `c_cfdno` ='$cfdno'");
    //$queuearray = Array();
	$result = mysqli_fetch_array($sql);
	if($result[0]!=" " AND $result[0]!=NULL)
	{
	    return $result[0];
	}
	else
	{
	    $data="No";
	    return $data;
	}
}

function getconnecttime($callerno,$externalno)
{
	$Auth3CX        =    Get3CXCookie();
 
//If we got something back, Auth succeeded, so move on else login failed!
	if( strlen( $Auth3CX ) != 0 )
	{
		$DisplayCallLogAPI        =    GetAPIData( "CallLog?TimeZoneName=Asia/Bangkok&callState=All&dateRangeType=Today&fromFilter=$callerno&fromFilterType=MatchNumber&numberOfRows=1&searchFilter=&startRow=0&toFilter=$externalno&toFilterType=MatchNumber", $Auth3CX );
    
    //Output to screen pretty
		return json_encode( json_decode( $DisplayCallLogAPI ), JSON_PRETTY_PRINT );
	}
	else
	{
	  echo die( "Authentication failed" );
	}
}

function inserdata($rid,$agent,$caller,$externalno,$startdate,$starttime,$duration,$cfdno,$cfdname,$ttime,$finalendtime)
{
    global $conn;
    $sqlErrorDirectory = __DIR__ . '/sqlerror/';
    
    if (!is_dir($sqlErrorDirectory)) {
        mkdir($sqlErrorDirectory, 0775, true);
    }

    $sql="INSERT INTO `custdata`(`r_callid`, `r_ext`, `r_caller`, `r_externalno`, `r_cfdno`, `r_cfdname`,`r_totaltime`, `r_duration`, `r_startdt`, `r_starttime`,`r_endtime`) VALUES ('$rid',NULLIF('$agent',''),'$caller',NULLIF('$externalno',''),NULLIF('$cfdno',''),NULLIF('$cfdname',''),'$ttime','$duration','$startdate','$starttime','$finalendtime')";
    
    if(mysqli_query($conn, $sql))
					{
		
						return true;
					}
				else 
					{
						file_put_contents($sqlErrorDirectory . date('Y-m-d') . "_err_sql_ins.txt", "Error: " . $sql . " " . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
						return false;
						
					}
    
}

function gettotaltime($stime,$etime)
{
    $startTime = DateTime::createFromFormat('H:i:s', $stime);
$endTime = DateTime::createFromFormat('H:i:s', $etime);

// Calculate the time difference
$timeDifference = $startTime->diff($endTime);

// Format the time difference
$formattedDifference = $timeDifference->format('%H:%I:%S');

// Echo the formatted time difference
return $formattedDifference;
}
?>