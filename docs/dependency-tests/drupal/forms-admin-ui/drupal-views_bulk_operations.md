# drupal/views_bulk_operations

| | |
|---|---|
| Contrainte composer.json | `^4.1` |
| Version lockée | `4.2.5` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — 2 actions custom héritent de `ViewsBulkOperationsActionBase`. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
Module optionnel `vactory_locator`.

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_locator | `src/Plugin/Action/PublishLocatorEntityAction.php:7,12,19` | `@Action` `extends ViewsBulkOperationsActionBase` |
| vactory_locator | `src/Plugin/Action/UnpublishLocatorEntityAction.php:7,12,19` | idem |
| vactory_locator | `config/install/views.view.vactory_locator.yml:1321-1324` | Champ `views_bulk_operations_bulk_form` |
| vactory_locator | `vactory_locator.info.yml:33` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_locator/tests/src/Kernel/LocatorBulkActionsTest.php`** — créer 2 entités locator dépubliées, instancier l'action publish via `plugin.manager.action` et `executeMultiple()` → statut publié. Attrape : changement de `ViewsBulkOperationsActionBase` (méthodes abstraites, `access()`).

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^3.3.0`
- 2022-06-09 `02645c51` montée `^3.3.0` → `^4.1` — « Update: update not supported contrib modules. »
