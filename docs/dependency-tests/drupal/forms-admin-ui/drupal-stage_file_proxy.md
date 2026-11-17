# drupal/stage_file_proxy

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.1.4` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — outil dev. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_devtools | `vactory_devtools.info.yml:17,30` | Listes install/uninstall |
| vactory_core | `vactory_core.drush.inc:53` | Mode development |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun.

## Notes
- Candidat `require-dev`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0`
- 2023-07-12 `3890cd62` montée `^1.0` → `^2.0` — « Fix: #44188 - Drupal 10 compatibility: Update webform, security_review, stage_file_proxy, chos… »
- 2026-07-31 alerte sécurité #67434 (Nouveau) : scan OSV signale `drupal/stage_file_proxy` vulnérable
