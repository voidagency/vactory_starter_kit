# drupal/time_range

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.1` |
| Patches | aucun |
| Statut | Indirect (widget en config) |
| Risque à la MAJ | Faible — seul le widget `time_range` sur un champ `daterange` de `vactory_welcome`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_welcome | `modules/vactory_welcome/config/install/core.entity_form_display.taxonomy_term.welcome.default.yml:33` | Widget `type: time_range` |
| vactory_welcome | `modules/vactory_welcome/vactory_welcome.info.yml:7` | Dépendance `time_range:time_range` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- — (smoke install de vactory_welcome si le module est activé en CI ; non installé par le profil)

## Notes
Le storage `field_time_range_v` est un `daterange` core ; seul l'id de widget dépend du module.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2022-09-07 `10ce876c` ajout `^2.0` — « Update: Update vactory welcome use DF aproach »
