<?php
/**
 * pages.php — Template-driven page manager (admin only).
 * Create / edit / draft / publish case-study, project-preview, and root pages
 * from rec/templates/page-template.html. Reuses the admin session set by collect.php.
 */
require __DIR__ . '/config.php';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);

// ── DB ────────────────────────────────────────────────────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }
    return $pdo;
}
function installPagesTable() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(160) NOT NULL,
        type ENUM('case-study','project-preview','page') NOT NULL DEFAULT 'page',
        status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
        lang ENUM('en','ar') NOT NULL DEFAULT 'en',
        title VARCHAR(255), og_image VARCHAR(255), body_html LONGTEXT, meta_json JSON,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug_lang (slug, lang), INDEX (status), INDEX (lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// ── Auth + CSRF (shared session with collect.php) ─────────────────────────────
function startSession() { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); }
function isAdmin() { startSession(); return ($_SESSION['admin_auth'] ?? false) === true; }
function csrfToken() { startSession(); if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrfCheck() { startSession(); $s = $_SESSION['csrf'] ?? ''; $p = $_POST['csrf'] ?? ''; if ($s === '' || $p === '' || !hash_equals($s, $p)) { http_response_code(403); exit('Invalid CSRF token'); } }
function requireAdmin() { if (!isAdmin()) { header('Location: collect.php?action=admin'); exit; } }

// ── Helpers ───────────────────────────────────────────────────────────────────
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function slugify($s) {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
function typeLabel($t) {
    return ['case-study' => 'Case Study', 'project-preview' => 'Project Preview', 'page' => 'Page'][$t] ?? 'Page';
}
function baseFor($type) { return $type === 'page' ? '' : '../../'; }
function liveDir($type) {
    return ['case-study' => 'work/case-study', 'project-preview' => 'work/project-preview', 'page' => ''][$type] ?? '';
}
// Build the absolute filesystem path for a page's rendered HTML.
function filePath($type, $slug, $isDraft) {
    $root = dirname(__DIR__); // project root (rec/ is one level down)
    if ($isDraft) return $root . '/work/_drafts/' . $slug . '.html';
    $dir = liveDir($type);
    return $root . ($dir ? '/' . $dir : '') . '/' . $slug . '.html';
}
function renderTemplate($p, $isDraft) {
    $tpl = file_get_contents(__DIR__ . '/templates/page-template.html');
    $base = baseFor($p['type']);
    $dir = ($p['lang'] === 'ar') ? 'rtl' : 'ltr';
    $robots = $isDraft ? '<meta name="robots" content="noindex,nofollow">' : '';
    $og = $p['og_image'] ?: 'assets/images/projects/project1.webp';
    $map = [
        '{{lang}}' => e($p['lang']), '{{dir}}' => $dir, '{{robots}}' => $robots,
        '{{title}}' => e($p['title']), '{{og_image}}' => e($og), '{{base}}' => $base,
        '{{type_label}}' => e(typeLabel($p['type'])), '{{slug}}' => e($p['slug']),
        '{{body}}' => $p['body_html'] ?: '', // raw HTML (admin-trusted)
    ];
    return strtr($tpl, $map);
}
function writePage($p, $isDraft) {
    $path = filePath($p['type'], $p['slug'], $isDraft);
    @mkdir(dirname($path), 0775, true);
    file_put_contents($path, renderTemplate($p, $isDraft));
    return $path;
}
function cardSnippet($p) {
    $href = ($p['type'] === 'page' ? '' : liveDir($p['type']) . '/') . $p['slug'] . '.html';
    return '<a href="' . e($href) . '" class="project-btn">' . e($p['title']) . '</a>';
}

requireAdmin();
installPagesTable();
$action = $_GET['action'] ?? 'list';
$db = getDB();
$notice = '';

// ── SAVE (create or update) ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    csrfCheck();
    $id    = (int)($_POST['id'] ?? 0);
    $type  = in_array($_POST['type'] ?? '', ['case-study','project-preview','page']) ? $_POST['type'] : 'page';
    $lang  = in_array($_POST['lang'] ?? '', ['en','ar']) ? $_POST['lang'] : 'en';
    $slug  = slugify($_POST['slug'] ?? '');
    if ($slug === '') $slug = slugify($_POST['title'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $og    = trim($_POST['og_image'] ?? '');
    $body  = $_POST['body_html'] ?? '';
    $publish = isset($_POST['publish']);
    $status  = $publish ? 'published' : 'draft';
    if ($slug === '' || $title === '') { http_response_code(400); exit('Title and slug are required.'); }

    if ($id > 0) {
        $db->prepare("UPDATE pages SET slug=?,type=?,status=?,lang=?,title=?,og_image=?,body_html=? WHERE id=?")
           ->execute([$slug,$type,$status,$lang,$title,$og,$body,$id]);
    } else {
        $db->prepare("INSERT INTO pages (slug,type,status,lang,title,og_image,body_html) VALUES (?,?,?,?,?,?,?)")
           ->execute([$slug,$type,$status,$lang,$title,$og,$body]);
        $id = $db->lastInsertId();
    }
    $p = $db->query("SELECT * FROM pages WHERE id=" . (int)$id)->fetch();
    if ($publish) {
        $live = writePage($p, false);
        @unlink(filePath($p['type'], $p['slug'], true)); // remove any old draft file
        $msg = 'Published → ' . basename(dirname($live)) . '/' . basename($live);
    } else {
        writePage($p, true);
        $msg = 'Saved as draft → work/_drafts/' . $p['slug'] . '.html';
    }
    header('Location: pages.php?action=list&ok=' . urlencode($msg) . ($publish ? '&card=' . (int)$id : ''));
    exit;
}

// ── PUBLISH / UNPUBLISH / DELETE ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['publish','draft','delete'])) {
    csrfCheck();
    $id = (int)($_POST['id'] ?? 0);
    $p = $db->query("SELECT * FROM pages WHERE id=" . $id)->fetch();
    if ($p) {
        if ($action === 'publish') {
            $db->prepare("UPDATE pages SET status='published' WHERE id=?")->execute([$id]);
            $p['status'] = 'published'; writePage($p, false); @unlink(filePath($p['type'],$p['slug'],true));
            $msg = 'Published ' . $p['slug'];
        } elseif ($action === 'draft') {
            $db->prepare("UPDATE pages SET status='draft' WHERE id=?")->execute([$id]);
            $p['status'] = 'draft'; writePage($p, true); @unlink(filePath($p['type'],$p['slug'],false));
            $msg = 'Unpublished ' . $p['slug'] . ' (moved to draft)';
        } else {
            $db->prepare("DELETE FROM pages WHERE id=?")->execute([$id]);
            @unlink(filePath($p['type'],$p['slug'],true)); @unlink(filePath($p['type'],$p['slug'],false));
            $msg = 'Deleted ' . $p['slug'];
        }
    }
    header('Location: pages.php?action=list&ok=' . urlencode($msg ?? 'done'));
    exit;
}

// ── EDIT/NEW FORM ─────────────────────────────────────────────────────────────
$editing = null;
if ($action === 'edit') { $editing = $db->query("SELECT * FROM pages WHERE id=" . (int)($_GET['id'] ?? 0))->fetch() ?: null; }
$isForm = ($action === 'new' || $action === 'edit');
$rows = $db->query("SELECT * FROM pages ORDER BY updated_at DESC")->fetchAll();
$ok = $_GET['ok'] ?? '';
$cardId = (int)($_GET['card'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pages — Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f4f4;color:#333;line-height:1.5;}
.wrap{max-width:1080px;margin:0 auto;padding:1.2rem;}
header{background:#fff;border-bottom:2px solid #000;padding:.8rem 1.2rem;display:flex;justify-content:space-between;align-items:center;}
header h1{font-size:1.2rem;}
.btn{background:#000;color:#fff;border:none;padding:.5rem 1rem;border-radius:3px;font-size:.88rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.btn:hover{background:#333;}
.btn-sm{padding:.25rem .6rem;font-size:.8rem;}
.btn-outline{background:#fff;color:#333;border:1px solid #ccc;}
.btn-danger{background:#b91c1c;}
.ok{background:#dcfce7;color:#166534;padding:.6rem .9rem;border-radius:4px;margin:1rem 0;font-size:.9rem;}
.card-snip{background:#0e1116;color:#9acd32;padding:.6rem .9rem;border-radius:4px;font-family:monospace;font-size:.82rem;margin:.4rem 0 1rem;word-break:break-all;}
table{width:100%;border-collapse:collapse;background:#fff;margin-top:1rem;}
th,td{text-align:left;padding:.55rem .7rem;border-bottom:1px solid #eee;font-size:.86rem;}
th{background:#f9f9f9;font-size:.74rem;text-transform:uppercase;color:#555;}
.badge{padding:.12rem .5rem;border-radius:2px;font-size:.72rem;font-weight:700;text-transform:uppercase;}
.badge.published{background:#dcfce7;color:#166534;}.badge.draft{background:#fef3c7;color:#92400e;}.badge.archived{background:#e5e7eb;color:#374151;}
form.inline{display:inline;}
.fld{margin-bottom:1rem;}
.fld label{display:block;font-size:.78rem;text-transform:uppercase;color:#555;font-weight:700;margin-bottom:.25rem;}
.fld input,.fld select,.fld textarea{width:100%;padding:.5rem;border:1px solid #ccc;border-radius:3px;font-size:.9rem;font-family:inherit;}
.fld textarea{min-height:200px;font-family:monospace;}
.row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;}
.panel{background:#fff;border:1px solid #ddd;border-radius:4px;padding:1.2rem;margin-top:1rem;}
.muted{color:#888;font-size:.82rem;}
</style>
</head>
<body>
<header>
  <h1>Page Manager</h1>
  <div style="display:flex;gap:.6rem;align-items:center;">
    <a href="pages.php?action=new" class="btn btn-sm">+ New Page</a>
    <a href="collect.php?action=admin" class="btn btn-outline btn-sm">Recommendations</a>
    <a href="collect.php?action=logout" class="btn-outline btn-sm" style="padding:.25rem .6rem;border-radius:3px;text-decoration:none;color:#666;">Logout</a>
  </div>
</header>
<div class="wrap">
<?php if ($ok): ?><div class="ok"><?= e($ok) ?></div><?php endif; ?>
<?php if ($cardId): $cp = $db->query("SELECT * FROM pages WHERE id=" . $cardId)->fetch(); if ($cp): ?>
  <div class="muted">Paste this card link into works.html / projects.html:</div>
  <div class="card-snip"><?= e(cardSnippet($cp)) ?></div>
<?php endif; endif; ?>

<?php if ($isForm): ?>
  <div class="panel">
    <h2 style="font-size:1.05rem;margin-bottom:1rem;border-left:3px solid #000;padding-left:.6rem;">
      <?= $editing ? 'Edit Page' : 'New Page' ?></h2>
    <form method="POST" action="pages.php?action=save">
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
      <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
      <div class="fld"><label>Title</label>
        <input type="text" name="title" value="<?= e($editing['title'] ?? '') ?>" required></div>
      <div class="row">
        <div class="fld"><label>Slug (kebab-case)</label>
          <input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="auto from title if blank"></div>
        <div class="fld"><label>Type</label>
          <select name="type">
            <?php foreach (['case-study','project-preview','page'] as $t): ?>
              <option value="<?= $t ?>" <?= ($editing['type'] ?? '')===$t?'selected':'' ?>><?= typeLabel($t) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="fld"><label>Language</label>
          <select name="lang">
            <option value="en" <?= ($editing['lang'] ?? 'en')==='en'?'selected':'' ?>>English</option>
            <option value="ar" <?= ($editing['lang'] ?? '')==='ar'?'selected':'' ?>>العربية</option>
          </select></div>
      </div>
      <div class="fld"><label>Hero / OG image path (relative to site root)</label>
        <input type="text" name="og_image" value="<?= e($editing['og_image'] ?? '') ?>" placeholder="assets/images/projects/project1.webp"></div>
      <div class="fld"><label>Body (HTML)</label>
        <textarea name="body_html" placeholder="&lt;p&gt;Your content…&lt;/p&gt;"><?= e($editing['body_html'] ?? '') ?></textarea></div>
      <div style="display:flex;gap:.6rem;">
        <button type="submit" class="btn btn-outline">Save Draft</button>
        <button type="submit" name="publish" value="1" class="btn">Publish</button>
        <a href="pages.php?action=list" class="btn-outline btn-sm" style="padding:.5rem 1rem;border-radius:3px;text-decoration:none;color:#333;">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<table>
  <thead><tr><th>Title</th><th>Slug</th><th>Type</th><th>Lang</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="muted" style="padding:1.5rem;text-align:center;">No pages yet. Click “+ New Page”.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['title']) ?></td>
      <td class="muted"><?= e($r['slug']) ?></td>
      <td><?= typeLabel($r['type']) ?></td>
      <td><?= strtoupper($r['lang']) ?></td>
      <td><span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span></td>
      <td class="muted"><?= e(substr($r['updated_at'],0,16)) ?></td>
      <td style="white-space:nowrap;">
        <a href="pages.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
        <?php if ($r['status']==='published'): ?>
          <form class="inline" method="POST" action="pages.php?action=draft">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn btn-outline btn-sm">Unpublish</button></form>
        <?php else: ?>
          <form class="inline" method="POST" action="pages.php?action=publish">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn btn-sm">Publish</button></form>
        <?php endif; ?>
        <form class="inline" method="POST" action="pages.php?action=delete" onsubmit="return confirm('Delete this page and its file?');">
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn btn-danger btn-sm">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
<p class="muted" style="margin-top:1rem;">Drafts render to <code>work/_drafts/</code> with <code>noindex</code>. Publishing writes the live HTML and shows a card link to paste into your grid pages.</p>
</div>
</body>
</html>
