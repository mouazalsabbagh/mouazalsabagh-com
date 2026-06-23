<?php
/**
 * admin/index.php — CMS Admin Dashboard
 * Main entry point for all admin functions
 */
require __DIR__ . '/lib.php';
requireAdminAuth('viewer');

$stats = getStats();
$currentPage = $_GET['page'] ?? 'dashboard';

// Handle basic routing
$allowed_pages = ['dashboard', 'recommendations', 'pages', 'media', 'users', 'analytics'];
if (!in_array($currentPage, $allowed_pages)) {
    $currentPage = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Admin Dashboard — Mouaz AlSabbagh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        :root {
            --accent: #9ACD32;
            --accent-bright: #C9F31D;
            --bg: #0e1116;
            --panel: #161b22;
            --card: #1c2128;
            --border: #30363d;
            --text: #e6edf3;
            --muted: #8b949e;
        }
        
        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, sans-serif;
        }
        
        .sidebar {
            background: var(--panel);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar .brand {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid var(--border);
            font-weight: 800;
            font-size: 1.1rem;
        }
        
        .sidebar .brand strong { color: var(--accent); }
        
        .sidebar-nav {
            list-style: none;
            padding: 0.5rem 0;
            margin: 0;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--muted);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        
        .sidebar-nav li a:hover {
            color: var(--text);
            background: rgba(156, 205, 50, 0.05);
            border-left-color: var(--accent);
        }
        
        .sidebar-nav li a.active {
            color: var(--accent-bright);
            background: rgba(156, 205, 50, 0.1);
            border-left-color: var(--accent-bright);
            font-weight: 600;
        }
        
        .sidebar-nav li a i {
            width: 20px;
            text-align: center;
        }
        
        main {
            margin-left: 260px;
            padding: 2rem;
        }
        
        .top-bar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-menu .dropdown-toggle::after {
            border-color: var(--accent);
        }
        
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 0.25rem;
        }
        
        .stat-card .stat-label {
            font-size: 0.9rem;
            color: var(--muted);
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: 0.2;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
        }
        
        .action-btn {
            background: var(--accent);
            color: #0e1116;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: var(--accent-bright);
            transform: translateY(-1px);
        }
        
        .action-btn-secondary {
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .action-btn-secondary:hover {
            background: var(--border);
            border-color: var(--accent);
        }
        
        .badge-status {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge-new {
            background: rgba(248, 113, 113, 0.2);
            color: #ff8c8c;
        }
        
        .badge-draft {
            background: rgba(184, 134, 11, 0.2);
            color: #ffd700;
        }
        
        .badge-published {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        
        .badge-archived {
            background: rgba(107, 114, 128, 0.2);
            color: #d1d5db;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }
            main {
                margin-left: 0;
                padding: 1rem;
            }
            .top-bar {
                margin: -1rem -1rem 1.5rem -1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <strong>mouaz</strong><span style="color: var(--accent);">.</span> admin
    </div>
    <ul class="sidebar-nav">
        <li><a href="?page=dashboard" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a></li>
        <li><a href="?page=recommendations" class="<?php echo $currentPage === 'recommendations' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Recommendations
        </a></li>
        <li><a href="?page=pages" class="<?php echo $currentPage === 'pages' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Pages
        </a></li>
        <li><a href="?page=media" class="<?php echo $currentPage === 'media' ? 'active' : ''; ?>">
            <i class="fas fa-images"></i> Media
        </a></li>
        <li><a href="?page=analytics" class="<?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Analytics
        </a></li>
        <li><a href="?page=users" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Users
        </a></li>
        <li style="border-top: 1px solid var(--border); margin-top: 1rem; padding-top: 1rem;">
            <a href="/../collect.php?action=logout" onclick="return confirm('Logout?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</aside>

<!-- MAIN CONTENT -->
<main>
    <div class="top-bar">
        <div>
            <h1><?php 
                $labels = [
                    'dashboard' => 'Dashboard',
                    'recommendations' => 'Recommendations',
                    'pages' => 'Portfolio Pages',
                    'media' => 'Media Library',
                    'analytics' => 'Analytics',
                    'users' => 'User Management'
                ];
                echo $labels[$currentPage] ?? 'Admin Panel';
            ?></h1>
        </div>
        <div class="user-menu">
            <span style="color: var(--muted); font-size: 0.9rem;">
                <i class="fas fa-user-circle"></i>
                <?php echo e($_SESSION['admin_username'] ?? 'Admin'); ?>
                (<?php echo ucfirst(getCurrentUserRole()); ?>)
            </span>
        </div>
    </div>

    <?php
    // Load page content
    if ($currentPage === 'dashboard') {
        include 'pages/dashboard.php';
    } elseif ($currentPage === 'recommendations') {
        include 'pages/recommendations.php';
    } elseif ($currentPage === 'pages') {
        include 'pages/pages.php';
    } elseif ($currentPage === 'media') {
        include 'pages/media.php';
    } elseif ($currentPage === 'analytics') {
        include 'pages/analytics.php';
    } elseif ($currentPage === 'users') {
        include 'pages/users.php';
    }
    ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
</body>
</html>
