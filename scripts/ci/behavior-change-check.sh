#!/usr/bin/env bash
# Checks that a change of test expectation is declared in the changelog.
#
# A PR that removes or rewrites an assertion of an existing test changes what
# the profile promises: the changelog must then get a line under
# "### Changements de comportement" (Redmine #71766, AGENTS.md).
#
# Limit: an assertion is detected by the removed line that holds the word
# "assert" (or expectException, markTestSkipped). An expected value changed
# elsewhere (another line of a multi-line assertion, a data provider, an
# $expected variable) is not detected.
#
# Way out for a test that is only rewritten or moved: tick "Le comportement
# existant ne change pas" in the description of the PR, and give the file
# with the description in PR_BODY_FILE. The check then passes and still lists
# the assertions, for the reviewer.
#
# Usage: scripts/ci/behavior-change-check.sh [base]   (default: origin/3.x)
# A deleted or moved test counts: its removed assertions must be declared.
#
# Exit code: 0 if nothing to declare or declared, 1 if not declared, 2 if the
# check could not run.
set -euo pipefail

base="${1:-origin/3.x}"
# Exit code 2: the check could not run (unknown base, shallow clone). It must
# not look like a rejection of the PR.
if ! merge_base="$(git merge-base "$base" HEAD 2>/dev/null)"; then
  echo "behavior-change-check: cannot find the merge base with $base (fetch it, or deepen the clone)." >&2
  exit 2
fi

# Assertions removed from test files that already existed in the base
# (modified, deleted or renamed).
diff_tests="$(git diff --unified=0 --find-renames --diff-filter=MDR "$merge_base" -- \
  'modules/**/tests/**/*.php' 'tests/**/*.php')" || {
  echo "behavior-change-check: git diff failed against $merge_base." >&2
  exit 2
}
diff_changelog="$(git diff --unified=0 "$merge_base" -- CHANGELOG.md)" || {
  echo "behavior-change-check: git diff of CHANGELOG.md failed against $merge_base." >&2
  exit 2
}

changed="$(
  printf '%s\n' "$diff_tests" \
  | awk '
      /^--- a\// { file = substr($0, 7); next }
      /^\+\+\+ b\// { file = substr($0, 7); next }
      /^-[^-]/ && /(assert|expectException|markTestSkipped)/ {
        line = substr($0, 2); gsub(/^[ \t]+/, "", line);
        print file ": " line
      }'
)"

if [ -z "$changed" ]; then
  echo "behavior-change-check: no assertion of an existing test changed."
  exit 0
fi

# Lines added under "### Changements de comportement" of the [Non publié]
# section of the changelog.
declared="$(
  printf '%s\n' "$diff_changelog" \
  | awk '/^@@/ { split($3, a, ","); n = substr(a[1], 2) + 0; next }
         /^\+\+\+ / { next }
         /^\+/ { print n; n++ }' \
  | while read -r n; do
      awk -v target="$n" '
        /^## \[/ { release = ($0 ~ /Non publié/) }
        /^### / { section = $0 }
        NR == target && release && section ~ /Changements de comportement/ && /^- / { print; exit }
      ' CHANGELOG.md
    done
)"

if [ -n "$declared" ]; then
  echo "behavior-change-check: test expectations changed, declared in the changelog:"
  echo "$declared"
  exit 0
fi

if [ -n "${PR_BODY_FILE:-}" ] && [ -r "$PR_BODY_FILE" ] \
  && tr -d '\r' < "$PR_BODY_FILE" | grep -qE '^\s*- \[[xX]\] Le comportement existant ne change pas'; then
  echo "behavior-change-check: assertions of existing tests changed, and the PR says the behavior does not change:"
  echo "$changed"
  echo "Check it in the review."
  exit 0
fi

echo "behavior-change-check: assertions of existing tests were removed or rewritten:"
echo "$changed"
echo
echo "If the behavior of the profile changes, add a line under"
echo "\"### Changements de comportement\" in CHANGELOG.md ([Non publié])."
echo "If it does not (test only rewritten or moved), tick \"Le comportement existant ne change pas\""
echo "in the description of the PR (read through PR_BODY_FILE)."
exit 1
