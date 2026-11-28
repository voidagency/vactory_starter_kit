#!/usr/bin/env bash
# Checks that a change of test expectation is declared in the changelog.
#
# A PR that removes or rewrites an assertion of an existing test changes what
# the profile promises: the changelog must then get a line under
# "### Changements de comportement" (Redmine #71766, AGENTS.md).
#
# Limit: an assertion is detected by the removed line that holds the word
# "assert" (or expectException, markTestSkipped). A multi-line assertion
# whose expected value changes on another line is not detected.
#
# Usage: scripts/ci/behavior-change-check.sh [base]   (default: origin/3.x)
# Exit code: 0 if nothing to declare or declared, 1 otherwise.
set -euo pipefail

base="${1:-origin/3.x}"
merge_base="$(git merge-base "$base" HEAD)"

# Assertions removed from test files that already existed in the base.
changed="$(
  git diff --unified=0 --diff-filter=M "$merge_base" -- \
    'modules/**/tests/**/*.php' 'tests/**/*.php' \
  | awk '
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
  git diff --unified=0 "$merge_base" -- CHANGELOG.md \
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

echo "behavior-change-check: assertions of existing tests were removed or rewritten:"
echo "$changed"
echo
echo "If the behavior of the profile changes, add a line under"
echo "\"### Changements de comportement\" in CHANGELOG.md ([Non publié])."
echo "If it does not (the test was wrong, or only rewritten), say so in the PR."
exit 1
