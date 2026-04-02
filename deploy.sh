#!/bin/bash
#
# Deploy cecilia-dan-fine-art-theme to production via rsync over SSH
#
# Usage:
#   ./deploy.sh          — dry run (preview changes)
#   ./deploy.sh --go     — execute deployment
#

set -euo pipefail

# ─── Configuration ──────────────────────────────────────────────
REMOTE="TODO_SSH_USER@TODO_SSH_HOST"
REMOTE_PATH="files/wp-content/themes/cecilia-dan-fine-art-theme"
REMOTE_WP_PATH="files"
LOCAL_PATH="$(cd "$(dirname "$0")" && pwd)/"
SITE_URL="TODO_PRODUCTION_URL"

# Cloudflare cache purge (optional — set these to enable)
CF_ZONE_ID="${CF_ZONE_ID:-}"
CF_API_TOKEN="${CF_API_TOKEN:-}"

# ─── Pre-flight checks ─────────────────────────────────────────
echo "▸ Running pre-flight checks..."

# 1. Verify Vite build output exists
if [ ! -f "$LOCAL_PATH/public/build/manifest.json" ]; then
  echo "✗ No build manifest found. Running npm run build..."
  cd "$LOCAL_PATH" && npm run build
  echo "✓ Build complete."
fi

# 2. Verify vendor directory exists
if [ ! -d "$LOCAL_PATH/vendor" ]; then
  echo "✗ No vendor directory found. Run: composer install --no-dev"
  exit 1
fi

# 3. Check for dev server marker
if [ -f "$LOCAL_PATH/public/hot" ]; then
  echo "✗ Dev server is running (public/hot exists). Stop it before deploying."
  exit 1
fi

echo "✓ Pre-flight checks passed."
echo ""

# ─── Exclude list ───────────────────────────────────────────────
EXCLUDES=(
  # Version control & dev environment
  --exclude='.git/'
  --exclude='.gitignore'
  --exclude='.editorconfig'
  --exclude='.claude/'

  # Node.js / build tooling
  --exclude='node_modules/'
  --exclude='package.json'
  --exclude='package-lock.json'
  --exclude='vite.config.js'

  # Dev server marker
  --exclude='public/hot'

  # macOS metadata
  --exclude='.DS_Store'

  # This script and documentation (not needed on server)
  --exclude='deploy.sh'
  --exclude='maintenance.sh'
  --exclude='backup.sh'
  --exclude='SERVER-CHANGES.md'
  --exclude='LICENSE.md'
  --exclude='CLAUDE.md'
  --exclude='screenshot.png'
  --exclude='composer.json'
  --exclude='composer.lock'

  # Source CSS/JS/fonts (compiled output in public/build/ is deployed)
  --exclude='resources/css/'
  --exclude='resources/js/'
  --exclude='resources/fonts/'

  # Composer dev dependencies (vendor/ is needed, but trim dev extras)
  --exclude='vendor/laravel/pint/'
  --exclude='vendor/**/tests/'
  --exclude='vendor/**/Tests/'
  --exclude='vendor/**/test/'
  --exclude='vendor/**/.git/'
  --exclude='vendor/**/.github/'
  --exclude='vendor/**/doc/'
  --exclude='vendor/**/docs/'
  --exclude='vendor/**/*.md'
  --exclude='vendor/**/CHANGELOG*'
  --exclude='vendor/**/LICENSE*'
  --exclude='vendor/**/phpunit*'
  --exclude='vendor/**/phpstan*'
  --exclude='vendor/**/.editorconfig'
  --exclude='vendor/**/.gitignore'
  --exclude='vendor/**/.gitattributes'
  --exclude='vendor/**/composer.json'
  --exclude='vendor/**/*.map'
)

# ─── Determine mode ─────────────────────────────────────────────
if [ "${1:-}" = "--go" ]; then
  DRY_RUN=""
  echo "▸ DEPLOYING to $REMOTE:$REMOTE_PATH"
else
  DRY_RUN="--dry-run"
  echo "▸ DRY RUN — preview only (use --go to deploy)"
fi

echo ""

# ─── Deploy ─────────────────────────────────────────────────────
rsync -avz --delete \
  $DRY_RUN \
  "${EXCLUDES[@]}" \
  "$LOCAL_PATH" \
  "$REMOTE:$REMOTE_PATH"

