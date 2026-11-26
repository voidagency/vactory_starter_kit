#!/usr/bin/env bash
# Lance les parcours navigateur contre une instance `stack` (docs/dev-stack.md).
#
#   tests/e2e/run.sh <instance> [options de playwright test]
#
# Récupère l'adresse du site (`stack url`) et un lien de connexion à usage
# unique (`stack uli`, compte stack_editor), sans jamais l'afficher ni l'écrire
# dans un fichier, prépare le site (template de test) puis lance Playwright.
#
# Variables :
#   STACK            commande stack (défaut : scripts/stack/stack du dépôt)
#   E2E_OUTPUT_DIR   traces, captures, vidéos (défaut : tests/e2e/test-results)
#   E2E_REPORT_DIR   rapport HTML (défaut : tests/e2e/playwright-report)
#   E2E_SKIP_PREPARE=1  ne pas préparer le site
set -euo pipefail

here="$(cd "$(dirname "$0")" && pwd)"
repo="$(cd "$here/../.." && pwd)"
STACK="${STACK:-$repo/scripts/stack/stack}"

inst="${1:-}"
if [[ -z "$inst" || "$inst" == -* ]]; then
  echo "usage : $0 <instance> [options de playwright test]" >&2
  exit 2
fi
shift

base_url="$("$STACK" url "$inst" </dev/null)"
[[ "$base_url" == https://* || "$base_url" == http://* ]] || { echo "stack url $inst : adresse introuvable" >&2; exit 1; }

if [[ "${E2E_SKIP_PREPARE:-}" != 1 ]]; then
  # Le parcours utilise le template « DWidget Links » de
  # vactory_dynamic_field_default (module du profil, inactif dans une
  # installation neuve). Sans effet s'il est déjà actif.
  "$STACK" drush "$inst" pm:install vactory_dynamic_field_default -y </dev/null >/dev/null
fi

cd "$here"
if [[ ! -x node_modules/.bin/playwright ]]; then
  npm ci --no-audit --no-fund >/dev/null
fi
npx playwright install chromium >/dev/null

# Lien de connexion : variable d'environnement du seul processus Playwright.
login_url="$("$STACK" uli "$inst" </dev/null | tail -n 1)"
[[ "$login_url" == http* ]] || { echo "stack uli $inst : pas de lien de connexion" >&2; exit 1; }

E2E_BASE_URL="$base_url" \
E2E_LOGIN_URL="$login_url" \
E2E_DRUSH="$STACK drush $inst" \
  exec npx playwright test "$@"
