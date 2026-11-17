# drupal/file_entity

| | |
|---|---|
| Contrainte composer.json | `^2.0-beta8` |
| Version lockée | `2.0.0-rc5` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — `vactory_cloudinary` étend le formulaire et l'interface du module, route de téléchargement contrôlée par l'accès fichiers privés, formatters en config. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : 2 patchs correctifs (#49035, #53096) dont un a provoqué la régression urgente #53706 (`22449f5b`) (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_cloudinary | `modules/vactory_cloudinary/src/Form/FileEditForm.php:13` | `extends Drupal\file_entity\Form\FileEditForm` (héritage) |
| vactory_cloudinary | `modules/vactory_cloudinary/vactory_cloudinary.module:35` | Signatures typées `FileEntityInterface` (aussi l.123) |
| vactory_private_files_decoupled | `modules/vactory_decoupled/modules/vactory_private_files_decoupled/vactory_private_files_decoupled.module:29` | Route `file_entity.file_download` dans la liste des routes contrôlées (accès fichiers privés) |
| vactory_dynamic_field_dummy | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_dummy/src/Services/GenerateDummyPageService.php:258` | `instanceof FileEntity` |
| profil | `config/install/file_entity.type.document.yml` | Type de fichier `document` |
| modules de contenu | `modules/vactory_press_release/config/install/core.entity_view_display.node.vactory_press_release.card.yml` | Formatter `file_download_link` (9 occurrences, 12 fichiers dépendants) |
| info.yml | `vactory_starter_kit.info.yml:91` | Profil ; vactory_locator.info.yml:12 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_decoupled/modules/vactory_private_files_decoupled/tests/src/ExistingSite/PrivateFileDownloadTest.php`** — fichier `private://` ; anonyme sur `file_entity.file_download` → 403, ayant droit → 200.
- **[Kernel] `modules/vactory_cloudinary/tests/src/Kernel/FileEditFormTest.php`** — construire `FileEditForm` custom pour un fichier → pas d'erreur (constructeur/`form()` parent compatibles).

## Notes
Version RC ; contrainte `^2.0-beta8`. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.0.0-beta7`
- 2021-01-31 `1be86d53` réécriture contrainte `^2.0.0-beta7` → `^2.0-beta8` — « Update: Add v2.1.0 Factory release changes. »
- 2024-02-16 `ca604782` patch ajouté « Fix missing bundle entity, entity type file_type, entity id undefined » — « Fix: #49460 &&  #49035 - request link with target widget && missing bundle entity, entity type… »
- 2024-02-16 `266a36f7` patch modifié (URL) « Fix missing bundle entity, entity type file_type, entity id undefined » — « Update: #49035 - MAJ patch link »
- 2024-08-07 `0965d660` patch ajouté « Fix the entity:file:undefined plugin does not exist » — « fix: #53096- The entity:file:undefined plugin does not exist »
- 2024-10-10 `22449f5b` patch retiré « Fix the entity:file:undefined plugin does not exist » — « fix: #53706- issue related to file entity »
- 2024-10-10 `22449f5b` régression causée par un patch #53706 (Urgent, « Jobs / Post Webform ») : « Le problème est lié au patch appliqué sur file_entity » (MR !15 ajouté pour #53096) → patch retiré
- 2026-09-17 `27d8f3c5` patch retiré « Fix missing bundle entity, entity type file_type, entity id undefined » — « chore: remove deprecated entry for drupal/file_entity from composer.json »

Note : seul cas du lot où un **patch correctif a lui-même causé une régression** : MR !15 (#53096) ajouté le 2024-08-07, cause du bug urgent #53706 (post Webform Jobs), retiré le 2024-10-10.
