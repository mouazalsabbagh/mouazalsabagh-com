<?php
/**
 * admin/pages/dashboard.php — Dashboard home page with stats
 */
$stats = getStats();
$db = getAdminDB();

// Recent recommendations
$recent_recs = $db->prepare("
    SELECT id, rec_name, rec_title, rec_company, status, submitted_at 
    FROM recommendations 
    ORDER BY submitted_at DESC 
    LIMIT 5
");
$recent_recs->execute();
$recentRecs = $recent_recs->fetchAll();

// Recent pages
$recent_pages = $db->prepare("
    SELECT id, slug, title, status, updated_at 
    FROM portfolio_items 
    ORDER BY updated_at DESC 
    LIMIT 5
");
$recent_pages->execute();
$recentPages = $recent_pages->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card position-relative">
            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            <div class="stat-number"><?php echo $stats['total_recommendations']; ?></div>
            <div class="stat-label">Total Recommendations</div>
            <div style="font-size: 0.85rem; color: var(--accent); margin-top: 0.5rem;">
                <strong><?php echo $stats['new_recommendations']; ?></strong> new
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="stat-card position-relative">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-number"><?php echo $stats['total_pages']; ?></div>
            <div class="stat-label">Published Pages</div>
            <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">
                <?php echo $stats['drafts']; ?> drafts
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="stat-card position-relative">
            <div class="stat-icon"><i class="fas fa-images"></i></div>
            <div class="stat-number"><?php echo $stats['total_media']; ?></div>
            <div class="stat-label">Media Files</div>
            <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">
                in library
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6">
        <div class="stat-card position-relative">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
            <div class="stat-label">Admin Users</div>
            <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">
                active
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700;">Recent Recommendations</h3>
                <a href="?page=recommendations" style="color: var(--accent); font-size: 0.9rem; text-decoration: none;">View all →</a>
            </div>
            
            <?php if (empty($recentRecs)): ?>
                <p style="color: var(--muted); text-align: center; padding: 2rem 0; margin: 0;">
                    No recommendations yet
                </p>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($recentRecs as $rec): ?>
                        <div style="display: flex; justify-content: space-between; align-items: start; padding: 0.8rem 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <div style="font-weight: 600; color: var(--text);"><?php echo e($rec['rec_name']); ?></div>
                                <div style="font-size: 0.85rem; color: var(--muted);">
                                    <?php echo e($rec['rec_title']); ?> at <?php echo e($rec['rec_company']); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--muted); margin-top: 0.25rem;">
                                    <?php echo timeAgo($rec['submitted_at']); ?>
                                </div>
                            </div>
                            <span class="badge-status badge-<?php echo $rec['status']; ?>">
                                <?php echo $rec['status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700;">Recent Pages</h3>
                <a href="?page=pages" style="color: var(--accent); font-size: 0.9rem; text-decoration: none;">View all →</a>
            </div>
            
            <?php if (empty($recentPages)): ?>
                <p style="color: var(--muted); text-align: center; padding: 2rem 0; margin: 0;">
                    No pages yet
                </p>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($recentPages as $page): ?>
                        <div style="display: flex; justify-content: space-between; align-items: start; padding: 0.8rem 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <div style="font-weight: 600; color: var(--text);"><?php echo e($page['title']); ?></div>
                                <div style="font-size: 0.85rem; color: var(--muted);">
                                    /<?php echo e($page['slug']); ?>.html
                                </div>
                                <div style="font-size: 0.8rem; color: var(--muted); margin-top: 0.25rem;">
                                    Updated <?php echo timeAgo($page['updated_at']); ?>
                                </div>
                            </div>
                            <span class="badge-status badge-<?php echo $page['status']; ?>">
                                <?php echo $page['status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem;">
    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: 700;">Quick Actions</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <a href="?page=recommendations&action=new" class="action-btn" style="display: inline-block; text-align: center; text-decoration: none;">
            <i class="fas fa-plus"></i> New Recommendation
        </a>
        <a href="?page=pages&action=new" class="action-btn" style="display: inline-block; text-align: center; text-decoration: none;">
            <i class="fas fa-plus"></i> New Page
        </a>
        <a href="?page=media&action=upload" class="action-btn" style="display: inline-block; text-align: center; text-decoration: none;">
            <i class="fas fa-cloud-upload-alt"></i> Upload Media
        </a>
        <a href="?page=analytics" class="action-btn action-btn-secondary" style="display: inline-block; text-align: center; text-decoration: none;">
            <i class="fas fa-chart-bar"></i> View Analytics
        </a>
    </div>
</div>
