# drupal/entity_embed

| | |
|---|---|
| Contrainte composer.json | `^1.0` |
| Version lockée | `1.5.0` |
| Patches | aucun |
| Statut | Indirect (config embed button) |
| Risque à la MAJ | Faible — un bouton d'embed `vactory_embed_gallery` dans un sous-module optionnel. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_field_unitegallery | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_unitegallery/config/install/embed.button.vactory_embed_gallery.yml:7` | Embed button `type_id: entity` |
| vactory_dynamic_field_unitegallery | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_unitegallery/vactory_dynamic_field_unitegallery.info.yml:8` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- —

## Notes
Non installé par le profil. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0`
- — aucun changement contrainte inchangée sur `3.x`
