# CI et outillage

## Drone (état au 2026-11-19, après #71156)

- **Serveur :** `ci-github.leserveurdetest.com` (Drone 2.28), sur un Mac mini M2 dédié (`192.168.100.164`). Docker tourne dans OrbStack, et l'image `vactory/vactory-ci` est en amd64 (émulée par Rosetta). 2 builds GitHub + 2 Bitbucket en parallèle au maximum. Le CLI `drone` permet de lister, relancer et lire les logs des builds.
- **Déclenchement** (`.drone.yml`, #921) : `trigger.ref` limité à `refs/heads/3.x` et `refs/pull/**`. Un build par PR, et un par push sur 3.x.
- **Image** `vactory/vactory-ci:latest` (dépôt Bitbucket `adminvoid/droneci`, dossier `vactory-ci`). L'autobuild Docker Hub est **désactivé** : chaque changement demande un build manuel (Docker Hub → Builds → Trigger), puis un `docker pull` sur le Mac CI (le pipeline utilise `pull: if-not-exists`).
- **Ce que fait le build** (mode dual-clone) :
  1. clone de vactory8 `develop` (`VACTORY8_BRANCH`) et du profil au commit de la PR ;
  2. `composer update voidagency/vactory_starter_kit -W`, en **arrêtant le pipeline si composer échoue** (patch compris) ;
  3. `drush si`, puis phpunit (testsuites Unit, Kernel, ExistingSite et `tests/Functional` du profil), phpcs sur les fichiers modifiés et SonarQube.
- **Correctifs apportés** (#71156) :
  - les tests tournaient sur la release 3.1.7 et le lock de vactory8 : composer échouait et l'échec était ignoré ;
  - Sonar se bloquait parfois sur le pont Node.js de SonarJS : il est désormais borné par `SONAR_TIMEOUT` (900 s), et les tests tournent quand même ;
  - les builds de PR n'étaient pas créés quand la description contenait un emoji : la connexion MySQL de Drone est passée en `charset=utf8mb4`.
- **Résultat :** les nouveaux tests sont bien exécutés (45 tests sur 3.x, jusqu'à 56 sur les PR qui en ajoutent). La vraie CI a révélé #71167 (captcha) et #71168 (simple_oauth), corrigés depuis.
- **Quality gate Sonar** (`sonar.qualitygate.wait=true`) : en échec sur 3.x à cause de `vactory_webform_typesafe` (#71220). Les exclusions et la règle S1781 ont été corrigées (PR #929).

## Points ouverts

1. Le statut Drone n'est pas encore **requis** dans la protection de branche `3.x`.
2. Dependabot ne propose plus que les mises à jour de sécurité, regroupées en une seule PR (PR #930) : le `composer.lock` du profil n'est utilisé ni par les projets ni par la CI.
3. La VM Docker du Mac CI passe de 8 à 12 Go pour absorber 4 builds en parallèle.

## Patches

- Le profil déclare `enable-patching: true` et ~20 patches dans `extra.patches`. Un patch déclaré dans un paquet dépendant n'est appliqué que si le **projet racine** l'autorise (composer-patches). À vérifier dans le template de projet et dans l'image CI.
- Le `vendor/` local du profil n'a pas les patches appliqués (pas de `PATCHES.txt`) : les fiches qui comparent nos surcharges au code contrib l'ont fait sur du code **non patché**.
- Plusieurs patches viennent de gists (dont personnels) : rapatrier dans `patches/` du dépôt.
- Clé de patch erronée `drupal/jsonapi_extra` : corrigée (#71101, PR #909).
- Patch captcha tronqué (gist) : KO sur 2.0.12 (#71167).

## Environnement local

- `vendor/` désynchronisé du `composer.lock` (ex. jquery_colorpicker 4.0.0 installé / 5.0.0 locké ; hreflang, merge_translations, block_list_override absents).
- Le hook `post-merge` lance `composer install`, qui échoue : PHP 8.3 Homebrew ne trouve pas `libtidy.58.dylib` (`brew reinstall tidy-html5`).
