# drupal/image_effects

| | |
|---|---|
| Contrainte composer.json | `^3.6` |
| Version lockée | `3.6.0` |
| Patches | aucun |
| Statut | Indirect (dépendance de module uniquement) |
| Risque à la MAJ | Faible — déclaré par `vactory_decoupled_image_styles` mais aucun effet `image_effects_*` en config (style `dark` = `image_desaturate` core). |
| Couverture actuelle | Aucune |
| Priorité test | — |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_image_styles | `modules/vactory_decoupled/modules/vactory_decoupled_image_styles/vactory_decoupled_image_styles.info.yml:7` | Dépendance déclarée |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- —

## Notes
0 occurrence de `image_effects_` en config/code. Dépendance probablement superflue (à vérifier sur les sites qui créent des styles en BO).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2024-04-16 `e711b98c` ajout `^3.6` — « feat: #50342 - Générer les images via Images Styles »
