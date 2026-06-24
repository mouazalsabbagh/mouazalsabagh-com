<?php
/**
 * config.php — secrets and environment config. NEVER COMMIT (see .gitignore).
 * Production: replace with rotated values; keep this file out of the web root's
 * readable space (.htaccess denies it).
 */

// API key — prefer environment variable, fall back to literal.
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'sk-ant-REPLACE-WITH-ROTATED-KEY');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'omemfste_mouaz_recommendations');
define('DB_USER', 'omemfste_mouaz_recuser');
define('DB_PASS', 'CwiA~7oL*pmt'); // LOCAL value — rotate for production

// Admin login — bcrypt hash, NOT plaintext. Distinct from DB password.
// Local test password: MouazAdmin#2026  (regenerate for production)
define('ADMIN_PASS_HASH', '$2y$12$5LnmcNjRi90jUrevh1PbvulfWB7GEowsSxq2XkmR.gtFPassWi3Nu');

define('SITE_OWNER_EMAIL', 'mo3az.e.s@gmail.com');
define('SITE_NAME', 'Mouaz AlSabbagh — Recommendation System');

// CORS allowlist — only these origins may call save/ai endpoints cross-origin.
$ALLOWED_ORIGINS = [
    'https://mouazalsabbagh.com',
    'https://www.mouazalsabbagh.com',
    'http://localhost:8000', // local dev
];
