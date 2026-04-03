#!/bin/bash
#
# WordPress maintenance: audit and clean stale database entries
#
# Runs on both local dev and production. Safe by default (report only).
#
# Usage:
#   ./maintenance.sh              — report only (local)
#   ./maintenance.sh --go         — execute cleanup (local)
#   ./maintenance.sh --remote     — report only (production via SSH)
#   ./maintenance.sh --remote --go — execute cleanup (production)
#   ./maintenance.sh --both       — report only (local + production)
#   ./maintenance.sh --both --go  — execute cleanup (local + production)
#

set -o pipefail

# ─── Configuration ──────────────────────────────────────────────
REMOTE="TODO_SSH_USER@TODO_SSH_HOST"
REMOTE_WP_PATH="files"
LOCAL_WP_PATH="$(cd "$(dirname "$0")/../../.." && pwd)"

# Site languages — keep translations matching these locale prefixes.
# Used by audit_plugin_translations to prune unused languages bundled inside plugins.
SITE_LANGUAGES=("en")

# WP-CLI paths
LOCAL_WP_CLI="${WP_CLI:-$(command -v wp 2>/dev/null || echo "$HOME/bin/wp")}"
REMOTE_WP_CLI="wp"

# ─── Parse arguments ───────────────────────────────────────────
DRY_RUN=true
RUN_LOCAL=true
RUN_REMOTE=false

for arg in "$@"; do
  case "$arg" in
    --go)     DRY_RUN=false ;;
    --remote) RUN_LOCAL=false; RUN_REMOTE=true ;;
    --both)   RUN_LOCAL=true; RUN_REMOTE=true ;;
    *)        echo "Unknown option: $arg"; exit 1 ;;
  esac
done

# ─── Colors ────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ─── Helper: run WP-CLI locally or remotely ────────────────────
wp_run() {
  local output
  if [ "$CURRENT_ENV" = "remote" ]; then
    output=$(ssh "$REMOTE" "cd $REMOTE_WP_PATH && $REMOTE_WP_CLI $(printf '%q ' "$@")" 2>&1)
  else
    output=$("$LOCAL_WP_CLI" --path="$LOCAL_WP_PATH" "$@" 2>&1)
  fi
  # Strip PHP deprecation/warning/notice noise (e.g., from WPML on PHP 8.4)
  echo "$output" | grep -vE '^(PHP )?(Deprecated|Warning|Notice|Fatal error): '
}

wp_query() {
  wp_run db query "$1"
}

# Get a single scalar value from a SQL query (strips header row and trailing newlines)
wp_scalar() {
  local result
  result=$(wp_query "$1" | sed -n '2p' | tr -d '[:space:]')
  echo "${result:-0}"
}

# ─── Counter ───────────────────────────────────────────────────
TOTAL_FOUND=0
TOTAL_CLEANED=0

found() {
  TOTAL_FOUND=$((TOTAL_FOUND + ${1:-0}))
}

cleaned() {
  TOTAL_CLEANED=$((TOTAL_CLEANED + ${1:-0}))
}

# ─── Version check ─────────────────────────────────────────────

