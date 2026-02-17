#!/bin/bash
# Session Health Check Script
# Runs every 5 minutes via CRON
# Checks for hung sessions and restarts gateway if needed

GATEWAY_URL="http://localhost:18789"
GATEWAY_TOKEN="12d428196a8bd886132da350e6c78c3535b124379c98aaeb"
SESSIONS_FILE="$HOME/.openclaw/agents/main/sessions/sessions.json"
LOG_FILE="$HOME/.openclaw/logs/session-health.log"

# Create logs directory if not exists
mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date -Iseconds)] $1" >> "$LOG_FILE"
}

# Check if gateway is running
check_gateway() {
    if curl -s -o /dev/null -w "%{http_code}" "$GATEWAY_URL/health" 2>/dev/null | grep -q "200"; then
        return 0
    else
        return 1
    fi
}

# Check for hung sessions
check_sessions() {
    if [ ! -f "$SESSIONS_FILE" ]; then
        log "Sessions file not found"
        return 0
    fi
    
    # Check for abortedLastRun or stale sessions (updatedAt > 30 min ago)
    local now=$(date +%s%3N)
    local threshold=$((30 * 60 * 1000))  # 30 min in ms
    
    # Use jq if available, otherwise skip detailed check
    if command -v jq &> /dev/null; then
        local stale=$(jq -r --argjson now "$now" --argjson threshold "$threshold" '
            to_entries[] | 
            select(.value.abortedLastRun == true or (($now - .value.updatedAt) > $threshold)) |
            .key
        ' "$SESSIONS_FILE" 2>/dev/null)
        
        if [ -n "$stale" ]; then
            log "Found stale/hung sessions: $stale"
            return 1
        fi
    fi
    
    return 0
}

# Restart gateway
restart_gateway() {
    log "Restarting gateway due to health check failure"
    
    # Try graceful restart via API
    curl -s -X POST "$GATEWAY_URL/restart" \
        -H "Authorization: Bearer $GATEWAY_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"reason": "Session health check - hung session detected"}' \
        2>/dev/null
    
    # If API restart fails, try signal (Unix only)
    if [ $? -ne 0 ]; then
        log "API restart failed, attempting signal-based restart"
        pkill -USR1 -f "openclaw gateway" 2>/dev/null || true
    fi
}

# Main logic
log "Starting health check"

if ! check_gateway; then
    log "Gateway not responding, attempting restart"
    restart_gateway
elif ! check_sessions; then
    log "Hung sessions detected, attempting restart"
    restart_gateway
else
    log "Health check passed"
fi
