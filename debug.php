<?php
require_once 'config.php';

echo "<h1>Cloudflare API Debug</h1>";

// Test 1: Check if config is loaded
echo "<h2>1. Configuration Check</h2>";
if (defined('CF_API_TOKEN') && CF_API_TOKEN !== 'your_cloudflare_api_token_here') {
    echo "✓ API Token is configured<br>";
    echo "Token length: " . strlen(CF_API_TOKEN) . " characters<br>";
} else {
    echo "✗ API Token not configured properly<br>";
    die("Please set CF_API_TOKEN in config.php");
}

// Test 2: Check cURL
echo "<h2>2. cURL Check</h2>";
if (function_exists('curl_init')) {
    echo "✓ cURL is available<br>";
} else {
    echo "✗ cURL is not available<br>";
    die("Please enable cURL extension");
}

// Test 3: Test API connection
echo "<h2>3. API Connection Test</h2>";
$ch = curl_init('https://api.cloudflare.com/client/v4/user/tokens/verify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . CF_API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($error) {
    echo "cURL Error: $error<br>";
}

$result = json_decode($response, true);
echo "<pre>" . print_r($result, true) . "</pre>";

if ($result['success']) {
    echo "✓ API Token is valid and active<br>";
} else {
    echo "✗ API Token verification failed<br>";
}

// Test 4: Fetch zones
echo "<h2>4. Fetch Zones Test</h2>";
$ch = curl_init('https://api.cloudflare.com/client/v4/zones?per_page=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . CF_API_TOKEN,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode<br>";
if ($error) {
    echo "cURL Error: $error<br>";
}

$result = json_decode($response, true);
echo "<pre>" . print_r($result, true) . "</pre>";

if ($result['success']) {
    echo "✓ Successfully fetched zones<br>";
    echo "Total zones found: " . $result['result_info']['total_count'] . "<br>";
    echo "Showing first " . count($result['result']) . " zones<br>";
} else {
    echo "✗ Failed to fetch zones<br>";
    if (isset($result['errors'])) {
        foreach ($result['errors'] as $err) {
            echo "Error: " . $err['message'] . "<br>";
        }
    }
}
