# drupal/config_update

| | |
|---|---|
| Contrainte composer.json | `^2.0@alpha` |
| Version lockée | `2.0.0-alpha3` |
| Patches | aucun |
| Statut | Indirect (dépendance runtime de features) |
| Risque à la MAJ | Faible — requis par `features.info.yml` (`config_update:config_update`), aucun appel direct. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| (features) | `vendor/drupal/features/features.info.yml` | `dependencies: - config_update:config_update` ; features est installé par le profil (`vactory_starter_kit.info.yml:66`) |

## Tests existants qui l'exercent
- aucun (installé par `drush si`).

## Tests à écrire
- Aucun dédié.

## Notes
- 0 occurrence dans le code custom hors `.po`. Pourrait être retiré de `require` (transitif via features) mais garde la contrainte alpha sous contrôle.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.7`
- 2023-07-12 `ec1ae4e6` montée `1.7` → `^2.0@alpha` — « Fix: #44188 - Drupal 10 compatibility: Update components, config_update, ctools, focal_point a… »
