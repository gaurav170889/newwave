<?php
require_once dirname(__DIR__) . '/includes/variables.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();
header("Location: " . BASE_URL);
exit;
?>