echo ""

if [ -z "${DRY_RUN:-}" ]; then
  echo "✓ Deployment complete."

  # ─── Post-deploy: file tasks ───────────────────────────────────

  # Copy favicon to document root (direct /favicon.ico requests bypass theme <link> tags)
  echo "▸ Copying favicon.ico to document root..."
  ssh "$REMOTE" "cp $REMOTE_PATH/favicon.ico $REMOTE_WP_PATH/favicon.ico; echo '✓ Favicon copied.'"

  # Remove readme.html (leaks WP version, regenerated on every core update)
  ssh "$REMOTE" "rm -f $REMOTE_WP_PATH/readme.html"

  # ─── Post-deploy: cache purge ──────────────────────────────────

  # 1. Acorn caches (compiled Blade views, service/package discovery, file cache)
  echo "▸ Purging Acorn caches..."
  ssh "$REMOTE" "rm -rf $REMOTE_WP_PATH/wp-content/cache/acorn/framework/views/*.php $REMOTE_WP_PATH/wp-content/cache/acorn/framework/cache/data/* $REMOTE_WP_PATH/wp-content/cache/acorn/framework/cache/packages.php $REMOTE_WP_PATH/wp-content/cache/acorn/framework/cache/services.php 2>/dev/null"
  echo "  ✓ Acorn caches cleared."

  # 2. WordPress object cache (transients: modern format srcsets, format availability)
  echo "▸ Flushing object cache..."
  ssh "$REMOTE" "cd $REMOTE_WP_PATH && wp cache flush 2>/dev/null && echo '  ✓ Object cache flushed.' || echo '  ⚠ Object cache flush failed.'"

  # 3. SpinupWP page cache (nginx fastcgi-cache)
  echo "▸ Purging page cache..."
  ssh "$REMOTE" "cd $REMOTE_WP_PATH && wp spinupwp cache purge-site 2>/dev/null && echo '  ✓ Page cache purged.' || echo '  ⚠ SpinupWP cache purge not available.'"

  # 4. OPcache (PHP opcode cache — must be reset via web request, not CLI)
  echo "▸ Resetting OPcache..."
  OPCACHE_FILE="opcache-reset-$(date +%s).php"
  ssh "$REMOTE" "echo '<?php opcache_reset(); echo \"OK\"; unlink(__FILE__);' > $REMOTE_WP_PATH/$OPCACHE_FILE"
  OPCACHE_RESULT=$(curl -sf "${SITE_URL}/${OPCACHE_FILE}" 2>/dev/null || echo "FAIL")
  if [ "$OPCACHE_RESULT" = "OK" ]; then
    echo "  ✓ OPcache reset."
  else
    # Clean up if curl failed (file self-deletes on success)
    ssh "$REMOTE" "rm -f $REMOTE_WP_PATH/$OPCACHE_FILE 2>/dev/null"
    echo "  ⚠ OPcache reset failed (may require PHP-FPM restart)."
  fi

  # 5. WordPress rewrite rules
  echo "▸ Flushing rewrite rules..."
  ssh "$REMOTE" "cd $REMOTE_WP_PATH && wp rewrite flush 2>/dev/null && echo '  ✓ Rewrite rules flushed.' || echo '  ⚠ Rewrite flush failed.'"

  # 6. Cloudflare CDN cache (optional — requires CF_ZONE_ID and CF_API_TOKEN)
  if [ -n "$CF_ZONE_ID" ] && [ -n "$CF_API_TOKEN" ]; then
    echo "▸ Purging Cloudflare cache..."
    CF_RESPONSE=$(curl -sf -X POST \
      "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
      -H "Authorization: Bearer ${CF_API_TOKEN}" \
      -H "Content-Type: application/json" \
      --data '{"purge_everything":true}' 2>/dev/null)
    if echo "$CF_RESPONSE" | grep -q '"success":true'; then
      echo "  ✓ Cloudflare cache purged."
    else
      echo "  ⚠ Cloudflare purge failed. Check CF_ZONE_ID and CF_API_TOKEN."
    fi
  else
    echo "▸ Cloudflare: skipped (set CF_ZONE_ID and CF_API_TOKEN to enable)."
  fi

  echo ""
  echo "✓ All done."
else
  echo "▸ Dry run complete. Review the output above, then run:"
  echo "  ./deploy.sh --go"
fi
