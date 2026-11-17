# drupal/rate

| | |
|---|---|
| Contrainte composer.json | `3.1.0` |
| Version lockée | `3.1.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — `vactory_rate` injecte `rate.bot_detector` et la config `rate.settings` ; API JSON de vote. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : 2 reverts de la montée 1.x → 3.x (`a176f28d`, `6915227d`), patchs D9/D10, épinglage 3.1.0 (`c176cf9d`, #48597) (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_rate | `modules/vactory_rate/vactory_rate.services.yml:4` | Service `vactory_rate.vote` ← `@rate.bot_detector` |
| vactory_rate | `modules/vactory_rate/src/RateVote.php:11` | `use Drupal\rate\Plugin\RateBotDetector` (docblock l.43 dit `Drupal\rate\RateBotDetector`) |
| vactory_rate | `modules/vactory_rate/src/Controller/VoteController.php:86` | Lecture `rate.settings` — routes `/api/rate/{entity_type_id}/{entity_id}/{vote_type_id}/{value}` |
| vactory_rate | `modules/vactory_rate/vactory_rate.module:11` | `hook_entity_base_field_info` |
| vactory_academy | `modules/vactory_academy/vactory_academy.install:39` | Édition de `rate.settings` à l'install (et l.178) |
| vactory_academy | `modules/vactory_academy/vactory_academy.module:316` | `preprocess_rate_widget` |
| info.yml | `modules/vactory_rate/vactory_rate.info.yml:6` | Dépendance (aussi vactory_academy:11, vactory_ux_notes:7) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_rate/tests/src/ExistingSite/RateVoteApiTest.php`** — node noté, GET `/api/rate/node/{nid}/fivestar/4` en authentifié → vote créé ; `/api/rate/results/node/{nid}` → moyenne 4 ; `/api/rate/undo/node/{nid}` → 0 vote. Attrape un déplacement de `RateBotDetector` ou un changement de schéma `rate.settings`.

## Notes
Version figée `3.1.0`. Classe `RateBotDetector` sous `src/Plugin/` dans 3.1.0 (vérifié dans vendor) : un déplacement de namespace en MAJ casse l'autowiring de `vactory_rate.vote`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0`
- 2020-06-29 `22b235d7` patch ajouté « Make rate available for all node display mode. » — « Add: Add profile patches. »
- 2021-01-31 `1be86d53` patch ajouté « Drupal 9 readiness » — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-11 `ae0e3a00` patch retiré « Drupal 9 readiness » — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2022-06-09 `02645c51` montée `^1.0` → `^3.0` — « Update: update not supported contrib modules. »
- 2022-06-09 `02645c51` patch retiré « Make rate available for all node display mode. » — « Update: update not supported contrib modules. »
- 2022-06-10 `a176f28d` downgrade `^3.0` → `^1.0` — « Update: Rollback rate update (Major version with struture changes). »
- 2022-06-10 `a176f28d` patch ajouté « Make rate available for all node display mode. » — « Update: Rollback rate update (Major version with struture changes). »
- 2022-06-10 `b5c281c8` montée `^1.0` → `^3.0` — « Update: Upgrade rate to latest version (for test). »
- 2022-06-10 `b5c281c8` patch retiré « Make rate available for all node display mode. » — « Update: Upgrade rate to latest version (for test). »
- 2022-06-10 `6915227d` downgrade `^3.0` → `^1.0` — « Update: Rollback rate update (Major version with struture changes needs work). »
- 2022-06-10 `6915227d` patch ajouté « Make rate available for all node display mode. » — « Update: Rollback rate update (Major version with struture changes needs work). »
- 2022-06-10 `a176f28d` revert « Rollback rate update (Major version with struture changes) »
- 2022-06-10 `6915227d` revert « Rollback rate update (Major version with struture changes needs work) »
- 2022-06-14 `c0bcd7f3` montée `^1.0` → `^3.0` — « Update: Upgrade rate contrib module. »
- 2022-06-14 `c0bcd7f3` patch retiré « Make rate available for all node display mode. » — « Update: Upgrade rate contrib module. »
- 2023-07-20 `26bcbc6f` patch ajouté « Entity queries must explicitly set whether the query should be access checked or not » — « Fix: #44188 - Drupal 10 compatibility: Patch rate contrib module to explicitly set entity quer… »
- 2024-01-04 `c176cf9d` épinglage exact `^3.0` → `3.1.0` — « refactor: rate contrib module version to 3.1.0 »
- 2024-01-04 `c176cf9d` patch retiré « Entity queries must explicitly set whether the query should be access checked or not » — « refactor: rate contrib module version to 3.1.0 »
