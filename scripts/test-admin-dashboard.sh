#!/bin/bash
##############################################################################
# test-admin-dashboard.sh
# Automated integration tests for the admin dashboard
# Tests: login flow, auth, API endpoints, session persistence
##############################################################################

# Note: NOT using 'set -e' to allow all tests to run even if one fails

# ── CONFIG ─────────────────────────────────────────────────────────────────
BASE_URL="${BASE_URL:-http://localhost:8000}"
TEST_PASSWORD="${TEST_PASSWORD:-MouazAdmin#2026}"
COOKIES_FILE=$(mktemp)
CSRF_TOKEN=""
PASS_COUNT=0
FAIL_COUNT=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ── HELPERS ────────────────────────────────────────────────────────────────
log_pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASS_COUNT++))
}

log_fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((FAIL_COUNT++))
}

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_section() {
    echo ""
    echo -e "${YELLOW}━━━ $1 ━━━${NC}"
}

cleanup() {
    rm -f "$COOKIES_FILE"
}

# Wrapper to run test functions and catch errors
run_test() {
    local test_func="$1"
    $test_func || true  # Always continue even if test fails
}

trap cleanup EXIT

# ── TEST: Server Connectivity ──────────────────────────────────────────────
test_server_connectivity() {
    log_section "Server Connectivity"
    
    if curl -s -f "$BASE_URL/rec/collect.php?action=admin" > /dev/null 2>&1; then
        log_pass "Server is reachable at $BASE_URL"
        return 0
    else
        log_fail "Server is NOT reachable at $BASE_URL"
        return 1
    fi
}

# ── TEST: Login Page & CSRF ────────────────────────────────────────────────
test_login_page_and_csrf() {
    log_section "Login Page & CSRF Token Generation"
    
    # Fetch login page
    local login_html=$(curl -s -c "$COOKIES_FILE" "$BASE_URL/rec/collect.php?action=admin")
    
    # Extract CSRF token
    CSRF_TOKEN=$(echo "$login_html" | grep -oE 'name="csrf" value="[^"]+' | sed 's/.*value="//' || echo "")
    
    if [ -z "$CSRF_TOKEN" ]; then
        log_fail "CSRF token not found in login page"
        return 1
    else
        log_pass "CSRF token generated: ${CSRF_TOKEN:0:16}..."
    fi
    
    if echo "$login_html" | grep -q 'Admin Login'; then
        log_pass "Login form rendered correctly"
        return 0
    else
        log_fail "Login form not found in HTML"
        return 1
    fi
}

# ── TEST: Authentication (Login) ───────────────────────────────────────────
test_login() {
    log_section "Authentication (Login)"
    
    if [ -z "$CSRF_TOKEN" ]; then
        log_fail "CSRF token is empty, cannot test login"
        return 1
    fi
    
    # Attempt login
    local response=$(curl -s -L -b "$COOKIES_FILE" -c "$COOKIES_FILE" \
        --data-urlencode "csrf=$CSRF_TOKEN" \
        --data-urlencode "password=$TEST_PASSWORD" \
        "$BASE_URL/rec/collect.php?action=login" -w "\n%{http_code}")
    
    local http_code=$(echo "$response" | tail -n1)
    
    if [ "$http_code" == "200" ]; then
        log_pass "Login successful (HTTP 200)"
        return 0
    else
        log_fail "Login failed (HTTP $http_code)"
        return 1
    fi
}

# ── TEST: Dashboard Access ─────────────────────────────────────────────────
test_dashboard_access() {
    log_section "Dashboard Access (Authenticated)"
    
    # Access admin dashboard
    local response=$(curl -s -b "$COOKIES_FILE" "$BASE_URL/rec/admin/" -w "\n%{http_code}")
    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" == "200" ]; then
        log_pass "Admin dashboard HTTP 200"
    else
        log_fail "Admin dashboard HTTP $http_code"
        return 1
    fi
    
    if echo "$body" | grep -q 'mouaz.*admin'; then
        log_pass "Dashboard brand found"
    else
        log_fail "Dashboard brand not found"
    fi
    
    if echo "$body" | grep -q 'Dashboard\|Recommendations\|Pages'; then
        log_pass "Dashboard navigation links found"
    else
        log_fail "Dashboard navigation links not found"
    fi
}

