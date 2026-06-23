<?php
/**
 * admin/pages/pages.php — Portfolio pages management
 */
requireAdminAuth('editor');

$db = getAdminDB();
$action = $_GET['action'] ?? 'list';
$filter_status = $_GET['status'] ?? 'all';
$filter_type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
if ($filter_status !== 'all') {
    $where[] = "status = '" . $db->quote($filter_status) . "'";
}
if ($filter_type !== 'all') {
    $where[] = "type = '" . $db->quote($filter_type) . "'";
}
if (!empty($search)) {
    $where[] = "(title LIKE " . $db->quote('%' . $search . '%') . " OR slug LIKE " . $db->quote('%' . $search . '%') . ")";
}

$query = "SELECT * FROM portfolio_items " . (count($where) ? "WHERE " . implode(" AND ", $where) : "") . " ORDER BY updated_at DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();
$pages = $stmt->fetchAll();

// Stats
$stats = $db->query("SELECT status, COUNT(*) as cnt FROM portfolio_items GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$typeStats = $db->query("SELECT type, COUNT(*) as cnt FROM portfolio_items WHERE status='published' GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 250px;">
        <form method="get" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="page" value="pages">
            <input type="text" name="search" placeholder="Search by title or slug..." value="<?php echo e($search); ?>" 
                   style="flex: 1; background: var(--card); border: 1px solid var(--border); color: var(--text); padding: 0.5rem 0.75rem; border-radius: 6px;">
            <button type="submit" class="action-btn" style="padding: 0.5rem 1rem;">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
    <a href="?page=pages&action=new" class="action-btn" style="white-space: nowrap;">
        <i class="fas fa-plus"></i> New Page
    </a>
</div>

<!-- Filter tabs -->
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; flex-wrap: wrap;">
    <a href="?page=pages" class="<?php echo $filter_status === 'all' ? 'active' : ''; ?>" 
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'all' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'all' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        All (<?php echo array_sum($stats); ?>)
    </a>
    <a href="?page=pages&status=published" class="<?php echo $filter_status === 'published' ? 'active' : ''; ?>"
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'published' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'published' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        Published (<?php echo $stats['published'] ?? 0; ?>)
    </a>
    <a href="?page=pages&status=draft" class="<?php echo $filter_status === 'draft' ? 'active' : ''; ?>"
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'draft' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'draft' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        Drafts (<?php echo $stats['draft'] ?? 0; ?>)
    </a>
    <a href="?page=pages&status=archived" class="<?php echo $filter_status === 'archived' ? 'active' : ''; ?>"
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'archived' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'archived' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        Archived (<?php echo $stats['archived'] ?? 0; ?>)
    </a>
</div>

<!-- Pages table -->
<div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
    <?php if (empty($pages)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--muted);">
            <i class="fas fa-file-alt" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            No pages found. <a href="?page=pages&action=new" style="color: var(--accent);">Create one →</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--border); border-bottom: 1px solid var(--border);">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Title</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Type</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Updated</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;">
                                <div style="font-weight: 600;"><?php echo e($page['title']); ?></div>
                                <div style="font-size: 0.85rem; color: var(--muted);"><?php echo e($page['slug']); ?>.html</div>
                            </td>
                            <td style="padding: 1rem; color: var(--muted);"><?php echo ucfirst(str_replace('-', ' ', $page['type'])); ?></td>
                            <td style="padding: 1rem; color: var(--muted); font-size: 0.9rem;">
                                <?php echo timeAgo($page['updated_at']); ?>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge-status badge-<?php echo $page['status']; ?>">
                                    <?php echo $page['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <a href="?page=pages&action=edit&id=<?php echo $page['id']; ?>" style="color: var(--accent); text-decoration: none; font-weight: 600; margin-right: 1rem;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if ($page['status'] === 'published'): ?>
                                    <a href="<?php echo '/' . ($page['type'] !== 'page' ? $page['type'] . '/' : '') . $page['slug'] . '.html'; ?>" target="_blank" style="color: var(--muted); text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-external-link-alt"></i> View
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .badge-status {
        display: inline-block;
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .badge-published {
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
    }
    .badge-draft {
        background: rgba(184, 134, 11, 0.2);
        color: #ffd700;
    }
    .badge-archived {
        background: rgba(107, 114, 128, 0.2);
        color: #d1d5db;
    }
</style>
