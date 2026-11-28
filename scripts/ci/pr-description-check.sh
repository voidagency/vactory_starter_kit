#!/usr/bin/env bash
# Checks the title and the description of a pull request (Redmine #71766).
#
# - Title: "type: #ticket - sujet" (RELEASING.md).
# - Description: cites the Redmine ticket, and fills the sections of the PR
#   template that say what the PR does to the projects (.github/
#   pull_request_template.md): "Surface publique touchée" and "Comportement
#   existant" have a ticked box or a text, "Tests" has a text.
#
# Usage: PR_TITLE="..." scripts/ci/pr-description-check.sh <file with the body>
#   In Drone: PR_TITLE="$DRONE_PULL_REQUEST_TITLE"; the body is read from the
#   GitHub API (gh pr view "$DRONE_PULL_REQUEST" --json body -q .body).
# Exit code: 0 if everything is there, 1 if something is missing, 2 if the
# check could not run.
set -uo pipefail

body_file="${1:-}"
if [ -z "${PR_TITLE:-}" ] || [ -z "$body_file" ] || [ ! -r "$body_file" ]; then
  echo "pr-description-check: PR_TITLE and a readable body file are required." >&2
  exit 2
fi

# The GitHub API returns CRLF line endings for a description typed in the web
# interface: work on a copy with LF only.
body_lf="$(mktemp)"
trap 'rm -f "$body_lf"' EXIT
tr -d '\r' < "$body_file" > "$body_lf"
body_file="$body_lf"

missing=()

if ! printf '%s' "$PR_TITLE" | grep -qE '^(feat|fix|refactor|build|chore|ci|docs|perf|revert|style|test)(\([^)]*\))?: #[0-9]+ - .+'; then
  missing+=("title: \"type: #ticket - sujet\" (for example \"fix: #71517 - ...\")")
fi

if ! grep -qE '(Redmine #[0-9]+|#7[0-9]{4})' "$body_file"; then
  missing+=("description: the Redmine ticket (\"Redmine #n\")")
fi

# Text of a section of the body, without HTML comments and blank lines.
section() {
  awk -v title="$1" '
    /^## / { inside = ($0 == "## " title); next }
    inside { print }
  ' "$body_file" \
  | perl -0pe 's/<!--.*?-->//gs' \
  | grep -vE '^[[:space:]]*$' || true
}

# A section is filled when a box is ticked, or when it has a line that is not
# an unticked box.
filled() {
  local text
  text="$(section "$1")"
  printf '%s\n' "$text" | grep -qE '^\s*- \[[xX]\]' && return 0
  printf '%s\n' "$text" | grep -vqE '^\s*- \[ \]' && [ -n "$text" ] && return 0
  return 1
}

for title in "Surface publique touchée" "Comportement existant" "Tests"; do
  if ! filled "$title"; then
    missing+=("description: section \"## $title\" of the PR template, empty or missing")
  fi
done

if [ ${#missing[@]} -eq 0 ]; then
  echo "pr-description-check: title and description are complete."
  exit 0
fi

echo "pr-description-check: missing in the pull request:"
printf ' - %s\n' "${missing[@]}"
echo
echo "See .github/pull_request_template.md and RELEASING.md (\"Une PR est terminée quand\")."
exit 1
