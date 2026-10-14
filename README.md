# Cloudflare DNS Manager

A minimal, lightweight PHP application for managing DNS A records across multiple Cloudflare domains. Built for simplicity and efficiency, perfect for managing large numbers of domains (300+).

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-MIT-green)

## Quick Reference

**Web Interface:** `http://localhost:8000`
**CLI Command:** `cf <command>` (after alias setup)
**API Endpoint:** `https://api.cloudflare.com/client/v4`

## Features

### Domain Management
- **List all domains** - View all zones in your Cloudflare account with pagination support
- **Real-time search** - Filter domains instantly with client-side search
- **Domain status** - See active/pending status for each domain

### DNS Record Management
- **View A records** - Display all A records for any domain
- **Add records** - Create new A records with custom TTL and proxy settings
- **Update records** - Modify existing A records individually
- **Bulk updates** - Select multiple A records and update them all at once with the same IP
- **Delete records** - Remove A records with confirmation
- **Proxy control** - Toggle Cloudflare proxy (orange cloud) on/off

### User Interface
- Clean, minimal design
- Mobile-responsive layout
- No external dependencies
- Fast and lightweight

## Installation

### Prerequisites
- **PHP 7.4 or higher** (tested up to PHP 8.3)
- **cURL extension** enabled (`php -m | grep curl`)
- **Cloudflare account** with domains added
- **Cloudflare API token** with proper permissions

### Step-by-Step Setup

#### 1. Clone or Download the Repository

```bash
git clone https://github.com/yourusername/cf-manager.git
cd cf-manager
```

Or download and extract to `~/sites/cf-manager/`

#### 2. Create Configuration File

```bash
cp config.example.php config.php
```

#### 3. Get Cloudflare API Token

Visit: https://dash.cloudflare.com/profile/api-tokens

**Option A - Use Template (Recommended):**
1. Click "Create Token"
2. Select "Edit zone DNS" template
3. Under "Zone Resources":
   - Choose "Include" → "All zones" (for all domains)
   - Or select specific zones
