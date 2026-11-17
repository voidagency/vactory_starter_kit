# drupal/log_stdout

| | |
|---|---|
| Contrainte composer.json | `^1.5` |
| Version lockée | `1.5.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — logger passif ; casse = logs perdus en conteneur. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:177` | Installé |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/LoggersTest.php`** — module `log_stdout` activé : `\Drupal::logger("x")->error("y")` ne lève pas d'exception (le logger tagué est bien enregistré). P3.

## Notes
- 0 occurrence hors profil.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2024-03-14 `947a10a0` ajout `^1.5` — « fix: 49137 - add stdout_log to composer »
