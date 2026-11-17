# drupal/block_field

| | |
|---|---|
| Contrainte composer.json | `^1.0.0-rc1` |
| Version lockée | `1.0.0-rc4` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — type de champ sur node/paragraph/menu/block_content + service `block_field.manager` dans un élément de formulaire custom. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_core | `modules/vactory_core/src/Element/BlockField.php:62` | `\Drupal::service('block_field.manager')->getBlockDefinitions()` |
| vactory_anchor | `modules/vactory_anchor/src/Plugin/Block/AnchorBlock.php:32` | Élément `#type => block_field` (élément de vactory_core) |
| vactory_field | `modules/vactory_field/config/install/field.storage.node.field_vactory_block.yml` | Config : 8 storages `block_field` (node, paragraph, menu_link_content, block_content) |
| vactory_paragraphs | `modules/vactory_paragraphs/config/install/field.field.paragraph.vactory_paragraph_block.field_vactory_block.yml` | Paragraph « bloc » |
| vactory_banner | `modules/vactory_banner/config/install/field.field.block_content.vactory_banner.field_vactory_blocks_multiple.yml` | Config block_content |
| info.yml | `modules/vactory_core/vactory_core.info.yml:9` | Dépendance (aussi vactory_field:10, vactory_header:9, vactory_banner:10, profil:80) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_core/tests/src/Kernel/BlockFieldElementTest.php`** — construire le form element `block_field` (processElement) → `plugin_id` est un select dont les options contiennent un bloc système connu (ex. `system_powered_by_block`). Attrape un changement de `BlockFieldManagerInterface::getBlockDefinitions()`.
- **[ExistingSite] `modules/vactory_paragraphs/tests/src/ExistingSite/ParagraphBlockFieldTest.php`** — paragraph `vactory_paragraph_block` avec `field_vactory_block` = plugin connu, rendu de la page → contenu du bloc présent.

## Notes
Version RC (`1.0.0-rc4`), contrainte `^1.0.0-rc1` : la sortie de la 1.0.0 stable sera prise automatiquement. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0-rc1`
- 2023-10-20 `9f8eee27` montée (hors 3.x) `^1.0.0-rc1` → `^1.0@RC` sur `origin/2.x`/`origin/3.x-beta` seulement