# ── TEST: API Authentication ───────────────────────────────────────────────
test_api_auth() {
    log_section "API Authentication & Authorization"
    
    # Test unauthenticated API call (should redirect to login)
    local response_unauth=$(curl -s -X POST -d 'action=export_recommendations' \
        "$BASE_URL/rec/admin/api.php" -w "\n%{http_code}")
    local http_code_unauth=$(echo "$response_unauth" | tail -n1)
    
    if [ "$http_code_unauth" == "302" ] || [ "$http_code_unauth" == "403" ]; then
        log_pass "Unauthenticated API request blocked (HTTP $http_code_unauth)"
    else
        log_fail "Unauthenticated API request should block (302/403), got HTTP $http_code_unauth"
    fi
    
    # Test authenticated API call (should succeed with JSON response)
    local response_auth=$(curl -s -b "$COOKIES_FILE" -X POST -d 'action=export_recommendations' \
        "$BASE_URL/rec/admin/api.php" -w "\n%{http_code}")
    local http_code_auth=$(echo "$response_auth" | tail -n1)
    local body_auth=$(echo "$response_auth" | sed '$d')
    
    if [ "$http_code_auth" == "200" ]; then
        log_pass "Authenticated API request successful (HTTP 200)"
    else
        log_fail "Authenticated API request failed (HTTP $http_code_auth)"
        return 1
    fi
    
    # Check for valid JSON response (success can be true or false depending on data state)
    if echo "$body_auth" | grep -q '"success":'; then
        log_pass "API returned valid JSON response"
        return 0
    else
        log_fail "API response invalid: $(echo "$body_auth" | head -c 100)..."
        return 1
    fi
}

# ── TEST: Session Persistence ─────────────────────────────────────────────
test_session_persistence() {
    log_section "Session Persistence"
    
    # Make multiple authenticated requests
    local urls=(
        "/rec/admin/?page=dashboard"
        "/rec/admin/?page=recommendations"
        "/rec/admin/?page=pages"
        "/rec/admin/?page=media"
    )
    
    for url in "${urls[@]}"; do
        local response=$(curl -s -b "$COOKIES_FILE" "$BASE_URL$url" -w "\n%{http_code}")
        local http_code=$(echo "$response" | tail -n1)
        
        if [ "$http_code" == "200" ]; then
            log_pass "Session persisted for $url"
        else
            log_fail "Session lost for $url (HTTP $http_code)"
        fi
    done
}

# ── TEST: Logout ───────────────────────────────────────────────────────────
test_logout() {
    log_section "Logout & Session Termination"
    
    # Logout
    curl -s -b "$COOKIES_FILE" -c "$COOKIES_FILE" "$BASE_URL/rec/collect.php?action=logout" > /dev/null
    
    # Try to access protected page (should redirect/fail)
    local response=$(curl -s -b "$COOKIES_FILE" "$BASE_URL/rec/admin/api.php" \
        -X POST -d 'action=export_recommendations' -w "\n%{http_code}")
    local http_code=$(echo "$response" | tail -n1)
    
    if [ "$http_code" == "403" ] || [ "$http_code" == "302" ]; then
        log_pass "Session terminated, protected endpoint returns $http_code"
        return 0
    else
        log_fail "Session did not terminate properly (HTTP $http_code)"
        return 1
    fi
}

# ── TEST: Database Connectivity ────────────────────────────────────────────
test_database_connectivity() {
    log_section "Database Connectivity"
    
    # Re-login for fresh session
    local login_html=$(curl -s -c "$COOKIES_FILE" "$BASE_URL/rec/collect.php?action=admin")
    local token=$(echo "$login_html" | grep -oE 'name="csrf" value="[^"]+' | sed 's/.*value="//')
    
    curl -s -L -b "$COOKIES_FILE" -c "$COOKIES_FILE" \
        --data-urlencode "csrf=$token" \
        --data-urlencode "password=$TEST_PASSWORD" \
        "$BASE_URL/rec/collect.php?action=login" > /dev/null
    
    # Fetch page with database query
    local response=$(curl -s -b "$COOKIES_FILE" "$BASE_URL/rec/admin/?page=recommendations" -w "\n%{http_code}")
    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" == "200" ]; then
        log_pass "Database queries successful (HTTP 200)"
    else
        log_fail "Database queries failed (HTTP $http_code)"
        return 1
    fi
    
    if echo "$body" | grep -q 'Recommendations\|recommendations'; then
        log_pass "Recommendations page rendered"
    else
        log_fail "Recommendations page not rendered properly"
    fi
}

