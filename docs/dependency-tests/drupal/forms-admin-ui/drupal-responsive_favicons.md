# drupal/responsive_favicons

| | |
|---|---|
| Contrainte composer.json | `1.x-dev@dev` |
| Version lockée | `dev-1.x` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — balises head ; branche dev non figée. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:148` | Installé |

0 autre occurrence (pas de config livrée).

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun.

## Notes
- Contrainte `1.x-dev@dev` : à épingler (release 2.x/3.x disponibles sur d.o, à vérifier).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.5`
- 2021-01-31 `1be86d53` passage branche dev `^1.5` → `1.x-dev@dev` — « Update: Add v2.1.0 Factory release changes. »
- 2026-07-31 alerte sécurité #67434 (Nouveau) : scan OSV signale `drupal/responsive_favicons` vulnérable ; contrainte `1.x-dev@dev`
