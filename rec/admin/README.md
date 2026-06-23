# CMS Admin Dashboard

A comprehensive, modern admin dashboard for managing your portfolio site, recommendations, pages, media, users, and analytics.

## Features

### 📊 Dashboard
- **Overview Stats**: Real-time counts for recommendations, pages, media, and users
- **Recent Activity**: Latest recommendations and pages with quick access
- **Quick Actions**: Fast buttons to create new content

### 💌 Recommendations Management
- **Inbox View**: See all recommendation letters from submitters
- **Status Tracking**: Track submissions as new, reviewed, or downloaded
- **Search & Filter**: Find recommendations by name, email, or company
- **Bulk Actions**: Update status, tag, and export recommendations
- **CSV Export**: Download all recommendations for backup

### 📄 Portfolio Pages
- **Create/Edit Pages**: Build rich case studies or quick pages
- **Draft & Publish**: Save as draft before going live
- **Multiple Types**: Case studies, project previews, or standalone pages
- **Bilingual Support**: Create English & Arabic pages with RTL support
- **Template System**: Consistent styling across all pages
- **Publishing**: Manage published, draft, and archived content

### 🖼️ Media Library
- **Upload Files**: WebP, PNG, JPG, GIF, MP4, WebM, PDF
- **Organization**: Browse and search uploaded files
- **File Details**: See file size, dimensions, alt text
- **Copy URLs**: Quick URL copy for easy reference
- **Bulk Cleanup**: Delete unused media

### 📈 Analytics
- **Dashboard Stats**: Overview of all content and submissions
- **Trends**: Monthly recommendations and page publications
- **Top Companies**: See which organizations send recommendations
- **Activity Log**: Audit trail of all admin actions
- **Category Breakdown**: Portfolio items by type

### 👥 User Management (Admin only)
- **Add Users**: Create new admin, editor, or viewer accounts
- **Role-Based Access**: Control what each user can do
- **Activity Tracking**: See who did what and when
- **Enable/Disable**: Activate or deactivate accounts
- **Last Login**: Monitor user activity

## Access & Permissions

### Role Levels
- **Admin**: Full access to all features and user management
- **Editor**: Can manage content (pages, recommendations, media)
- **Viewer**: Read-only access to analytics and reports

### Login
Access the dashboard at: `https://yoursite.com/rec/admin/`

**Authentication:**
- Shares session with the main recommendations form (`/rec/collect.php`)
- Same admin login credentials
- CSRF-protected forms

## Database Schema

New tables created on first access:

- `admin_users` - Admin user accounts and roles
- `media` - Uploaded file tracking
- `audit_log` - Admin action history
- `portfolio_items` - Enhanced page management

Enhanced existing tables:
- `recommendations` - Added tags, notes, review status
- `pages` - Migrated to `portfolio_items`

## File Structure

```
rec/
├── admin/
│   ├── index.php          # Main dashboard entry point
│   ├── lib.php            # Shared utilities & database
│   ├── api.php            # AJAX API endpoints
│   ├── admin.js           # Frontend JavaScript
│   ├── styles.css         # Custom styling
│   ├── pages/
│   │   ├── dashboard.php  # Dashboard home
│   │   ├── recommendations.php
│   │   ├── pages.php
│   │   ├── media.php
│   │   ├── analytics.php
│   │   └── users.php
│   ├── uploads/           # Media library directory
│   └── README.md          # This file
├── collect.php            # Recommendations form (public)
├── pages.php              # Legacy page manager
├── config.php             # Secrets (git-ignored)
└── config.example.php
```

## Setup Instructions

### 1. Automatic Installation
The dashboard creates all necessary database tables automatically on first access. Simply navigate to `/rec/admin/` after logging in to the recommendations form.

### 2. Database
Ensure MySQL is running and your `config.php` has correct credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 3. Permissions
Ensure the `uploads/` directory is writable:

```bash
chmod 755 rec/admin/uploads/
```

### 4. Security
- ✅ Secrets stored in `config.php` (git-ignored)
- ✅ CSRF tokens on all forms
- ✅ Session-based authentication
- ✅ HttpOnly, SameSite=Strict cookies
- ✅ SQL injection protection via prepared statements
- ✅ XSS prevention via HTML escaping
- ✅ Audit logging for all changes

## Usage

### Creating a Page

1. Click **"New Page"** in sidebar or dashboard
2. Fill in title, slug, type (case-study, project-preview, or page)
3. Add hero image, body content
4. **Save as Draft** to preview first
5. **Publish** when ready (creates live HTML file)
6. Copy the card snippet to add to galleries (`works.html`, `projects.html`)

### Uploading Media

1. Click **"Upload"** in Media section
2. Select file (WebP, PNG, JPG, MP4, etc.)
3. Add alt text for accessibility
4. Media auto-optimized and stored in `/uploads/`
5. **Copy URL** button to use in pages or recommendations

### Managing Recommendations

1. Click **Recommendations** to see all submissions
2. Filter by status (new, reviewed, downloaded)
3. Click **View** to see full letter
4. Change status or add notes
5. **Export CSV** for backup

### Viewing Analytics

- **Dashboard Stats**: Pie charts showing recommendation status, page types
- **Trends**: Monthly submissions and publications
- **Activity Log**: Who changed what and when
- **Top Companies**: Organizations sending recommendations

## API Endpoints

All AJAX operations go through `/rec/admin/api.php`:

```javascript
// Update recommendation status
fetch('/rec/admin/api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'update_rec_status',
        id: 123,
        status: 'reviewed'
    })
});

// Publish a page
fetch('/rec/admin/api.php', {
    method: 'POST',
    body: new FormData(/* form */),
});
```

## Keyboard Shortcuts

- **Cmd/Ctrl + K** - Focus search
- **Escape** - Close modals/forms

## Troubleshooting

### "Database connection failed"
→ Check `config.php` credentials and ensure MySQL is running

### "Access denied" on admin login
→ Verify your admin password hash in `config.php` (use the `rec/collect.php` login form)

### Media upload fails
→ Check `uploads/` directory permissions (`chmod 755`)

### Can't see published pages
→ Ensure published files are in correct directories:
- Case studies: `/work/case-study/`
- Project previews: `/work/project-preview/`
- Root pages: `/`

## Production Deployment

1. **Database**: Create database and user in cPanel → MySQL Databases
2. **Config**: Create `rec/config.php` with rotated keys/passwords
3. **Upload**: Upload all files except `.git/`, `node_modules/`
4. **Permissions**: Set `uploads/` to 755
5. **PHP Version**: Ensure PHP ≥ 8.0 with `pdo_mysql` extension
6. **CORS**: Update `$ALLOWED_ORIGINS` in `collect.php` for your domain
7. **Test**: Submit form, log into dashboard, verify CSS loads

## Future Enhancements

- [ ] Rich text editor for page content
- [ ] Image optimization & WebP conversion
- [ ] SEO optimization panel
- [ ] Scheduled publishing
- [ ] Content versioning & rollback
- [ ] Email notifications for recommendations
- [ ] Advanced analytics & charts
- [ ] API key management for third-party integrations
- [ ] Multi-language content versioning
- [ ] Comment/collaboration on drafts

## License

Part of mouazalsabbagh.com portfolio. All rights reserved.

## Support

For issues or feature requests, see the main handbook at `/handbook/index.html`.
