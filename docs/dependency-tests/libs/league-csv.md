# league/csv

| | |
|---|---|
| Contrainte composer.json | `9.16.0` (version exacte) |
| Version lockée | `9.16.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — aucun appel direct ; utilisé via `drupal/migrate_source_csv` (17 migrations) et `drupal/csv_serialization`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_migrate_ui | `modules/vactory_migrate/modules/vactory_migrate_ui/src/Form/Import.php:10` | `use League\Csv\Reader;` **jamais utilisé** — le fichier lit avec `fgetcsv()` (l.291-293) |
| 12 modules (academy, blog, event, faq, glossary, job_ads, locator ×3, mediatheque, news ×5, publication, user_migrate) | `*/config/install/migrate_plus.migration.*.yml` | `source: plugin: csv` (migrate_source_csv → `League\Csv\Reader`) — 17 fichiers |
| vactory_appointment | `modules/vactory_appointment/config/install/views.view.appointments_edit.yml:9` | dépendance `csv_serialization` (export CSV de vue) |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `modules/vactory_news/tests/src/Kernel/NewsCsvMigrationSourceTest.php`** — charger la migration `node_vactory_news_migration_default`, pointer `path` vers une fixture CSV 2 lignes → `getSourcePlugin()->count() === 2` et 1ʳᵉ ligne lue avec les bons en-têtes. Couvre la chaîne migrate_source_csv + league/csv utilisée par toutes les migrations du profil.

## Notes
- Épinglage exact `9.16.0` ajouté au commit `9cbd99aa` (2025-05-26, montée `csv_serialization` 4.0.1) ; raison non documentée — **à vérifier** (probable contournement d'une incompatibilité 9.17+/PHP). Empêche tout correctif de sécurité.
- Supprimer le `use` mort dans `Import.php:10`.

## Historique
- 2025-05-26 `9cbd99aa` ajout **épinglé** `9.16.0` (avec csv_serialization 4.0.1) — épinglage à l'ajout, pas après incident
- Aucun incident.

## Suivi (2026-11-19)
- Figé en `9.16.0` (dernière : 9.29.0). Raison du blocage à retrouver (#71222).
