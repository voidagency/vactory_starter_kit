# drupal/media_library_form_element

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.6` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — élément `#type => media_library` au cœur du widget des dynamic fields (image/file/vidéo) et de plusieurs formulaires. |
| Couverture actuelle | Partielle (sortie seulement) |
| Priorité test | P1 (confirmée par l'historique : patchs correctifs #37628, #53005) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Plugin/Field/FieldWidget/FormWidgetTrait.php:51` | `#type => media_library` pour les types image/file/remote_video/video |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Form/ModalForm.php:1033` | Lit `$element['widget']['media_library_update_widget']` |
| vactory_dynamic_field_video_help | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_video_help/src/Element/MediaVideoElement.php:47` | `#type => media_library` |
| vactory_locator | `modules/vactory_locator/src/Plugin/Block/VactoryLocatorBlock.php:78` | `#type => media_library` (4×), SettingsForm.php:62, VactoryLocatorBlockAgence.php:47 |
| vactory_video_ask / satisfaction / sondage / content_inline_edit | `modules/vactory_video_ask/src/Element/DynamicVideoAskElement.php:276` | `#type => media_library` (aussi SatisfactionFormElement.php:89) |
| info.yml | `modules/vactory_dynamic_field/vactory_dynamic_field.info.yml:11` | Dépendance ; vactory_locator.info.yml:18 |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/tests/Functional/DynamicFieldMediaTest.php` — valeurs stockées (id média) puis sortie JSON ; ne passe pas par le formulaire (élément non exercé).

## Tests à écrire
- **[ExistingSite] `modules/vactory_dynamic_field/tests/src/ExistingSite/DynamicFieldModalFormTest.php`** — admin ouvre le modal DF d'un widget avec champ image, soumet avec un id média existant → valeur `widget_data` contient l'id ; aucun warning sur `media_library_update_widget`. Attrape un changement de structure de l'élément.

## Notes
Structure interne (`media_library_update_widget`, `media_library_selection`) lue/écrite en dur (ModalForm.php:1033, GenerateDummyPageService.php:200).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `2.0.0` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch ajouté « Fixing multiple modals closing » — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` dé-épinglage `2.0.0` → `^2.0` — « Update: update not supported contrib modules. »
- 2022-08-08 `e005b839` patch retiré « Fixing multiple modals closing » — « Fix: #37628 - Fix media file type modal closing on DF form. »
- 2024-08-08 `ac773590` patch ajouté « Fix: explode(): Argument #2 ($string) must be of type string, array given » — « fix: #53005- Vidéo Ask / Bug sur DF »
- 2025-09-10 `4df457c4` patch retiré « Fix: explode(): Argument #2 ($string) must be of type string, array given » — « fix: delete media_library_form_element patch (applied in latest version) »
