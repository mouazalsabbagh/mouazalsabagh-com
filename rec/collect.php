<?php
/**
 * Recommendation Letter — Data Collection Backend
 * For: Mouaz AlSabbagh Portfolio
 * Handles: form submissions, database storage, admin dashboard, CSV export
 */

 // ── CONFIG (secrets live in config.php, never committed) ──────────────────────
 require __DIR__ . '/config.php';

 // Harden session cookies (secure only over HTTPS, so local HTTP still works).
 session_set_cookie_params([
     'httponly' => true,
     'samesite' => 'Strict',
     'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
 ]);

 // ── CSRF helpers ──────────────────────────────────────────────────────────────
 function csrfToken() {
     if (session_status() !== PHP_SESSION_ACTIVE) session_start();
     if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
     return $_SESSION['csrf'];
 }
 function csrfCheck() {
     if (session_status() !== PHP_SESSION_ACTIVE) session_start();
     $sessionToken = $_SESSION['csrf'] ?? '';
     $postToken    = $_POST['csrf'] ?? '';
     // Reject if no token was ever issued or the submitted token is missing/blank,
     // before hash_equals (which treats two empty strings as a match).
     if ($sessionToken === '' || $postToken === '' || !hash_equals($sessionToken, $postToken)) {
         http_response_code(403);
         exit('Invalid CSRF token');
     }
 }

// ── DB CONNECTION ─────────────────────────────────────────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'Database connection failed. Please check config.']));
        }
    }
    return $pdo;
}

