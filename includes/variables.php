<?php
if (!function_exists('loadSimpleEnvFile')) {
    function loadSimpleEnvFile($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if ($name === '') {
                continue;
            }

            $firstChar = substr($value, 0, 1);
            $lastChar = substr($value, -1);
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('envValue')) {
    function envValue($key, $default = '')
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }
}

$projectRoot = dirname(__DIR__);
loadSimpleEnvFile($projectRoot . '/.env.local');
loadSimpleEnvFile($projectRoot . '/.env');

$localConfigFile = __DIR__ . '/variables.local.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

$ip = $_SERVER['HTTP_HOST'] ?? envValue('APP_HOST', 'localhost');
$protocol = envValue(
    'APP_PROTOCOL',
    ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? 'https' : 'http'
);

$isWebRequest = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '';
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDirectory = trim(str_replace('\\', '/', dirname($scriptName)), '/');

if ($isWebRequest) {
    $basePath = ($scriptDirectory === '' || $scriptDirectory === '.') ? '/' : '/' . $scriptDirectory;
} else {
    $configuredBasePath = trim((string) envValue('APP_BASE_PATH', '/newwave'), '/');
    $basePath = $configuredBasePath === '' ? '/' : '/' . $configuredBasePath;
}

$pathSuffix = ($basePath === '/') ? '' : rtrim($basePath, '/');
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname($projectRoot);
$calculatedBaseUrl = $protocol . '://' . $ip . $pathSuffix;
$baseUrl = $isWebRequest ? $calculatedBaseUrl : (string) envValue('APP_BASE_URL', $calculatedBaseUrl);
$baseUrl = rtrim($baseUrl, '/') . '/';

define('ROOT_PATH', $documentRoot);
define('MODULEPATH', ROOT_PATH . $pathSuffix . '/modules/');
define('DBHOST', envValue('DBHOST', envValue('DB_HOST', 'localhost')));
define('NAVURL', $baseUrl);
define('INCLUDEPATH', ROOT_PATH . $pathSuffix . '/');
define('HEADURL', $baseUrl);
define('LOGOUT', rtrim($baseUrl, '/'));
define('BASE_URL', $baseUrl);
define('UPLOAD', ROOT_PATH . $pathSuffix . '/asset/importnum/');
define('WEBHOOK_URL', $baseUrl . 'api/webhook_rating.php');
define('QUEUE_WEBHOOK_URL', $baseUrl . 'api/webhook_queue_status_secure.php');

// Shared DB config (supports both DB_* and DIALERDB_* names)
define('DIALERDB_HOST', envValue('DIALERDB_HOST', envValue('DB_HOST', 'localhost')));
define('DIALERDB_USER', envValue('DIALERDB_USER', envValue('DB_USER', 'root')));
define('DIALERDB_PASS', envValue('DIALERDB_PASS', envValue('DB_PASS', 'root')));
define('DIALERDB_NAME', envValue('DIALERDB_NAME', envValue('DB_NAME', 'dialerwave')));

// SMTP / PHPMailer settings
define('SMTP_HOST', envValue('SMTP_HOST', ''));
define('SMTP_PORT', envValue('SMTP_PORT', 587));
define('SMTP_USERNAME', envValue('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', envValue('SMTP_PASSWORD', ''));
define('SMTP_SECURE', envValue('SMTP_SECURE', 'tls'));
define('SMTP_FROM_EMAIL', envValue('SMTP_FROM_EMAIL', 'no-reply@example.com'));
define('SMTP_FROM_NAME', envValue('SMTP_FROM_NAME', 'New Wave Dialer'));
?>