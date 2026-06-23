<?php
/**
 * admin/pages/recommendations.php — Recommendations management
 */
requireAdminAuth('editor');

$db = getAdminDB();
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'submitted_at DESC';

// Build query
$where = [];
if ($filter_status !== 'all') {
    $where[] = "status = '" . $db->quote($filter_status) . "'";
}
if (!empty($search)) {
    $where[] = "(rec_name LIKE " . $db->quote('%' . $search . '%') . " OR rec_email LIKE " . $db->quote('%' . $search . '%') . " OR rec_company LIKE " . $db->quote('%' . $search . '%') . ")";
}

$query = "SELECT * FROM recommendations " . (count($where) ? "WHERE " . implode(" AND ", $where) : "") . " ORDER BY " . $sort . " LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();
$recommendations = $stmt->fetchAll();

// Stats
$stats = $db->query("SELECT status, COUNT(*) as cnt FROM recommendations GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 250px;">
        <form method="get" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="page" value="recommendations">
            <input type="text" name="search" placeholder="Search by name, email, company..." value="<?php echo e($search); ?>" 
                   style="flex: 1; background: var(--card); border: 1px solid var(--border); color: var(--text); padding: 0.5rem 0.75rem; border-radius: 6px;">
            <button type="submit" class="action-btn" style="padding: 0.5rem 1rem;">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
</div>

<!-- Filter tabs -->
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; flex-wrap: wrap;">
    <a href="?page=recommendations" class="<?php echo $filter_status === 'all' ? 'active' : ''; ?>" 
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'all' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'all' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        All (<?php echo array_sum($stats); ?>)
    </a>
    <a href="?page=recommendations&status=new" class="<?php echo $filter_status === 'new' ? 'active' : ''; ?>"
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'new' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'new' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        New (<?php echo $stats['new'] ?? 0; ?>)
    </a>
    <a href="?page=recommendations&status=reviewed" class="<?php echo $filter_status === 'reviewed' ? 'active' : ''; ?>"
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'reviewed' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'reviewed' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        Reviewed (<?php echo $stats['reviewed'] ?? 0; ?>)
    </a>
    <a href="?page=recommendations&status=downloaded" class="<?php echo $filter_status === 'downloaded' ? 'active' : ''; ?>"
       style="padding: 0.5rem 1rem; border-bottom: 2px solid <?php echo $filter_status === 'downloaded' ? 'var(--accent)' : 'transparent'; ?>; color: <?php echo $filter_status === 'downloaded' ? 'var(--accent)' : 'var(--muted)'; ?>; text-decoration: none; font-weight: 600; transition: all 0.2s;">
        Downloaded (<?php echo $stats['downloaded'] ?? 0; ?>)
    </a>
</div>

<!-- Recommendations table -->
<div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
    <?php if (empty($recommendations)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--muted);">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            No recommendations found
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--border); border-bottom: 1px solid var(--border);">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Recommender</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Company</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Submitted</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recommendations as $rec): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;">
                                <div style="font-weight: 600;"><?php echo e($rec['rec_name']); ?></div>
                                <div style="font-size: 0.85rem; color: var(--muted);"><?php echo e($rec['rec_title']); ?></div>
                            </td>
                            <td style="padding: 1rem; color: var(--muted);"><?php echo e($rec['rec_company']); ?></td>
                            <td style="padding: 1rem; color: var(--muted); font-size: 0.9rem;">
                                <?php echo formatDate($rec['submitted_at'], 'M d, Y'); ?>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge-status badge-<?php echo $rec['status']; ?>">
                                    <?php echo $rec['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <a href="#" data-id="<?php echo $rec['id']; ?>" onclick="viewRecommendation(<?php echo $rec['id']; ?>); return false;" style="color: var(--accent); text-decoration: none; font-weight: 600;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function viewRecommendation(id) {
    // TODO: Open modal with full recommendation details
    alert('View recommendation ' + id);
}
</script>
