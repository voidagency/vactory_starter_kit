# drupal/migrate_tools

| | |
|---|---|
| Contrainte composer.json | `^6.0` |
| Version lockée | `6.0.4` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — exécution des imports via `Drupal\migrate_tools\MigrateExecutable` et list builder étendu. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (confirmée par l'historique : patch correctif « drush command not found » (`620c057c`)) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_migrate | `src/Services/Import.php:8,68-77` | `new MigrateExecutable($migration, new MigrateMessage())`, `->import()`, `getFailedCount()`, `getCreatedCount()` (service `vactory_migrate.import`, appelé par `DynamicImportConfirmation.php:79` et `ImportConfirmation.php:100`) |
| vactory_migrate_ui | `src/Controller/MigrationUiListBuilder.php:7,16,23,52` | Étend `Drupal\migrate_tools\Controller\MigrationListBuilder` (`buildHeader`, `buildRow`) |
| vactory_migrate, vactory_dynamic_import, vactory_migrate_ui, vactory_locator, vactory_user_migrate | `vactory_migrate.info.yml:8`, `vactory_dynamic_import.info.yml:9`, `vactory_migrate_ui.info.yml:9`, `vactory_locator.info.yml:22`, `vactory_user_migrate.info.yml:10` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_migrate/tests/src/Kernel/ImportServiceTest.php`** — migration CSV minimale (fixture 3 lignes, destination `entity:taxonomy_term`) ; appeler le callback `Import::importCallback()` avec un `$context` vide → 3 termes créés, `getCreatedCount() === 3`. Attrape : changement de constructeur/compteurs de `migrate_tools\MigrateExecutable`.
- **[ExistingSite] `vactory_migrate_ui/tests/src/ExistingSite/MigrationListTest.php`** — admin GET de la liste des migrations du groupe par défaut → 200 et colonnes ajoutées présentes. Attrape : signature de `MigrationListBuilder`.

## Notes
- `drupal/path_redirect_import` dépend aussi de `migrate_tools` (composer.lock).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `4.4.0`
- 2021-01-31 `1be86d53` montée (épinglée) `4.4.0` → `4.5.0` — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-11 `ae0e3a00` patch ajouté « Fix migrate tools drush command not found » (issue 3099611) — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2022-06-09 `02645c51` montée `4.5.0` → `^5.1` — « Update: update not supported contrib modules. »
- 2022-06-09 `7c630cc4` patch retiré « Fix migrate tools drush command not found » — « Update: Remove unused migrate_tools patch. »
- 2023-06-19 `3f46f492` montée `^5.1` → `^6.0` — « Add: #45165 - Add vactory migrate plugin custom module & Add migrate file contrib module »
