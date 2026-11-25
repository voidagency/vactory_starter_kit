# shellcheck shell=bash
# Mesure d'un module sur une instance (chargé par « . » après common.sh).
#
# Avant chaque module : restauration du snapshot « audit-base » (base et
# fichiers). Puis : installation seule, santé du site, désinstallation, santé,
# restes, réinstallation. Résultat : $OUT/raw/<module>/{result.json,summary.md}.

SNAP_LABEL=audit-base

# Une page : une ligne JSON dans $1. Arguments : sortie, URL de base, cookies
# (vide : anonyme), libellé, chemin.
http_probe() {
  local out="$1" url="$2" jar="$3" label="$4" path="$5" r
  if [ -n "$jar" ]; then
    r=$(curl -sk --max-time 60 -b "$jar" -o /dev/null -w '%{http_code} %{time_total}' "$url$path" 2>/dev/null || echo "000 0")
  else
    r=$(curl -sk --max-time 60 -o /dev/null -w '%{http_code} %{time_total}' "$url$path" 2>/dev/null || echo "000 0")
  fi
  printf '{"check":"%s","path":"%s","code":%s,"time":%s}\n' "$label" "$path" "${r%% *}" "${r##* }" >>"$out"
}

# Santé du site : pages anonymes, pages d'administration, pages du module ($3,
# un chemin par ligne). Résultat : lignes JSON dans $2.
http_checks() {
  local inst="$1" out="$2" extra="${3:-}" url jar link uuid waited=0 path
  url="$(inst_url "$inst")"
  jar="$(mktemp)"
  : >"$out"
  # Routeur ddev injoignable (coupure constatée pendant l'audit) : attendre
  # 5 min au plus, pour ne pas l'imputer au module.
  until [ "$(curl -sk --max-time 10 -o /dev/null -w '%{http_code}' "$url/en/espace-prive/login" 2>/dev/null)" != 000 ] || [ "$waited" -ge 300 ]; do
    sleep 10
    waited=$((waited + 10))
  done
  if [ "$waited" -gt 0 ]; then echo "{\"check\":\"_router_wait\",\"path\":\"\",\"code\":0,\"time\":$waited}" >>"$out"; fi
  # shellcheck disable=SC2016 # code PHP, pas de variable shell.
  uuid=$(st drush "$inst" php:eval 'try { $p = \Drupal::config("system.site")->get("page.front"); $n = \Drupal::service("path_alias.manager")->getPathByAlias($p); if (preg_match("#^/node/(\d+)#", $n, $m) && ($e = \Drupal\node\Entity\Node::load($m[1]))) { echo $e->bundle() . "/" . $e->uuid(); } } catch (\Throwable $t) { echo "ERR"; }' 2>/dev/null | tail -1)
  http_probe "$out" "$url" "" home_anon /en
  http_probe "$out" "$url" "" login_page /en/espace-prive/login
  http_probe "$out" "$url" "" router_home "/en/router/translate-path?path=/en"
  if [ -n "$uuid" ] && [ "$uuid" != ERR ]; then http_probe "$out" "$url" "" jsonapi_home "/en/api/node/$uuid"; fi
  link=$(st drush "$inst" uli --uid=1 --no-browser --uri="$url" 2>/dev/null | tail -1)
  curl -sk --max-time 60 -c "$jar" -b "$jar" -L -o /dev/null "$link" 2>/dev/null
  for path in /en/admin/config /en/admin/reports/status /en/admin/people/permissions /en/admin/structure/block /en/admin/content /en/admin/modules; do
    http_probe "$out" "$url" "$jar" admin "$path"
  done
  if [ -n "$extra" ] && [ -s "$extra" ]; then
    while read -r path; do
      if [ -n "$path" ]; then http_probe "$out" "$url" "$jar" module_page "$path"; fi
    done <"$extra"
  fi
  rm -f "$jar"
}

cr_cron() { # instance, dossier, préfixe
  drush_to "$1" "$2/$3-cr.txt" cr
  echo $? >"$2/$3-cr.rc"
  drush_to "$1" "$2/$3-cron.txt" cron
  echo $? >"$2/$3-cron.rc"
}

has_snapshot() { st snapshot "$1" --list 2>/dev/null | awk '{print $1}' | grep -qx "$SNAP_LABEL"; }

# Prépare une instance : dblog, snapshot « audit-base », passage témoin (les
# mêmes étapes sans module, pour connaître le bruit). Refait si le snapshot
# manque ou si le témoin n'est pas allé au bout (marqueur control.done écrit en
# dernier).
prepare() {
  local inst="$1" d="$OUT/raw/_control/$1" t0 w
  if [ -f "$d/control.done" ] && has_snapshot "$inst"; then return 0; fi
  t0=$(now)
  rm -rf "$d"
  mkdir -p "$d"
  push_php "$inst"
  drush_to "$inst" "$d/dblog-en.txt" en dblog -y
  st drush "$inst" cr >/dev/null 2>&1
  st snapshot "$inst" "$SNAP_LABEL" >"$d/snapshot.txt" 2>&1 || { cat "$d/snapshot.txt"; return 1; }
  enabled_modules "$inst" >"$d/base-modules.txt"
  snap "$inst" "$d/s0.json"
  cr_cron "$inst" "$d" c1
  http_checks "$inst" "$d/http1.jsonl"
  cr_cron "$inst" "$d" c2
  http_checks "$inst" "$d/http2.jsonl"
  cr_cron "$inst" "$d" c3
  http_checks "$inst" "$d/http3.jsonl"
  snap "$inst" "$d/s1.json"
  w=$(json_max_wid "$d/s0.json")
  wd "$inst" "$w" "$d/wd.json"
  st restore "$inst" "$SNAP_LABEL" >"$d/restore.txt" 2>&1 || { cat "$d/restore.txt"; return 1; }
  : >"$d/control.done"
  log "$inst préparée en $(el "$t0") s"
}

