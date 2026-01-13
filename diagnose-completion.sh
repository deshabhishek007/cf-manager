#!/bin/zsh
# Diagnostic script for cf completion

echo "🔍 Diagnosing CF Completion Setup"
echo "=========================================="
echo ""

# Check if Oh My Zsh is loaded
if [ -n "$ZSH" ]; then
    echo "✓ Oh My Zsh detected: $ZSH"
else
    echo "✗ Oh My Zsh not detected"
fi

# Check if compdef exists
if type compdef &>/dev/null; then
    echo "✓ compdef is available"
else
    echo "✗ compdef not available"
fi

# Check if cf alias exists
if alias cf &>/dev/null; then
    echo "✓ cf alias is set: $(alias cf)"
else
    echo "✗ cf alias not set"
fi

# Check if completion script was sourced
if type _cf_zsh &>/dev/null; then
    echo "✓ _cf_zsh function is loaded"
else
    echo "✗ _cf_zsh function not loaded"
    echo ""
    echo "Attempting to load completion script..."
    source ~/sites/cf-manager/cf-completion.sh
    if type _cf_zsh &>/dev/null; then
        echo "✓ Successfully loaded after manual source"
    else
        echo "✗ Failed to load completion script"
        echo "  Check: ~/sites/cf-manager/cf-completion.sh"
        exit 1
    fi
fi

# Check if completion is registered for cf
echo ""
echo "Checking completion registration..."
# Try to get completion for cf
setopt local_options extended_glob
if (( ${+_comps[cf]} )); then
    echo "✓ Completion registered for 'cf': $_comps[cf]"
else
    echo "⚠ Completion not yet registered"
    echo "  This is normal if running in subshell"
    echo "  In your main shell, completions should work"
fi

# Test domain fetching
echo ""
echo "Testing domain list..."
if type _cf_get_domains &>/dev/null; then
    domains=$(_cf_get_domains 2>/dev/null | wc -l | tr -d ' ')
    if [ "$domains" -gt 0 ]; then
        echo "✓ Can fetch $domains domains"
        echo ""
        echo "Sample domains:"
        _cf_get_domains 2>/dev/null | head -3 | sed 's/^/  - /'
    else
        echo "⚠ Domain fetch returned 0 domains"
        echo "  Check: cf list"
    fi
else
    echo "✗ _cf_get_domains function not found"
fi

echo ""
echo "=========================================="
echo ""
echo "📋 Summary:"
echo ""
echo "If all checks pass, try in your main shell:"
echo "  cf info <Tab><Tab>"
echo ""
echo "If completion doesn't work:"
echo "1. Close ALL terminal windows/tabs"
echo "2. Open a new terminal"
echo "3. Try: cf info <Tab><Tab>"
echo ""
echo "Or force reload: exec zsh"
