#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

if [[ ! -f "$ROOT_DIR/.env" ]]; then
  echo "Missing .env in $ROOT_DIR" >&2
  exit 1
fi

source "$ROOT_DIR/.env"

if [[ -z "${FTP_HOST:-}" || -z "${FTP_USER:-}" || -z "${FTP_PASS:-}" || -z "${FTP_PLUGIN_PATH:-}" ]]; then
  echo "FTP credentials are incomplete in .env" >&2
  exit 1
fi

FTP_BASE="ftp://$FTP_HOST$FTP_PLUGIN_PATH"

ftp_list() {
  local remote_dir="$1"
  curl -s --ftp-ssl --insecure -u "$FTP_USER:$FTP_PASS" "$FTP_BASE$remote_dir"
}

ftp_delete_file() {
  local remote_file="$1"
  if ! curl -s --ftp-ssl --insecure -u "$FTP_USER:$FTP_PASS" -Q "DELE $FTP_PLUGIN_PATH$remote_file" "$FTP_BASE/" >/dev/null; then
    echo "skip missing file: $remote_file"
    return 0
  fi
  echo "deleted file: $remote_file"
}

ftp_remove_dir() {
  local remote_dir="$1"
  if ! curl -s --ftp-ssl --insecure -u "$FTP_USER:$FTP_PASS" -Q "RMD $FTP_PLUGIN_PATH$remote_dir" "$FTP_BASE/" >/dev/null; then
    echo "skip missing dir: $remote_dir"
    return 0
  fi
  echo "removed dir: $remote_dir"
}

cleanup_path() {
  local remote_path="$1"

  if [[ "$remote_path" != /* ]]; then
    remote_path="/$remote_path"
  fi

  if [[ "$remote_path" == */ ]]; then
    local listing
    listing="$(ftp_list "$remote_path" || true)"
    if [[ -z "$listing" ]]; then
      echo "skip missing dir: $remote_path"
      return 0
    fi

    while IFS= read -r line; do
      [[ -z "$line" ]] && continue
      local entry_type entry_name
      entry_type="${line:0:1}"
      entry_name="$(printf '%s\n' "$line" | awk '{print $NF}')"

      [[ "$entry_name" == "." || "$entry_name" == ".." || -z "$entry_name" ]] && continue

      if [[ "$entry_type" == "d" ]]; then
        cleanup_path "${remote_path%/}/$entry_name/"
      else
        cleanup_path "${remote_path%/}/$entry_name"
      fi
    done <<< "$listing"

    ftp_remove_dir "${remote_path%/}"
    return 0
  fi

  ftp_delete_file "$remote_path"
}

TARGETS=(
  "/node_modules/"
  "/scripts/"
  "/tmp/"
  "/.gitignore"
  "/package.json"
  "/package-lock.json"
  "/playwright.config.js"
  "/tests/"
  "/test-results/"
)

for target in "${TARGETS[@]}"; do
  cleanup_path "$target"
done

echo "Remote cleanup completed."
