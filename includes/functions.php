<?php
function loadmodal($modulename){
				$class = ucwords($modulename."_modal");
				if (class_exists($class)) {
				   $classinstance = new $class();
				} else {
				    if(file_exists(MODULEPATH.strtolower($modulename)."/modal.php")){
							include_once(MODULEPATH.strtolower($modulename)."/modal.php");
							$classinstance = new $class();
				     }
				}
				return $classinstance;
 }
 
 function ConnectDB()
{	
	
	$host = 'localhost';
         $user = 'root';
         $pass = 'root';
         $db = 'dialerwave';
         $connection = mysqli_connect($host,$user,$pass,$db); 
		 
		// Check connection
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
			exit();
			}
         return $connection;
     }

 function Generate3CXToken($pbxurl, $auth_method, $creds) {
    // Ensure HTTPS and handle custom ports
    $base_url = trim($pbxurl);
    if ($base_url && !preg_match("~^https?://~i", $base_url)) {
        $base_url = "https://" . $base_url;
    }
    // Remove trailing slash
    $base_url = rtrim($base_url, '/');

    if (!$base_url) return null;

    if ($auth_method == 'oauth') {
        $client_id = $creds['client_id'] ?? '';
        $client_secret = $creds['client_secret'] ?? '';
        
        if ($client_id && $client_secret) {
            $tokenUrl = $base_url . "/connect/token";
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $tokenUrl,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => http_build_query([
                  'client_id' => $client_id,
                  'client_secret' => $client_secret,
                  'grant_type' => 'client_credentials'
              ]),
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
              ),
              CURLOPT_SSL_VERIFYPEER => false,
              CURLOPT_SSL_VERIFYHOST => false
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if (!$err) {
                $json = json_decode($response, true);
                if (isset($json['access_token'])) {
                    return $json['access_token'];
                }
            }
        }
    } elseif ($auth_method == 'login') {
        $username = $creds['username'] ?? '';
        $password = $creds['password'] ?? '';
        
        if ($username && $password) {
            $tokenUrl = $base_url . "/webclient/api/Login/GetAccessToken";
            
            $loginData = json_encode([
                'username' => $username,
                'password' => $password
            ]);
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $tokenUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $loginData,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'User-Agent: PHP'
                ),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if (!$err) {
                $json = json_decode($response, true);
                if (isset($json['Status']) && $json['Status'] == 'AuthSuccess' && isset($json['Token']['access_token'])) {
                   return $json['Token']['access_token'];
                }
            }
        }
    }
    return null;
 }

 function RefreshTokenIfNeeded($company_id) {
     $conn = ConnectDB();
     $company_id = intval($company_id);
     
     // 1. Fetch current settings
     $sql = "SELECT * FROM pbxdetail WHERE company_id = $company_id";
     $res = mysqli_query($conn, $sql);
     if (!$res || mysqli_num_rows($res) == 0) return null;
     $row = mysqli_fetch_assoc($res);
     
     $last_updated = $row['auth_updated_at'];
     $current_token = $row['auth_token'];
     
     $needs_update = false;
     if (empty($last_updated)) {
         $needs_update = true; // Never updated or legacy
     } else {
         $diff_seconds = time() - strtotime($last_updated);
         if ($diff_seconds > 3600) { // 60 minutes
             $needs_update = true;
         }
     }
     
     if ($needs_update) {
         // Determine Auth Method
         $auth_method = '';
         $creds = [];
         
         if (!empty($row['pbxclientid'])) {
             $auth_method = 'oauth';
             $creds['client_id'] = $row['pbxclientid'];
             $creds['client_secret'] = $row['pbxsecret'];
         } elseif (!empty($row['pbxloginid'])) {
             $auth_method = 'login';
             $creds['username'] = $row['pbxloginid'];
             $creds['password'] = $row['pbxloginpass'];
         }
         
         if ($auth_method) {
             $new_token = Generate3CXToken($row['pbxurl'], $auth_method, $creds);
             if ($new_token) {
                 // Update DB
                 $safe_token = mysqli_real_escape_string($conn, $new_token);
                 $update_sql = "UPDATE pbxdetail SET auth_token = '$safe_token', auth_updated_at = NOW() WHERE company_id = $company_id";
                 mysqli_query($conn, $update_sql);
                 return $new_token;
             }
         }
     }
     
     return $current_token; // Return existing if no update needed or update failed
 }

 function Fetch3CXCallAnalytics($base_url, $call_id, $token) {
     // Placeholder endpoint - Adjust based on actual 3CX API for sentiment/transcript
     // Assuming GET /api/CallAnalytics?callid=... or similar
     $url = rtrim($base_url, '/') . "/api/CallAnalytics?callid=" . urlencode($call_id);
     
     $curl = curl_init();
     curl_setopt_array($curl, array(
         CURLOPT_URL => $url,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_TIMEOUT => 20,
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
         return ['error' => $err];
     }
     
     return json_decode($response, true);
 }
 ?>