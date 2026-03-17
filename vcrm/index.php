<?php
$postdata = file_get_contents("php://input");
file_put_contents($_SERVER['DOCUMENT_ROOT']."/cyril/crm/contact/".date('Y-m-d')."_contact.txt","Data is : " .$postdata.PHP_EOL,FILE_APPEND);
$headers = $_SERVER['HTTP_AUTHORIZATION'];
if($headers=="Bearer 715f1d46-7a17-4533-9934-972828709f4d")
{
  
    http_response_code(500);
    //echo $insdata['lastid'];
    
}
else
{
    file_put_contents($_SERVER['DOCUMENT_ROOT']."/cyril/crm/contact/".date('Y-m-d')."_contact.txt","Data is : " .$headers.PHP_EOL,FILE_APPEND);
    
    $number=$_GET['number'];
    $direction=$_GET['direction'];
    if(!empty($number) AND $number!=" ")
    {
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/vantrp/vcrm/contact/".date('Y-m-d')."_indexnum.txt","Data is : " .$number.PHP_EOL,FILE_APPEND);
        $data=array("contact"=>array("id"=>rand(100,999),"firstname"=>"Mr".$number,"lastname"=>"last".$number,"mobilephone"=>$number));
        //$data=array();
        
        echo json_encode($data);
        http_response_code(200);
        
    }
}
/*date_default_timezone_set("Asia/Kolkata");
$key=$_GET['apikey'];

if($key=='715f1d46-7a17-4533-9934-972828709f4d' AND $_GET['number']!=''AND $_GET['number']!=NULL)
{
   
    http_response_code(500);
    //echo $getdata;
    
}
else
{
    //file_put_contents($_SERVER['DOCUMENT_ROOT']."/ehelp/ausincoming/".date('Y-m-d')."_authfail.txt","Number is : " . $_GET['number']." ".date('H:i:s').PHP_EOL,FILE_APPEND);
    http_response_code(404);
    //echo "800";
}*/

?>