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

## Environnement local

- `vendor/` désynchronisé du `composer.lock` (ex. jquery_colorpicker 4.0.0 installé / 5.0.0 locké ; hreflang, merge_translations, block_list_override absents).
- Le hook `post-merge` lance `composer install`, qui échoue : PHP 8.3 Homebrew ne trouve pas `libtidy.58.dylib` (`brew reinstall tidy-html5`).
