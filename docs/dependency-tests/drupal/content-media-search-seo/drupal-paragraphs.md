# drupal/paragraphs

| | |
|---|---|
| Contrainte composer.json | `^1.16` |
| Version lockée | `1.17.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — entité `paragraph` au cœur de toutes les pages (`field_vactory_paragraphs`), classe d'entité surchargée, API JSON d'édition live et imports/exports manipulent directement révisions et champs parent. |
| Couverture actuelle | Partielle |
| Priorité test | P1 (confirmée par l'historique : patch correctif #42408 (`035dd9f3`)) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_core | `modules/vactory_core/vactory_core.module:85` | `hook_entity_type_build` : `$entity_types['paragraph']->setClass(VactoryParagraph)` (l.87) |
| vactory_core | `modules/vactory_core/src/Entity/VactoryParagraph.php:10` | `extends Paragraph`, surcharge `getSummary()` (couplage à la classe concrète) |
| vactory_paragraphs | `modules/vactory_paragraphs/vactory_paragraphs.module:44` | `hook_entity_base_field_info` : ajoute `paragraph_identifier`, `paragraph_container`… sur l'entité paragraph |
| vactory_paragraphs | `modules/vactory_paragraphs/vactory_paragraphs.module:149` | `hook_preprocess_paragraph` (id/classe CSS) |
| vactory_paragraphs | `modules/vactory_paragraphs/vactory_paragraphs.module:172` | `hook_field_widget_entity_reference_paragraphs_form_alter` (widget legacy `entity_reference_paragraphs`, structure `$elements['subform']`) |
| vactory_paragraphs | `modules/vactory_paragraphs/vactory_paragraphs.module:192` | `hook_field_widget_single_element_form_alter` : lit `$element['top']['paragraph_summary']['fields_info']['#summary']` (structure interne du widget `paragraphs`) |
| vactory_paragraphs_bg_wrapper | `modules/vactory_paragraphs_bg_wrapper/vactory_paragraphs_bg_wrapper.module:57` | `hook_form_paragraphs_subform_alter` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/vactory_dynamic_field.module:336` | `hook_paragraph_access` (visibilité par rôles `field_roles`/`field_negate_roles`) |
| vactory_decoupled | `modules/vactory_decoupled/src/Controller/EditLiveMode.php:185` | `setNewRevision(TRUE)`, `getRevisionId()`, requête sur `parent_id`/`parent_type` (l.193-194), réécriture `target_revision_id` (l.205, l.620) |
| vactory_decoupled | `modules/vactory_decoupled/src/Controller/DuplicateParagraphController.php:59` | `createDuplicate()` (ou `replicate.replicator`) + `addTranslation()` + insertion `target_id`/`target_revision_id` (l.78, l.86) — route `/api/paragraph/duplicate` |
| vactory_decoupled | `modules/vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/EditLiveModeParagraphTitle.php:7` | `Paragraph` dans un enhancer JSON:API |
| vactory_paragraph_clipboard | `modules/vactory_paragraph_clipboard/src/Controller/ClipboardController.php:82` | `createDuplicate()` puis `field_vactory_paragraphs->appendItem()` (l.85) |
| vactory_content_collector | `modules/vactory_content_collector/vactory_content_collector.module:85` | `Paragraph::load()` + champ `paragraph_need_review` |
| vactory_page_import | `modules/vactory_page/modules/vactory_page_import/src/Form/Import.php:189` | `Paragraph::create()` + `getLatestRevisionId()` (l.193) — 8 occurrences dans le fichier (import Excel/JSON de pages) |
| vactory_page_import | `modules/vactory_page/modules/vactory_page_import/src/Services/PageImportService.php:14` | `use Paragraph` (import) |
| vactory_page_import | `modules/vactory_page/modules/vactory_page_import/src/Services/PageExportService.php:8` | `use Paragraph` (export) |
| vactory_content_package | `modules/vactory_content_package/src/Services/ContentPackageManager.php:591` | `Paragraph::create()` + `getLatestRevisionId()` (l.597) ; exclut `parent_id/parent_type/parent_field_name` (ContentPackageManagerInterface.php:103) |
| vactory_dynamic_field_dummy | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_dummy/src/Services/GenerateDummyPageService.php:325` | `Paragraph::create()` |
| vactory_content_inline_edit | `modules/vactory_content_inline_edit/src/Controller/VactoryContentInlineEditController.php:7` | `use Paragraph` |
| vactory_anchor | `modules/vactory_anchor/src/AnchorMenuTrait.php:6` | `use Paragraph` |
| *.install (news, blog, event, job_ads, contact, academy, publication, mediatheque, router_default_pages) | `modules/vactory_news/vactory_news.install:66` | Contenu par défaut : `Paragraph::create()` + `getLatestRevisionId()` (l.84) — même motif dans les 9 fichiers .install |
| vactory_decoupled | `modules/vactory_decoupled/config/install/jsonapi_extras.jsonapi_resource_config.paragraph--vactory_component.yml:53` | Expose `parent_id`/`parent_type`/`parent_field_name` en JSON:API |
| vactory_field / vactory_paragraphs / types de contenu | `modules/vactory_field/config/install/field.storage.node.field_vactory_paragraphs.yml:7` | Config : 35 fichiers dépendent de `paragraphs` (storages `paragraph.*`, form displays widget `paragraphs`/`entity_reference_paragraphs`) |
| profil | `vactory_starter_kit.info.yml:116` | Installé par le profil |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/tests/Functional/JsonApiCollectionTest.php` — crée un paragraph (`createParagraph`, l.95) rattaché à un node via `target_revision_id` puis lit le JSON:API.
- `modules/vactory_decoupled/tests/Functional/DynamicFieldMediaTest.php` — paragraph `vactory_component` + médias, rendu JSON.
- `modules/vactory_decoupled/modules/vactory_decoupled_webform/tests/Functional/WebformParagraphTest.php`, `modules/vactory_jsonapi_cross_bundles/tests/Functional/VactoryCrossBundlesTest.php`, `modules/vactory_single_content_sync_extra/tests/Functional/DynamicFieldImportExportTest.php` — même helper `createParagraph()` (`modules/vactory_core/tests/src/Functional/VactoryExistingSiteBase.php:334`).
- Non couvert : surcharge de classe, `createDuplicate`/clipboard, édition live (révisions), `hook_paragraph_access`, import/export de pages, widgets.