// ── AUTO-INSTALL TABLE ────────────────────────────────────────────────────────
function installTable() {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS recommendations (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address      VARCHAR(45),
            -- Recommender
            rec_name        VARCHAR(200),
            rec_title       VARCHAR(200),
            rec_company     VARCHAR(200),
            rec_email       VARCHAR(200),
            rec_contact     VARCHAR(200),
            rec_date        VARCHAR(30),
            -- Relationship
            rel_type        VARCHAR(60),
            rel_duration    VARCHAR(30),
            rel_context     TEXT,
            -- Target
            target_role     VARCHAR(100),
            target_company  VARCHAR(200),
            target_industry VARCHAR(100),
            -- Strengths & observations
            strengths       TEXT,
            obs_project     TEXT,
            obs_character   TEXT,
            letter_tone     VARCHAR(60),
            letter_length   VARCHAR(30),
            lang            VARCHAR(5) DEFAULT 'en',
            -- Generated letter
            generated_letter LONGTEXT,
            -- Meta
            status          ENUM('new','reviewed','downloaded') DEFAULT 'new'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // Migration: add lang column if upgrading from an older version
    try { $db->exec("ALTER TABLE recommendations ADD COLUMN lang VARCHAR(5) DEFAULT 'en'"); } catch (PDOException $e) { /* column exists */ }
}

// ── HELPERS ───────────────────────────────────────────────────────────────────
function sanitize($v) { return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8'); }
function sendJSON($data) { header('Content-Type: application/json'); echo json_encode($data); exit; }
function isAdmin() {
    session_start();
    return isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
}

// ── ROUTING ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

// SAVE SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    header('Content-Type: application/json');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $ALLOWED_ORIGINS, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Content-Type');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { sendJSON(['success' => false, 'error' => 'Invalid JSON payload']); }

    installTable();
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO recommendations
        (ip_address, rec_name, rec_title, rec_company, rec_email, rec_contact, rec_date,
         rel_type, rel_duration, rel_context, target_role, target_company, target_industry,
         strengths, obs_project, obs_character, letter_tone, letter_length, generated_letter, lang)
        VALUES
        (:ip, :rec_name, :rec_title, :rec_company, :rec_email, :rec_contact, :rec_date,
         :rel_type, :rel_duration, :rel_context, :target_role, :target_company, :target_industry,
         :strengths, :obs_project, :obs_character, :letter_tone, :letter_length, :generated_letter, :lang)
    ");

    $stmt->execute([
        ':ip'               => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ':rec_name'         => sanitize($input['rec_name'] ?? ''),
        ':rec_title'        => sanitize($input['rec_title'] ?? ''),
        ':rec_company'      => sanitize($input['rec_company'] ?? ''),
        ':rec_email'        => sanitize($input['rec_email'] ?? ''),
        ':rec_contact'      => sanitize($input['rec_contact'] ?? ''),
        ':rec_date'         => sanitize($input['rec_date'] ?? ''),
        ':rel_type'         => sanitize($input['rel_type'] ?? ''),
        ':rel_duration'     => sanitize($input['rel_duration'] ?? ''),
        ':rel_context'      => sanitize($input['rel_context'] ?? ''),
        ':target_role'      => sanitize($input['target_role'] ?? ''),
        ':target_company'   => sanitize($input['target_company'] ?? ''),
        ':target_industry'  => sanitize($input['target_industry'] ?? ''),
        ':strengths'        => sanitize($input['strengths'] ?? ''),
        ':obs_project'      => sanitize($input['obs_project'] ?? ''),
        ':obs_character'    => sanitize($input['obs_character'] ?? ''),
        ':letter_tone'      => sanitize($input['letter_tone'] ?? ''),
        ':letter_length'    => sanitize($input['letter_length'] ?? ''),
        ':generated_letter' => $input['generated_letter'] ?? '',
        ':lang'             => in_array($input['lang'] ?? 'en', ['en','ar']) ? $input['lang'] : 'en',
    ]);

    $newId = $db->lastInsertId();

    // Email notification to Mouaz
    $subject = "New Recommendation Letter — " . sanitize($input['rec_name'] ?? 'Unknown');
    $body  = "New recommendation letter submitted on " . date('Y-m-d H:i') . "\n\n";
    $body .= "From: " . sanitize($input['rec_name'] ?? '') . " (" . sanitize($input['rec_title'] ?? '') . " @ " . sanitize($input['rec_company'] ?? '') . ")\n";
    $body .= "Target Role: " . sanitize($input['target_role'] ?? '') . "\n";
    $body .= "Target Company: " . sanitize($input['target_company'] ?? 'Not specified') . "\n\n";
    $body .= "View all submissions: https://" . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') . "/collect.php?action=admin\n";
    @mail(SITE_OWNER_EMAIL, $subject, $body, 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com'));

    sendJSON(['success' => true, 'id' => $newId]);
}

// ADMIN LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    csrfCheck();
    $pass = $_POST['password'] ?? '';
    if (password_verify($pass, ADMIN_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_username'] = 'admin';
        $_SESSION['admin_role'] = 'admin';
        header('Location: collect.php?action=admin');
    } else {
        header('Location: collect.php?action=admin&error=1');
    }
    exit;
}

// ADMIN LOGOUT
if ($action === 'logout') {
    session_start();
    session_destroy();
    header('Location: collect.php?action=admin');
    exit;
}

// UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'status' && isAdmin()) {
    csrfCheck();
    $id     = (int)($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new','reviewed','downloaded']) ? $_POST['status'] : 'new';
    getDB()->prepare("UPDATE recommendations SET status=? WHERE id=?")->execute([$status, $id]);
    header('Location: collect.php?action=admin');
    exit;
}

// CSV EXPORT
if ($action === 'export' && isAdmin()) {
    installTable();
    $rows = getDB()->query("SELECT * FROM recommendations ORDER BY submitted_at DESC")->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="recommendations_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ── ADMIN DASHBOARD ───────────────────────────────────────────────────────────
if ($action === 'admin') {
    session_start();
    $authed = isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
    $error  = isset($_GET['error']);

    installTable();
    $submissions = [];
    $stats = ['total' => 0, 'new' => 0, 'reviewed' => 0, 'downloaded' => 0];
    if ($authed) {
        $submissions = getDB()->query("SELECT * FROM recommendations ORDER BY submitted_at DESC")->fetchAll();
        foreach ($submissions as $r) {
            $stats['total']++;
            $stats[$r['status']] = ($stats[$r['status']] ?? 0) + 1;
        }
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Recommendation Letters</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f4f4f4; color:#333; line-height:1.5; }
.wrap { max-width:1100px; margin:0 auto; padding:1.2rem; }
header { background:#fff; border-bottom:2px solid #000; padding:0.8rem 1.2rem; display:flex; justify-content:space-between; align-items:center; }
header h1 { font-size:1.2rem; font-weight:700; }
header small { color:#666; font-size:0.82rem; }
.login-box { max-width:380px; margin:3rem auto; background:#fff; padding:2rem; border:1px solid #ddd; border-radius:4px; }
.login-box h2 { font-size:1.1rem; margin-bottom:1rem; border-left:3px solid #000; padding-left:0.6rem; }
.login-box input { width:100%; padding:0.5rem; border:1px solid #ccc; border-radius:3px; font-size:0.9rem; margin-bottom:0.8rem; }
.btn { background:#000; color:#fff; border:none; padding:0.5rem 1.2rem; border-radius:3px; font-size:0.9rem; font-weight:700; cursor:pointer; }
.btn:hover { background:#333; }
.btn-sm { padding:0.25rem 0.6rem; font-size:0.8rem; }
.btn-outline { background:#fff; color:#333; border:1px solid #ccc; }
.btn-outline:hover { background:#f0f0f0; }
.stats { display:grid; grid-template-columns:repeat(4,1fr); gap:0.8rem; margin:1.2rem 0; }
.stat-box { background:#fff; padding:0.8rem; border-left:3px solid #000; }
.stat-box.new { border-color:#f59e0b; }
.stat-box.reviewed { border-color:#3b82f6; }
.stat-box.downloaded { border-color:#16a34a; }
.stat-val { font-size:1.6rem; font-weight:700; }
.stat-lbl { font-size:0.78rem; color:#666; margin-top:0.2rem; }
.card { background:#fff; border:1px solid #ddd; border-radius:3px; margin-bottom:0.8rem; overflow:hidden; }
.card-head { display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.8rem; background:#f9f9f9; border-bottom:1px solid #eee; cursor:pointer; }
.card-head h3 { font-size:0.95rem; font-weight:700; }
.badge { display:inline-block; padding:0.15rem 0.5rem; border-radius:2px; font-size:0.75rem; font-weight:700; text-transform:uppercase; }
.badge.new { background:#fef3c7; color:#92400e; }
.badge.reviewed { background:#dbeafe; color:#1e40af; }
.badge.downloaded { background:#dcfce7; color:#166534; }
.card-body { padding:0.8rem; display:none; }
.card-body.open { display:block; }
.detail-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem 1rem; font-size:0.85rem; margin-bottom:0.8rem; }
.detail-grid dt { font-weight:700; color:#555; font-size:0.78rem; text-transform:uppercase; }
.detail-grid dd { margin-top:0.1rem; }
.letter-box { background:#f9f9f9; border-left:2px solid #ccc; padding:0.7rem 0.9rem; font-size:0.85rem; white-space:pre-wrap; max-height:260px; overflow-y:auto; border-radius:2px; }
.actions { display:flex; gap:0.5rem; align-items:center; margin-top:0.6rem; flex-wrap:wrap; }
.toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem; }
.empty { text-align:center; padding:2rem; color:#888; font-size:0.9rem; }
.error-msg { background:#fee2e2; color:#991b1b; padding:0.5rem 0.8rem; border-radius:3px; margin-bottom:0.8rem; font-size:0.88rem; }
a.logout { font-size:0.85rem; color:#666; text-decoration:none; }
a.logout:hover { color:#000; }
</style>
</head>
<body>
<header>
  <div>
    <h1>Recommendation Letters — Admin</h1>
    <small><?= SITE_NAME ?></small>
  </div>
  <?php if ($authed): ?>
  <div style="display:flex;gap:1rem;align-items:center;">
    <a href="collect.php?action=export" class="btn btn-sm">⬇ Export CSV</a>
    <a href="collect.php?action=logout" class="logout">Logout</a>
  </div>
  <?php endif; ?>
</header>
<div class="wrap">

<?php if (!$authed): ?>
  <div class="login-box">
    <h2>Admin Login</h2>
    <?php if ($error): ?><div class="error-msg">Incorrect password. Try again.</div><?php endif; ?>
    <form method="POST" action="collect.php?action=login">
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
      <input type="password" name="password" placeholder="Admin password" autofocus required>
      <button type="submit" class="btn" style="width:100%">Login</button>
    </form>
  </div>

<?php else: ?>

  <!-- STATS -->
  <div class="stats">
    <div class="stat-box"><div class="stat-val"><?= $stats['total'] ?></div><div class="stat-lbl">Total Submissions</div></div>
    <div class="stat-box new"><div class="stat-val"><?= $stats['new'] ?></div><div class="stat-lbl">New (Unread)</div></div>
    <div class="stat-box reviewed"><div class="stat-val"><?= $stats['reviewed'] ?></div><div class="stat-lbl">Reviewed</div></div>
    <div class="stat-box downloaded"><div class="stat-val"><?= $stats['downloaded'] ?></div><div class="stat-lbl">Downloaded / Used</div></div>
  </div>

  <div class="toolbar">
    <strong style="font-size:0.9rem"><?= count($submissions) ?> recommendation<?= count($submissions) !== 1 ? 's' : '' ?> collected</strong>
    <a href="collect.php?action=export" class="btn btn-outline btn-sm">Export all as CSV</a>
  </div>

  <?php if (empty($submissions)): ?>
    <div class="card"><div class="empty">No submissions yet. Share the recommendation form link with your recommenders.</div></div>
  <?php else: ?>
    <?php foreach ($submissions as $r): ?>
    <div class="card">
      <div class="card-head" onclick="toggle(<?= $r['id'] ?>)">
        <div>
          <h3><?= htmlspecialchars($r['rec_name']) ?> — <?= htmlspecialchars($r['rec_title']) ?> @ <?= htmlspecialchars($r['rec_company']) ?></h3>
          <small style="color:#666;font-size:0.8rem"><?= $r['submitted_at'] ?> &nbsp;·&nbsp; Target: <?= htmlspecialchars($r['target_role']) ?><?= $r['target_company'] ? ' @ ' . htmlspecialchars($r['target_company']) : '' ?></small>
        </div>
        <span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span>
      </div>
      <div class="card-body" id="card-<?= $r['id'] ?>">
        <dl class="detail-grid">
          <div><dt>Email</dt><dd><?= htmlspecialchars($r['rec_email'] ?: '—') ?></dd></div>
          <div><dt>Contact</dt><dd><?= htmlspecialchars($r['rec_contact'] ?: '—') ?></dd></div>
          <div><dt>Letter Date</dt><dd><?= htmlspecialchars($r['rec_date'] ?: '—') ?></dd></div>
          <div><dt>Relationship</dt><dd><?= htmlspecialchars($r['rel_type']) ?></dd></div>
          <div><dt>Duration</dt><dd><?= htmlspecialchars($r['rel_duration']) ?></dd></div>
          <div><dt>Context</dt><dd><?= htmlspecialchars($r['rel_context']) ?></dd></div>
          <div><dt>Industry</dt><dd><?= htmlspecialchars($r['target_industry'] ?: '—') ?></dd></div>
          <div><dt>Tone</dt><dd><?= htmlspecialchars($r['letter_tone']) ?></dd></div>
          <div><dt>Length</dt><dd><?= htmlspecialchars($r['letter_length']) ?></dd></div>
        </dl>
        <?php if ($r['strengths']): ?>
        <p style="font-size:0.83rem;margin-bottom:0.5rem"><strong>Strengths:</strong> <?= htmlspecialchars($r['strengths']) ?></p>
        <?php endif; ?>
        <?php if ($r['obs_project']): ?>
        <p style="font-size:0.83rem;margin-bottom:0.3rem"><strong>Project observation:</strong> <?= nl2br(htmlspecialchars($r['obs_project'])) ?></p>
        <?php endif; ?>
        <?php if ($r['obs_character']): ?>
        <p style="font-size:0.83rem;margin-bottom:0.5rem"><strong>Character observation:</strong> <?= nl2br(htmlspecialchars($r['obs_character'])) ?></p>
        <?php endif; ?>
        <?php if ($r['generated_letter']): ?>
        <p style="font-weight:700;font-size:0.85rem;margin-bottom:0.3rem">Generated Letter:</p>
        <div class="letter-box"><?= nl2br(htmlspecialchars($r['generated_letter'])) ?></div>
        <?php endif; ?>
        <div class="actions">
          <form method="POST" action="collect.php?action=status" style="display:flex;gap:0.4rem;align-items:center;">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <select name="status" style="font-size:0.82rem;padding:0.25rem 0.4rem;border:1px solid #ccc;border-radius:3px;">
              <option value="new" <?= $r['status']==='new'?'selected':'' ?>>New</option>
              <option value="reviewed" <?= $r['status']==='reviewed'?'selected':'' ?>>Reviewed</option>
              <option value="downloaded" <?= $r['status']==='downloaded'?'selected':'' ?>>Downloaded</option>
            </select>
            <button type="submit" class="btn btn-sm">Update</button>
          </form>
          <?php if ($r['generated_letter']): ?>
          <button class="btn btn-outline btn-sm" onclick="copyLetter(<?= $r['id'] ?>, `<?= addslashes(htmlspecialchars($r['generated_letter'])) ?>`)">Copy Letter</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

<?php endif; ?>
</div>

<script>
function toggle(id) {
  const el = document.getElementById('card-' + id);
  el.classList.toggle('open');
}
function copyLetter(id, text) {
  navigator.clipboard.writeText(text).then(() => alert('Letter #' + id + ' copied to clipboard!'));
}
</script>
</body>
</html>
    <?php
    exit;
}

// Default: redirect to form
header('Location: index.html');
exit;
