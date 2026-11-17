# drupal/imagemagick

| | |
|---|---|
| Contrainte composer.json | `^3.7` |
| Version lockée | `3.7.0` |
| Patches | aucun |
| Statut | Indirect (dépendance de module uniquement) |
| Risque à la MAJ | Faible — déclaré par `vactory_decoupled_image_styles`, aucune config de toolkit dans le repo. |
| Couverture actuelle | Aucune |
| Priorité test | — |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_image_styles | `modules/vactory_decoupled/modules/vactory_decoupled_image_styles/vactory_decoupled_image_styles.info.yml:8` | Dépendance déclarée |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- —

## Notes
Aucune config `system.image` / `imagemagick.settings` livrée : le toolkit par défaut reste GD sauf réglage site (à vérifier). Requiert le binaire ImageMagick sur l'image CI/prod.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2024-04-22 `677763cf` ajout `^3.7` — « feat: #50342 - install imagemagick module »
