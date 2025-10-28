<?php

$_API_KEY = "22da07ffb9ae00c9c2f372351ca5e840";
$_NGROK_URL = "https://bbd5287c9d60.ngrok-free.app"; // no space, no slash at end

// sanitize
$shop = isset($_GET['shop']) ? trim($_GET['shop']) : '';
if (empty($shop)) {
    die("Missing 'shop' parameter.");
}

// make sure shop ends with .myshopify.com
if (!str_ends_with($shop, '.myshopify.com')) {
    $shop .= '.myshopify.com';
}

$scopes = "read_products,write_products,read_script_tags,write_script_tags,read_orders,write_orders,read_customers,write_customers,read_themes,write_themes,read_inventory,write_inventory";

$redirect_uri = $_NGROK_URL . "/newrestapp/token.php";
$nonce = bin2hex(random_bytes(12));
$access_mode = "per-user";

$oauth_url = "https://{$shop}/admin/oauth/authorize?" .
    "client_id={$_API_KEY}" .
    "&scope=" . urlencode($scopes) .
    "&redirect_uri=" . urlencode($redirect_uri) .
    "&state={$nonce}" .
    "&grant_options[]=per-user";

header("Location: $oauth_url");
exit();
