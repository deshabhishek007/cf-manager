<?php
require_once 'config.php';

function cfApi($endpoint, $method = 'GET', $data = null) {
    $url = 'https://api.cloudflare.com/client/v4' . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . CF_API_TOKEN,
        'Content-Type: application/json'
    ]);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);

    // Debug: uncomment to see API response
    // echo "<pre>HTTP Code: $httpCode\nError: $error\nResponse: " . print_r($decoded, true) . "</pre>";

    return $decoded;
}

// Fetch all zones with pagination
$zones = [];
$page = 1;
$perPage = 50;
$error = null;

do {
    $result = cfApi("/zones?page=$page&per_page=$perPage");

    if (!$result) {
        $error = "Failed to connect to Cloudflare API";
        break;
    }

    if (isset($result['success']) && $result['success'] && !empty($result['result'])) {
        $zones = array_merge($zones, $result['result']);
        $page++;
    } else {
        if (isset($result['errors']) && !empty($result['errors'])) {
            $error = "API Error: " . $result['errors'][0]['message'];
        }
        break;
    }
} while (!empty($result['result']));

// Sort zones alphabetically
if (!empty($zones)) {
    usort($zones, fn($a, $b) => strcasecmp($a['name'], $b['name']));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloudflare DNS Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Cloudflare DNS Manager</h1>
        <p class="subtitle">Total Domains: <?= count($zones) ?></p>

        <?php if ($error): ?>
            <div class="error-box">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($zones)): ?>
            <div class="info-box">
                No domains found. Please check your API token has the correct permissions (Zone.Zone.Read and Zone.DNS.Edit).
            </div>
        <?php else: ?>
            <div class="search-box">
                <input type="text" id="search" placeholder="Search domains..." onkeyup="filterDomains()">
            </div>

            <div class="zones-list">
                <?php foreach ($zones as $zone): ?>
                    <div class="zone-item" data-name="<?= strtolower($zone['name']) ?>">
                        <a href="dns.php?zone_id=<?= $zone['id'] ?>&name=<?= urlencode($zone['name']) ?>">
                            <strong><?= htmlspecialchars($zone['name']) ?></strong>
                            <span class="zone-status <?= $zone['status'] ?>"><?= $zone['status'] ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function filterDomains() {
        const search = document.getElementById('search').value.toLowerCase();
        const items = document.querySelectorAll('.zone-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(search) ? '' : 'none';
        });
    }
    </script>
</body>
</html>
