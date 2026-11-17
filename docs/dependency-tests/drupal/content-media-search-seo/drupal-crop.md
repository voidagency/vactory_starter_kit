# drupal/crop

| | |
|---|---|
| Contrainte composer.json | `^2.1` |
| Version lockée | `2.3.0` |
| Patches | aucun |
| Statut | Indirect (config) |
| Risque à la MAJ | Faible/Moyen — base de focal_point ; config `third_party_settings.crop` sur un media type, ressource JSON:API `crop_type`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:90` | Installé par le profil |
| vactory_dynamic_field_video_help | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_video_help/config/install/media.type.remote_cloudinary_video.yml:7` | `third_party_settings.crop.image_field` |
| vactory_decoupled | `modules/vactory_decoupled/config/install/jsonapi_extras.jsonapi_resource_config.crop_type--crop_type.yml` | Ressource JSON:API `crop_type` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Couvert par le test proposé dans la fiche drupal/focal_point.

## Notes
Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.5.0`
- 2021-01-31 `1be86d53` montée `^1.5.0` → `^2.1` — « Update: Add v2.1.0 Factory release changes. »
