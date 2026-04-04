<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Express Repair</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="all,follow">
	<base href="<?php echo htmlspecialchars(HEADURL, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="modules/common/login/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome CSS-->
    <link rel="stylesheet" href="modules/common/login/vendor/font-awesome/css/font-awesome.min.css">
    <!-- Fontastic Custom icon font-->
    <link rel="stylesheet" href="modules/common/login/css/fontastic.css">
    <!-- Google fonts - Poppins -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,700">
    <!-- theme stylesheet-->
    <link rel="stylesheet" href="modules/common/login/css/system_new_default.css">
    <!-- Custom stylesheet - for your changes-->
    <!--<link rel="stylesheet" href="css/custom.css">->
    <!-- Favicon-->
    <link rel="shortcut icon" href="modules/common/login/img/favicon.ico">
    <!-- Tweaks for older IEs--><!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
	
  </head>
  <body>
    <div class="page login-page">
	  <div class="login-container">
		<!-- Enlarged Company Logo -->
		<img src="modules/common/login/img/company-logo.png" alt="Company Logo" class="logo">
			 <?php if(!empty($error_msg)) echo $error_msg; ?>
		<!-- Login Form -->
		<h2>Login to Your Account</h2>
			
		<form method="post" role="form" class="form-validate" autocomplete="off" id="formLogin" novalidate="">
			<input type="text" placeholder="Username" name="uname1" id="uname1" required>
			<input type="password" placeholder="Password" name="pwd1" id="pwd1" required>
			
			<!-- <div class="h-captcha" data-sitekey="c74a8650-31cc-4410-8375-0d0fee6ad83a"></div> -->
			
			<button type="submit">Login</button>
		</form>
		 <div class="login-container">
			<a href="<?php echo $googleLoginUrl; ?>" class="google-login-btn">
				<img src="https://developers.google.com/identity/images/btn_google_signin_dark_normal_web.png" alt="Sign in with Google">
			</a>
			
			<a href="<?php echo $msLoginUrl; ?>" class="ms-login-btn">
				<img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft Logo">
				Sign in with Microsoft
			</a>
			
		</div>

		<style>
/* Ensure parent container centers everything */
.login-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    text-align: center;
    margin-top: 20px;
}

/* Google Button */
.google-login-btn img {
    width: 200px; /* Ensuring consistent width */
}

/* Microsoft Button */
.ms-login-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #ffffff;
    color: #5E5E5E;
    border: 1px solid #DADCE0;
    border-radius: 4px;
    padding: 10px 15px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    width: 200px; /* Same width as Google button */
    box-shadow: 0 2px 2px rgba(0, 0, 0, 0.2);
    margin-top: 10px; /* Add spacing */
}

/* Microsoft logo inside the button */
.ms-login-btn img {
    width: 20px;
    margin-right: 10px;
}

/* Hover effect */
.ms-login-btn:hover {
    background-color: #f5f5f5;
    border-color: #c2c2c2;
}
</style>
		<!-- Footer -->
		<!--<div class="footer">
			<p>Forgot password? <a href="#">Reset here</a></p>
		</div>-->
	</div>
      <div class="copyrights text-center">
        <p>Design by <a href="https://allsmartone.com" class="external">Allsmartone</a>
          <!-- Please do not remove the backlink to us unless you support further theme's development at https://bootstrapious.com/donate. It is part of the license conditions. Thank you for understanding :)-->
        </p>
      </div>
    </div>
    <!-- JavaScript files-->
	<script src="https://hcaptcha.com/1/api.js" async defer></script>
    <script src="modules/common/login/vendor/jquery/jquery.min.js"></script>
    <script src="modules/common/login/vendor/popper.js/umd/popper.min.js"> </script>
    <script src="modules/common/login/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="modules/common/login/vendor/jquery.cookie/jquery.cookie.js"> </script>
    <script src="modules/common/login/vendor/chart.js/Chart.min.js"></script>
    <script src="modules/common/login/vendor/jquery-validation/jquery.validate.min.js"></script>
    <!-- Main File-->
    <script src="modules/common/login/js/front.js"></script>
	<script type="text/javascript" >

$("#btnLogin").click(function(event) {

      //Fetch form to apply custom Bootstrap validation
      var form = $("#formLogin")

      if (form[0].checkValidity() === false) {
          event.preventDefault()
          event.stopPropagation()
      }

      form.addClass('was-validated');
  });
 </script>
  </body>
</html>