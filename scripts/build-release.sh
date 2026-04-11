#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BUILD_DIR="$ROOT_DIR/dist"
STAGE_DIR="$BUILD_DIR/release/layrix"

VERSION="$(
  sed -n "s/^ \* Version: \(.*\)$/\1/p" "$ROOT_DIR/layrix.php" | head -n 1
)"

if [[ -z "$VERSION" ]]; then
  echo "Could not determine plugin version from layrix.php" >&2
  exit 1
fi

ZIP_NAME="layrix-${VERSION}-transition.zip"
ZIP_PATH="$BUILD_DIR/$ZIP_NAME"
SHA_PATH="$ZIP_PATH.sha256"

EXCLUDES=(
  ".env"
  "dist"
  ".git"
  ".github"
  ".gitignore"
  ".claude"
  ".vscode"
  ".DS_Store"
  "tmp"
  "node_modules"
  "tests"
  "scripts"
  "playwright.config.js"
  "package.json"
  "package-lock.json"
  "test-results"
  "website-quality-check"
)

case_sensitive_fs() {
  local probe_dir="$1"
  local lower="$probe_dir/.layrix_case_probe"
  local upper="$probe_dir/.LAYRIX_CASE_PROBE"

  rm -f "$lower" "$upper"
  touch "$lower"
  if [[ -e "$upper" ]]; then
    rm -f "$lower" "$upper"
    return 1
  fi

  rm -f "$lower" "$upper"
  return 0
}

rm -rf "$BUILD_DIR"
mkdir -p "$STAGE_DIR"

RSYNC_EXCLUDES=()
for item in "${EXCLUDES[@]}"; do
  RSYNC_EXCLUDES+=(--exclude "$item")
done

rsync -a "${RSYNC_EXCLUDES[@]}" "$ROOT_DIR/" "$STAGE_DIR/"

# Transitional package for case-sensitive Linux builds:
# keep the new main file and add the old bootstrap filename once more
# so older GitHub-updater installs can migrate cleanly.
if case_sensitive_fs "$STAGE_DIR"; then
  cp "$STAGE_DIR/layrix.php" "$STAGE_DIR/Layrix.php"
else
  echo "Warning: case-insensitive filesystem detected; local build cannot add both layrix.php and Layrix.php." >&2
  echo "GitHub Actions on Linux will include the legacy bootstrap filename in the release zip." >&2
fi

(
  cd "$BUILD_DIR/release"
  zip -rq "$ZIP_PATH" layrix
)

shasum -a 256 "$ZIP_PATH" > "$SHA_PATH"

echo "Built $ZIP_PATH"
echo "Checksum $SHA_PATH"
