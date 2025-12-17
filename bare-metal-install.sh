#!/usr/bin/env bash
set -euo pipefail

# Blog Advanced - Bare Metal Setup Helper
# This script enhances install.sh for non-Docker setups:
# - Detects web user (www-data/nginx/apache) if not provided
# - Ensures Theme Editor paths/permissions (static/styles/custom1.css)
# - Optionally applies ACLs instead of changing ownership
# - Optionally reloads the web server

# Usage:
#   ./bare-metal.sh [--web-user USER] [--web-group GROUP] [--apply-acl] [--skip-install] [--reload]
#
# Examples:
#   ./bare-metal.sh                          # auto-detect user/group, run install.sh, set permissions
#   ./bare-metal.sh --web-user www-data --web-group www-data --reload
#   ./bare-metal.sh --apply-acl              # use ACLs to grant www-data write access without chown

WEB_USER=""
WEB_GROUP=""
APPLY_ACL="false"
SKIP_INSTALL="false"
RELOAD_WEBSERVER="false"

info()  { echo -e "\033[0;32m[INFO]\033[0m $*"; }
warn()  { echo -e "\033[1;33m[WARN]\033[0m $*"; }
error() { echo -e "\033[0;31m[ERROR]\033[0m $*"; }

# Parse args
while [[ $# -gt 0 ]]; do
  case "$1" in
    --web-user)   WEB_USER="${2:-}"; shift 2 ;;
    --web-group)  WEB_GROUP="${2:-}"; shift 2 ;;
    --apply-acl)  APPLY_ACL="true"; shift ;;
    --skip-install) SKIP_INSTALL="true"; shift ;;
    --reload)     RELOAD_WEBSERVER="true"; shift ;;
    -h|--help)
      sed -n '1,50p' "$0"; exit 0 ;;
    *)
      warn "Unknown argument: $1"; shift ;;
  esac
done

# Detect web user/group if not provided
detect_user() {
  local candidates=(www-data nginx apache)
  for u in "${candidates[@]}"; do
    if id "$u" &>/dev/null; then
      WEB_USER="${WEB_USER:-$u}"
      WEB_GROUP="${WEB_GROUP:-$u}"
      return
    fi
  done
  # Fallback to current user
  WEB_USER="${WEB_USER:-$(id -un)}"
  WEB_GROUP="${WEB_GROUP:-$(id -gn)}"
}

detect_user
info "Using web user: ${WEB_USER} group: ${WEB_GROUP}"

# Run install.sh unless skipped
if [[ "$SKIP_INSTALL" != "true" ]]; then
  if [[ -x "./install.sh" ]]; then
    info "Running ./install.sh ..."
    ./install.sh
  else
    warn "install.sh not found or not executable, proceeding with manual setup steps"
  fi
fi

# Ensure Theme Editor paths exist
mkdir -p static/styles
if [[ ! -f static/styles/custom1.css ]]; then
  info "Creating static/styles/custom1.css"
  touch static/styles/custom1.css
fi

# Decide permission strategy
if [[ "$APPLY_ACL" == "true" ]]; then
  # Use ACLs to grant access without changing ownership
  if command -v setfacl &>/dev/null; then
    info "Applying ACLs for ${WEB_USER} on writable paths"
    setfacl -m "u:${WEB_USER}:rwX" data uploads logs sessions static/styles || warn "setfacl failed on directories"
    setfacl -d -m "u:${WEB_USER}:rwX" data uploads logs sessions static/styles || true
    # specific files
    [[ -f data/config.ini ]] && setfacl -m "u:${WEB_USER}:rw" data/config.ini || true
    setfacl -m "u:${WEB_USER}:rw" static/styles/custom1.css || true
    # Base permissions (group-writable)
    chmod -R 0775 data uploads logs sessions static/styles || warn "chmod on dirs failed"
    [[ -f data/config.ini ]] && chmod 0664 data/config.ini || true
    chmod 0664 static/styles/custom1.css || true
  else
    warn "setfacl not available; falling back to chown/chmod"
    APPLY_ACL="false"
  fi
fi

# Ownership + permissions if ACL not used
if [[ "$APPLY_ACL" != "true" ]]; then
  if command -v chown &>/dev/null; then
    info "Setting ownership to ${WEB_USER}:${WEB_GROUP}"
    chown -R "${WEB_USER}:${WEB_GROUP}" data uploads logs sessions static/styles || warn "chown on dirs failed"
    [[ -f data/config.ini ]] && chown "${WEB_USER}:${WEB_GROUP}" data/config.ini || true
    chown "${WEB_USER}:${WEB_GROUP}" static/styles/custom1.css || true
  else
    warn "chown not available; skipping ownership change"
  fi
  info "Setting safe permissions"
  chmod -R 0775 data uploads logs sessions static/styles || warn "chmod on dirs failed"
  [[ -f data/config.ini ]] && chmod 0664 data/config.ini || true
  chmod 0664 static/styles/custom1.css || true
fi

# Optional: reload webserver to pick up changes
if [[ "$RELOAD_WEBSERVER" == "true" ]]; then
  if command -v systemctl &>/dev/null; then
    for svc in nginx apache2 httpd; do
      if systemctl is-active --quiet "$svc"; then
        info "Reloading $svc"
        systemctl reload "$svc" || warn "reload failed for $svc"
      fi
    done
  else
    warn "systemctl not found; skipping web server reload"
  fi
fi

# PHP checks (informational)
if command -v php &>/dev/null; then
  info "PHP: $(php -v | head -n1)"
  required=(pdo pdo_mysql pdo_sqlite gd mbstring fileinfo curl zip)
  for ext in "${required[@]}"; do
    if php -m | grep -q "^${ext}$"; then
      echo -e "  \033[0;32m✓\033[0m ${ext}"
    else
      echo -e "  \033[0;31m✗\033[0m ${ext} (missing)"
    fi
  done
else
  warn "PHP not found in PATH"
fi

echo ""
info "Bare-metal setup complete."
echo "Writable paths prepared for ${WEB_USER}:${WEB_GROUP}:"
echo "  - data/, uploads/, logs/, sessions/"
echo "  - static/styles/ (Theme Editor)"
echo "  - static/styles/custom1.css"
echo ""
echo "Tips:"
echo "  - Use --apply-acl if you prefer ACLs over ownership changes."
echo "  - Use --reload to reload nginx/apache after permissions."
echo "  - Adjust --web-user/--web-group if your web server runs as a different user."