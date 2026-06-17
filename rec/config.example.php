<?php
/**
 * config.example.php — TEMPLATE. Copy to config.php and fill in real values.
 *   cp config.example.php config.php
 * config.php is git-ignored and must never be committed.
 */

// API key — prefer environment variable, fall back to literal.
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'sk-ant-YOUR-KEY-HERE');

// Database (create in cPanel → MySQL Databases)
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Admin login — store a bcrypt HASH, not plaintext. Generate with:
//   php -r "echo password_hash('your-admin-password', PASSWORD_DEFAULT), PHP_EOL;"
define('ADMIN_PASS_HASH', '$2y$12$REPLACE_WITH_GENERATED_HASH');

define('SITE_OWNER_EMAIL', 'you@example.com');
define('SITE_NAME', 'Mouaz AlSabbagh — Recommendation System');

// CORS allowlist — only these origins may call save/ai endpoints cross-origin.
$ALLOWED_ORIGINS = [
    'https://mouazalsabbagh.com',
    'https://www.mouazalsabbagh.com',
];
