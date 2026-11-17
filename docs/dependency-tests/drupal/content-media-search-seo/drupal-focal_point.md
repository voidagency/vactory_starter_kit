# drupal/focal_point

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.1.0` |
| Patches | aucun |
| Statut | Indirect (config : effets d'image + widget) |
| Risque à la MAJ | Moyen — effet `focal_point_scale_and_crop` dans les styles d'image du profil (`config/install`) et widget `image_focal_point` du média image. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `config/install/image.style.vactory_one_col_x1.yml:11` | Effet `focal_point_scale_and_crop` (7 styles du profil : one/two/three cols x1/x2, opengraph) |
| vactory_media_image_focal_point | `modules/vactory_media_image_focal_point/config/install/core.entity_form_display.media.image.default.yml:29` | Widget `image_focal_point` |
| vactory_testimonials / sliders / banner / locator | `modules/vactory_testimonials/config/install/image.style.vactory_testimonials_1.yml` | Styles d'image avec effet focal point |
| info.yml | `vactory_starter_kit.info.yml:92` | Profil ; vactory_banner:13, vactory_locator:13, vactory_dynamic_field_sliders:7, vactory_media_image_focal_point:5 |

## Tests existants qui l'exercent
- aucun (DynamicFieldMediaTest utilise des médias image mais n'asserte pas de dérivé)

## Tests à écrire
- **[ExistingSite] `modules/vactory_media_image_focal_point/tests/src/ExistingSite/FocalPointImageStyleTest.php`** — média image avec focal point (crop `focal_point` à x,y) → `ImageStyle::load('vactory_one_col_x1')->createDerivative()` réussit et produit les dimensions attendues.

## Notes
Les styles d'image sont dans `config/install` du **profil** : un changement d'id/schéma d'effet casse `drush si`. Dépend de `drupal/crop`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.4`
- 2021-01-31 `1be86d53` patch ajouté « Add the focal point to image. » — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` montée `1.4` → `^1.5` — « Update: update not supported contrib modules. »
- 2022-06-09 `8f6bb40b` patch retiré « Add the focal point to image. » — « Update: Remove unused focal_point patch. »
- 2023-07-12 `ec1ae4e6` montée `^1.5` → `^2.0` — « Fix: #44188 - Drupal 10 compatibility: Update components, config_update, ctools, focal_point a… »
