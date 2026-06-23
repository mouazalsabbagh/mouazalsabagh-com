<?php
/**
 * admin/pages/users.php — User management
 */
requireAdminAuth('admin');

$db = getAdminDB();

// Get all users
$stmt = $db->query("SELECT id, username, email, role, is_active, created_at, last_login FROM admin_users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$roles = ['admin' => 'Admin', 'editor' => 'Editor', 'viewer' => 'Viewer'];
?>

<div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 250px;">
        <input type="text" id="search-users" placeholder="Search by username or email..." 
               style="width: 100%; background: var(--card); border: 1px solid var(--border); color: var(--text); padding: 0.5rem 0.75rem; border-radius: 6px;">
    </div>
    <button class="action-btn" onclick="document.getElementById('new-user-form').style.display='block'">
        <i class="fas fa-user-plus"></i> Add User
    </button>
</div>

<!-- New user form (hidden by default) -->
<div id="new-user-form" style="display: none; background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
    <h4 style="margin-bottom: 1rem;">Add New User</h4>
    <form method="post" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Username:</label>
            <input type="text" name="username" required style="width: 100%; padding: 0.5rem 0.75rem; background: var(--card); border: 1px solid var(--border); border-radius: 6px; color: var(--text);">
        </div>
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email:</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.5rem 0.75rem; background: var(--card); border: 1px solid var(--border); border-radius: 6px; color: var(--text);">
        </div>
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Password:</label>
            <input type="password" name="password" required style="width: 100%; padding: 0.5rem 0.75rem; background: var(--card); border: 1px solid var(--border); border-radius: 6px; color: var(--text);">
        </div>
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Role:</label>
            <select name="role" style="width: 100%; padding: 0.5rem 0.75rem; background: var(--card); border: 1px solid var(--border); border-radius: 6px; color: var(--text);">
                <option value="editor">Editor</option>
                <option value="viewer">Viewer</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div style="grid-column: 1 / -1; display: flex; gap: 0.5rem;">
            <button type="submit" class="action-btn" name="action" value="add_user">Add User</button>
            <button type="button" class="action-btn action-btn-secondary" onclick="document.getElementById('new-user-form').style.display='none'">Cancel</button>
        </div>
    </form>
</div>

<!-- Users table -->
<div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
    <?php if (empty($users)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--muted);">
            <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            No users yet. <a href="#" onclick="document.getElementById('new-user-form').style.display='block'; return false;" style="color: var(--accent);">Create one →</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--border); border-bottom: 1px solid var(--border);">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Username</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Email</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Role</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Last Login</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;">
                                <div style="font-weight: 600;"><?php echo e($user['username']); ?></div>
                            </td>
                            <td style="padding: 1rem; color: var(--muted);"><?php echo e($user['email']); ?></td>
                            <td style="padding: 1rem;">
                                <span style="background: var(--border); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; color: var(--text);">
                                    <?php echo $roles[$user['role']]; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; 
                                    <?php if ($user['is_active']): ?>
                                        background: rgba(34, 197, 94, 0.2); color: #86efac;
                                    <?php else: ?>
                                        background: rgba(107, 114, 128, 0.2); color: #d1d5db;
                                    <?php endif; ?>
                                ">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; color: var(--muted); font-size: 0.9rem;">
                                <?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Never'; ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <button class="action-btn action-btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;" onclick="editUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function editUser(userId) {
    alert('Edit user ' + userId + ' (feature coming soon)');
}

// Search users
document.getElementById('search-users').addEventListener('keyup', function(e) {
    const query = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
});
</script>