## Tests à écrire
- **[Kernel] `modules/vactory_core/tests/src/Kernel/VactoryParagraphClassTest.php`** — activer paragraphs+vactory_core, `Paragraph::create(['type'=>…, 'field_vactory_title'=>'X'])` → `assertInstanceOf(VactoryParagraph::class)` et `getSummary() === 'X'`. Attrape un changement de signature de `getSummary()` / de la classe d'entité.
- **[ExistingSite] `modules/vactory_decoupled/tests/src/ExistingSite/DuplicateParagraphTest.php`** — node `vactory_page` avec 1 paragraph ; POST `/api/paragraph/duplicate` → node a 2 items, `target_revision_id` du nouvel item = `getRevisionId()` du clone, traductions présentes. Attrape un changement de `createDuplicate()`/révisions ERR.
- **[ExistingSite] `modules/vactory_decoupled/tests/src/ExistingSite/EditLiveModeTest.php`** — modification d'un onglet de `vactory_paragraph_multi_template` via `/api/edit_live_mode` → nouvelle révision du paragraph parent et du node ; requête `parent_id/parent_type` retrouve le paragraph. Attrape une modification des champs de base parent_*.
- **[ExistingSite] `modules/vactory_dynamic_field/tests/src/ExistingSite/ParagraphAccessTest.php`** — paragraph avec `field_roles=[editor]` sur page publique : anonyme ne voit pas le composant, editor le voit. Attrape une rupture de `hook_paragraph_access`.
- **[ExistingSite] `modules/vactory_page/modules/vactory_page_import/tests/src/ExistingSite/PageImportExportTest.php`** — export d'une page → réimport → mêmes bundles/ordre de paragraphs et `target_revision_id` valides.
- **[ExistingSite] `modules/vactory_paragraphs/tests/src/ExistingSite/ParagraphsWidgetFormTest.php`** — GET `/node/add/vactory_page` en admin : 200, champ `field_vactory_paragraphs` présent sans warning PHP (couvre les form_alter qui lisent la structure interne du widget).

## Notes
- `getParentEntity()` n'est **pas** utilisé ; le code passe par les champs de base `parent_id`/`parent_type` (requêtes d'entité, JSON:API).
- Motif répandu `target_revision_id => getStorage('paragraph')->getLatestRevisionId($id)` : dépend du stockage révisionnable de paragraph.
- `vactory_paragraphs.module:192` lit `$element['top']['paragraph_summary']['fields_info']['#summary']['content']` : structure interne du widget, fragile à toute MAJ.
- Le widget legacy `entity_reference_paragraphs` (hook l.172) et `paragraphs` (stable) coexistent en config (4 occurrences chacun) — à vérifier si le legacy est encore supporté par la version cible.
- Tests existants dans `tests/Functional/` (hors `tests/src/`), certains avec namespace non PSR-4 (`Drupal\vactory_decoupled\Functional`) — à vérifier qu'ils sont bien découverts par la CI.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.11`
- 2021-01-31 `1be86d53` montée `^1.11` → `^1.12` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` montée `^1.12` → `^1.14` — « Update: update not supported contrib modules. »
- 2023-02-10 `035dd9f3` patch ajouté « Core 9.5.0 causing broken-looking styling (Legacy widget, with Seven) » — « Fix: #42408 - Fix paragraph style bug on seven theme after apdating to Drupal core 9.5.3 versi… »
- 2023-09-25 `98e75165` montée `^1.14` → `^1.16` — « Fix: fix paragraphs composer patch (update paragraphs to 1.16 version) »
- 2023-09-25 `98e75165` patch retiré « Core 9.5.0 causing broken-looking styling (Legacy widget, with Seven) » — « Fix: fix paragraphs composer patch (update paragraphs to 1.16 version) »
