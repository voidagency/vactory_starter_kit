# drupal/single_content_sync

| | |
|---|---|
| Contrainte composer.json | `^1.4` |
| Version lockée | `1.4.13` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — plugin `@SingleContentSyncFieldProcessor` qui étend la base du paquet et utilise `single_content_sync.exporter/importer` ; format d'export (`single_content_sync_media`) consommé à l'import. |
| Couverture actuelle | Partielle |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_single_content_sync_extra | `vactory_single_content_sync_extra.info.yml:7` | dépendance |
| vactory_single_content_sync_extra | `src/Plugin/SingleContentSyncFieldProcessor/WysiwygDynamicField.php:22-27` | plugin `vactory_component` pour `field_wysiwyg_dynamic`, `extends SingleContentSyncFieldProcessorPluginBase` |
| vactory_single_content_sync_extra | `…/WysiwygDynamicField.php:85-86,95,106` | services `single_content_sync.exporter` / `.importer`, `exportFieldValue()` / `importFieldValue()` |
| vactory_diff | `vactory_diff.info.yml:9` | dépendance ; `vactory_diff_config_client/src/Service/TodoListGeneratorService.php:466` (libellé de rapport) |

## Tests existants qui l'exercent
- `modules/vactory_single_content_sync_extra/tests/Functional/DynamicFieldImportExportTest.php:65` — export d'un DF avec média image : `exported_media` (uuid, entity_type, bundle).
- `…/DynamicFieldImportExportTest.php:153` — export d'un DF wysiwyg.
- Non couvert : **import** (`importFieldValue`, `processMediaImport`, `processWysiwygImport`).

## Tests à écrire
- **[ExistingSite] `vactory_single_content_sync_extra/tests/src/ExistingSite/DynamicFieldRoundTripTest.php`** — exporter un node avec DF (image + wysiwyg avec média embarqué) via `single_content_sync.exporter->doExportToArray()`, supprimer média/node, réimporter via `single_content_sync.importer->doImport($array)` → node recréé, `widget_data` décodé pointe vers un média existant de même uuid. Attrape une rupture de format ou de signature d'import.

## Historique
- 2025-10-30 `f0bfbbe6` ajout ^1.4 — #60894
- Aucun incident.
