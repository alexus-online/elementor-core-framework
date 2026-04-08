#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

ADMIN_USER="${ECF_WP_ADMIN_USER:-${ECF_WP_USER:-}}"
ADMIN_PASSWORD="${ECF_WP_ADMIN_PASSWORD:-}"

if [[ -z "${ECF_WP_URL:-}" || -z "$ADMIN_USER" || -z "$ADMIN_PASSWORD" ]]; then
  echo "Skipping ECF E2E UI checks."
  echo "Set ECF_WP_URL, ECF_WP_ADMIN_USER (or ECF_WP_USER) and ECF_WP_ADMIN_PASSWORD to run them in a real browser."
  echo "Optional: set ECF_WP_LOGIN_PATH when the site uses a custom login URL."
  exit 0
fi

echo "Running ECF E2E UI checks against ${ECF_WP_URL}..."
npx playwright test tests/ui/admin-ui.spec.js --config=playwright.config.js "$@"
