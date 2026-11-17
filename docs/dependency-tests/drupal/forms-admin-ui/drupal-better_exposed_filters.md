# drupal/better_exposed_filters

| | |
|---|---|
| Contrainte composer.json | `^6.0` |
| Version lockée | `6.0.3` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — 25 vues livrées avec `exposed_form: type: bef` et un form_alter qui dépend de la classe CSS `bef-datepicker`. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:130` | Installé |
| vactory_views | `vactory_views.info.yml:8` ; `vactory_views.module:163,210-220` | `hook_form_alter` : retire la classe `bef-datepicker` pour la remplacer par un datepicker Bootstrap |
| vactory_search, vactory_governance, vactory_forums | `vactory_search.info.yml:6`, `vactory_governance.info.yml:6`, `vactory_forums.info.yml:8` | Dépendance |
| 25 modules | `config/install/views.view.*.yml` (news, event, blog, publication, job_ads, locator, smart_search, …) | `exposed_form: type: bef` (32 occurrences), `plugin_id: bef`, `bef_datepicker` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_views/tests/src/ExistingSite/BefExposedFormTest.php`** — rendre la vue `vactory_news` (page) avec filtres exposés → 200, présence du formulaire exposé et absence de la classe `bef-datepicker` sur les champs date. Attrape : changement de plugin id/markup BEF.
- **[Kernel] `vactory_views/tests/src/Kernel/BefPluginsTest.php`** — `plugin.manager.views.exposed_form->hasDefinition('bef')` et filtres `bef_datepicker` présents dans `plugin.manager.better_exposed_filters_filter_widget`. P2.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^3.0.0-alpha6`
- 2021-01-31 `1be86d53` montée `^3.0.0-alpha6` → `^5.0@beta` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` réécriture contrainte `^5.0@beta` → `^5.0` — « Update: update not supported contrib modules. »
- 2023-07-13 `ab59b8f6` montée `^5.0` → `^6.0` — « Fix: #44188 - Drupal 10 compatibility: Update addtoany, advagg, better_exposed_filters, block_… »
