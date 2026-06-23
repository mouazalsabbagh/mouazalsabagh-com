# CMS Admin Dashboard — Setup Guide

## Quick Start

The admin dashboard is **ready to use immediately**. It integrates seamlessly with your existing recommendations form and uses the same login credentials.

### Step 1: Access the Dashboard

After you've logged into `/rec/collect.php?action=admin`, you can access the full admin dashboard at:

```
https://localhost:8000/rec/admin/
```

or in production:

```
https://yourdomain.com/rec/admin/
```

### Step 2: Initialize Database

The dashboard **automatically creates all necessary tables** on first access:
- `admin_users` - For future multi-user management
- `media` - Tracks uploaded files
- `audit_log` - Action history
- `portfolio_items` - Enhanced page management

**Note:** Your existing `recommendations` and `pages` tables remain unchanged for backward compatibility.

### Step 3: Start Managing Content

#### Create Your First Page
1. Click **"New Page"** button
2. Fill in title and content
3. Click **"Save as Draft"** to preview
4. Click **"Publish"** when ready
5. Copy the card snippet to your gallery pages

#### Upload Media
1. Click **Media** in sidebar
2. Click **"Upload"** button
3. Select a WebP, PNG, JPG, MP4, or PDF file
4. Add alt text
5. Click **"Copy URL"** to use in pages

#### Review Recommendations
1. Click **Recommendations** in sidebar
2. View submissions grouped by status
3. Update status as you process them
4. Export CSV for backup

## Integration with Existing Code

### Shared Authentication
The admin dashboard shares the same session and login as `collect.php`:

```php
// Both use the same session variables:
$_SESSION['admin_auth'] = true;
$_SESSION['admin_username'] = 'admin';
$_SESSION['csrf'] = 'token...';
```

### Backward Compatibility
- Your existing `collect.php` continues to work unchanged
- Legacy `pages.php` still functions (new content goes to `portfolio_items`)
- All database migrations are additive—nothing is deleted

### Path References
The dashboard references assets like the rest of your site:

```
/rec/admin/index.php          → Main dashboard
/rec/admin/uploads/           → Media files
/rec/admin/api.php            → AJAX endpoints
```

## Configuration

### Enable Multi-User Support (Optional)

Currently, the dashboard uses the shared admin login from `config.php`. To enable per-user accounts:

#### 1. Modify `config.php`

```php
// Old (single password):
define('ADMIN_PASS_HASH', '$2y$12$...');

// New (multi-user - optional):
define('USE_ADMIN_USERS', true);
```

#### 2. Create First Admin User

```php
// Once in your database:
INSERT INTO admin_users (username, email, password_hash, role, is_active)
VALUES ('admin', 'admin@example.com', 
        PASSWORD_HASH('your-password', PASSWORD_DEFAULT), 
        'admin', 1);
```

#### 3. Modify login flow in `collect.php`

Add this check after CSRF validation:

```php
// If multi-user enabled, check admin_users table instead
if (defined('USE_ADMIN_USERS') && USE_ADMIN_USERS) {
    $db = getDB();
    $admin = $db->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1")
        ->execute([$username])->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_user_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
    }
}
```

## Features by Role

### Viewer (Read-only)
- ✅ View dashboard stats
- ✅ View recommendations (no edit)
- ✅ View pages
- ✅ View media library
- ✅ View analytics
- ❌ Cannot create/edit/delete
- ❌ Cannot manage users

### Editor
- ✅ All Viewer permissions
- ✅ Create/edit pages
- ✅ Create/edit recommendations
- ✅ Upload media
- ✅ Delete own content
- ✅ Change status/tags
- ❌ Cannot delete other users' content
- ❌ Cannot manage accounts

### Admin
- ✅ All Editor permissions
- ✅ Delete any content
- ✅ Manage user accounts
- ✅ View audit logs
- ✅ Sensitive operations

## Directory Permissions

Ensure the media upload directory is writable:

```bash
# Local development
chmod 755 rec/admin/uploads/

# Production (cPanel)
Right-click folder → Change Permissions → 755
```

## Security Checklist

Before deploying to production:

- [ ] Rotate admin password (`password_hash()` new one)
- [ ] Update `$ALLOWED_ORIGINS` in `collect.php` to your domain only
- [ ] Ensure HTTPS is enabled
- [ ] Test `.htaccess` blocks PHP execution in `/uploads/`
- [ ] Verify `config.php` returns 403 (git-ignored, not accessible)
- [ ] Check database backups are working
- [ ] Enable audit logging (automatic)
- [ ] Set strong passwords for all admin users

## Troubleshooting

### Dashboard shows "Insufficient permissions"

**Cause:** Your session role is not set or has expired.

**Fix:** Log out of `collect.php` and log back in. Your session should transfer to the dashboard.

### "Database connection failed" error

**Cause:** MySQL isn't running or credentials in `config.php` are wrong.

**Fix:**
```bash
# macOS/Homebrew
brew services start mysql

# Linux/Docker
docker start mysql

# Windows/manual
# Start MySQL from Services
```

### Media upload fails with 403

**Cause:** `uploads/` directory isn't writable.

**Fix:**
```bash
chmod 755 rec/admin/uploads/
# or via cPanel → File Manager → Permissions
```

### Can't see new pages live

**Cause:** Incorrect path depth or file not created in right folder.

**Fix:**
- Case studies: `/work/case-study/{slug}.html`
- Project previews: `/work/project-preview/{slug}.html`
- Root pages: `/{slug}.html`
- All drafts: `/work/_drafts/{slug}.html` (with `noindex` tag)

## Next Steps

1. **Test locally** - Create a draft page and verify it works
2. **Upload media** - Try uploading an image
3. **Review a recommendation** - Change its status
4. **Export data** - Run a CSV export for backup
5. **Deploy to production** - Follow the handbook's deployment guide
6. **Invite team** - Add editor/viewer accounts (when multi-user is enabled)

## Advanced Usage

### Creating Custom Page Types

Edit `pages/pages.php` to add more page types:

```php
// Line ~20, add new type:
$types = ['case-study', 'project-preview', 'page', 'blog-post'];  // ← add 'blog-post'
```

Then update the template handling in `lib.php` if needed.

### Bulk Operations

The AJAX API supports bulk actions. Examples:

```javascript
// Mark all 'new' recommendations as reviewed
fetch('/rec/admin/api.php', {
    method: 'POST',
    body: new FormData(form)
}).then(r => r.json()).then(d => console.log(d));
```

### Custom Audit Logging

All admin actions are logged to `audit_log` table. Use for:

```php
// Query audit log
$changes = $db->query("
    SELECT * FROM audit_log 
    WHERE action='publish' AND entity_type='page' 
    ORDER BY logged_at DESC
")->fetchAll();
```

## Support & Documentation

- **Full handbook:** `/handbook/index.html`
- **API reference:** `rec/admin/README.md`
- **Database schema:** Check `rec/admin/lib.php` (table creation SQL)
- **Existing guides:** `docs-reports/GUIDES-MANUAL.md`

---

**Ready to go?** Open `/rec/admin/` and start managing your portfolio! 🚀
