# drupal/search_api_exclude_entity

| | |
|---|---|
| Contrainte composer.json | `^3.0.1` |
| Version lockée | `3.0.1` |
| Patches | aucun |
| Statut | Indirect (type de champ + processor en config) |
| Risque à la MAJ | Moyen — champ `field_exclude_from_search` (storage dans vactory_field) et processor `search_api_exclude_entity_processor` dans les index ; l'API décuplée en dépend implicitement. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_field | `modules/vactory_field/config/install/field.storage.node.field_exclude_from_search.yml:10` | `type: search_api_exclude_entity` |
| vactory_page | `modules/vactory_page/config/install/field.field.node.vactory_page.field_exclude_from_search.yml` | Champ sur vactory_page |
| vactory_search | `modules/vactory_search/config/install/search_api.index.default_content_index.yml:158` | Processor `search_api_exclude_entity_processor` |
| vactory_faq | `modules/vactory_faq/config/install/search_api.index.vactory_faq_index.yml:98` | Processor |
| info.yml | `vactory_starter_kit.info.yml:119` | Profil ; vactory_field:20, vactory_faq:12, vactory_faq_prive:12 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `SearchControllerTest::testExcludeFromSearch`** (dans `modules/vactory_decoupled/modules/vactory_decoupled_search/tests/…`) — 2 nodes « Drupal », l'un avec `field_exclude_from_search=1`, indexation → `/api/search?q=Drupal` ne renvoie que l'autre.

## Notes
Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0`
- 2021-01-31 `1be86d53` montée `^1.0.0` → `^1.3.0` — « Update: Add v2.1.0 Factory release changes. »
- 2026-09-17 `f337dbc2` montée `^1.3.0` → `^3.0.1` — « Update: upgrade profile dependencies and support Metatag 2 »
