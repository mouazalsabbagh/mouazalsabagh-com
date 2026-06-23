<?php
/**
 * admin/lib.php — Shared admin dashboard library
 * Helpers, DB functions, user/role management, audit logging
 */

// ── DATABASE CONNECTION ────────────────────────────────────────────────────────
function getAdminDB() {
    static $pdo = null;
    if ($pdo === null) {
        require __DIR__ . '/../config.php';
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
    return $pdo;
}

// ── ADMIN TABLES SETUP ─────────────────────────────────────────────────────────
function initAdminTables() {
    $db = getAdminDB();
    
    // Users table (for future multi-user support & permissions)
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','editor','viewer') DEFAULT 'editor',
        is_active TINYINT(1) DEFAULT 1,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (is_active), INDEX (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Media library tracking
    $db->exec("CREATE TABLE IF NOT EXISTS media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255),
        mime_type VARCHAR(100),
        file_size INT,
        width INT,
        height INT,
        alt_text VARCHAR(500),
        uploaded_by INT,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL,
        INDEX (uploaded_at), INDEX (mime_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Audit log for tracking changes
    $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100),
        entity_type VARCHAR(50),
        entity_id INT,
        changes JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
        INDEX (logged_at), INDEX (action), INDEX (entity_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Portfolio items table (for better management)
    $db->exec("CREATE TABLE IF NOT EXISTS portfolio_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(160) UNIQUE NOT NULL,
        title VARCHAR(255),
        type ENUM('case-study','project-preview','page') DEFAULT 'page',
        status ENUM('draft','published','archived') DEFAULT 'draft',
        category VARCHAR(100),
        featured TINYINT(1) DEFAULT 0,
        featured_order INT,
        description TEXT,
        og_image VARCHAR(255),
        hero_image VARCHAR(255),
        hero_video VARCHAR(255),
        body_html LONGTEXT,
        tags JSON,
        meta_json JSON,
        lang ENUM('en','ar') DEFAULT 'en',
        created_by INT,
        updated_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at DATETIME,
        FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
        UNIQUE KEY uq_slug_lang (slug, lang),
        INDEX (status), INDEX (lang), INDEX (featured), INDEX (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Recommendations table enhancements (compatibility with existing)
    $db->exec("ALTER TABLE recommendations ADD COLUMN IF NOT EXISTS 
        tags VARCHAR(255),
        ai_draft LONGTEXT,
        reviewed_by INT,
        reviewed_at DATETIME,
        notes TEXT,
        rating INT DEFAULT 5");
}

// ── SESSION & AUTH ────────────────────────────────────────────────────────────
function startAdminSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }
}

function isAdminLoggedIn() {
    startAdminSession();
    return isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
}

function requireAdminAuth($minRole = 'editor') {
    if (!isAdminLoggedIn()) {
        header('Location: /rec/collect.php?action=admin');
        exit;
    }
    
    $allowedRoles = [];
    if ($minRole === 'editor') $allowedRoles = ['admin', 'editor'];
    if ($minRole === 'admin') $allowedRoles = ['admin'];
    if ($minRole === 'viewer') $allowedRoles = ['admin', 'editor', 'viewer'];
    
    $role = $_SESSION['admin_role'] ?? 'viewer';
    if (!in_array($role, $allowedRoles)) {
        http_response_code(403);
        exit('Insufficient permissions');
    }
}

function getCurrentUserId() {
    startAdminSession();
    return $_SESSION['admin_user_id'] ?? null;
}

function getCurrentUserRole() {
    startAdminSession();
    return $_SESSION['admin_role'] ?? 'viewer';
}

function logoutAdmin() {
    startAdminSession();
    session_destroy();
    header('Location: /rec/collect.php?action=logout');
    exit;
}

// ── CSRF PROTECTION ───────────────────────────────────────────────────────────
function csrfToken() {
    startAdminSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrfToken($token) {
    startAdminSession();
    $valid = !empty($_SESSION['csrf']) && 
             !empty($token) && 
             hash_equals($_SESSION['csrf'], $token);
    if (!$valid) {
        http_response_code(403);
        return false;
    }
    return true;
}

// ── SANITIZATION ──────────────────────────────────────────────────────────────
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function sanitize($string) {
    return htmlspecialchars(strip_tags(trim($string ?? '')), ENT_QUOTES, 'UTF-8');
}

function stripHTML($string) {
    return strip_tags(trim($string ?? ''));
}

function sanitizeJSON($json) {
    return json_encode(json_decode($json), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ── AUDIT LOGGING ─────────────────────────────────────────────────────────────
function logAudit($action, $entityType, $entityId, $changes = null, $userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    $db = getAdminDB();
    $stmt = $db->prepare("
        INSERT INTO audit_log 
        (user_id, action, entity_type, entity_id, changes, ip_address, user_agent)
        VALUES (:user_id, :action, :entity_type, :entity_id, :changes, :ip, :agent)
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':changes' => $changes ? json_encode($changes) : null,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);
}

// ── ANALYTICS HELPERS ─────────────────────────────────────────────────────────
function getStats() {
    $db = getAdminDB();
    
    $stats = [
        'total_recommendations' => (int)$db->query("SELECT COUNT(*) as cnt FROM recommendations")->fetch()['cnt'],
        'new_recommendations' => (int)$db->query("SELECT COUNT(*) as cnt FROM recommendations WHERE status='new'")->fetch()['cnt'],
        'total_pages' => (int)$db->query("SELECT COUNT(*) as cnt FROM portfolio_items WHERE status='published'")->fetch()['cnt'],
        'drafts' => (int)$db->query("SELECT COUNT(*) as cnt FROM portfolio_items WHERE status='draft'")->fetch()['cnt'],
        'total_media' => (int)$db->query("SELECT COUNT(*) as cnt FROM media")->fetch()['cnt'],
        'total_users' => (int)$db->query("SELECT COUNT(*) as cnt FROM admin_users WHERE is_active=1")->fetch()['cnt'],
    ];
    
    return $stats;
}

// ── MEDIA HELPERS ─────────────────────────────────────────────────────────────
function getAllowedMimeTypes() {
    return [
        'image/webp', 'image/png', 'image/jpeg', 'image/gif', 'image/svg+xml',
        'video/mp4', 'video/webm',
        'application/pdf',
    ];
}

function getFileExtension($filename) {
    $parts = explode('.', $filename);
    return strtolower(end($parts)) ?? '';
}

function getMediaPath() {
    return __DIR__ . '/uploads/';
}

function getMediaUrl() {
    return '/rec/admin/uploads/';
}

// ── DATE FORMATTING ───────────────────────────────────────────────────────────
function formatDate($datetime, $format = 'M d, Y H:i') {
    if (!$datetime) return '—';
    return (new DateTime($datetime))->format($format);
}

function timeAgo($datetime) {
    if (!$datetime) return '';
    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days > 30) return $date->format('M d');
    if ($diff->days > 1) return $diff->days . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}

// ── VALIDATION ────────────────────────────────────────────────────────────────
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// ── ERROR HANDLING ────────────────────────────────────────────────────────────
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit;
}

// Initialize on load
initAdminTables();
