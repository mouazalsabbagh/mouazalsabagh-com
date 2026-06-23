<?php
/**
 * admin/pages/analytics.php — Analytics & insights
 */
requireAdminAuth('viewer');

$db = getAdminDB();

// Get time-based stats
$recsByMonth = $db->query("
    SELECT DATE_FORMAT(submitted_at, '%Y-%m') as month, COUNT(*) as cnt 
    FROM recommendations 
    WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$pagesByMonth = $db->query("
    SELECT DATE_FORMAT(published_at, '%Y-%m') as month, COUNT(*) as cnt 
    FROM portfolio_items 
    WHERE status='published' AND published_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Recommendations by status
$recStatus = $db->query("
    SELECT status, COUNT(*) as cnt FROM recommendations GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Top recommenders
$topRecommenders = $db->query("
    SELECT rec_company, COUNT(*) as cnt FROM recommendations 
    GROUP BY rec_company 
    ORDER BY cnt DESC 
    LIMIT 10
")->fetchAll();

// Top tags
$topTags = $db->query("
    SELECT tags, COUNT(*) as cnt FROM recommendations 
    WHERE tags IS NOT NULL AND tags != ''
    GROUP BY tags 
    ORDER BY cnt DESC 
    LIMIT 10
")->fetchAll();

// Page categories
$pageCategories = $db->query("
    SELECT category, COUNT(*) as cnt FROM portfolio_items 
    WHERE status='published'
    GROUP BY category 
    ORDER BY cnt DESC
")->fetchAll();

// Audit trail (recent actions)
$auditLog = $db->query("
    SELECT al.*, au.username FROM audit_log al
    LEFT JOIN admin_users au ON au.id = al.user_id
    ORDER BY logged_at DESC
    LIMIT 20
")->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
            <h3 style="margin: 0 0 1.5rem; font-size: 1.1rem; font-weight: 700;">Recommendations by Status</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php 
                $statuses = ['new' => '#ff8c8c', 'reviewed' => '#ffd700', 'downloaded' => '#86efac'];
                foreach ($statuses as $status => $color):
                    $count = $recStatus[$status] ?? 0;
                    $total = array_sum($recStatus);
                    $pct = $total > 0 ? ($count / $total * 100) : 0;
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            <span><?php echo ucfirst($status); ?></span>
                            <strong><?php echo $count; ?> (<?php echo number_format($pct, 1); ?>%)</strong>
                        </div>
                        <div style="height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: <?php echo $color; ?>; width: <?php echo $pct; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
            <h3 style="margin: 0 0 1.5rem; font-size: 1.1rem; font-weight: 700;">Portfolio Pages by Type</h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php 
                $types = $db->query("SELECT type, COUNT(*) as cnt FROM portfolio_items WHERE status='published' GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
                $total = array_sum($types);
                foreach ($types as $type => $count):
                    $pct = $total > 0 ? ($count / $total * 100) : 0;
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                            <span><?php echo ucfirst(str_replace('-', ' ', $type)); ?></span>
                            <strong><?php echo $count; ?></strong>
                        </div>
                        <div style="height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: var(--accent); width: <?php echo $pct; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
            <h3 style="margin: 0 0 1.5rem; font-size: 1.1rem; font-weight: 700;">Top 10 Companies</h3>
            <div style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($topRecommenders)): ?>
                    <p style="color: var(--muted);">No data yet</p>
                <?php else: ?>
                    <table style="width: 100%; font-size: 0.9rem;">
                        <tbody>
                            <?php $rank = 1; foreach ($topRecommenders as $item): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 0.5rem 0; color: var(--muted);"><?php echo $rank++; ?></td>
                                    <td style="padding: 0.5rem 0.5rem; color: var(--text);"><?php echo e($item['rec_company']) ?: '(Unknown)'; ?></td>
                                    <td style="padding: 0.5rem 0; text-align: right; font-weight: 600; color: var(--accent);"><?php echo $item['cnt']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
            <h3 style="margin: 0 0 1.5rem; font-size: 1.1rem; font-weight: 700;">Recent Activity Log</h3>
            <div style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($auditLog)): ?>
                    <p style="color: var(--muted);">No activity yet</p>
                <?php else: ?>
                    <?php foreach ($auditLog as $entry): ?>
                        <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); font-size: 0.9rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                <strong><?php echo ucfirst($entry['action']); ?> — <?php echo e($entry['entity_type']); ?></strong>
                                <span style="color: var(--muted);"><?php echo timeAgo($entry['logged_at']); ?></span>
                            </div>
                            <div style="color: var(--muted); font-size: 0.85rem;">
                                by <?php echo e($entry['username'] ?? 'Unknown'); ?> • <?php echo $entry['ip_address']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
