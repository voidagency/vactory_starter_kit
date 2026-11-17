# drupal/entity_reference_revisions

| | |
|---|---|
| Contrainte composer.json | `^1.8` |
| Version lockée | `1.11.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — type de champ de `field_vactory_paragraphs` (node), `field_vactory_paragraph_tab`, `field_menu_paragraphs` ; toute la gestion `target_revision_id` du code en dépend. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_field | `modules/vactory_field/config/install/field.storage.node.field_vactory_paragraphs.yml:14` | Config : `module: entity_reference_revisions` (storage) |
| vactory_field | `modules/vactory_field/config/install/field.storage.paragraph.field_vactory_paragraph_tab.yml:13` | Config storage (onglets multi-template) |
| vactory_field | `modules/vactory_field/config/install/field.storage.menu_link_content.field_menu_paragraphs.yml:14` | Config storage sur `menu_link_content` |
| types de contenu (page, academy, mediatheque, help_center, multivers, annual_report, newsletter…) | `modules/vactory_page/config/install/field.field.node.vactory_page.field_vactory_paragraphs.yml:12` | Config : 29 fichiers ; formatter `entity_reference_revisions_entity_view` (14 occurrences) |
| vactory_content_collector | `modules/vactory_content_collector/vactory_content_collector.module:78` | `EntityReferenceRevisionsFieldItemList` (`filter()` sur les items) |
| vactory_decoupled | `modules/vactory_decoupled/src/Controller/EditLiveMode.php:205` | Écriture directe de `target_revision_id` |
| vactory_decoupled | `modules/vactory_decoupled/src/Controller/DuplicateParagraphController.php:78` | Insertion `target_id`/`target_revision_id` |
| vactory_paragraph_clipboard | `modules/vactory_paragraph_clipboard/src/Controller/ClipboardController.php:85` | `appendItem($entity)` sur champ ERR |
| profil | `vactory_starter_kit.info.yml:86` | Installé par le profil |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/tests/Functional/JsonApiCollectionTest.php` — node avec `field_vactory_paragraphs` (`target_id`+`target_revision_id`, l.112) lu en JSON:API.
- `modules/vactory_decoupled/tests/Functional/DynamicFieldMediaTest.php`, `WebformParagraphTest.php`, `VactoryCrossBundlesTest.php`, `DynamicFieldImportExportTest.php` — même montage.
- Non couvert : révisions (nouvelle révision du parent ⇒ nouvelle révision composite), suppression orpheline, `appendItem`.

## Tests à écrire
- **[Kernel] `modules/vactory_field/tests/src/Kernel/ParagraphsRevisionReferenceTest.php`** — node + paragraph ; modifier le paragraph avec `setNewRevision()`, mettre à jour `target_revision_id` du node → `$node->field_vactory_paragraphs->entity->getRevisionId()` = nouvelle révision ; charger l'ancienne révision du node → ancienne révision du paragraph. Attrape une régression du chargement composite ERR.
- **[ExistingSite] `modules/vactory_paragraph_clipboard/tests/src/ExistingSite/ClipboardCloneTest.php`** — clonage via route clipboard → node a +1 item, item référence une révision existante.

## Notes
- Contrainte `^1.8` très large vs lock 1.11.0 : une install fraîche pourrait résoudre n'importe quelle 1.x ≥ 1.8 (à aligner).
- Schéma du champ (colonnes `target_id`, `target_revision_id`) utilisé en dur dans ~20 fichiers PHP.
- Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.8`
- — aucun changement contrainte inchangée sur `3.x`
