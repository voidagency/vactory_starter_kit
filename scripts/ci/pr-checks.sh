#!/usr/bin/env bash
# Runs the pull request checks in Drone (pipeline "pr-checks", Redmine #71766).
#
# - behavior-change-check.sh: existing test assertions removed or rewritten
#   need a "Changements de comportement" line in the CHANGELOG, or the box
#   "Le comportement existant ne change pas" ticked in the PR description.
# - pr-description-check.sh: title "type: #ticket - sujet", Redmine ticket
#   cited, sections of the PR template filled.
#
# CHECKS_BLOCKING=true: a missing item (1) or a check that could not run (2)
# fails the build. Otherwise (default) it is only printed as a warning.
# Needs DRONE_REPO, DRONE_PULL_REQUEST, DRONE_PULL_REQUEST_TITLE,
# DRONE_TARGET_BRANCH (set by Drone) and GITHUB_TOKEN (read-only).
set -uo pipefail
cd "$(dirname "$0")/../.."

blocking="${CHECKS_BLOCKING:-false}"
body_file="$(mktemp)"
trap 'rm -f "$body_file"' EXIT

# Description of the PR, from the GitHub API. Empty file if it cannot be read:
# the checks then report it (code 1 or 2).
if ! curl -fsS --max-time 30 \
    -H "Authorization: Bearer ${GITHUB_TOKEN:-}" \
    -H "Accept: application/vnd.github+json" \
    "https://api.github.com/repos/${DRONE_REPO}/pulls/${DRONE_PULL_REQUEST}" \
    | jq -r '.body // ""' > "$body_file"; then
  echo "pr-checks: could not read the description of PR #${DRONE_PULL_REQUEST} from GitHub." >&2
  : > "$body_file"
fi

failed=0
run() {
  local name="$1"; shift
  echo "=== ${name}"
  "$@"
  local rc=$?
  case "$rc" in
    0) echo "--- ${name} : OK" ;;
    1) echo "--- ${name} : MANQUE (code 1)"; failed=1 ;;
    *) echo "--- ${name} : CONTRÔLE IMPOSSIBLE (code ${rc})"; failed=1 ;;
  esac
  echo
}

run behavior-change-check \
  env PR_BODY_FILE="$body_file" bash scripts/ci/behavior-change-check.sh "origin/${DRONE_TARGET_BRANCH}"
run pr-description-check \
  env PR_TITLE="${DRONE_PULL_REQUEST_TITLE:-}" bash scripts/ci/pr-description-check.sh "$body_file"

if [ "$failed" -ne 0 ]; then
  if [ "$blocking" = "true" ]; then
    echo "pr-checks: échec (CHECKS_BLOCKING=true)."
    exit 1
  fi
  echo "AVERTISSEMENT : contrôles non bloquants pour l'instant (CHECKS_BLOCKING=false). Corriger avant le passage en bloquant."
fi
exit 0
