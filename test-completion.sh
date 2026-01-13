#!/bin/zsh
# Test script for cf tab completion

echo "🧪 Testing CF Tab Completion"
echo "======================================"
echo ""

# Check if completion function is loaded
if type _cf_zsh &>/dev/null; then
    echo "✓ Zsh completion function (_cf_zsh) is loaded"
else
    echo "✗ Zsh completion function not found"
    echo "  Try: exec zsh"
    exit 1
fi

# Check if compdef registered cf
if compdef -p | grep -q "^_cf_zsh cf$"; then
    echo "✓ Completion is registered for 'cf' command"
else
    echo "✗ Completion not registered"
    exit 1
fi

# Test domain fetching
echo ""
echo "Testing domain list fetch..."
domains=$(_cf_get_domains 2>/dev/null | wc -l | tr -d ' ')
if [ "$domains" -gt 0 ]; then
    echo "✓ Successfully fetched $domains domains"
else
    echo "✗ No domains fetched"
    echo "  Check: cf list"
    exit 1
fi

# Show sample domains
echo ""
echo "Sample domains available for completion:"
_cf_get_domains 2>/dev/null | head -5 | sed 's/^/  - /'
echo "  ..."

echo ""
echo "✅ Completion is working!"
echo ""
echo "Try these commands:"
echo "  cf <Tab><Tab>          # Shows commands and domains"
echo "  cf info <Tab><Tab>     # Shows all domains"
echo "  cf blog<Tab>           # Autocomplete partial match"
