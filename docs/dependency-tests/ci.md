# CI et outillage

## Drone

- `.drone.yml` : une étape `setup`, image `vactory/vactory-ci:latest` (source : dépôt `vactory-test-ci`, `entrypoint.sh`). Aucun filtre `trigger` : tourne sur tout événement reçu par Drone.
- L'image : `drush si vactory_starter_kit`, puis `phpunit` + drupal-test-traits sur **tout le dossier du profil** (`entrypoint.sh:207`), plus phpcs, Sonar, notification Slack.
- Les testsuites du `phpunit.xml` (`*/tests/src/{Unit,Kernel,ExistingSite,ExistingSiteJavascript}`) ne servent pas, phpunit étant lancé sur un chemin : les tests rangés dans `tests/Functional/` sont donc bien trouvés.
- `SIMPLETEST_DB` est une valeur factice : les tests **Kernel** (`KernelTestBase`) ont besoin d'une vraie base pour s'installer. À vérifier que les 2 tests Kernel existants tournent réellement en CI et ne sont pas en erreur/skip.

## À vérifier (point 5 de #71097)

1. Drone se déclenche-t-il sur les PR Dependabot ? (webhook GitHub, PR depuis une branche du même dépôt, secrets disponibles pour les PR Dependabot)
2. Le statut Drone est-il **requis** dans la protection de branche `3.x` ?
3. Le build installe-t-il les dépendances **de la PR** (et pas celles de `3.x`) avant `drush si` ?

Sans ces trois points, écrire des tests ne protège pas des MAJ Dependabot.

## Patches

- Le profil déclare `enable-patching: true` et ~20 patches dans `extra.patches`. Un patch déclaré dans un paquet dépendant n'est appliqué que si le **projet racine** l'autorise (composer-patches). À vérifier dans le template de projet et dans l'image CI.
- Le `vendor/` local du profil n'a pas les patches appliqués (pas de `PATCHES.txt`) : les fiches qui comparent nos surcharges au code contrib l'ont fait sur du code **non patché**.
- Plusieurs patches viennent de gists (dont personnels) : rapatrier dans `patches/` du dépôt.
- Clé de patch erronée : `drupal/jsonapi_extra` (#71101).

## Constat sur un build réel (#705, 2026-11-17)

Log du build `ci-github.leserveurdetest.com/voidagency/vactory_starter_kit/705` (vert, 45 tests, 607 assertions) :

- Mode « Dual-Clone » : le profil est monté en dépôt `path` dans un projet racine `vactory8` qui épingle `drupal/core 10.4.7` et `drupal/linkit 7.0.12`, avec `cweagans/composer-patches` et `patches-file` (les patches des dépendances sont donc rassemblés).
- `composer update` échoue (`editor_advanced_link 2.3.4` exige core ^10.5), l'échec est ignoré : **les tests tournent sur le lock de vactory8**, pas sur les contraintes du profil → #71156.
- Le `entrypoint.sh` local de `~/dev/vactory-test-ci` (`composer install`) ne correspond plus à l'image utilisée.
- Push de branche : `DRONE_TARGET_BRANCH` = branche source → `git fetch` échoue, aucun fichier modifié détecté, PHPCS à 0 s.
- Bruit sans gravité : imports de traductions ar refusés (HTML), DSN mailer invalide, `RouteNotFoundException jsonapi.node--vactory_news.collection` pendant l'indexation search_api juste après l'install de `vactory_news` dans un test (routeur pas encore reconstruit).

## Environnement local

- `vendor/` désynchronisé du `composer.lock` (ex. jquery_colorpicker 4.0.0 installé / 5.0.0 locké ; hreflang, merge_translations, block_list_override absents).
- Le hook `post-merge` lance `composer install`, qui échoue : PHP 8.3 Homebrew ne trouve pas `libtidy.58.dylib` (`brew reinstall tidy-html5`).