# Référence des restes : base + seulement les dépendances que l'installation a
# tirées (elles restent après « pmu <module> »).
ref_step() {
  local inst="$1" m="$2" d="$3" pulled t
  pulled=$("$PY" "$MA_HOME/lib/diff.py" pulled "$d/s0.json" "$d/s1.json" "$m")
  if [ -z "$pulled" ]; then return 0; fi
  t=$(now)
  st restore "$inst" "$SNAP_LABEL" >"$d/40-ref-restore.txt" 2>&1
  # shellcheck disable=SC2086 # liste de modules.
  drush_to "$inst" "$d/40-ref-deps-install.txt" en $pulled -y
  echo $? >"$d/40-ref-deps-install.rc"
  snap "$inst" "$d/sD.json"
  el "$t" >"$d/40-ref.time"
}

# Un module. Déjà mesuré (result.json) : passé, sauf mesure invalide (site
# injoignable), alors refaite ; FORCE=1 refait tout.
measure_module() {
  local inst="$1" m="$2" d="$OUT/raw/$2" ctl="$OUT/raw/_control/$1" t0 t mode w0 w1 w2 w3 w4 casc
  if [ -f "$d/result.json" ] && "$PY" -c 'import json, sys; sys.exit(0 if json.load(open(sys.argv[1]))["flags"].get("measurement_invalid") else 1)' "$d/result.json"; then
    mkdir -p "$OUT/raw/_invalid"
    rm -rf "$OUT/raw/_invalid/$m"
    mv "$d" "$OUT/raw/_invalid/$m"
    log "$m : mesure précédente invalide (site injoignable), refaite"
  fi
  if [ -f "$d/result.json" ] && [ "${FORCE:-0}" != 1 ]; then
    log "$m : déjà mesuré, passé"
    return 0
  fi
  rm -rf "$d"
  mkdir -p "$d"
  t0=$(now)
  echo "{\"instance\":\"$inst\",\"started\":\"$(date -u +%FT%TZ)\"}" >"$d/meta.json"
  if grep -qx "$m" "$ctl/base-modules.txt"; then mode=enabled; else mode=new; fi
  echo "$mode" >"$d/mode.txt"

  t=$(now)
  st restore "$inst" "$SNAP_LABEL" >"$d/00-restore.txt" 2>&1
  echo $? >"$d/00-restore.rc"
  el "$t" >"$d/00-restore.time"
  push_php "$inst"
  snap "$inst" "$d/s0.json"
  w0=$(json_max_wid "$d/s0.json")

  if [ "$mode" = new ]; then
    t=$(now)
    drush_to "$inst" "$d/10-install.txt" en "$m" -y
    echo $? >"$d/10-install.rc"
    el "$t" >"$d/10-install.time"
    snap "$inst" "$d/s1.json"
    wd "$inst" "$w0" "$d/10-install-wd.json"
    "$PY" "$MA_HOME/lib/diff.py" routes "$d/s0.json" "$d/s1.json" >"$d/module-pages.txt"
    cr_cron "$inst" "$d" 11
    http_checks "$inst" "$d/11-http.jsonl" "$d/module-pages.txt"
    w1=$(json_max_wid "$d/s1.json")
    wd "$inst" "$w1" "$d/11-health-wd.json"
  else
    cp "$d/s0.json" "$d/s1.json"
    : >"$d/module-pages.txt"
  fi

  w2=$(max_wid "$inst")
  t=$(now)
  drush_to "$inst" "$d/20-uninstall.txt" pmu "$m" -y
  echo $? >"$d/20-uninstall.rc"
  el "$t" >"$d/20-uninstall.time"
  wd "$inst" "${w2:-0}" "$d/20-uninstall-wd.json"
  snap "$inst" "$d/s2.json"
  cr_cron "$inst" "$d" 21
  http_checks "$inst" "$d/21-http.jsonl" "$d/module-pages.txt"
  w3=$(json_max_wid "$d/s2.json")
  wd "$inst" "$w3" "$d/21-health-wd.json"

  # Réinstallation : le module, puis (module activé par le profil) les modules
  # que la désinstallation a emportés.
  w4=$(max_wid "$inst")
  t=$(now)
  drush_to "$inst" "$d/30-reinstall.txt" en "$m" -y
  echo $? >"$d/30-reinstall.rc"
  el "$t" >"$d/30-reinstall.time"
  casc=$("$PY" "$MA_HOME/lib/diff.py" cascade "$d/s1.json" "$d/s2.json" "$m")
  if [ -n "$casc" ]; then
    echo "$casc" >"$d/30-cascade-modules.txt"
    # shellcheck disable=SC2086 # liste de modules.
    drush_to "$inst" "$d/31-reinstall-cascade.txt" en $casc -y
    echo $? >"$d/31-reinstall-cascade.rc"
  fi
  wd "$inst" "${w4:-0}" "$d/30-reinstall-wd.json"
  snap "$inst" "$d/s3.json"

  el "$t0" >"$d/total.time"
  ref_step "$inst" "$m" "$d"
  "$PY" "$MA_HOME/lib/diff.py" result "$d" "$m" "$ctl" || log "$m : diff.py a échoué"
  log "$m ($mode) : $(cat "$d/total.time") s"
}
