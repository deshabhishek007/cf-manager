<?php
require_once 'config.php';

$zoneId = $_GET['zone_id'] ?? '';
$zoneName = $_GET['name'] ?? '';

if (!$zoneId) {
    die('Zone ID required');
}

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
    curl_close($ch);

    return json_decode($response, true);
}

// Handle POST requests (Add/Update/Delete/Bulk Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $result = cfApi("/zones/$zoneId/dns_records", 'POST', [
            'type' => 'A',
            'name' => $_POST['name'],
            'content' => $_POST['content'],
            'ttl' => (int)$_POST['ttl'],
            'proxied' => isset($_POST['proxied'])
        ]);
    } elseif ($action === 'update') {
        $recordId = $_POST['record_id'];
        $result = cfApi("/zones/$zoneId/dns_records/$recordId", 'PUT', [
            'type' => 'A',
            'name' => $_POST['name'],
            'content' => $_POST['content'],
            'ttl' => (int)$_POST['ttl'],
            'proxied' => isset($_POST['proxied'])
        ]);
    } elseif ($action === 'bulk_update') {
        $recordIds = $_POST['record_ids'] ?? [];
        $newIp = $_POST['bulk_content'];
        $newTtl = (int)$_POST['bulk_ttl'];
        $newProxied = isset($_POST['bulk_proxied']);

        foreach ($recordIds as $recordId) {
            // Get the record to preserve the name
            $recordResult = cfApi("/zones/$zoneId/dns_records/$recordId");
            if ($recordResult['success']) {
                $record = $recordResult['result'];
                cfApi("/zones/$zoneId/dns_records/$recordId", 'PUT', [
                    'type' => 'A',
                    'name' => $record['name'],
                    'content' => $newIp,
                    'ttl' => $newTtl,
                    'proxied' => $newProxied
                ]);
            }
        }
    } elseif ($action === 'delete') {
        $recordId = $_POST['record_id'];
        $result = cfApi("/zones/$zoneId/dns_records/$recordId", 'DELETE');
    }

    header("Location: dns.php?zone_id=$zoneId&name=" . urlencode($zoneName));
    exit;
}

// Fetch A records
$result = cfApi("/zones/$zoneId/dns_records?type=A&per_page=100");
$records = $result['success'] ? $result['result'] : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>DNS Records - <?= htmlspecialchars($zoneName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-link">&larr; Back to Domains</a>
            <h1><?= htmlspecialchars($zoneName) ?></h1>
            <p class="subtitle">A Records (<?= count($records) ?>)</p>
        </div>

        <div class="add-record">
            <h2>Add New A Record</h2>
            <form method="post" class="record-form">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" placeholder="Name (e.g., @ or subdomain)" required>
                <input type="text" name="content" placeholder="IPv4 Address" required pattern="^(\d{1,3}\.){3}\d{1,3}$">
                <input type="number" name="ttl" value="1" placeholder="TTL (1=Auto)">
                <label><input type="checkbox" name="proxied"> Proxied</label>
                <button type="submit">Add Record</button>
            </form>
        </div>

        <div class="bulk-update-section" id="bulkUpdateSection" style="display: none;">
            <h2>Bulk Update Selected Records (<span id="selectedCount">0</span> selected)</h2>
            <form method="post" id="bulkUpdateForm" class="record-form">
                <input type="hidden" name="action" value="bulk_update">
                <input type="text" name="bulk_content" placeholder="New IPv4 Address" required pattern="^(\d{1,3}\.){3}\d{1,3}$">
                <input type="number" name="bulk_ttl" value="1" placeholder="TTL (1=Auto)">
                <label><input type="checkbox" name="bulk_proxied"> Proxied</label>
                <button type="submit" class="btn-bulk-update">Update Selected</button>
                <button type="button" class="btn-cancel" onclick="clearSelection()">Cancel</button>
            </form>
        </div>

        <div class="records-list">
            <h2>Existing A Records</h2>
            <?php if (empty($records)): ?>
                <p>No A records found.</p>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <div class="record-item">
                        <div class="record-checkbox">
                            <input type="checkbox" class="record-select" value="<?= $record['id'] ?>" onchange="updateBulkUI()">
                        </div>

                        <form method="post" class="record-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="record_id" value="<?= $record['id'] ?>">

                            <div class="record-row">
                                <input type="text" name="name" value="<?= htmlspecialchars($record['name']) ?>" required>
                                <input type="text" name="content" value="<?= htmlspecialchars($record['content']) ?>" required pattern="^(\d{1,3}\.){3}\d{1,3}$">
                                <input type="number" name="ttl" value="<?= $record['ttl'] ?>">
                                <label><input type="checkbox" name="proxied" <?= $record['proxied'] ? 'checked' : '' ?>> Proxied</label>
                                <button type="submit" class="btn-update">Update</button>
                            </div>
                        </form>

                        <form method="post" class="delete-form" onsubmit="return confirm('Delete this record?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function updateBulkUI() {
        const checkboxes = document.querySelectorAll('.record-select:checked');
        const count = checkboxes.length;
        const bulkSection = document.getElementById('bulkUpdateSection');
        const selectedCount = document.getElementById('selectedCount');

        selectedCount.textContent = count;
        bulkSection.style.display = count > 0 ? 'block' : 'none';
    }

    function clearSelection() {
        document.querySelectorAll('.record-select').forEach(cb => cb.checked = false);
        updateBulkUI();
    }

    document.getElementById('bulkUpdateForm').addEventListener('submit', function(e) {
        const checkboxes = document.querySelectorAll('.record-select:checked');

        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one record');
            return;
        }

        if (!confirm(`Update ${checkboxes.length} record(s)?`)) {
            e.preventDefault();
            return;
        }

        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'record_ids[]';
            input.value = cb.value;
            this.appendChild(input);
        });
    });
    </script>
</body>
</html>
