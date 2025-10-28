<?php
include_once('includes/mysql_connect.php');

function loadEnv($path)
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}
loadEnv(__DIR__ . '/.env');

$api_key = getenv('SHOPIFY_API_KEY');
$secret_key = getenv('SHOPIFY_API_SECRET');


if (!isset($_GET['shop']) || !isset($_GET['code'])) {
    die("Missing shop or code.");
}

$parameters = $_GET;
$shop_url = trim($parameters['shop']);
$hmac = $parameters['hmac'] ?? '';

// Verify HMAC from Shopify
$verify_params = $parameters;
unset($verify_params['hmac']);
ksort($verify_params);
$calculated_hmac = hash_hmac('sha256', http_build_query($verify_params), $secret_key);

if (!hash_equals($calculated_hmac, $hmac)) {
    die("Invalid HMAC — possible tampering detected.");
}

// Exchange authorization code for permanent access token
$access_token_endpoint = 'https://' . $shop_url . '/admin/oauth/access_token';
$post_fields = [
    'client_id' => $api_key,
    'client_secret' => $secret_key,
    'code' => $parameters['code']
];

$ch = curl_init($access_token_endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
$response_raw = curl_exec($ch);
$curl_err = curl_error($ch);
curl_close($ch);

if ($response_raw === false) {
    die("cURL failed: " . $curl_err);
}

$response = json_decode($response_raw, true);
if (!isset($response['access_token'])) {
    die("No access_token found in response: " . htmlspecialchars($response_raw));
}

$access_token = $response['access_token'];

//  Insert or update shop record (NO HMAC in DB)
$query = "
    INSERT INTO shops (shop_url, access_token, install_date)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        access_token = VALUES(access_token),
        install_date = NOW()
";

$stmt = $mysql->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $mysql->error);
}

$stmt->bind_param("ss", $shop_url, $access_token);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$stmt->close();

// ✅ Redirect back to Shopify admin apps page
header("Location: https://" . $shop_url . "/admin/apps");
exit();
?>
