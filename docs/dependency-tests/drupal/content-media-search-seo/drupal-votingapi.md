# drupal/votingapi

| | |
|---|---|
| Contrainte composer.json | `^3.0@beta` |
| Version lockée | `3.0.0-beta4` |
| Patches | aucun |
| Statut | Utilisé (via vactory_rate, sans dépendance déclarée) |
| Risque à la MAJ | Moyen — stockage `vote`/`vote_type` et `plugin.manager.votingapi.resultfunction` utilisés directement. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_rate | `modules/vactory_rate/src/RateVote.php:111` | `VoteStorageInterface::getUserVotes()` (et l.155), `getStorage('vote')->create()` (l.123) |
| vactory_rate | `modules/vactory_rate/vactory_rate.services.yml:4` | `@plugin.manager.votingapi.resultfunction` |
| vactory_rate | `modules/vactory_rate/src/Controller/VoteController.php:17` | `VoteResultFunctionManager` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Couvert par **[ExistingSite] `modules/vactory_rate/tests/src/ExistingSite/RateVoteApiTest.php`** (fiche drupal/rate) — ajouter l'assertion sur `getUserVotes()` après vote.

## Notes
Version beta. Aucune dépendance `votingapi` dans les info.yml custom (arrive via `rate`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `3.0-beta2` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` dé-épinglage `3.0-beta2` → `^3.0@beta` — « Update: update not supported contrib modules. »
