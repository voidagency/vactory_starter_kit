# drupal/merge_translations

| | |
|---|---|
| Contrainte composer.json | `^2.0.1` |
| Version lockée | `2.0.1` |
| Patches | aucun |
| Statut | Indirect (install profil uniquement) |
| Risque à la MAJ | Faible — outil admin activé par le profil, 0 code/config. |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — — historique : fork b-khouy → voidagency (`8a9ab9ce`, `f1c27559`) + patch D10, retour drupal en 2026-09 (`f337dbc2`) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:143` | Installé par le profil |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- — (smoke `drush si`)

## Notes
Paquet absent de `vendor/drupal/` local (vendor non synchronisé — à vérifier).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.1`
- 2023-07-13 `1cbde7df` patch ajouté « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Patch merge_translations contrib module »
- 2023-07-17 `8a9ab9ce` retrait `^1.1` retiré — « Fix: #44188 - Drupal 10 compatibility: Fix merge_translations contrib module D10 composer depe… »
- 2023-07-17 `8a9ab9ce` patch retiré « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Fix merge_translations contrib module D10 composer depe… »
- 2023-07-17 `8a9ab9ce` fork / remplacement `drupal/merge_translations ^1.1` + patch D10 → `b-khouy/merge_translations ^2.0` — #44188
- 2024-01-29 `f1c27559` fork / remplacement `b-khouy/` → `voidagency/merge_translations ^2.0`
- 2026-09-17 `f337dbc2` réajout `^2.0.1` — « Update: upgrade profile dependencies and support Metatag 2 »
- 2026-09-17 `f337dbc2` remplacement `voidagency/merge_translations` → `drupal/merge_translations ^2.0.1` (#62859)
