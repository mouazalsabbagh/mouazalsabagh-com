#!/usr/bin/env bash
# build-deploy.sh — assemble a clean cPanel upload bundle.
# Usage:  bash scripts/build-deploy.sh
# Output: dist/deploy/  (the folder to upload to public_html)
#         dist/deploy.zip (zipped equivalent)
#
# What it does NOT include (kept off the live host):
#   - .git/ history, _archive/ (126 MB local PDFs), local tooling/docs
#   - rec/config.php (SECRETS — you create this ON THE SERVER, see RUNBOOK)
#   - node_modules, OS/editor cruft, drafts
set -euo pipefail
cd "$(dirname "$0")/.."                 # project root (script lives in scripts/)

OUT="dist/deploy"
ZIP="dist/deploy.zip"

echo "▶ Cleaning previous bundle…"
rm -rf "$OUT" "$ZIP"
mkdir -p "$OUT"

echo "▶ Copying deployable files (rsync with exclude list)…"
rsync -a \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude '.gemini/' \
  --exclude '.vscode/' \
  --exclude '.claude/' \
  --exclude 'dist/' \
  --exclude '_archive/' \
  --exclude 'docs-reports/' \
  --exclude 'scripts/' \
  --exclude 'database/' \
  --exclude 'handbook/' \
  --exclude 'templates/' \
  --exclude 'i18n/demo.html' \
  --exclude 'i18n/export_i18n.py' \
  --exclude 'work/_drafts/' \
  --exclude '**/.DS_Store' \
  --exclude '__pycache__/' \
  --exclude '*.pyc' \
  --exclude 'assets/sass/' \
  --exclude 'assets/docs/source/' \
  --exclude 'rec/config.php' \
  --exclude '.gitignore' \
  ./ "$OUT/"

# Ship the config TEMPLATE (not the real secrets) so the server has a reference.
if [ -f rec/config.example.php ]; then
  cp rec/config.example.php "$OUT/rec/config.example.php"
fi

echo "▶ Verifying no secrets leaked into the bundle…"
if [ -f "$OUT/rec/config.php" ]; then
  echo "✗ ERROR: rec/config.php is in the bundle — aborting." >&2
  exit 1
fi
# Match real keys (mixed alphanumerics, length 30+), but ignore docs placeholders
# like sk-ant-api03-xxxx… / sk-ant-...-REPLACE / sk-ant-YOUR-KEY-HERE.
if grep -rInE 'sk-ant-api03-[A-Za-z0-9_-]{30,}' "$OUT" 2>/dev/null \
     | grep -viE 'x{6,}|REPLACE|YOUR|PASTE|EXAMPLE|HERE'; then
  echo "✗ ERROR: a real-looking API key was found in the bundle — aborting." >&2
  exit 1
fi
echo "  ✓ no rec/config.php, no live key in bundle."

echo "▶ Zipping…"
( cd dist && zip -qr "$(basename "$ZIP")" "$(basename "$OUT")" )

echo "▶ Done."
echo "  Folder : $OUT  ($(du -sh "$OUT" | cut -f1))"
echo "  Zip    : $ZIP  ($(du -sh "$ZIP" | cut -f1))"
echo
echo "Next: follow scripts/DEPLOY-RUNBOOK.md to upload and configure on cPanel."
echo "Remember: create rec/config.php ON THE SERVER with ROTATED secrets — never upload the local one."
