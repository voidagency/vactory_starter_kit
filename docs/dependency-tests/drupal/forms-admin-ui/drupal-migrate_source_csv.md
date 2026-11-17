# drupal/migrate_source_csv

| | |
|---|---|
| Contrainte composer.json | `^3.1.0` |
| Version lockée | `3.6.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — source `csv` de toutes les migrations livrées et générées (options `path`, `ids`, `delimiter`, `header_row_count`). |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_import | `src/Service/DynamicImportHelpers.php:242-250` | Source générée `plugin: csv`, `header_row_count`, `ids`, `delimiter`, `path`, `constants` |
| vactory_migrate_plugin | `src/Form/VactoryMigratePluginForm.php:741` | Source générée `plugin: csv` |
| vactory_migrate | `src/Services/Import.php:61-62` | Remplace `source.path` à l'exécution (batch par fichier) |
| 12 modules | 17 × `config/install/migrate_plus.migration.*.yml` (ex. `vactory_locator/config/install/migrate_plus.migration.locator_items.yml:15`) | `source: plugin: csv` |
| vactory_migrate, vactory_dynamic_import, vactory_locator, vactory_user_migrate | `vactory_migrate.info.yml:9`, `vactory_dynamic_import.info.yml:10`, `vactory_locator.info.yml:21`, `vactory_user_migrate.info.yml:9` | Dépendance |

## Tests existants qui l'exercent
- aucun (`TermNormalizationServiceTest` lit un CSV avec du PHP natif, pas via le plugin).

## Tests à écrire
- **[Kernel] `vactory_dynamic_import/tests/src/Kernel/CsvSourceTest.php`** — instancier la migration générée avec `path` = fixture `tests/artifacts/TestVDI_0.csv` et `delimiter` du settings ; itérer `getSourcePlugin()` → nombre de lignes et clés de colonnes attendus (y compris accents/BOM). Attrape : changement de gestion du délimiteur/en-têtes (league/csv).

## Notes
- Couplé au test migrate_plus proposé (même fixture).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^3.1.0`
- — aucun changement contrainte inchangée sur `3.x`
