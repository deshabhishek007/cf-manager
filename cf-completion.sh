#!/bin/bash
# Bash/Zsh completion script for cf (Cloudflare DNS Manager)
#
# Installation:
# Add to ~/.zshrc or ~/.bashrc:
#   source ~/sites/cf-manager/cf-completion.sh

# Function to get domains (used by both bash and zsh)
_cf_get_domains() {
    local cache_file="/tmp/.cf_domains_cache"
    local cache_time=300  # 5 minutes

    # Check if cache exists and is recent
    if [ -f "$cache_file" ] && [ $(($(date +%s) - $(stat -f %m "$cache_file" 2>/dev/null || stat -c %Y "$cache_file" 2>/dev/null))) -lt $cache_time ]; then
        cat "$cache_file"
    else
        # Fetch domains and cache them (filter out headers and formatting)
        php ~/sites/cf-manager/cli.php list 2>/dev/null | \
            grep -v "DOMAIN" | \
            grep -v "^$" | \
            grep -v "====" | \
            grep -v "Found" | \
            grep -v "Fetching" | \
            grep -v "Cloudflare" | \
            awk '{print $1}' | \
            grep '\.' > "$cache_file" 2>/dev/null
        cat "$cache_file"
    fi
}

# Zsh completion
if [ -n "$ZSH_VERSION" ]; then
    # Don't reinitialize compinit if already done (e.g., by Oh My Zsh)
    # Just ensure it's loaded
    if ! type compdef &>/dev/null; then
        autoload -Uz compinit
        compinit -i
    fi

    _cf_zsh() {
        local -a commands domains options
        commands=('list:List all domains with NS status' 'info:Show detailed domain information' 'sub:Create/update one subdomain A record (never creates a zone)')
        options=('--no-proxy:Disable Cloudflare proxy' '--ttl=:Set TTL in seconds' '--dry-run:Show changes without applying (sub)')

        local curcontext="$curcontext" state line
        typeset -A opt_args

        _arguments -C \
            '1: :->command' \
            '2: :->arg' \
            '*: :->options'

        case $state in
            command)
                # First argument: commands or domain names
                local -a all_options
                all_options=($commands)

                # Add domains
                local domain_list
                domain_list=$(_cf_get_domains)
                if [ -n "$domain_list" ]; then
                    while IFS= read -r domain; do
                        all_options+=("$domain:Update domain DNS")
                    done <<< "$domain_list"
                fi

                _describe -t commands 'cf commands' all_options
                ;;
            arg)
                case $words[2] in
                    info)
                        # After 'info', complete with domain names
                        local -a domain_options
                        local domain_list
                        domain_list=$(_cf_get_domains)
                        if [ -n "$domain_list" ]; then
                            while IFS= read -r domain; do
                                domain_options+=("$domain")
                            done <<< "$domain_list"
                        fi
                        _describe -t domains 'domains' domain_options
                        ;;
                    list)
                        # No completion after list
                        ;;
                    *)
                        # After domain name, expect IP address (no completion)
                        ;;
                esac
                ;;
            options)
                # Third argument onwards: options
                _describe -t options 'options' options
                ;;
        esac
    }

    compdef _cf_zsh cf

# Bash completion
else
    _cf_completion() {
        local cur prev commands
        COMPREPLY=()
        cur="${COMP_WORDS[COMP_CWORD]}"
        prev="${COMP_WORDS[COMP_CWORD-1]}"

        # Main commands
        commands="list info sub"

        # If we're completing the first argument
        if [ $COMP_CWORD -eq 1 ]; then
            # Get domains
            local domains=$(_cf_get_domains | tr '\n' ' ')

            # Combine commands and domains for completion
            COMPREPLY=( $(compgen -W "${commands} ${domains}" -- ${cur}) )
            return 0
        fi

        # If previous word is 'info', complete with domain names
        if [ "$prev" = "info" ]; then
            local domains=$(_cf_get_domains | tr '\n' ' ')
            COMPREPLY=( $(compgen -W "${domains}" -- ${cur}) )
            return 0
        fi

        # If we're on the third argument (after domain and IP), suggest options
        if [ $COMP_CWORD -eq 3 ]; then
            COMPREPLY=( $(compgen -W "--no-proxy --ttl= --dry-run" -- ${cur}) )
            return 0
        fi
    }

    # Register the completion function
    complete -F _cf_completion cf
fi