4. Click "Continue to summary" → "Create Token"
5. **Copy the token immediately** (you won't see it again)

**Option B - Custom Token:**
Create token with these permissions:
- `Zone` → `Zone` → `Read`
- `Zone` → `Zone` → `Edit` (for CLI zone creation)
- `Zone` → `DNS` → `Edit`

**Test your token:**
```bash
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

#### 4. Configure the Application

Edit `config.php`:
```php
<?php
define('CF_API_TOKEN', 'your_actual_cloudflare_api_token_here');
```

**Important Notes:**
- Never commit `config.php` to git (already in `.gitignore`)
- Keep your token secure
- API endpoint is: `https://api.cloudflare.com/client/v4`

#### 5. Set Up CLI Command with Tab Completion (Recommended)

**For Oh My Zsh users (macOS/Linux default):**

The easiest way is to use the included Oh My Zsh plugin:

1. Add `cf-manager` to your plugins in `~/.zshrc`:
   ```bash
   # Find this line in ~/.zshrc:
   plugins=(git ...)

   # Add cf-manager to the list:
   plugins=(git ... cf-manager)
   ```

2. Copy the plugin to Oh My Zsh custom plugins:
   ```bash
   mkdir -p ~/.oh-my-zsh/custom/plugins/cf-manager
   cp ~/.oh-my-zsh/custom/plugins/cf-manager/cf-manager.plugin.zsh \
      ~/sites/cf-manager/cf-completion.sh
   ```

   Or create a symlink:
   ```bash
   mkdir -p ~/.oh-my-zsh/custom/plugins/cf-manager
   ln -s ~/sites/cf-manager/cf-completion.sh \
      ~/.oh-my-zsh/custom/plugins/cf-manager/cf-manager.plugin.zsh
   ```

3. Reload your shell:
   ```bash
   exec zsh
   ```

**For bash or non-Oh My Zsh users:**
```bash
echo "source ~/sites/cf-manager/cf-completion.sh" >> ~/.bashrc
source ~/.bashrc
```

**Tab Completion Features:**
After setup, you can use Tab to autocomplete:
- `cf <Tab>` - Shows all commands (list, info) and domain names
- `cf info exam<Tab>` - Completes to `cf info example.com`
- `cf site<Tab>` - Shows domains starting with "site"
- Domain list is cached for 5 minutes for faster completion

**Verify setup:**
```bash
cf list
# Try tab completion
cf info <Tab><Tab>
```

**Troubleshooting tab completion:**
If tab completion shows files instead of domains:
```bash
# Clear completion cache
rm -f ~/.zcompdump*
exec zsh
```

#### 6. Test the Setup

**Test API connection:**
```bash
php -S localhost:8000
```

Then visit:
- Web UI: http://localhost:8000
- Debug page: http://localhost:8000/debug.php

**Test CLI:**
```bash
# With alias
cf list

# Without alias
php cli.php list
```

### Installation on a New System

When setting up on another machine:

1. Copy the entire `cf-manager` folder to `~/sites/`
2. Install PHP 7.4+ with cURL extension
3. Create `config.php` from `config.example.php`
4. Add your Cloudflare API token to `config.php`
5. Set up the shell alias (see step 5 above)
6. Test with `cf list`

That's it! No dependencies to install, no composer, no npm.

## Usage

### Managing Domains
1. Open the application in your browser
2. Use the search box to filter domains
3. Click on any domain to view its A records

### Adding A Records
1. Navigate to a domain's DNS page
2. Fill in the "Add New A Record" form:
   - **Name**: Subdomain or `@` for root domain
   - **IPv4 Address**: The IP address to point to
   - **TTL**: Time to live (1 = Auto)
   - **Proxied**: Enable Cloudflare proxy (orange cloud)
3. Click "Add Record"

### Updating Individual Records
1. On the DNS page, modify the fields in any existing record
2. Click "Update" next to that record

### Bulk Updating Records
1. Check the boxes next to multiple A records
2. A yellow "Bulk Update" section appears
3. Enter the new IP address, TTL, and proxy settings
4. Click "Update Selected"
5. Confirm the update

### Deleting Records
1. Click "Delete" next to any record
2. Confirm the deletion

## CLI Usage

The application includes a command-line interface for quick domain and DNS management.

### Setting Up Shell Alias (Recommended)

For easier access, add an alias to your shell configuration:

**For zsh (macOS default):**
```bash
echo "alias cf='php ~/sites/cf-manager/cli.php'" >> ~/.zshrc
source ~/.zshrc
```

**For bash:**
```bash
echo "alias cf='php ~/sites/cf-manager/cli.php'" >> ~/.bashrc
source ~/.bashrc
```

After setting up the alias, you can use the shorter `cf` command:
```bash
cf list
cf info example.com
cf example.com 192.168.1.1
```

### Commands

**List all domains:**
```bash
php cli.php list
# or with alias:
cf list
```

**View domain information:**
```bash
php cli.php info <domain>
# or with alias:
cf info <domain>
```

**Update domain DNS:**
```bash
php cli.php <domain> <ip-address> [options]
# or with alias:
cf <domain> <ip-address> [options]
```

### List Command

View all domains in your Cloudflare account with their nameserver status:

```bash
php cli.php list
```

Output example:
```
DOMAIN                                        NS STATUS
----------------------------------------------------------------------
example.com                                   ✓ Active
newdomain.com                                 ⚠ Pending
another-site.com                              ✓ Active
```

- **✓ Active** - Domain is using Cloudflare nameservers
- **⚠ Pending** - Domain created but nameservers not configured yet

### Info Command

View detailed information about a specific domain including nameservers, DNS records, and SSL status:

```bash
php cli.php info example.com
```

Output includes:
- **Basic Information**: Domain name, Zone ID, status, plan, creation date
- **Nameservers**: All configured nameservers for the domain
- **DNS Records**: All DNS records grouped by type (A, AAAA, MX, CNAME, TXT, etc.)
- **SSL/TLS Status**: Current SSL configuration status

Example output:
```
================================================================================
Domain Information: example.com
================================================================================

BASIC INFORMATION
--------------------------------------------------------------------------------
Domain:                   example.com
Zone ID:                  abc123...
Status:                   Active
Plan:                     Free Plan
Created:                  2024-01-15 10:30:00

NAMESERVERS
--------------------------------------------------------------------------------
  → ns1.cloudflare.com
  → ns2.cloudflare.com

DNS RECORDS (All Types)
--------------------------------------------------------------------------------

A Records (3):
  example.com                              → 192.168.1.1                        TTL: Auto [Proxied]
  www.example.com                          → 192.168.1.1                        TTL: Auto [Proxied]
  mail.example.com                         → 192.168.1.2                        TTL: 3600

MX Records (1):
  example.com                              → mail.example.com                   Priority: 10, TTL: Auto

SSL/TLS STATUS
--------------------------------------------------------------------------------
Status:                   Active
```

### Sub Command (subdomains)

`cf sub <subdomain> <ip>` creates or updates **one** A record for a subdomain, e.g. `gcp.nagpur.pro`:

1. Finds the parent zone by trying each suffix of the name (`gcp.nagpur.pro` → `nagpur.pro`). **It never creates a zone.** If no zone in your account contains the name, it stops without changing anything.
2. Refuses a zone apex (use `cf <domain> <ip>` for `@`/`www`).
3. Refuses if the name already has a CNAME record (an A record can't coexist with it).
4. Creates the A record, or updates it if it exists. If the record already matches, it does nothing.
5. `--dry-run` shows the current and target values without changing anything.

```bash
cf sub gcp.nagpur.pro 103.174.102.137                # proxied, Auto TTL
cf sub api.example.com 192.168.1.1 --no-proxy        # DNS only
cf sub gcp.nagpur.pro 103.174.102.137 --dry-run      # preview only
```

> ⚠️ Don't pass a subdomain to the plain update command (`cf gcp.nagpur.pro <ip>`): when no zone matches the name it **creates a new zone** called `gcp.nagpur.pro`. Use `cf sub` for subdomains.

### Update Command

The DNS update command will:
1. Check if the domain exists in your Cloudflare account
2. If not found, create a new zone (domain) in your account
3. Update or create A records for both `@` (root) and `www` subdomains
4. Display the nameservers you need to configure at your domain registrar

### Examples

List all domains:
```bash
php cli.php list
```

View domain information:
```bash
php cli.php info example.com
```

Update existing domain (proxied by default):
```bash
php cli.php example.com 192.168.1.1
```

Add new domain without proxy:
```bash
php cli.php newdomain.com 192.168.1.1 --no-proxy
```

Set custom TTL (still proxied):
```bash
php cli.php example.com 192.168.1.1 --ttl=3600
```

Disable proxy with custom TTL:
```bash
php cli.php example.com 192.168.1.1 --no-proxy --ttl=3600
```

### CLI Options

- `--no-proxy` - Disable Cloudflare proxy (default: enabled/proxied)
- `--ttl=<seconds>` - Set TTL in seconds (default: 1 for Auto)
- `--dry-run` - (`sub` only) Show what would change without changing it

### CLI Command Reference

| Command | Description | Example |
|---------|-------------|---------|
| `cf list` | List all domains with status | Shows nameserver status |
| `cf info <domain>` | Show detailed domain info | DNS records, NS, SSL |
| `cf <domain> <ip>` | Update @ and www records | Creates zone if needed |
| `cf <domain> <ip> --no-proxy` | Update without proxy | Direct DNS only |
| `cf <domain> <ip> --ttl=3600` | Update with custom TTL | TTL in seconds |
| `cf sub <subdomain> <ip>` | Create/update one subdomain A record | Never creates a zone |
| `cf sub <subdomain> <ip> --dry-run` | Preview a subdomain change | No changes made |

**What the update command does:**
1. Searches for domain in your Cloudflare account
2. If not found, creates new zone (requires Zone.Edit permission)
3. Updates or creates `@` (root) A record
4. Updates or creates `www` A record
5. Shows nameservers if zone was newly created
6. Default: Proxied enabled (orange cloud), TTL Auto

### Output

The CLI provides colored output with:
- ✓ Success messages in green
- ✗ Error messages in red
- ⚠ Warning messages in yellow
- → Info messages in cyan

When creating a new domain, it displays the nameservers you need to configure:
```
==========================================================
Nameservers for example.com:
==========================================================
  ns1.cloudflare.com
  ns2.cloudflare.com
==========================================================
Update your domain registrar to use these nameservers.
```

## Technical Architecture

### File Structure

```
cf-manager/
├── index.php              # Main page - lists all domains
│                          # - Fetches zones with pagination (50/page)
│                          # - Sorts alphabetically
│                          # - Real-time search with JavaScript
│
├── dns.php                # DNS management page for a domain
│                          # - View/add/update/delete A records
│                          # - Bulk update multiple records
│                          # - Handles POST requests for all operations
│
├── cli.php                # CLI tool for domain management
│                          # - list: Show all domains with status
│                          # - info: Detailed domain information
│                          # - update: Create/update @ and www records
│                          # - Colored terminal output
│
├── cf-completion.sh       # Shell tab completion script
│                          # - Autocomplete domain names
│                          # - Autocomplete commands (list, info)
│                          # - Caches domains for 5 minutes
│
├── debug.php              # Debug/test API connection
│                          # - Tests cURL availability
│                          # - Verifies API token
│                          # - Tests zone fetching
│
├── config.example.php     # Configuration template
├── config.php             # Your configuration (gitignored)
│                          # - Contains CF_API_TOKEN
│
├── style.css              # Minimal styling
│                          # - Mobile-responsive
│                          # - No external dependencies
│
├── .gitignore            # Excludes config.php, .claude, temp files
└── README.md             # This file
```

### How It Works

**API Communication:**
- All files use `cfApi()` function for API calls
- Endpoint: `https://api.cloudflare.com/client/v4`
- Authentication: Bearer token in header
- Response: JSON decoded to PHP array

**Web UI Flow:**
1. `index.php` → List all zones
2. Click domain → `dns.php?zone_id=xxx&name=domain.com`
3. `dns.php` → Fetch A records for zone
4. POST actions → Update/delete records → Redirect back

**CLI Flow:**
1. Parse command (`list`, `info`, or domain update)
2. Call appropriate API endpoints
3. Format and display colored output
4. Exit with status code (0 = success, 1 = error)

**Key Functions:**
- `cfApi($endpoint, $method, $data)` - API wrapper
- `findZoneByDomain($domain)` - Search for zone by name
- `createZone($domain)` - Create new zone (CLI only)
- `updateOrCreateRecord()` - Upsert A record
- `output($message, $type)` - Colored CLI output

## API Permissions

The application requires the following Cloudflare API permissions:

| Permission | Access | Purpose |
|------------|--------|---------|
| Zone | Read | List all domains |
| Zone | Edit | Create new domains (CLI only) |
| DNS | Edit | View and modify DNS records |

Note: The CLI tool requires `Zone.Edit` permission to create new domains.

## Development

### Running in Development
```bash
php -S localhost:8000
```

### Testing API Connection
Visit `http://localhost:8000/debug.php` to test your API connection and troubleshoot issues.

### Requirements
- PHP 7.4+ (tested up to PHP 8.3)
- Extensions: cURL, JSON
- Cloudflare account

## Security Considerations

1. **Never commit `config.php`** - It contains your API token
2. **Use API tokens, not API keys** - Tokens have better security and scoping
3. **Restrict token permissions** - Only grant necessary permissions
4. **Add authentication** - If exposing to the internet, add HTTP authentication
5. **Use HTTPS** - Always run behind HTTPS in production
6. **Consider IP restrictions** - Limit API token to specific IPs if possible

### Production Deployment

For production use, consider:

```nginx
# Nginx example with HTTP auth
location / {
    auth_basic "Restricted";
    auth_basic_user_file /etc/nginx/.htpasswd;

    # PHP-FPM configuration
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    include fastcgi_params;
}
```

## Troubleshooting

### Common Issues

**No domains showing in web UI:**
1. Check `config.php` exists and has valid token
2. Verify API token has `Zone.Zone.Read` permission
3. Visit `http://localhost:8000/debug.php` to test API connection
4. Check PHP error logs: `tail -f /var/log/php/error.log`

**CLI alias not working:**
```bash
# Reload shell configuration
source ~/.zshrc  # or source ~/.bashrc

# Verify alias exists
alias | grep cf

# Test without alias
php ~/sites/cf-manager/cli.php list
```

**cURL errors:**
```bash
# Check if cURL is enabled
php -m | grep curl

# If not enabled, install it:
# macOS with Homebrew:
brew install php-curl

# Ubuntu/Debian:
sudo apt-get install php-curl
```

**API Permission errors:**
- Error: "Insufficient permissions" → Add `Zone.DNS.Edit` to token
- Error: "Zone not found" → Add `Zone.Zone.Read` to token
- Error: "Cannot create zone" → Add `Zone.Zone.Edit` to token (CLI only)

**Token expired or invalid:**
```bash
# Test token validity
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Wrong API endpoint (returns 0 domains):**
- ✗ Wrong: `https://api.cloudflare.com/v4`
- ✓ Correct: `https://api.cloudflare.com/client/v4`

### Debug Mode

To enable debug output in `index.php`, uncomment line 29:
```php
// Debug: uncomment to see API response
echo "<pre>HTTP Code: $httpCode\nError: $error\nResponse: " . print_r($decoded, true) . "</pre>";
```

## Limitations

- Only manages A records (not AAAA, CNAME, MX, etc.)
- Fetches up to 100 A records per domain
- Requires modern browser with JavaScript enabled

## Roadmap

- [ ] Support for other DNS record types (AAAA, CNAME, MX)
- [ ] Export/import DNS records
- [ ] Bulk domain operations
- [ ] Record search/filter within domain
- [ ] API rate limiting indicators

## Contributing

Contributions are welcome! This is a minimal tool, but improvements are always appreciated.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

MIT License - feel free to use this for personal or commercial projects.

## Author

Built with ❤️ for managing multiple Cloudflare domains efficiently.

## Acknowledgments

- Uses [Cloudflare API v4](https://developers.cloudflare.com/api/)
- No external PHP dependencies
- Vanilla JavaScript for interactivity

---

## Quick Start Cheat Sheet

### Essential Commands

```bash
# List all domains
cf list

# Show domain details
cf info example.com

# Update domain (proxied, auto TTL)
cf example.com 192.168.1.1

# Update without proxy
cf example.com 192.168.1.1 --no-proxy

# Update with custom TTL
cf example.com 192.168.1.1 --ttl=3600

# Start web UI
php -S localhost:8000

# Test API connection
open http://localhost:8000/debug.php
```

### File Locations

```
Config:     ~/sites/cf-manager/config.php
Web UI:     http://localhost:8000
Debug:      http://localhost:8000/debug.php
CLI:        ~/sites/cf-manager/cli.php (or just 'cf')
Alias:      ~/.zshrc or ~/.bashrc
```

### API Token Setup

1. Visit: https://dash.cloudflare.com/profile/api-tokens
2. Create token → "Edit zone DNS" template
3. Copy token to `config.php`
4. Test: `cf list`

### Required Permissions

- `Zone.Zone.Read` - List domains
- `Zone.DNS.Edit` - Manage DNS records
- `Zone.Zone.Edit` - Create new zones (CLI only)

### Common Operations

**Add new domain to Cloudflare:**
```bash
cf newdomain.com 192.168.1.1
# Creates zone + @ and www records
# Shows nameservers to configure
```

**Update multiple domains:**
```bash
for domain in site1.com site2.com site3.com; do
  cf $domain 192.168.1.1
done
```

**Check domain status:**
```bash
cf list | grep -i "domain.com"
```

**View all DNS records:**
```bash
cf info domain.com
```

### Troubleshooting One-Liners

```bash
# Check PHP version
php -v

# Check cURL extension
php -m | grep curl

# Test API token
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# Reload shell config
source ~/.zshrc

# Check if alias exists
alias | grep cf
```
