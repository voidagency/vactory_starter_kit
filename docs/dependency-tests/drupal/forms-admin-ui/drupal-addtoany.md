# drupal/addtoany

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.5` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible — hooks de vactory_core qui manipulent la librairie `addtoany/addtoany`, l'attachement `addtoany-js` et le bloc `addtoany_block`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:97` | Installé |
| vactory_core | `vactory_core.module:405-417` (`vactory_core_page_attachments_alter`) | Retire `html_head` `addtoany-js` et la librairie `addtoany/addtoany` |
| vactory_core | `vactory_core.module:435-468` (`vactory_core_preprocess_block`) | Pour `plugin_id === 'addtoany_block'` : lit `addtoany.settings`, ré-attache `addtoany-js` et la librairie |
| vactory_news, vactory_dynamic_field_default | `vactory_news/templates/full.html.twig:125`, `vactory_dynamic_field_default/widgets/36/template.html.twig:34` | `vactory_render('block', 'addtoany_block')` |
| ~35 modules | `config/install/core.entity_view_display.node.*.yml` (149 occurrences) | Pseudo-champ `addtoany` dans les displays |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/AddtoanyBlockTest.php`** — `plugin.manager.block->createInstance('addtoany_block')` existe ; `library.discovery->getLibraryByName('addtoany','addtoany')` non vide. P3.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.14`
- 2022-06-09 `02645c51` montée `1.14` → `^1.16` — « Update: update not supported contrib modules. »
- 2023-07-13 `ab59b8f6` montée `^1.16` → `^2.0` — « Fix: #44188 - Drupal 10 compatibility: Update addtoany, advagg, better_exposed_filters, block_… »
