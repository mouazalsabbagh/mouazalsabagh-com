<?php
/**
 * ai.php — Anthropic API Proxy
 * Keeps your API key server-side. The browser calls this file, not Anthropic directly.
 * Place in the same folder as index.html and collect.php
 */

// ── CONFIG (secrets live in config.php, never committed) ──────────────────────
require __DIR__ . '/config.php';

// ── CORS — allow only whitelisted origins ─────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $ALLOWED_ORIGINS, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── READ INCOMING PROMPT ──────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
$prompt = trim($input['prompt'] ?? '');

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['error' => 'No prompt provided']);
    exit;
}

// ── BASIC RATE LIMIT — 10 requests per IP per hour ───────────────────────────
$ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rlFile  = sys_get_temp_dir() . '/rec_rl_' . md5($ip) . '.json';
$now     = time();
$window  = 3600; // 1 hour
$limit   = 10;

$rl = file_exists($rlFile) ? json_decode(file_get_contents($rlFile), true) : ['count' => 0, 'start' => $now];
if ($now - $rl['start'] > $window) { $rl = ['count' => 0, 'start' => $now]; } // reset window
$rl['count']++;
file_put_contents($rlFile, json_encode($rl));

if ($rl['count'] > $limit) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again in an hour.']);
    exit;
}

// ── CALL ANTHROPIC API ────────────────────────────────────────────────────────
$payload = json_encode([
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 550,
    'messages'   => [['role' => 'user', 'content' => $prompt]]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
]);

$response   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach AI service: ' . $curlError]);
    exit;
}

// Pass response back to browser as-is
http_response_code($httpStatus);
echo $response;
