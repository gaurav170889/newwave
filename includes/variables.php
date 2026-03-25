<?php
$ip = $_SERVER['HTTP_HOST'];
$protocol = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) ? "https" : "http";

define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
define("MODULEPATH", ROOT_PATH."/newwave/modules/");
define("DBHOST", "localhost");
define("NAVURL","$protocol://$ip/newwave/");
define("INCLUDEPATH", ROOT_PATH."/newwave/");
define("HEADURL","$protocol://$ip/newwave/");
define("LOGOUT","$protocol://$ip/newwave");
define("BASE_URL", "$protocol://$ip/newwave/");
define("UPLOAD", ROOT_PATH."/newwave/asset/importnum/");
define("WEBHOOK_URL", "$protocol://$ip/newwave/api/webhook_rating.php");
define("QUEUE_WEBHOOK_URL", "$protocol://$ip/newwave/api/webhook_queue_status_secure.php");

// Dialer DB — override these on the server via a local config
define('DIALERDB_HOST', 'localhost');
define('DIALERDB_USER', '');
define('DIALERDB_PASS', '');
define('DIALERDB_NAME', '');
?>