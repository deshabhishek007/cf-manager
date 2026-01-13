#!/usr/bin/env php
<?php
/**
 * Cloudflare DNS Manager CLI
 *
 * Usage: php cli.php <domain> <ip-address> [options]
 *
 * Examples:
 *   php cli.php example.com 192.168.1.1
 *   php cli.php example.com 192.168.1.1 --proxied
 *   php cli.php example.com 192.168.1.1 --ttl=3600
 */

require_once __DIR__ . '/config.php';

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
    curl_close($ch);

    $result = json_decode($response, true);

    if (!$result) {
        return ['success' => false, 'errors' => [['message' => 'Failed to decode response']]];
    }

    return $result;
}

function output($message, $type = 'info') {
    $colors = [
        'success' => "\033[0;32m",
        'error' => "\033[0;31m",
        'warning' => "\033[0;33m",
        'info' => "\033[0;36m",
        'reset' => "\033[0m"
    ];

    $prefix = [
        'success' => '✓',
        'error' => '✗',
        'warning' => '⚠',
        'info' => '→'
    ];

    echo $colors[$type] . $prefix[$type] . ' ' . $message . $colors['reset'] . PHP_EOL;
}

function findZoneByDomain($domain) {
    output("Searching for zone: $domain");

    $page = 1;
    while (true) {
        $result = cfApi("/zones?name=$domain&page=$page&per_page=50");

        if (!$result['success']) {
            return null;
        }

        foreach ($result['result'] as $zone) {
            if ($zone['name'] === $domain) {
                return $zone;
            }
        }

        if (empty($result['result']) || count($result['result']) < 50) {
            break;
        }
        $page++;
    }

    return null;
}

function createZone($domain) {
    output("Creating new zone for: $domain", 'info');

    $result = cfApi("/zones", 'POST', [
        'name' => $domain,
        'jump_start' => false
    ]);

    if (!$result['success']) {
        $error = $result['errors'][0]['message'] ?? 'Unknown error';
        output("Failed to create zone: $error", 'error');
        return null;
    }

    output("Zone created successfully!", 'success');
    return $result['result'];
}

function getARecords($zoneId, $name) {
    $result = cfApi("/zones/$zoneId/dns_records?type=A&name=$name");

    if (!$result['success']) {
        return [];
    }

    return $result['result'];
}

function createARecord($zoneId, $name, $ip, $proxied, $ttl) {
    $result = cfApi("/zones/$zoneId/dns_records", 'POST', [
        'type' => 'A',
        'name' => $name,
        'content' => $ip,
        'ttl' => $ttl,
        'proxied' => $proxied
    ]);

    return $result;
}

function updateARecord($zoneId, $recordId, $name, $ip, $proxied, $ttl) {
    $result = cfApi("/zones/$zoneId/dns_records/$recordId", 'PUT', [
        'type' => 'A',
        'name' => $name,
        'content' => $ip,
        'ttl' => $ttl,
        'proxied' => $proxied
    ]);

    return $result;
}

function updateOrCreateRecord($zoneId, $name, $ip, $proxied, $ttl) {
    $records = getARecords($zoneId, $name);

    if (empty($records)) {
        output("Creating A record for: $name → $ip");
        $result = createARecord($zoneId, $name, $ip, $proxied, $ttl);

        if ($result['success']) {
            output("Record created successfully!", 'success');
            return true;
        } else {
            $error = $result['errors'][0]['message'] ?? 'Unknown error';
            output("Failed to create record: $error", 'error');
            return false;
        }
    } else {
        output("Updating A record for: $name → $ip");
        $recordId = $records[0]['id'];
        $result = updateARecord($zoneId, $recordId, $name, $ip, $proxied, $ttl);

        if ($result['success']) {
            output("Record updated successfully!", 'success');
            return true;
        } else {
            $error = $result['errors'][0]['message'] ?? 'Unknown error';
            output("Failed to update record: $error", 'error');
            return false;
        }
    }
}