# ── TEST: File Upload Permissions ──────────────────────────────────────────
test_upload_permissions() {
    log_section "File Upload Permissions"
    
    local upload_dir="/Users/m3aze/Documents/GitHub/mouazalsabagh-com/rec/admin/uploads"
    
    if [ ! -d "$upload_dir" ]; then
        log_fail "Upload directory does not exist: $upload_dir"
        return 1
    fi
    
    if [ -w "$upload_dir" ]; then
        log_pass "Upload directory is writable"
        return 0
    else
        log_fail "Upload directory is not writable (permissions issue)"
        return 1
    fi
}

# ── TEST: Database Tables Exist ────────────────────────────────────────────
test_database_tables() {
    log_section "Database Tables"
    
    # This is a basic check - verify the dashboard loads without DB errors
    local response=$(curl -s -b "$COOKIES_FILE" "$BASE_URL/rec/admin/?page=dashboard" -w "\n%{http_code}")
    local body=$(echo "$response" | sed '$d')
    
    if echo "$body" | grep -q 'Fatal error\|PDOException'; then
        log_fail "Database errors detected in dashboard"
        return 1
    else
        log_pass "Dashboard loaded without database errors"
    fi
}

# ── TEST: CSRF Protection ──────────────────────────────────────────────────
test_csrf_protection() {
    log_section "CSRF Protection"
    
    # Try to POST with missing CSRF token
    local response=$(curl -s -X POST -d 'action=export_recommendations' \
        "$BASE_URL/rec/admin/api.php" -w "\n%{http_code}")
    local http_code=$(echo "$response" | tail -n1)
    
    if [ "$http_code" == "403" ] || [ "$http_code" == "302" ]; then
        log_pass "Missing CSRF/auth blocked (HTTP $http_code)"
        return 0
    else
        log_fail "Missing CSRF token should be blocked, got HTTP $http_code"
        return 1
    fi
}

# ── MAIN EXECUTION ─────────────────────────────────────────────────────────
main() {
    echo -e "${BLUE}"
    cat << "EOF"
╔══════════════════════════════════════════════════════════════════════════════╗
║                      ADMIN DASHBOARD TEST SUITE                             ║
║                                                                              ║
║ Testing login flow, authentication, API endpoints, and session management   ║
╚══════════════════════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
    
    log_info "Target URL: $BASE_URL"
    log_info "Testing with credentials: admin / [password hidden]"
    echo ""
    
    # Run all tests (continue even if some fail)
    run_test test_server_connectivity
    run_test test_login_page_and_csrf
    run_test test_login
    run_test test_dashboard_access
    run_test test_api_auth
    run_test test_session_persistence
    run_test test_database_connectivity
    run_test test_upload_permissions
    run_test test_database_tables
    run_test test_csrf_protection
    run_test test_logout
    
    # ─── SUMMARY ───────────────────────────────────────────────────────────
    log_section "Test Summary"
    
    local total=$((PASS_COUNT + FAIL_COUNT))
    if [ "$total" -eq 0 ]; then
        echo -e "${YELLOW}No tests were run.${NC}"
        return 1
    fi
    
    local pass_pct=$((PASS_COUNT * 100 / total))
    
    if [ "$FAIL_COUNT" -eq 0 ]; then
        echo -e "${GREEN}✓ All $PASS_COUNT tests passed!${NC}"
        return 0
    else
        echo -e "${GREEN}✓ $PASS_COUNT passed${NC}, ${RED}✗ $FAIL_COUNT failed${NC} (${YELLOW}${pass_pct}%${NC} pass rate)"
        return 1
    fi
}

# Run tests if script is executed directly
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
    exit $?
fi