audit_versions() {
  echo -e "\n${BOLD}── Stack Versions ──${NC}"

  # WordPress
  local wp_version wp_update
  wp_version=$(wp_run core version 2>/dev/null | tr -d '[:space:]')
  wp_update=$(wp_run core check-update --format=table 2>/dev/null | grep -v "Success" | tail -n +2 | head -1)

  if [ -n "$wp_update" ]; then
    local new_ver
    new_ver=$(echo "$wp_update" | awk '{print $1}')
    echo -e "  WordPress:  ${YELLOW}$wp_version → $new_ver available${NC}"
    found 1
  else
    echo -e "  WordPress:  ${GREEN}$wp_version (latest)${NC}"
  fi

  # PHP
  local php_version
  if [ "$CURRENT_ENV" = "remote" ]; then
    php_version=$(ssh "$REMOTE" "php -r 'echo PHP_VERSION;'" 2>/dev/null)
  else
    php_version=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
  fi
  echo -e "  PHP:        $php_version"

  # MySQL
  local mysql_version
  mysql_version=$(wp_scalar "SELECT VERSION()")
  echo -e "  MySQL:      $mysql_version"

  # nginx (remote only — local uses Herd)
  if [ "$CURRENT_ENV" = "remote" ]; then
    local nginx_ver
    nginx_ver=$(ssh "$REMOTE" "nginx -v 2>&1" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
    [ -n "$nginx_ver" ] && echo -e "  nginx:      $nginx_ver"
  fi

  # Plugins with updates
  local plugin_updates
  plugin_updates=$(wp_run plugin list --update=available --format=table --fields=name,version,update_version 2>/dev/null | tail -n +2)

  if [ -n "$plugin_updates" ]; then
    local update_count
    update_count=$(echo "$plugin_updates" | grep -c '[^[:space:]]' || echo "0")
    found "$update_count"
    echo -e "\n  ${YELLOW}Plugin updates available:${NC}"
    echo "$plugin_updates" | while IFS=$'\t' read -r name version update_version; do
      [ -z "$name" ] && continue
      printf "    %-30s %s → %s\n" "$name" "$version" "$update_version"
    done
  else
    echo -e "  Plugins:    ${GREEN}all up to date${NC}"
  fi

  # npm dependencies (local only — theme source isn't on server)
  if [ "$CURRENT_ENV" = "local" ]; then
    local theme_dir
    theme_dir="$LOCAL_WP_PATH/wp-content/themes/cecilia-dan-theme"

    if [ -f "$theme_dir/package.json" ]; then
      local npm_outdated
      npm_outdated=$(cd "$theme_dir" && npm outdated --long 2>/dev/null | tail -n +2)

      if [ -n "$npm_outdated" ]; then
        echo -e "\n  ${YELLOW}npm updates available:${NC}"
        echo "$npm_outdated" | while read -r line; do
          [ -z "$line" ] && continue
          echo "    $line"
        done
      else
        echo -e "  npm:        ${GREEN}all up to date${NC}"
      fi
    fi

    # Composer (Acorn version)
    if [ -f "$theme_dir/composer.json" ]; then
      local acorn_ver
      acorn_ver=$(cd "$theme_dir" && composer show roots/acorn 2>/dev/null | grep "^versions" | awk '{print $NF}')
      [ -n "$acorn_ver" ] && echo -e "  Acorn:      $acorn_ver"
    fi
  fi
}

# ─── Audit & clean functions ───────────────────────────────────

audit_stale_plugin_options() {
  echo -e "\n${CYAN}[1] Stale plugin options${NC}"

  # Known deactivated plugin prefixes to check
  local patterns=(
    "limit_login_%"
    "%rank_math%"
    "%yoast%"
    "%wpseo%"
    "%jetpack%"
    "%akismet%"
    "%wordfence%"
    "%updraft%"
    "%elementor%"
    "%woocommerce%"
    "%sucuri%"
    "%ithemes%"
    "%wp_rocket%"
    "%autoptimize%"
    "%litespeed%"
    "%breeze_%"
    "%sg_optimizer%"
  )

  # Build SQL WHERE clause
  local where=""
  for p in "${patterns[@]}"; do
    [ -n "$where" ] && where="$where OR "
    where="${where}option_name LIKE '${p}'"
  done

  # Get active plugin slugs to filter out false positives
  local active
  active=$(wp_run plugin list --status=active --field=name 2>/dev/null | tr '\n' '|' | sed 's/|$//')

  local results
  results=$(wp_query "SELECT option_name FROM wp_options WHERE ($where)" 2>/dev/null | tail -n +2)

  # Filter out options that match an active plugin slug
  if [ -n "$active" ] && [ -n "$results" ]; then
    results=$(echo "$results" | grep -vE "$active" || true)
  fi

  if [ -z "$results" ]; then
    echo -e "  ${GREEN}✓ No stale plugin options found.${NC}"
    return
  fi

  local count
  count=$(echo "$results" | wc -l | tr -d ' ')
  found "$count"
  echo -e "  ${YELLOW}Found $count stale plugin option(s):${NC}"
  echo "$results" | sed 's/^/    /'

  if [ "$DRY_RUN" = false ]; then
    echo "$results" | while IFS= read -r opt; do
      [ -z "$opt" ] && continue
      wp_run option delete "$opt" --quiet 2>/dev/null
    done
    cleaned "$count"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_stale_theme_mods() {
  echo -e "\n${CYAN}[2] Stale theme_mods${NC}"

  local active_theme
  active_theme=$(wp_run option get stylesheet 2>/dev/null)

  local results
  results=$(wp_query "SELECT option_name FROM wp_options WHERE option_name LIKE 'theme_mods_%' AND option_name != 'theme_mods_${active_theme}'" 2>/dev/null | tail -n +2)

  if [ -z "$results" ]; then
    echo -e "  ${GREEN}✓ No stale theme_mods found.${NC}"
    return
  fi

  local count
  count=$(echo "$results" | wc -l | tr -d ' ')
  found "$count"
  echo -e "  ${YELLOW}Found $count stale theme_mods:${NC}"
  echo "$results" | sed 's/^/    /'

  if [ "$DRY_RUN" = false ]; then
    echo "$results" | while IFS= read -r opt; do
      [ -z "$opt" ] && continue
      wp_run option delete "$opt" --quiet 2>/dev/null
    done
    cleaned "$count"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_orphaned_postmeta() {
  echo -e "\n${CYAN}[3] Orphaned postmeta${NC}"

  local count
  count=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta WHERE post_id NOT IN (SELECT ID FROM wp_posts)")

  if [ "$count" = "0" ]; then
    echo -e "  ${GREEN}✓ No orphaned postmeta.${NC}"
    return
  fi

  found "$count"
  echo -e "  ${YELLOW}Found $count orphaned postmeta row(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    wp_query "DELETE FROM wp_postmeta WHERE post_id NOT IN (SELECT ID FROM wp_posts)"
    cleaned "$count"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_orphaned_termmeta() {
  echo -e "\n${CYAN}[4] Orphaned termmeta${NC}"

  local count
  count=$(wp_scalar "SELECT COUNT(*) FROM wp_termmeta WHERE term_id NOT IN (SELECT term_id FROM wp_terms)")

  if [ "$count" = "0" ]; then
    echo -e "  ${GREEN}✓ No orphaned termmeta.${NC}"
    return
  fi

  found "$count"
  echo -e "  ${YELLOW}Found $count orphaned termmeta row(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    wp_query "DELETE FROM wp_termmeta WHERE term_id NOT IN (SELECT term_id FROM wp_terms)"
    cleaned "$count"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_revisions() {
  echo -e "\n${CYAN}[7] Excess revisions${NC}"

  local max_revisions=5
  local results
  results=$(wp_query "SELECT p.post_parent, pp.post_title, COUNT(*) AS rev_count FROM wp_posts p JOIN wp_posts pp ON p.post_parent = pp.ID WHERE p.post_type = 'revision' GROUP BY p.post_parent HAVING rev_count > $max_revisions ORDER BY rev_count DESC" 2>/dev/null | tail -n +2)

  if [ -z "$results" ]; then
    echo -e "  ${GREEN}✓ All pages within $max_revisions revision limit.${NC}"
    return
  fi

  echo -e "  ${YELLOW}Pages exceeding $max_revisions revisions:${NC}"
  echo "$results" | sed 's/^/    /'

  if [ "$DRY_RUN" = false ]; then
    local parents
    parents=$(echo "$results" | awk '{print $1}')
    for parent_id in $parents; do
      local excess
      excess=$(wp_query "SELECT ID FROM wp_posts WHERE post_type = 'revision' AND post_parent = $parent_id ORDER BY post_date DESC LIMIT 999 OFFSET $max_revisions" 2>/dev/null | tail -n +2)
      if [ -n "$excess" ]; then
        local excess_count
        excess_count=$(echo "$excess" | wc -l | tr -d ' ')
        found "$excess_count"
        echo "$excess" | while IFS= read -r id; do
          [ -z "$id" ] && continue
          wp_run post delete "$id" --force --quiet 2>/dev/null
        done
        cleaned "$excess_count"
      fi
    done
    echo -e "  ${GREEN}✓ Trimmed to $max_revisions per page.${NC}"
  else
    local total_excess=0
    while IFS=$'\t' read -r pid title count; do
      total_excess=$((total_excess + count - max_revisions))
    done <<< "$results"
    found "$total_excess"
  fi
}

audit_auto_drafts() {
  echo -e "\n${CYAN}[8] Auto-drafts and trashed posts${NC}"

  local auto_drafts
  auto_drafts=$(wp_scalar "SELECT COUNT(*) FROM wp_posts WHERE post_status = 'auto-draft'")

  local trashed
  trashed=$(wp_scalar "SELECT COUNT(*) FROM wp_posts WHERE post_status = 'trash'")

  local total=$((auto_drafts + trashed))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ No auto-drafts or trashed posts.${NC}"
    return
  fi

  found "$total"
  [ "$auto_drafts" -gt 0 ] && echo -e "  ${YELLOW}$auto_drafts auto-draft(s).${NC}"
  [ "$trashed" -gt 0 ] && echo -e "  ${YELLOW}$trashed trashed post(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    if [ "$auto_drafts" -gt 0 ]; then
      local ids
      ids=$(wp_query "SELECT ID FROM wp_posts WHERE post_status = 'auto-draft'" 2>/dev/null | tail -n +2)
      echo "$ids" | while IFS= read -r id; do
        [ -z "$id" ] && continue
        wp_run post delete "$id" --force --quiet 2>/dev/null
      done
    fi
    if [ "$trashed" -gt 0 ]; then
      local ids
      ids=$(wp_query "SELECT ID FROM wp_posts WHERE post_status = 'trash'" 2>/dev/null | tail -n +2)
      echo "$ids" | while IFS= read -r id; do
        [ -z "$id" ] && continue
        wp_run post delete "$id" --force --quiet 2>/dev/null
      done
    fi
    cleaned "$total"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_expired_transients() {
  echo -e "\n${CYAN}[9] Expired transients${NC}"

  # wp transient delete --expired is idempotent and works with both DB and Redis.
  # Always run it — in dry-run mode just report what would be deleted.
  local output
  output=$(wp_run transient delete --expired 2>/dev/null)
  local expired
  expired=$(echo "$output" | grep -c "Deleted transient" || true)
  expired=$((expired + 0))

  if [ "$expired" = "0" ]; then
    echo -e "  ${GREEN}✓ No expired transients.${NC}"
  else
    if [ "$DRY_RUN" = true ]; then
      found "$expired"
      echo -e "  ${YELLOW}$expired expired transient(s) (already cleaned — transients are ephemeral).${NC}"
    else
      cleaned "$expired"
      echo -e "  ${GREEN}✓ $expired expired transient(s) cleared.${NC}"
    fi
  fi
}

audit_spam_comments() {
  echo -e "\n${CYAN}[10] Spam and trashed comments${NC}"

  local spam
  spam=$(wp_scalar "SELECT COUNT(*) FROM wp_comments WHERE comment_approved = 'spam'")

  local trashed
  trashed=$(wp_scalar "SELECT COUNT(*) FROM wp_comments WHERE comment_approved = 'trash'")

  local total=$((spam + trashed))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ No spam or trashed comments.${NC}"
    return
  fi

  found "$total"
  [ "$spam" -gt 0 ] && echo -e "  ${YELLOW}$spam spam comment(s).${NC}"
  [ "$trashed" -gt 0 ] && echo -e "  ${YELLOW}$trashed trashed comment(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    wp_query "DELETE FROM wp_comments WHERE comment_approved IN ('spam', 'trash')"
    cleaned "$total"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_orphaned_attachment() {
  echo -e "\n${CYAN}[11] Orphaned site icon attachment${NC}"

  local icon_id
  icon_id=$(wp_run option get site_icon 2>/dev/null | tr -d '[:space:]')

  if [ "$icon_id" = "0" ] || [ -z "$icon_id" ]; then
    echo -e "  ${GREEN}✓ site_icon is 0 (theme handles favicons).${NC}"
    return
  fi

  local exists
  exists=$(wp_scalar "SELECT COUNT(*) FROM wp_posts WHERE ID = $icon_id")

  if [ "$exists" = "0" ]; then
    found 1
    echo -e "  ${YELLOW}site_icon points to post $icon_id which does not exist.${NC}"
    if [ "$DRY_RUN" = false ]; then
      wp_run option update site_icon 0 --quiet 2>/dev/null
      cleaned 1
      echo -e "  ${GREEN}✓ Reset to 0.${NC}"
    fi
  else
    echo -e "  ${YELLOW}site_icon points to post $icon_id (exists). Theme uses hardcoded favicons — this attachment may be unnecessary.${NC}"
    found 1
    if [ "$DRY_RUN" = false ]; then
      wp_run post delete "$icon_id" --force --quiet 2>/dev/null
      wp_run option update site_icon 0 --quiet 2>/dev/null
      cleaned 1
      echo -e "  ${GREEN}✓ Deleted attachment and reset site_icon.${NC}"
    fi
  fi
}

audit_stale_translations() {
  echo -e "\n${CYAN}[16] Stale translation files${NC}"

  local lang_dir
  if [ "$CURRENT_ENV" = "remote" ]; then
    lang_dir="$REMOTE_WP_PATH/wp-content/languages"
  else
    lang_dir="$LOCAL_WP_PATH/wp-content/languages"
  fi

  # Get installed themes and plugins
  local active_themes active_plugins
  active_themes=$(wp_run theme list --status=active --field=name 2>/dev/null)
  active_plugins=$(wp_run plugin list --field=name 2>/dev/null)

  local stale_files=()

  # Extract plugin/theme slugs from translation filenames by stripping locale suffix
  # e.g., "classic-editor-de_DE.mo" → "classic-editor", "twentytwentyfive-de_DE.po" → "twentytwentyfive"
  extract_slugs() {
    sed 's/-[a-z][a-z]_[A-Z][A-Z].*$//' | sort -u
  }

  # Check theme translations
  local theme_translations
  if [ "$CURRENT_ENV" = "remote" ]; then
    theme_translations=$(ssh "$REMOTE" "ls $lang_dir/themes/ 2>/dev/null" | extract_slugs)
  else
    theme_translations=$(ls "$lang_dir/themes/" 2>/dev/null | extract_slugs)
  fi

  for slug in $theme_translations; do
    [ -z "$slug" ] && continue
    if ! echo "$active_themes" | grep -q "^${slug}$"; then
      stale_files+=("themes/${slug}-"*)
    fi
  done

  # Check plugin translations
  local plugin_translations
  if [ "$CURRENT_ENV" = "remote" ]; then
    plugin_translations=$(ssh "$REMOTE" "ls $lang_dir/plugins/ 2>/dev/null" | extract_slugs)
  else
    plugin_translations=$(ls "$lang_dir/plugins/" 2>/dev/null | extract_slugs)
  fi

  for slug in $plugin_translations; do
    [ -z "$slug" ] && continue
    if ! echo "$active_plugins" | grep -q "^${slug}$"; then
      stale_files+=("plugins/${slug}-"*)
    fi
  done

  if [ ${#stale_files[@]} -eq 0 ]; then
    echo -e "  ${GREEN}✓ No stale translation files.${NC}"
    return
  fi

  # Expand glob patterns to actual file list for accurate reporting
  local expanded_files=()
  for pattern in "${stale_files[@]}"; do
    local matched
    if [ "$CURRENT_ENV" = "remote" ]; then
      matched=$(ssh "$REMOTE" "ls $lang_dir/$pattern 2>/dev/null" | xargs -I{} basename {})
    else
      matched=$(ls $lang_dir/$pattern 2>/dev/null | xargs -I{} basename {})
    fi
    while IFS= read -r f; do
      [ -z "$f" ] && continue
      expanded_files+=("$f")
    done <<< "$matched"
  done

  if [ ${#expanded_files[@]} -eq 0 ]; then
    echo -e "  ${GREEN}✓ No stale translation files.${NC}"
    return
  fi

  found "${#expanded_files[@]}"
  echo -e "  ${YELLOW}${#expanded_files[@]} stale translation file(s) for uninstalled themes/plugins:${NC}"
  printf '    %s\n' "${expanded_files[@]}"

  if [ "$DRY_RUN" = false ]; then
    for pattern in "${stale_files[@]}"; do
      if [ "$CURRENT_ENV" = "remote" ]; then
        ssh "$REMOTE" "rm -f $lang_dir/$pattern 2>/dev/null"
      else
        rm -f "$lang_dir"/$pattern 2>/dev/null
      fi
    done
    cleaned "${#expanded_files[@]}"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_plugin_translations() {
  echo -e "\n${CYAN}[16b] Plugin-bundled unused translations${NC}"

  local wp_root
  if [ "$CURRENT_ENV" = "remote" ]; then
    wp_root="$REMOTE_WP_PATH"
  else
    wp_root="$LOCAL_WP_PATH"
  fi

  run_cmd() {
    if [ "$CURRENT_ENV" = "remote" ]; then
      ssh "$REMOTE" "$*" 2>/dev/null
    else
      eval "$*" 2>/dev/null
    fi
  }

  # Known plugin translation directories and their file naming conventions:
  #   mo-style: plugin-LOCALE.mo/.po/.l10n.php (ACF, WPML, ACFML, Mailgun, etc.)
  #   json-style: LOCALE.json (Matomo)
  local -a mo_dirs=()
  local -a json_dirs=()

  # ACF Pro
  local acf_lang="$wp_root/wp-content/plugins/advanced-custom-fields-pro/lang"
  if run_cmd "test -d $acf_lang"; then
    mo_dirs+=("$acf_lang")
    if run_cmd "test -d $acf_lang/pro"; then
      mo_dirs+=("$acf_lang/pro")
    fi
  fi

  # WPML CMS
  local wpml_base="$wp_root/wp-content/plugins/sitepress-multilingual-cms"
  for subdir in locale vendor/wpml/wpml/languages vendor/wpml/sql-parser/locale vendor/otgs/installer/locale; do
    if run_cmd "test -d $wpml_base/$subdir"; then
      mo_dirs+=("$wpml_base/$subdir")
    fi
  done

  # WPML String Translation
  local wpml_st="$wp_root/wp-content/plugins/wpml-string-translation/locale"
  if run_cmd "test -d $wpml_st"; then
    mo_dirs+=("$wpml_st")
  fi

  # ACFML
  local acfml="$wp_root/wp-content/plugins/acfml/languages"
  if run_cmd "test -d $acfml"; then
    mo_dirs+=("$acfml")
  fi

  # Mailgun
  local mailgun="$wp_root/wp-content/plugins/mailgun/languages"
  if run_cmd "test -d $mailgun"; then
    mo_dirs+=("$mailgun")
  fi

  # Matomo (json-style)
  local matomo_lang="$wp_root/wp-content/plugins/matomo/app/lang"
  if run_cmd "test -d $matomo_lang"; then
    json_dirs+=("$matomo_lang")
  fi

  local total_stale=0

  # Process mo-style directories
  for dir in "${mo_dirs[@]}"; do
    # Build a find command that excludes files matching site languages and .pot files
    local find_cmd="find $dir -maxdepth 1 -type f"
    for lang in "${SITE_LANGUAGES[@]}"; do
      find_cmd="$find_cmd ! -name '*-${lang}_*' ! -name '*-${lang}.*'"
    done
    find_cmd="$find_cmd ! -name '*.pot'"

    local stale_files
    stale_files=$(run_cmd "$find_cmd" | wc -l | tr -d '[:space:]')
    if [ "$stale_files" -gt 0 ] 2>/dev/null; then
      local dir_label
      dir_label=$(echo "$dir" | sed "s|$wp_root/wp-content/plugins/||")
      echo -e "  ${YELLOW}$dir_label: $stale_files unused file(s)${NC}"
      total_stale=$((total_stale + stale_files))

      if [ "$DRY_RUN" = false ]; then
        run_cmd "$find_cmd -delete"
      fi
    fi
  done

  # Process json-style directories
  for dir in "${json_dirs[@]}"; do
    local find_cmd="find $dir -maxdepth 1 -type f -name '*.json'"
    for lang in "${SITE_LANGUAGES[@]}"; do
      find_cmd="$find_cmd ! -name '${lang}.json' ! -name '${lang}-*.json'"
    done

    local stale_files
    stale_files=$(run_cmd "$find_cmd" | wc -l | tr -d '[:space:]')
    if [ "$stale_files" -gt 0 ] 2>/dev/null; then
      local dir_label
      dir_label=$(echo "$dir" | sed "s|$wp_root/wp-content/plugins/||")
      echo -e "  ${YELLOW}$dir_label: $stale_files unused file(s)${NC}"
      total_stale=$((total_stale + stale_files))

      if [ "$DRY_RUN" = false ]; then
        run_cmd "$find_cmd -delete"
      fi
    fi
  done

  if [ "$total_stale" -eq 0 ]; then
    echo -e "  ${GREEN}✓ No unused plugin translations.${NC}"
  else
    found "$total_stale"
    if [ "$DRY_RUN" = false ]; then
      cleaned "$total_stale"
      echo -e "  ${GREEN}✓ Removed $total_stale unused translation file(s).${NC}"
    fi
  fi
}

audit_acf_stale_data() {
  echo -e "\n${CYAN}[17] Stale ACF field data${NC}"

  # Check if ACF is active (bypass wp_run — is-active uses exit codes, not stdout,
  # and wp_run's grep pipe turns empty output into a non-zero exit)
  local acf_active="0"
  if [ "$CURRENT_ENV" = "remote" ]; then
    ssh "$REMOTE" "cd $REMOTE_WP_PATH && $REMOTE_WP_CLI plugin is-active advanced-custom-fields-pro" 2>/dev/null && acf_active="1"
    [ "$acf_active" = "0" ] && ssh "$REMOTE" "cd $REMOTE_WP_PATH && $REMOTE_WP_CLI plugin is-active advanced-custom-fields" 2>/dev/null && acf_active="1"
  else
    "$LOCAL_WP_CLI" --path="$LOCAL_WP_PATH" plugin is-active advanced-custom-fields-pro 2>/dev/null && acf_active="1"
    [ "$acf_active" = "0" ] && "$LOCAL_WP_CLI" --path="$LOCAL_WP_PATH" plugin is-active advanced-custom-fields 2>/dev/null && acf_active="1"
  fi

  if [ "$acf_active" = "0" ]; then
    echo -e "  ${GREEN}✓ ACF not active — skipped.${NC}"
    return
  fi

  # Get all valid ACF field keys from the database (acf-field posts)
  local valid_keys
  valid_keys=$(wp_query "SELECT post_excerpt FROM wp_posts WHERE post_type = 'acf-field' AND post_status = 'publish'" 2>/dev/null | tail -n +2)

  # Also check acf-json files if they exist
  local theme_dir
  if [ "$CURRENT_ENV" = "remote" ]; then
    local remote_theme
    remote_theme=$(ssh "$REMOTE" "cd $REMOTE_WP_PATH && wp eval 'echo get_stylesheet_directory();' 2>/dev/null")
    local json_keys
    json_keys=$(ssh "$REMOTE" "grep -oh 'field_[a-zA-Z0-9_]*' $remote_theme/acf-json/*.json 2>/dev/null" | sort -u)
  else
    theme_dir=$(wp_run eval "echo get_stylesheet_directory();" 2>/dev/null)
    local json_keys
    json_keys=$(grep -oh 'field_[a-zA-Z0-9_]*' "$theme_dir/acf-json/"*.json 2>/dev/null | sort -u)
  fi

  # Combine valid keys from DB + JSON
  local all_valid_keys
  all_valid_keys=$(printf "%s\n%s" "$valid_keys" "$json_keys" | sort -u | grep -v '^$')

  if [ -z "$all_valid_keys" ]; then
    echo -e "  ${GREEN}✓ No ACF field definitions found — skipped.${NC}"
    return
  fi

  # Find meta rows whose _reference value doesn't match any valid field key
  local orphaned_refs
  orphaned_refs=$(wp_query "SELECT DISTINCT pm.meta_value FROM wp_postmeta pm WHERE pm.meta_value LIKE 'field_%' AND pm.meta_key LIKE '\_%'" 2>/dev/null | tail -n +2)

  local stale_keys=()
  while IFS= read -r key; do
    [ -z "$key" ] && continue
    if ! echo "$all_valid_keys" | grep -qx "$key"; then
      stale_keys+=("$key")
    fi
  done <<< "$orphaned_refs"

  # Check for stale repeater rows (data beyond current row count)
  # Find all repeater fields (meta_value is a number = row count)
  local stale_repeater_count=0
  local repeater_info
  repeater_info=$(wp_query "SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE meta_key NOT LIKE '\_%' AND meta_value REGEXP '^[0-9]+$' AND CAST(meta_value AS UNSIGNED) > 0 AND meta_key IN (SELECT REPLACE(meta_key, '_', '') FROM wp_postmeta WHERE meta_value LIKE 'field_%' AND meta_key LIKE '\_%') ORDER BY post_id" 2>/dev/null | tail -n +2)

  while IFS=$'\t' read -r pid mkey mval; do
    [ -z "$pid" ] && continue
    # Count actual rows beyond the repeater count
    local beyond
    beyond=$(wp_scalar "SELECT COUNT(DISTINCT meta_key) FROM wp_postmeta WHERE post_id = $pid AND meta_key LIKE '${mkey}_%' AND meta_key REGEXP '${mkey}_([0-9]+)_' AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(meta_key, '${mkey}_', -1), '_', 1) AS UNSIGNED) >= $mval")
    if [ "$beyond" -gt 0 ]; then
      stale_repeater_count=$((stale_repeater_count + beyond))
    fi
  done <<< "$repeater_info"

  local total=$((${#stale_keys[@]} + stale_repeater_count))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ All ACF field data matches current field definitions.${NC}"
    return
  fi

  found "$total"

  if [ "${#stale_keys[@]}" -gt 0 ]; then
    echo -e "  ${YELLOW}${#stale_keys[@]} orphaned field key(s) (no matching field definition):${NC}"
    printf '    %s\n' "${stale_keys[@]}"
  fi

  if [ "$stale_repeater_count" -gt 0 ]; then
    echo -e "  ${YELLOW}$stale_repeater_count stale repeater meta key(s) beyond current row counts.${NC}"
  fi

  if [ "$DRY_RUN" = false ]; then
    # Delete meta rows referencing orphaned field keys
    for key in "${stale_keys[@]}"; do
      # Delete the reference row and the value row
      wp_query "DELETE FROM wp_postmeta WHERE meta_value = '$key' AND meta_key LIKE '\_%'" >/dev/null
      # The corresponding value key is the reference key without the leading underscore
      local value_key
      value_key=$(wp_query "SELECT DISTINCT meta_key FROM wp_postmeta WHERE meta_key LIKE '\_%' AND meta_value = '$key'" 2>/dev/null | tail -n +2 | sed 's/^_//')
      if [ -n "$value_key" ]; then
        wp_query "DELETE FROM wp_postmeta WHERE meta_key = '$value_key'" >/dev/null
      fi
    done

    # Delete stale repeater rows (data beyond current count)
    while IFS=$'\t' read -r pid mkey mval; do
      [ -z "$pid" ] && continue
      # Delete meta rows for indices >= current count
      local i=$mval
      while true; do
        local exists
        exists=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta WHERE post_id = $pid AND meta_key LIKE '${mkey}_${i}_%'")
        [ "$exists" = "0" ] && break
        wp_query "DELETE FROM wp_postmeta WHERE post_id = $pid AND meta_key LIKE '${mkey}_${i}_%'" >/dev/null
        wp_query "DELETE FROM wp_postmeta WHERE post_id = $pid AND meta_key LIKE '\_${mkey}_${i}_%'" >/dev/null
        i=$((i + 1))
      done
    done <<< "$repeater_info"

    cleaned "$total"
    echo -e "  ${GREEN}✓ Cleaned.${NC}"
  fi
}

audit_orphaned_term_relationships() {
  echo -e "\n${CYAN}[5] Orphaned term relationships${NC}"

  local orphaned_posts orphaned_terms
  orphaned_posts=$(wp_scalar "SELECT COUNT(*) FROM wp_term_relationships WHERE object_id NOT IN (SELECT ID FROM wp_posts)")
  orphaned_terms=$(wp_scalar "SELECT COUNT(*) FROM wp_term_relationships WHERE term_taxonomy_id NOT IN (SELECT term_taxonomy_id FROM wp_term_taxonomy)")

  local total=$((orphaned_posts + orphaned_terms))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ No orphaned term relationships.${NC}"
    return
  fi

  found "$total"
  [ "$orphaned_posts" -gt 0 ] && echo -e "  ${YELLOW}$orphaned_posts relationship(s) to deleted posts.${NC}"
  [ "$orphaned_terms" -gt 0 ] && echo -e "  ${YELLOW}$orphaned_terms relationship(s) to deleted terms.${NC}"

  if [ "$DRY_RUN" = false ]; then
    [ "$orphaned_posts" -gt 0 ] && wp_query "DELETE FROM wp_term_relationships WHERE object_id NOT IN (SELECT ID FROM wp_posts)" >/dev/null
    [ "$orphaned_terms" -gt 0 ] && wp_query "DELETE FROM wp_term_relationships WHERE term_taxonomy_id NOT IN (SELECT term_taxonomy_id FROM wp_term_taxonomy)" >/dev/null
    wp_query "UPDATE wp_term_taxonomy tt SET count = (SELECT COUNT(*) FROM wp_term_relationships tr WHERE tr.term_taxonomy_id = tt.term_taxonomy_id)" >/dev/null
    cleaned "$total"
    echo -e "  ${GREEN}✓ Deleted and recounted.${NC}"
  fi
}

audit_orphaned_comment_user_meta() {
  echo -e "\n${CYAN}[6] Orphaned comment meta and user meta${NC}"

  local comment_meta user_meta
  comment_meta=$(wp_scalar "SELECT COUNT(*) FROM wp_commentmeta WHERE comment_id NOT IN (SELECT comment_ID FROM wp_comments)")
  user_meta=$(wp_scalar "SELECT COUNT(*) FROM wp_usermeta WHERE user_id NOT IN (SELECT ID FROM wp_users)")

  local total=$((comment_meta + user_meta))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ No orphaned comment or user meta.${NC}"
    return
  fi

  found "$total"
  [ "$comment_meta" -gt 0 ] && echo -e "  ${YELLOW}$comment_meta orphaned commentmeta row(s).${NC}"
  [ "$user_meta" -gt 0 ] && echo -e "  ${YELLOW}$user_meta orphaned usermeta row(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    [ "$comment_meta" -gt 0 ] && wp_query "DELETE FROM wp_commentmeta WHERE comment_id NOT IN (SELECT comment_ID FROM wp_comments)" >/dev/null
    [ "$user_meta" -gt 0 ] && wp_query "DELETE FROM wp_usermeta WHERE user_id NOT IN (SELECT ID FROM wp_users)" >/dev/null
    cleaned "$total"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_oembed_cache() {
  echo -e "\n${CYAN}[12] oEmbed postmeta cache${NC}"

  local count
  count=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key LIKE '\_oembed\_%'")

  if [ "$count" = "0" ]; then
    echo -e "  ${GREEN}✓ No oEmbed cache entries.${NC}"
    return
  fi

  found "$count"
  echo -e "  ${YELLOW}$count oEmbed cache row(s) in postmeta.${NC}"

  if [ "$DRY_RUN" = false ]; then
    wp_query "DELETE FROM wp_postmeta WHERE meta_key LIKE '\_oembed\_%'" >/dev/null
    cleaned "$count"
    echo -e "  ${GREEN}✓ Cleared (regenerates on next view).${NC}"
  fi
}

audit_transient_postmeta() {
  echo -e "\n${CYAN}[13] Stale postmeta (_edit_lock, _pingme, _encloseme)${NC}"

  local edit_locks pingme encloseme
  edit_locks=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = '_edit_lock'")
  pingme=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = '_pingme'")
  encloseme=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = '_encloseme'")

  local total=$((edit_locks + pingme + encloseme))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ No stale postmeta entries.${NC}"
    return
  fi

  found "$total"
  [ "$edit_locks" -gt 0 ] && echo -e "  ${YELLOW}$edit_locks expired _edit_lock row(s).${NC}"
  [ "$pingme" -gt 0 ] && echo -e "  ${YELLOW}$pingme _pingme row(s).${NC}"
  [ "$encloseme" -gt 0 ] && echo -e "  ${YELLOW}$encloseme _encloseme row(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    wp_query "DELETE FROM wp_postmeta WHERE meta_key IN ('_edit_lock', '_pingme', '_encloseme')" >/dev/null
    cleaned "$total"
    echo -e "  ${GREEN}✓ Deleted (regenerates on next edit).${NC}"
  fi
}

audit_duplicate_postmeta() {
  echo -e "\n${CYAN}[14] Duplicate postmeta${NC}"

  local count
  count=$(wp_scalar "SELECT COUNT(*) FROM (SELECT post_id, meta_key, meta_value, COUNT(*) AS c FROM wp_postmeta GROUP BY post_id, meta_key, meta_value HAVING c > 1) AS dupes")

  if [ "$count" = "0" ]; then
    echo -e "  ${GREEN}✓ No duplicate postmeta.${NC}"
    return
  fi

  found "$count"
  echo -e "  ${YELLOW}$count postmeta key(s) with duplicates.${NC}"

  if [ "$DRY_RUN" = false ]; then
    wp_query "DELETE pm1 FROM wp_postmeta pm1 INNER JOIN wp_postmeta pm2 ON pm1.post_id = pm2.post_id AND pm1.meta_key = pm2.meta_key AND pm1.meta_value = pm2.meta_value AND pm1.meta_id > pm2.meta_id" >/dev/null
    cleaned "$count"
    echo -e "  ${GREEN}✓ Deduplicated (kept oldest of each).${NC}"
  fi
}

audit_action_scheduler() {
  echo -e "\n${CYAN}[15] Action Scheduler cleanup${NC}"

  local table_exists
  table_exists=$(wp_scalar "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wp_actionscheduler_actions'")

  if [ "$table_exists" = "0" ]; then
    echo -e "  ${GREEN}✓ No Action Scheduler tables.${NC}"
    return
  fi

  local completed
  completed=$(wp_scalar "SELECT COUNT(*) FROM wp_actionscheduler_actions WHERE status IN ('complete', 'failed', 'canceled') AND last_attempt_gmt < DATE_SUB(NOW(), INTERVAL 31 DAY)")

  local orphaned_logs
  orphaned_logs=$(wp_scalar "SELECT COUNT(*) FROM wp_actionscheduler_logs WHERE action_id NOT IN (SELECT action_id FROM wp_actionscheduler_actions)")

  local total=$((completed + orphaned_logs))

  if [ "$total" = "0" ]; then
    echo -e "  ${GREEN}✓ Action Scheduler is clean.${NC}"
    return
  fi

  found "$total"
  [ "$completed" -gt 0 ] && echo -e "  ${YELLOW}$completed completed/failed action(s) older than 31 days.${NC}"
  [ "$orphaned_logs" -gt 0 ] && echo -e "  ${YELLOW}$orphaned_logs orphaned log row(s).${NC}"

  if [ "$DRY_RUN" = false ]; then
    [ "$completed" -gt 0 ] && wp_query "DELETE FROM wp_actionscheduler_actions WHERE status IN ('complete', 'failed', 'canceled') AND last_attempt_gmt < DATE_SUB(NOW(), INTERVAL 31 DAY)" >/dev/null
    [ "$orphaned_logs" -gt 0 ] && wp_query "DELETE FROM wp_actionscheduler_logs WHERE action_id NOT IN (SELECT action_id FROM wp_actionscheduler_actions)" >/dev/null
    cleaned "$total"
    echo -e "  ${GREEN}✓ Cleaned.${NC}"
  fi
}

audit_stale_cron() {
  echo -e "\n${CYAN}[18] Stale cron events${NC}"

  # Get all scheduled cron hooks
  local all_hooks
  all_hooks=$(wp_run cron event list --fields=hook --format=csv 2>/dev/null | tail -n +2 | sort -u)

  if [ -z "$all_hooks" ]; then
    echo -e "  ${GREEN}✓ No cron events.${NC}"
    return
  fi

  # WordPress core hooks that are always valid
  local core_hooks="wp_privacy_delete_old_export_files|wp_update_user_counts|wp_version_check|wp_update_plugins|wp_update_themes|wp_scheduled_delete|wp_scheduled_auto_draft_delete|wp_site_health_scheduled_check|wp_delete_temp_updater_backups|recovery_mode_clean_expired_keys|delete_expired_transients|wp_https_detection"

  # Filter to non-core hooks
  local check_hooks=()
  while IFS= read -r hook; do
    [ -z "$hook" ] && continue
    if ! echo "$hook" | grep -qE "^($core_hooks)$"; then
      check_hooks+=("$hook")
    fi
  done <<< "$all_hooks"

  # Check all hooks in a single WP-CLI call (one WordPress boot instead of N)
  local stale_hooks=()
  if [ ${#check_hooks[@]} -gt 0 ]; then
    local php_code
    php_code="\$hooks = array($(printf "'%s'," "${check_hooks[@]}" | sed 's/,$//'));foreach(\$hooks as \$h){if(!has_action(\$h))echo \$h.PHP_EOL;}"
    local stale_output
    stale_output=$(wp_run eval "$php_code" 2>/dev/null)
    while IFS= read -r hook; do
      [ -z "$hook" ] && continue
      stale_hooks+=("$hook")
    done <<< "$stale_output"
  fi

  if [ ${#stale_hooks[@]} -eq 0 ]; then
    local cron_size
    cron_size=$(wp_scalar "SELECT LENGTH(option_value) FROM wp_options WHERE option_name = 'cron'")
    echo -e "  ${GREEN}✓ All cron hooks have callbacks (${cron_size} bytes).${NC}"
    return
  fi

  found "${#stale_hooks[@]}"
  echo -e "  ${YELLOW}${#stale_hooks[@]} cron hook(s) with no registered callback:${NC}"
  printf '    %s\n' "${stale_hooks[@]}"

  if [ "$DRY_RUN" = false ]; then
    for hook in "${stale_hooks[@]}"; do
      wp_run cron event delete "$hook" 2>/dev/null
    done
    cleaned "${#stale_hooks[@]}"
    echo -e "  ${GREEN}✓ Deleted.${NC}"
  fi
}

audit_autoload() {
  echo -e "\n${CYAN}[21] Autoload audit (top 10 largest)${NC}"

  local total_kb
  total_kb=$(wp_scalar "SELECT ROUND(SUM(LENGTH(option_value)) / 1024, 1) FROM wp_options WHERE autoload NOT IN ('no', 'off', 'auto-off')")

  echo -e "  Total autoloaded: ${total_kb} KB"

  if [ "$(echo "$total_kb > 1000" | bc 2>/dev/null || echo 0)" = "1" ]; then
    found 1
    echo -e "  ${YELLOW}⚠ Over 1 MB autoloaded — review for bloat.${NC}"
  fi

  echo -e "  Top 10:"
  wp_query "SELECT option_name, LENGTH(option_value) AS bytes FROM wp_options WHERE autoload NOT IN ('no', 'off', 'auto-off') ORDER BY bytes DESC LIMIT 10" 2>/dev/null | tail -n +2 | while IFS=$'\t' read -r name bytes; do
    [ -z "$name" ] && continue
    local kb
    kb=$(echo "scale=1; $bytes / 1024" | bc 2>/dev/null || echo "$bytes")
    printf "    %-45s %s KB\n" "$name" "$kb"
  done
}

audit_stale_files() {
  echo -e "\n${CYAN}[19] Stale files${NC}"

  local wp_root
  if [ "$CURRENT_ENV" = "remote" ]; then
    wp_root="$REMOTE_WP_PATH"
  else
    wp_root="$LOCAL_WP_PATH"
  fi

  # Helper to run shell commands locally or remotely
  run_cmd() {
    if [ "$CURRENT_ENV" = "remote" ]; then
      ssh "$REMOTE" "$*" 2>/dev/null
    else
      eval "$*" 2>/dev/null
    fi
  }

  local stale_items=()

  # 1. WP core version-leaking files
  for f in readme.html license.txt; do
    if run_cmd "test -f $wp_root/$f" 2>/dev/null; then
      stale_items+=("$wp_root/$f (WP version leak)")
    fi
  done

  # 2. Debug and error logs
  for f in debug.log error_log php_errorlog; do
    if run_cmd "test -f $wp_root/$f" 2>/dev/null; then
      local size
      size=$(run_cmd "du -sh $wp_root/$f" | awk '{print $1}')
      stale_items+=("$wp_root/$f ($size — debug log)")
    fi
    if run_cmd "test -f $wp_root/wp-content/$f" 2>/dev/null; then
      local size
      size=$(run_cmd "du -sh $wp_root/wp-content/$f" | awk '{print $1}')
      stale_items+=("$wp_root/wp-content/$f ($size — debug log)")
    fi
  done

  # 3. Backup and temp files in WP root and wp-content
  local backup_files
  backup_files=$(run_cmd "find $wp_root -maxdepth 1 -name '*.bak' -o -name '*.old' -o -name '*.orig' -o -name '*.save' -o -name '*~' -o -name '*.swp' -o -name 'wp-config.php.bak' -o -name 'wp-config-backup.php' -o -name '.htaccess.bak' 2>/dev/null")
  while IFS= read -r f; do
    [ -z "$f" ] && continue
    stale_items+=("$f (backup/temp file)")
  done <<< "$backup_files"

  # 4. WordPress upgrade leftovers
  local upgrade_size
  upgrade_size=$(run_cmd "du -s $wp_root/wp-content/upgrade/ 2>/dev/null" | awk '{print $1}')
  if [ -n "$upgrade_size" ] && [ "$upgrade_size" -gt 4 ] 2>/dev/null; then
    local human_size
    human_size=$(run_cmd "du -sh $wp_root/wp-content/upgrade/" | awk '{print $1}')
    stale_items+=("wp-content/upgrade/ ($human_size — upgrade leftovers)")
  fi

  local backup_size
  backup_size=$(run_cmd "du -s $wp_root/wp-content/upgrade-temp-backup/ 2>/dev/null" | awk '{print $1}')
  if [ -n "$backup_size" ] && [ "$backup_size" -gt 4 ] 2>/dev/null; then
    local human_size
    human_size=$(run_cmd "du -sh $wp_root/wp-content/upgrade-temp-backup/" | awk '{print $1}')
    stale_items+=("wp-content/upgrade-temp-backup/ ($human_size — upgrade backup)")
  fi

  # 5. Inactive plugin directories (installed but not in active_plugins)
  local active_plugins
  active_plugins=$(wp_run plugin list --status=active --field=name 2>/dev/null)
  local all_plugin_dirs
  all_plugin_dirs=$(run_cmd "ls -d $wp_root/wp-content/plugins/*/ 2>/dev/null" | xargs -I{} basename {})

  while IFS= read -r dir; do
    [ -z "$dir" ] && continue
    [ "$dir" = "index.php" ] && continue
    if ! echo "$active_plugins" | grep -qx "$dir"; then
      local dir_size
      dir_size=$(run_cmd "du -sh $wp_root/wp-content/plugins/$dir" | awk '{print $1}')
      stale_items+=("wp-content/plugins/$dir/ ($dir_size — inactive plugin)")
    fi
  done <<< "$all_plugin_dirs"

  # 6. Inactive theme directories (installed but not the active theme)
  local active_theme
  active_theme=$(wp_run option get stylesheet 2>/dev/null | tr -d '[:space:]')
  local all_theme_dirs
  all_theme_dirs=$(run_cmd "ls -d $wp_root/wp-content/themes/*/ 2>/dev/null" | xargs -I{} basename {})

  while IFS= read -r dir; do
    [ -z "$dir" ] && continue
    [ "$dir" = "$active_theme" ] && continue
    local dir_size
    dir_size=$(run_cmd "du -sh $wp_root/wp-content/themes/$dir" | awk '{print $1}')
    stale_items+=("wp-content/themes/$dir/ ($dir_size — inactive theme)")
  done <<< "$all_theme_dirs"

  # 7. Orphaned upload files (files not referenced by any attachment)
  # Only report count — too slow to auto-clean, and false positives are dangerous
  local attachment_count
  attachment_count=$(wp_scalar "SELECT COUNT(*) FROM wp_posts WHERE post_type = 'attachment'")
  local upload_dir_exists
  upload_dir_exists=$(run_cmd "test -d $wp_root/wp-content/uploads && echo 1 || echo 0")

  if [ "$upload_dir_exists" = "1" ] && [ "$attachment_count" = "0" ]; then
    # No attachments but uploads directory has non-plugin content
    local non_plugin_files
    non_plugin_files=$(run_cmd "find $wp_root/wp-content/uploads -type f -not -path '*/matomo/*' -not -path '*/.htaccess' 2>/dev/null" | head -20)
    if [ -n "$non_plugin_files" ]; then
      local file_count
      file_count=$(run_cmd "find $wp_root/wp-content/uploads -type f -not -path '*/matomo/*' -not -path '*/.htaccess' 2>/dev/null | wc -l" | tr -d '[:space:]')
      if [ "$file_count" -gt 0 ]; then
        stale_items+=("wp-content/uploads: $file_count file(s) with no media library attachments")
      fi
    fi
  fi

  if [ ${#stale_items[@]} -eq 0 ]; then
    echo -e "  ${GREEN}✓ No stale files found.${NC}"
    return
  fi

  found "${#stale_items[@]}"
  echo -e "  ${YELLOW}${#stale_items[@]} stale item(s):${NC}"
  printf '    %s\n' "${stale_items[@]}"

  if [ "$DRY_RUN" = false ]; then
    for item in "${stale_items[@]}"; do
      local path
      path=$(echo "$item" | sed 's/ (.*//')

      # Skip orphaned uploads — too risky to auto-delete
      if echo "$item" | grep -q "no media library"; then
        continue
      fi

      # Skip inactive plugins/themes — report only, user decides
      if echo "$item" | grep -q "inactive plugin\|inactive theme"; then
        continue
      fi

      # Safe to auto-delete: version leaks, debug logs, backup files, upgrade dirs
      if echo "$item" | grep -q "version leak\|debug log\|backup/temp\|upgrade"; then
        if echo "$item" | grep -q "upgrade"; then
          run_cmd "rm -rf $wp_root/$path/* 2>/dev/null"
        else
          run_cmd "rm -f $path 2>/dev/null"
        fi
        cleaned 1
      fi
    done
    echo -e "  ${GREEN}✓ Auto-cleanable files removed. Review inactive plugins/themes manually.${NC}"
  fi
}

audit_integrity() {
  echo -e "\n${CYAN}[20] File integrity & database health${NC}"

  # 1. Database table check
  echo -e "  Checking tables..."
  local db_errors
  db_errors=$(wp_run db check 2>&1 | grep -i "error\|corrupt\|crash" || true)

  if [ -n "$db_errors" ]; then
    found 1
    echo -e "  ${RED}Database errors detected:${NC}"
    echo "$db_errors" | sed 's/^/    /'
    if [ "$DRY_RUN" = false ]; then
      echo -e "  Attempting repair..."
      wp_run db repair 2>/dev/null | grep -c "OK" | xargs -I{} echo -e "  ${GREEN}✓ Repair attempted ({} tables).${NC}"
      cleaned 1
    fi
  else
    echo -e "  ${GREEN}✓ All tables healthy.${NC}"
  fi

  # 2. WordPress core checksum verification
  echo -e "  Verifying core checksums..."
  local core_issues
  core_issues=$(wp_run core verify-checksums 2>&1)
  local core_exit=$?

  if [ $core_exit -ne 0 ]; then
    if echo "$core_issues" | grep -qi "doesn't verify\|no checksums"; then
      echo -e "  ${YELLOW}Core checksums unavailable (non-standard install or managed hosting).${NC}"
    else
      found 1
      echo -e "  ${RED}Core file modifications detected:${NC}"
      echo "$core_issues" | grep -v "^Success" | head -20 | sed 's/^/    /'
      echo -e "  ${YELLOW}⚠ Review manually — could be a hack or intentional patch.${NC}"
    fi
  else
    echo -e "  ${GREEN}✓ Core checksums verified.${NC}"
  fi

  # 3. Plugin checksum verification (only works for wordpress.org plugins)
  echo -e "  Verifying plugin checksums..."
  local plugin_issues
  plugin_issues=$(wp_run plugin verify-checksums --all 2>&1)
  local plugin_exit=$?

  if echo "$plugin_issues" | grep -qi "could not verify\|error\|file was added\|file has modified"; then
    local modified
    modified=$(echo "$plugin_issues" | grep -c "has modified\|was added" || echo "0")
    if [ "$modified" -gt 0 ]; then
      found 1
      echo -e "  ${YELLOW}Plugin file modifications:${NC}"
      echo "$plugin_issues" | grep -i "modified\|added\|error" | head -15 | sed 's/^/    /'
    else
      echo -e "  ${GREEN}✓ Plugin checksums verified (some plugins skipped — no checksums available for premium plugins).${NC}"
    fi
  else
    echo -e "  ${GREEN}✓ Plugin checksums verified.${NC}"
  fi
}

audit_database_health() {
  echo -e "\n${CYAN}[22] Database summary${NC}"

  local options autoloaded autoload_bytes posts postmeta tables db_size
  options=$(wp_scalar "SELECT COUNT(*) FROM wp_options")
  autoloaded=$(wp_scalar "SELECT COUNT(*) FROM wp_options WHERE autoload NOT IN ('no', 'off', 'auto-off')")
  autoload_bytes=$(wp_scalar "SELECT SUM(LENGTH(option_value)) FROM wp_options WHERE autoload NOT IN ('no', 'off', 'auto-off')")
  posts=$(wp_scalar "SELECT COUNT(*) FROM wp_posts")
  postmeta=$(wp_scalar "SELECT COUNT(*) FROM wp_postmeta")
  tables=$(wp_scalar "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")
  db_size=$(wp_scalar "SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")

  echo -e "  Options: $options ($autoloaded autoloaded, ${autoload_bytes} bytes)"
  echo -e "  Posts: $posts | Postmeta: $postmeta"
  echo -e "  Tables: $tables | DB size: ${db_size} MB"

  if [ "$DRY_RUN" = false ]; then
    echo -e "  Optimizing..."
    local ok_count
    ok_count=$(wp_run db optimize 2>/dev/null | grep -c "OK" || echo "0")
    echo -e "  ${GREEN}✓ Database optimized ($ok_count tables).${NC}"
  fi
}

# ─── Main ──────────────────────────────────────────────────────

run_audit() {
  CURRENT_ENV="$1"
  TOTAL_FOUND=0
  TOTAL_CLEANED=0

  local label
  if [ "$CURRENT_ENV" = "remote" ]; then
    label="PRODUCTION ($REMOTE)"
  else
    label="LOCAL ($LOCAL_WP_PATH)"
  fi

  echo -e "\n${BOLD}━━━ $label ━━━${NC}"

  if [ "$DRY_RUN" = true ]; then
    echo -e "${YELLOW}▸ Report only — use --go to execute cleanup${NC}"
  else
    echo -e "${RED}▸ EXECUTING CLEANUP${NC}"
  fi

  audit_versions

  echo -e "\n${BOLD}── Database Cleanup ──${NC}"

  audit_stale_plugin_options
  audit_stale_theme_mods
  audit_orphaned_postmeta
  audit_orphaned_termmeta
  audit_orphaned_term_relationships
  audit_orphaned_comment_user_meta
  audit_revisions
  audit_auto_drafts
  audit_expired_transients
  audit_spam_comments
  audit_orphaned_attachment
  audit_oembed_cache
  audit_transient_postmeta
  audit_duplicate_postmeta
  audit_action_scheduler
  audit_stale_translations
  audit_plugin_translations
  audit_acf_stale_data
  audit_stale_cron
  audit_stale_files
  audit_integrity
  audit_autoload
  audit_database_health

  echo -e "\n${BOLD}── Summary ──${NC}"
  if [ "$DRY_RUN" = true ]; then
    if [ "$TOTAL_FOUND" -eq 0 ]; then
      echo -e "${GREEN}✓ Database is clean. Nothing to do.${NC}"
    else
      echo -e "${YELLOW}Found $TOTAL_FOUND issue(s). Run with --go to clean up.${NC}"
    fi
  else
    echo -e "${GREEN}✓ Cleaned $TOTAL_CLEANED item(s).${NC}"
  fi
}

# ─── Pre-flight ────────────────────────────────────────────────

if [ "$RUN_LOCAL" = true ]; then
  if [ ! -f "$LOCAL_WP_PATH/wp-config.php" ]; then
    echo -e "${RED}✗ Cannot find WordPress at $LOCAL_WP_PATH${NC}"
    exit 1
  fi
  if ! command -v "$LOCAL_WP_CLI" &>/dev/null; then
    echo -e "${RED}✗ WP-CLI not found. Install: brew install wp-cli${NC}"
    exit 1
  fi
fi

if [ "$RUN_REMOTE" = true ]; then
  if ! ssh -o ConnectTimeout=5 "$REMOTE" "cd $REMOTE_WP_PATH && $REMOTE_WP_CLI core version" &>/dev/null; then
    echo -e "${RED}✗ Cannot reach production ($REMOTE) or WP-CLI not available${NC}"
    exit 1
  fi
fi

echo -e "${BOLD}WordPress Maintenance${NC}"
echo "──────────────────────"

if [ "$RUN_LOCAL" = true ]; then
  run_audit "local"
fi

if [ "$RUN_REMOTE" = true ]; then
  run_audit "remote"
fi

echo ""
