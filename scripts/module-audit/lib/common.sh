# shellcheck shell=bash
# Fonctions communes de module-audit (chargé par « . », jamais exécuté).
# Compatible bash 3.2 (macOS) : pas de « source <(…) », variables de boucle
# toujours « local » (portée dynamique de bash), entrée standard de « stack »
# fermée (une commande qui lit l'entrée avale la suite d'une boucle « while read »).

PY="${PYTHON:-python3}"
# Dossier de travail dans le site de l'instance (vu du conteneur : /var/www/html/_module_audit).
WORK_IN_SITE=_module_audit
WORK_IN_CONTAINER=/var/www/html/_module_audit

say() { printf '%s\n' "$*"; }
log() { printf '[%s] %s\n' "$(date -u +%H:%M:%SZ)" "$*"; }
die() { printf 'module-audit : %s\n' "$*" >&2; exit 1; }

now() { "$PY" -c 'import time; print(f"{time.time():.1f}")'; }
el() { "$PY" -c "import sys; print(round(float(sys.argv[2]) - float(sys.argv[1]), 1))" "$1" "$(now)"; }

# « stack » sans entrée standard.
st() { "$STACK" "$@" </dev/null; }

# Champ de la fiche d'une instance (copie de « stack info <nom> --json » dans $OUT/instances/).
inst_field() {
  "$PY" -c 'import json, sys
v = json.load(open(sys.argv[1]))
for k in sys.argv[2].split("."):
    v = v[k]
print(v)' "$OUT/instances/$1.json" "$2"
}
inst_url() { inst_field "$1" drupal.url; }
inst_drupal() { inst_field "$1" drupal.path; }
inst_profile() { inst_field "$1" profile.path; }
json_max_wid() { "$PY" -c 'import json, sys; print(json.load(open(sys.argv[1])).get("watchdog_max_wid") or 0)' "$1"; }

# Copie les scripts PHP dans le site de l'instance.
push_php() {
  local d; d="$(inst_drupal "$1")/$WORK_IN_SITE"
  mkdir -p "$d/out"
  cp "$MA_HOME"/php/*.php "$d/"
}

# drush dans l'instance ; sortie dans $2 ; arrêt au bout de 15 min.
# COLUMNS : les blocs d'erreur de drush ne sont pas coupés. Le délai est
# appliqué dans le conteneur (tuer « stack exec » sur l'hôte laisse drush
# tourner) ; l'hôte n'arrête « stack exec » qu'en dernier recours.
drush_to() {
  local inst="$1" out="$2" pid n=0 rc
  shift 2
  "$STACK" exec "$inst" timeout -k 10 900 env COLUMNS=1000 drush "$@" >"$out" 2>&1 </dev/null &
  pid=$!
  while kill -0 "$pid" 2>/dev/null; do
    sleep 1
    n=$((n + 1))
    if [ "$n" -gt 960 ]; then kill -9 "$pid" 2>/dev/null; fi
  done
  wait "$pid"
  rc=$?
  if [ "$rc" = 124 ] || [ "$rc" = 137 ]; then echo "module-audit: arrêté après 900 s" >>"$out"; fi
  return "$rc"
}

# php/<script> dans l'instance : $3 = fichier JSON écrit par le script.
php_script() {
  local inst="$1" script="$2" out="$3" d rel rc
  d="$(inst_drupal "$inst")/$WORK_IN_SITE"
  push_php "$inst"
  rel="out/$(basename "$out")"
  st drush "$inst" php:script "$WORK_IN_CONTAINER/$script" -- "$WORK_IN_CONTAINER/$rel" >"$out.log" 2>&1
  rc=$?
  if [ -f "$d/$rel" ]; then mv "$d/$rel" "$out"; fi
  return "$rc"
}

snap() { php_script "$1" snap.php "$2"; }

# Erreurs du journal (watchdog) plus récentes que l'identifiant $2.
wd() {
  st drush "$1" php:script "$WORK_IN_CONTAINER/wd.php" -- "$2" >"$3" 2>&1 || true
}

max_wid() { st drush "$1" sqlq "SELECT MAX(wid) FROM watchdog" 2>/dev/null | tail -1; }
enabled_modules() { st drush "$1" pm:list --status=enabled --type=module --format=list 2>/dev/null | sort; }

# Arrête un processus et tous ses descendants.
kill_tree() {
  local pid="$1" c
  for c in $(pgrep -P "$pid" 2>/dev/null); do kill_tree "$c"; done
  kill "$pid" 2>/dev/null || true
}
