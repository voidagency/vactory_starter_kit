# drupal/block_list_override

| | |
|---|---|
| Contrainte composer.json | `1.0.3` |
| Version lockée | `1.0.3` |
| Patches | aucun |
| Statut | Indirect (install profil uniquement) |
| Risque à la MAJ | Faible — activé par le profil, aucune config ni code. |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — — historique : fork b-khouy → voidagency (`07b911d9`, `f1c27559`) puis retour drupal épinglé 1.0.3 (`7759f845`) (avant : —) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:144` | Installé par le profil |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- — (smoke `drush si`)

## Notes
Version figée. Paquet absent de `vendor/drupal/` local (vendor non synchronisé — à vérifier). 0 autre occurrence de `block_list_override` dans le code.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-07-13 `cbe92e58` ajout `^1.0` — « Fix: #44188 - Drupal 10 compatibility: Add block_list_override contrib module as alternative o… »
- 2023-07-13 `cbe92e58` patch ajouté « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Add block_list_override contrib module as alternative o… »
- 2023-07-17 `07b911d9` retrait `^1.0` retiré — « Fix: #44188 - Drupal 10 compatibility: Fix block_list_override contrib module D10 composer dep… »
- 2023-07-17 `07b911d9` patch retiré « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Fix block_list_override contrib module D10 composer dep… »
- 2023-07-17 `07b911d9` fork / remplacement `drupal/block_list_override` + patch D10 → `b-khouy/block_list_override ^2.0` — #44188
- 2024-01-29 `f1c27559` fork / remplacement `b-khouy/` → `voidagency/block_list_override ^2.0` « use voidagency packages and correct patches »
- 2026-09-17 `7759f845` réajout `1.0.3` — « chore: update block_list_override dependency from voidagency to drupal and adjust version to 1… »
- 2026-09-17 `7759f845` remplacement + épinglage `voidagency/block_list_override ^2.0` → `drupal/block_list_override 1.0.3` (#62859, PR #880)