function showNameservers($zone) {
    output("\n" . str_repeat('=', 60), 'info');
    output("Nameservers for {$zone['name']}:", 'warning');
    output(str_repeat('=', 60), 'info');

    foreach ($zone['name_servers'] as $ns) {
        output("  $ns", 'info');
    }

    output(str_repeat('=', 60) . "\n", 'info');
    output("Update your domain registrar to use these nameservers.", 'warning');
}

// Parse command
$command = $argv[1] ?? null;

// Handle 'list' command
if ($command === 'list' || $command === 'domains') {
    output("\n" . str_repeat('=', 80), 'info');
    output("Cloudflare Domains List", 'success');
    output(str_repeat('=', 80) . "\n", 'info');

    output("Fetching domains...");

    $zones = [];
    $page = 1;

    do {
        $result = cfApi("/zones?page=$page&per_page=50");

        if (!$result['success']) {
            output("Failed to fetch domains", 'error');
            exit(1);
        }

        if (!empty($result['result'])) {
            $zones = array_merge($zones, $result['result']);
            $page++;
        }
    } while (!empty($result['result']));

    if (empty($zones)) {
        output("No domains found in your account", 'warning');
        exit(0);
    }

    // Sort alphabetically
    usort($zones, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    output("\nFound " . count($zones) . " domain(s):\n", 'success');

    // Header
    echo "\033[1m"; // Bold
    printf("%-50s %-20s\n", "DOMAIN", "NS STATUS");
    echo "\033[0m"; // Reset
    echo str_repeat('-', 70) . "\n";

    foreach ($zones as $zone) {
        $status = $zone['status'] ?? 'pending';

        if ($status === 'active') {
            $statusText = "\033[0;32m✓ Active\033[0m";
        } elseif ($status === 'pending') {
            $statusText = "\033[0;33m⚠ Pending\033[0m";
        } else {
            $statusText = "\033[0;31m✗ " . ucfirst($status) . "\033[0m";
        }

        printf("%-50s ", $zone['name']);
        echo $statusText . "\n";
    }

    echo "\n";
    exit(0);
}

// Handle 'info' command
if ($command === 'info') {
    $domain = $argv[2] ?? null;

    if (!$domain) {
        output("Usage: php cli.php info <domain>", 'error');
        exit(1);
    }

    output("\n" . str_repeat('=', 80), 'info');
    output("Domain Information: $domain", 'success');
    output(str_repeat('=', 80) . "\n", 'info');

    // Find zone
    $zone = findZoneByDomain($domain);

    if (!$zone) {
        output("Domain not found in your Cloudflare account", 'error');
        exit(1);
    }

    // Basic Information
    output("BASIC INFORMATION", 'warning');
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-25s %s\n", "Domain:", $zone['name']);
    echo sprintf("%-25s %s\n", "Zone ID:", $zone['id']);
    echo sprintf("%-25s %s\n", "Status:", ucfirst($zone['status']));
    echo sprintf("%-25s %s\n", "Plan:", $zone['plan']['name'] ?? 'N/A');
    echo sprintf("%-25s %s\n", "Created:", date('Y-m-d H:i:s', strtotime($zone['created_on'])));
    echo "\n";

    // Nameservers
    output("NAMESERVERS", 'warning');
    echo str_repeat('-', 80) . "\n";
    $nameservers = $zone['name_servers'] ?? [];
    if (!empty($nameservers)) {
        foreach ($nameservers as $ns) {
            echo "  → $ns\n";
        }
    } else {
        echo "  Not configured\n";
    }
    echo "\n";

    // DNS Records
    output("DNS RECORDS (All Types)", 'warning');
    echo str_repeat('-', 80) . "\n";

    $dnsResult = cfApi("/zones/{$zone['id']}/dns_records?per_page=100");

    if ($dnsResult['success'] && !empty($dnsResult['result'])) {
        $records = $dnsResult['result'];

        // Group by type
        $grouped = [];
        foreach ($records as $record) {
            $grouped[$record['type']][] = $record;
        }

        foreach ($grouped as $type => $typeRecords) {
            echo "\n\033[1m$type Records (" . count($typeRecords) . "):\033[0m\n";
            foreach ($typeRecords as $record) {
                $proxied = isset($record['proxied']) && $record['proxied'] ? ' [Proxied]' : '';
                $ttl = $record['ttl'] == 1 ? 'Auto' : $record['ttl'];

                if ($type === 'MX') {
                    echo sprintf("  %-40s → %-40s Priority: %d, TTL: %s\n",
                        $record['name'], $record['content'], $record['priority'], $ttl);
                } else {
                    echo sprintf("  %-40s → %-40s TTL: %s%s\n",
                        $record['name'], $record['content'], $ttl, $proxied);
                }
            }
        }
    } else {
        echo "  No DNS records found\n";
    }

    echo "\n";

    // SSL/TLS Status
    if (isset($zone['ssl']) && isset($zone['ssl']['status'])) {
        output("SSL/TLS STATUS", 'warning');
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-25s %s\n", "Status:", ucfirst($zone['ssl']['status']));
        echo "\n";
    }

    echo str_repeat('=', 80) . "\n";
    exit(0);
}

// Parse arguments for domain update
$domain = $argv[1] ?? null;
$ip = $argv[2] ?? null;

if (!$domain || !$ip) {
    output("Usage: php cli.php <command> [arguments] [options]", 'error');
    output("", 'info');
    output("Commands:", 'info');
    output("  list                            List all domains with NS status", 'info');
    output("  info <domain>                   Show detailed domain information", 'info');
    output("  <domain> <ip>                   Update domain DNS records", 'info');
    output("", 'info');
    output("Options:", 'info');
    output("  --no-proxy    Disable Cloudflare proxy (default: enabled)", 'info');
    output("  --ttl=<num>   Set TTL in seconds (default: 1 for Auto)", 'info');
    output("", 'info');
    output("Examples:", 'info');
    output("  php cli.php list                                       # List all domains", 'info');
    output("  php cli.php info example.com                           # Show domain details", 'info');
    output("  php cli.php example.com 192.168.1.1                    # Proxied, Auto TTL", 'info');
    output("  php cli.php example.com 192.168.1.1 --no-proxy         # No proxy", 'info');
    output("  php cli.php example.com 192.168.1.1 --ttl=3600         # Proxied, 1h TTL", 'info');
    exit(1);
}

// Validate IP
if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    output("Invalid IPv4 address: $ip", 'error');
    exit(1);
}

