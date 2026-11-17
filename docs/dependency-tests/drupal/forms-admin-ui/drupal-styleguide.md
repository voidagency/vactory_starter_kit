# drupal/styleguide

| | |
|---|---|
| Contrainte composer.json | `^2.0@beta` |
| Version lockée | `2.1.1` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — outil dev activé seulement par `vactory_devtools`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_devtools | `vactory_devtools.info.yml:19,32` ; `vactory_devtools.install:13-27` | Liste `install:`/`uninstall:` lue par `hook_install` qui installe les modules |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun.

## Notes
- Candidat au passage en `require-dev`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0-alpha3`
- 2021-01-31 `1be86d53` montée `^1.0.0-alpha3` → `^2.0@beta` — « Update: Add v2.1.0 Factory release changes. »
