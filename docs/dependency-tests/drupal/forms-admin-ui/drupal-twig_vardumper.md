# drupal/twig_vardumper

| | |
|---|---|
| Contrainte composer.json | `^3.1` |
| Version lockée | `3.1.1` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — outil dev. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_devtools | `vactory_devtools.info.yml:10,23` | Listes install/uninstall |
| vactory_core | `vactory_core.drush.inc:52` | Mode development |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun.

## Notes
- 2 `dump()` commentés dans des templates (`vactory_publication/templates/full.html.twig:67`). Candidat `require-dev`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.3`
- 2021-01-31 `1be86d53` montée `1.3` → `^2.2` — « Update: Add v2.1.0 Factory release changes. »
- 2023-02-10 `a56aaaf5` montée `^2.2` → `^3.1` — « Update: #42408 - Update unsupported modules versions »
