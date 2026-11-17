# drupal/migrate_plus

| | |
|---|---|
| Contrainte composer.json | `^6.0` |
| Version lockée | `6.0.1` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — l'import dynamique génère et réécrit des entités de config `migrate_plus.migration.*` et utilise les process plugins `entity_generate`/`entity_lookup`. |
| Couverture actuelle | Aucune (le test Kernel existant ne touche pas migrate_plus) |
| Priorité test | P1 |

## Où on l'utilise
Activé par le profil via `vactory_dynamic_import` (`vactory_starter_kit.info.yml:187`).

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_import | `src/Service/DynamicImportHelpers.php:233-460` | Génère le tableau de config d'une migration : process `entity_lookup` (l.391,434,453), `entity_generate` (l.411) |
| vactory_dynamic_import | `src/Form/DynamicImportForm.php:280` ; `src/Form/DynamicImportConfirmation.php:80,88` ; `src/Form/DynamicImportExecute.php:385` | Écrit/lit/liste les configs `migrate_plus.migration.*` |
| vactory_migrate_plugin | `src/Form/VactoryMigratePluginForm.php:173,808,820` | `getEditable("migrate_plus.migration.{id}")`, `plugin.manager.migration->getDefinitions()` |
| vactory_migrate_ui | `src/Form/MigrationFormEdit.php:22,48` ; `src/Form/Import.php:255` | Édition YAML brute de `migrate_plus.migration.*` |
| vactory_migrate | `src/Services/Import.php:178,187` ; `src/Services/EntityInfo.php:48` | Lecture des configs de migration |
| vactory_locator, vactory_user_migrate | `vactory_locator.info.yml:20` ; `vactory_user_migrate.info.yml:8` | Dépendance |
| 12 modules | `*/config/install/migrate_plus.migration.*.yml` (17 fichiers : news, news_migrate×4, locator×3, job_ads, faq, event, mediatheque, glossary, academy, publication, blog, user_migrate) | Migrations livrées avec `entity_generate`/`entity_lookup` |

## Tests existants qui l'exercent
- aucun (`TermNormalizationServiceTest` ne charge que `transliteration`).

## Tests à écrire
- **[Kernel] `vactory_dynamic_import/tests/src/Kernel/GeneratedMigrationTest.php`** — modules `migrate`, `migrate_plus`, `migrate_source_csv`, `node`, `taxonomy`, `vactory_migrate`, `vactory_dynamic_import` ; créer un bundle node avec un champ term, appeler `generateMigrationConfig()` puis sauver la config `migrate_plus.migration.test` et `plugin.manager.migration->createInstance('test')` → assert pas d'exception, process contient `entity_lookup`/`entity_generate` résolus. Importer un CSV fixture (2 lignes) via `MigrateExecutable` → 2 nodes + termes générés. Attrape : changement de schéma des configs, de signature des plugins `entity_generate`/`entity_lookup`.
- **[Kernel] `vactory_migrate/tests/src/Kernel/ShippedMigrationsValidTest.php`** — pour chaque `migrate_plus.migration.*.yml` livré, `createInstance()` + `checkRequirements()`/`getProcessPlugins()` ne lève pas `PluginNotFoundException`. Attrape : plugin renommé/supprimé.

## Notes
- Les modules de contenu (vactory_news, job_ads, faq, event, …) livrent des `migrate_plus.migration.*` en `config/install` sans déclarer `migrate_plus` dans leur `info.yml` : ils ne s'installent que si `migrate_plus` est déjà actif (vrai via le profil). À vérifier hors profil.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `4.2.0`
- 2021-01-31 `1be86d53` montée `4.2.0` → `^5.1` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` montée `^5.1` → `^5.3` — « Update: update not supported contrib modules. »
- 2023-07-13 `4b48e654` montée `^5.3` → `^6.0` — « Fix: #44188 - Drupal 10 compatibility: Update responsive_preview, twig_tweak, user_registratio… »
