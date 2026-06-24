# Admin Dashboard Test Suite

Automated integration tests for the CMS admin dashboard.

## Quick Start

Ensure the local server is running:
```bash
php -S localhost:8000 -t /path/to/mouazalsabagh-com &
brew services start mysql
```

Then run the test suite:
```bash
bash scripts/test-admin-dashboard.sh
```

## What Gets Tested

- **Server Connectivity** — Verify the PHP server responds
- **Login Page & CSRF** — Test CSRF token generation
- **Authentication** — Verify login succeeds with correct credentials
- **Dashboard Access** — Test authenticated dashboard loads
- **API Authentication** — Verify 403 on unauth, 200 on auth
- **Session Persistence** — Test multiple page loads within session
- **Database Connectivity** — Verify DB queries work
- **File Upload Permissions** — Check uploads directory is writable
- **Database Tables** — Verify tables exist (no SQL errors)
- **CSRF Protection** — Verify missing CSRF tokens are blocked
- **Logout** — Verify session is destroyed

## Configuration

Override defaults:
```bash
BASE_URL=http://localhost:3000 \
TEST_PASSWORD=MyPassword \
bash scripts/test-admin-dashboard.sh
```

## Exit Codes

- `0` — All tests passed
- `1` — One or more tests failed

## Integration with CI/CD

Add to GitHub Actions, GitLab CI, or similar:
```yaml
- name: Run admin dashboard tests
  run: |
    brew services start mysql
    php -S localhost:8000 &
    sleep 2
    bash scripts/test-admin-dashboard.sh
```
