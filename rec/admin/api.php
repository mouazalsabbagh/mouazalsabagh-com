<?php
/**
 * admin/api.php — AJAX API for admin dashboard
 * Handles dynamic operations like delete, bulk actions, etc.
 */
require __DIR__ . '/lib.php';
requireAdminAuth('editor');

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = getAdminDB();

try {
    switch ($action) {
        // Recommendations
        case 'update_rec_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (in_array($status, ['new', 'reviewed', 'downloaded'])) {
                $db->prepare("UPDATE recommendations SET status = ? WHERE id = ?")->execute([$status, $id]);
                logAudit('update_status', 'recommendation', $id, ['status' => $status]);
                jsonResponse(true, 'Status updated');
            }
            break;
        
        case 'delete_rec':
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("DELETE FROM recommendations WHERE id = ?")->execute([$id]);
            logAudit('delete', 'recommendation', $id);
            jsonResponse(true, 'Recommendation deleted');
            break;
        
        // Pages
        case 'publish_page':
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE portfolio_items SET status = 'published', published_at = NOW() WHERE id = ?")->execute([$id]);
            logAudit('publish', 'page', $id);
            jsonResponse(true, 'Page published');
            break;
        
        case 'archive_page':
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE portfolio_items SET status = 'archived' WHERE id = ?")->execute([$id]);
            logAudit('archive', 'page', $id);
            jsonResponse(true, 'Page archived');
            break;
        
        case 'delete_page':
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("DELETE FROM portfolio_items WHERE id = ?")->execute([$id]);
            logAudit('delete', 'page', $id);
            jsonResponse(true, 'Page deleted');
            break;
        
        // Media
        case 'delete_media':
            $id = (int)($_POST['id'] ?? 0);
            $media = $db->prepare("SELECT filename FROM media WHERE id = ?")->execute([$id])->fetch();
            if ($media) {
                $file = getMediaPath() . $media['filename'];
                if (file_exists($file)) @unlink($file);
                $db->prepare("DELETE FROM media WHERE id = ?")->execute([$id]);
                logAudit('delete', 'media', $id);
                jsonResponse(true, 'Media deleted');
            }
            break;
        
        // Users
        case 'delete_user':
            requireAdminAuth('admin');
            $id = (int)($_POST['id'] ?? 0);
            if ($id !== getCurrentUserId()) {
                $db->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$id]);
                logAudit('delete', 'user', $id);
                jsonResponse(true, 'User deleted');
            } else {
                jsonResponse(false, 'Cannot delete your own account');
            }
            break;
        
        case 'toggle_user_active':
            requireAdminAuth('admin');
            $id = (int)($_POST['id'] ?? 0);
            $active = (bool)($_POST['active'] ?? false);
            $db->prepare("UPDATE admin_users SET is_active = ? WHERE id = ?")->execute([$active ? 1 : 0, $id]);
            logAudit('toggle_active', 'user', $id, ['active' => $active]);
            jsonResponse(true, 'User status updated');
            break;
        
        // Export
        case 'export_recommendations':
            $stmt = $db->query("SELECT * FROM recommendations WHERE status != 'downloaded' ORDER BY submitted_at DESC");
            $data = $stmt->fetchAll();
            
            if (empty($data)) {
                jsonResponse(false, 'No data to export');
            }
            
            // Mark as downloaded
            $db->query("UPDATE recommendations SET status = 'downloaded' WHERE status != 'downloaded'");
            logAudit('export', 'recommendations', 0, ['count' => count($data)]);
            
            jsonResponse(true, 'Export ready', ['data' => $data]);
            break;
        
        default:
            jsonResponse(false, 'Unknown action');
    }
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
