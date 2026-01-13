#!/bin/zsh
# Test Oh My Zsh plugin for cf-manager

echo "🧪 Testing CF Manager Oh My Zsh Plugin"
echo "=========================================="
echo ""

# Reload zshrc to load the plugin
source ~/.zshrc

echo "✓ Reloaded .zshrc with cf-manager plugin"
echo ""

# Check if Oh My Zsh is loaded
if [ -n "$ZSH" ]; then
    echo "✓ Oh My Zsh: $ZSH"
else
    echo "✗ Oh My Zsh not loaded"
    exit 1
fi

# Check if compdef exists
if type compdef &>/dev/null; then
    echo "✓ compdef is available"
else
    echo "✗ compdef not available"
    exit 1
fi

# Check if cf alias exists
if alias cf &>/dev/null; then
    echo "✓ cf alias: $(alias cf)"
else
    echo "✗ cf alias not set"
    exit 1
fi

# Check if completion function exists
if type _cf_zsh &>/dev/null; then
    echo "✓ _cf_zsh completion function loaded"
else
    echo "✗ _cf_zsh function not loaded"
    exit 1
fi

# Check if completion is registered
if (( ${+_comps[cf]} )); then
    echo "✓ Completion registered: $_comps[cf]"
else
    echo "✗ Completion not registered"
    exit 1
fi

# Test domain fetching
echo ""
echo "Testing domain fetch..."
domains=$(_cf_get_domains 2>/dev/null | wc -l | tr -d ' ')
if [ "$domains" -gt 0 ]; then
    echo "✓ Fetched $domains domains"
    echo ""
    echo "Sample domains:"
    _cf_get_domains 2>/dev/null | head -5 | sed 's/^/  - /'
else
    echo "⚠ No domains fetched"
fi

echo ""
echo "=========================================="
echo "✅ ALL CHECKS PASSED!"
echo ""
echo "Tab completion is now active. Try:"
echo "  cf <Tab><Tab>"
echo "  cf info <Tab><Tab>"
echo "  cf blog<Tab>"
