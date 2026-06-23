<?php
/**
 * admin/pages/media.php — Media library management
 */
requireAdminAuth('editor');

$db = getAdminDB();
$filter_type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
if ($filter_type !== 'all') {
    $where[] = "mime_type LIKE '" . $db->quote($filter_type) . "%'";
}
if (!empty($search)) {
    $where[] = "(original_name LIKE " . $db->quote('%' . $search . '%') . " OR alt_text LIKE " . $db->quote('%' . $search . '%') . ")";
}

$query = "SELECT * FROM media " . (count($where) ? "WHERE " . implode(" AND ", $where) : "") . " ORDER BY uploaded_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$mediaItems = $stmt->fetchAll();

// Stats
$typeStats = $db->query("SELECT SUBSTRING(mime_type, 1, POSITION('/' IN mime_type) - 1) as type, COUNT(*) as cnt FROM media GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalSize = $db->query("SELECT SUM(file_size) as total FROM media")->fetch()['total'] ?? 0;
?>

<div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 250px;">
        <form method="get" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="page" value="media">
            <input type="text" name="search" placeholder="Search files..." value="<?php echo e($search); ?>" 
                   style="flex: 1; background: var(--card); border: 1px solid var(--border); color: var(--text); padding: 0.5rem 0.75rem; border-radius: 6px;">
            <button type="submit" class="action-btn" style="padding: 0.5rem 1rem;">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
    <button class="action-btn" onclick="document.getElementById('upload-form').style.display='block'">
        <i class="fas fa-cloud-upload-alt"></i> Upload
    </button>
</div>

<!-- Upload form (hidden by default) -->
<div id="upload-form" style="display: none; background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
    <h4 style="margin-bottom: 1rem;">Upload Media</h4>
    <form method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Select file:</label>
            <input type="file" name="media_file" required style="width: 100%; padding: 0.5rem; background: var(--border); border: 1px solid var(--border); border-radius: 6px; color: var(--text);">
            <small style="color: var(--muted); display: block; margin-top: 0.5rem;">Supported: WebP, PNG, JPG, GIF, MP4, WebM, PDF (Max 50MB)</small>
        </div>
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Alt text:</label>
            <input type="text" name="alt_text" placeholder="Describe the image for accessibility..." style="width: 100%; padding: 0.5rem 0.75rem; background: var(--card); border: 1px solid var(--border); border-radius: 6px; color: var(--text);">
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="action-btn">Upload</button>
            <button type="button" class="action-btn action-btn-secondary" onclick="document.getElementById('upload-form').style.display='none'">Cancel</button>
        </div>
    </form>
</div>

<!-- Stats -->
<div class="row" style="margin-bottom: 2rem;">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" style="margin: 0;"><?php echo count($mediaItems); ?></div>
            <div class="stat-label">Total Files</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" style="margin: 0; font-size: 1.2rem;"><?php echo number_format($totalSize / (1024*1024), 1); ?> MB</div>
            <div class="stat-label">Total Size</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" style="margin: 0;"><?php echo $typeStats['image'] ?? 0; ?></div>
            <div class="stat-label">Images</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" style="margin: 0;"><?php echo ($typeStats['video'] ?? 0) + ($typeStats['application'] ?? 0); ?></div>
            <div class="stat-label">Videos & Docs</div>
        </div>
    </div>
</div>

<!-- Media grid -->
<div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
    <?php if (empty($mediaItems)): ?>
        <div style="padding: 3rem; text-align: center; color: var(--muted);">
            <i class="fas fa-image" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
            No media files. <a href="#" onclick="document.getElementById('upload-form').style.display='block'; return false;" style="color: var(--accent);">Upload one →</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1px; background: var(--border);">
            <?php foreach ($mediaItems as $media): ?>
                <div style="background: var(--card); padding: 1rem; display: flex; flex-direction: column;">
                    <?php
                    $isImage = strpos($media['mime_type'], 'image/') === 0;
                    $isVideo = strpos($media['mime_type'], 'video/') === 0;
                    ?>
                    <div style="height: 120px; margin-bottom: 1rem; background: var(--border); border-radius: 6px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if ($isImage): ?>
                            <img src="<?php echo getMediaUrl() . e($media['filename']); ?>" alt="<?php echo e($media['alt_text']); ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                        <?php elseif ($isVideo): ?>
                            <i class="fas fa-video" style="font-size: 2rem; color: var(--muted);"></i>
                        <?php else: ?>
                            <i class="fas fa-file" style="font-size: 2rem; color: var(--muted);"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.25rem; word-break: break-word;">
                            <?php echo e($media['original_name']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--muted); margin-bottom: 0.5rem;">
                            <?php echo number_format($media['file_size'] / 1024, 1); ?> KB
                        </div>
                        <?php if ($media['alt_text']): ?>
                            <div style="font-size: 0.75rem; color: var(--muted); margin-bottom: 0.5rem;">
                                <?php echo e(substr($media['alt_text'], 0, 50)); ?>...
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.9rem;">
                        <a href="<?php echo getMediaUrl() . e($media['filename']); ?>" target="_blank" class="action-btn" style="flex: 1; text-align: center; padding: 0.35rem 0.5rem; text-decoration: none;">
                            <i class="fas fa-external-link-alt"></i> View
                        </a>
                        <button class="action-btn action-btn-secondary" style="flex: 1; padding: 0.35rem 0.5rem;" onclick="copyToClipboard('<?php echo getMediaUrl() . e($media['filename']); ?>')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(url) {
    navigator.clipboard.writeText(url).then(function() {
        alert('URL copied!');
    });
}
</script>
