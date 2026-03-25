<?php 
class Login {
	function __construct() {
    	$this->modal = loadmodal("login");;
    }

	public function index() {
		$error_msg="";
		if (isset($_SESSION['perror_msg']) && !empty($_SESSION['perror_msg'])) {
			$error_msg = $_SESSION['perror_msg'];
			unset($_SESSION['perror_msg']); // Clear the session variable after assigning
		}

		if(isset($_POST['uname1']) and $_POST['pwd1']!="") {
			//include("dashboard/modal.php");
			$userlogin = trim($_POST['uname1']);
			$userpass = trim($_POST['pwd1']);
			// Debugging Output
            // echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>"; ...
			
            // Update to match new schema: user_email instead of email
            // Use single quotes for SQL string literal
			$getuser = $this->modal->getAllRecords('users','*',"user_email='$userlogin'");
            
            /*
            echo "Query Result: ";
            if ($getuser) { ... }
            */
 			
            // Check if user exists
			if(isset($getuser['id'])) {
                // Verify password against password_hash
				if(password_verify($userpass, $getuser['password_hash'])) {
					/* 
                       session_start() is already called in index.php. 
                       Calling it again might be ignored or cause notice.
                       We will just set the session variables.
                    */
					// ini_set('session.gc_maxlifetime', 86400); // Optional: change dynamic
                    
                    // Regenerate ID to prevent fixation
                    session_regenerate_id(true); 

					$_SESSION['pid'] = $getuser['id'];
					$_SESSION['pname'] = $getuser['user_email'];
					$_SESSION['prole'] = $getuser['user_type'];
                    $_SESSION['company_id'] = $getuser['company_id'];

                    echo '<div class="alert alert-success" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <strong>Success!</strong> You have been signed in successfully!
                        </div>';
                    
                    // Redirect based on role
                    if ($getuser['user_type'] == 'super_admin') {
                         header("Location: ".BASE_URL."?route=admindashboard/index"); 
                    } else {
                         header("Location: ".BASE_URL);
                    }
					exit;
					
				} else {
                    // Password verification failed
					$error_msg = '<div class="alert alert-danger" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<strong>Login Failed!</strong> Invalid Password for query: '.$userlogin.'
					</div>';
				}
			} else {
                // User not found
				$error_msg = '<div class="alert alert-danger" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<strong>Login Failed!</strong> User not found! Input: '.$userlogin.'
				</div>';
			}
		} elseif($error_msg!="" AND !empty($error_msg)) {
			$error_msg = '<div class="alert alert-danger" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<strong>'.$error_msg.'</strong> Please try again!
			</div>';
		}
		//require $_SERVER['DOCUMENT_ROOT'] . '/ebsolution/3cxaddon/composer/vendor/autoload.php';
		//require $_SERVER['DOCUMENT_ROOT'] . '/ebsolution/3cxaddon/msoffice/vendor/autoload.php';
	//	$client = new Google_Client();
	//	$client->setClientId(GOOGLE_ID);
	//	$client->setClientSecret(GOOGLE_SECRET);
	//	$client->setRedirectUri('https://getmovers.ebsolution.ca/ebsolution/3cxaddon/callback.php');
	//	$client->addScope('email');
	//	$client->addScope('profile');

	//	$googleLoginUrl = $client->createAuthUrl();
		
	//	$ms_client_id = MS_CLIENT_ID;
	///	$ms_client_secret = MS_CLIENT_SECRET;
	//	$tenant_id = "ac5187f9-c725-4a69-976e-71877d47c35e";
	//	$ms_redirect_uri = 'https://getmovers.ebsolution.ca/ebsolution/3cxaddon/ms_callback.php';

	//	$_SESSION['ms_state'] = bin2hex(random_bytes(16)); // CSRF protection
	//	$msLoginUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?"
		//$msLoginUrl = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/authorize?"
	//		. "client_id=$ms_client_id"
	//		. "&response_type=code"
	//		. "&redirect_uri=" . urlencode($ms_redirect_uri)
	//		. "&scope=" . urlencode("openid email profile User.Read.All offline_access")
	//	    . "&state=" . $_SESSION['ms_state']
	//		. "&prompt=select_account";
		include("loginpages.php");
	}
	
	public function logout() {
		session_destroy(); // or  session_unset('session_name'); to dystroy individual session
		header("Location: ".BASE_URL);
	}
}

?>