// Parse options (proxied is ON by default, can be disabled with --no-proxy)
$proxied = !in_array('--no-proxy', $argv);
$ttl = 1;

foreach ($argv as $arg) {
    if (strpos($arg, '--ttl=') === 0) {
        $ttl = (int)substr($arg, 6);
    }
}

output("\n" . str_repeat('=', 60), 'info');
output("Cloudflare DNS Manager CLI", 'success');
output(str_repeat('=', 60), 'info');
output("Domain: $domain");
output("IP Address: $ip");
output("Proxied: " . ($proxied ? 'Yes' : 'No'));
output("TTL: $ttl");
output(str_repeat('=', 60) . "\n", 'info');

// Find or create zone
$zone = findZoneByDomain($domain);

if (!$zone) {
    output("Zone not found. Creating new zone...", 'warning');
    $zone = createZone($domain);

    if (!$zone) {
        output("Failed to create zone. Exiting.", 'error');
        exit(1);
    }

    showNameservers($zone);
} else {
    output("Zone found: {$zone['id']}", 'success');
}

$zoneId = $zone['id'];

// Update @ record
output("\nUpdating root domain (@)...");
$success1 = updateOrCreateRecord($zoneId, $domain, $ip, $proxied, $ttl);

// Update www record
output("\nUpdating www subdomain...");
$success2 = updateOrCreateRecord($zoneId, "www.$domain", $ip, $proxied, $ttl);

// Summary
output("\n" . str_repeat('=', 60), 'info');
if ($success1 && $success2) {
    output("All records updated successfully!", 'success');
} else {
    output("Some records failed to update. Check errors above.", 'warning');
}
output(str_repeat('=', 60) . "\n", 'info');

exit($success1 && $success2 ? 0 : 1);
