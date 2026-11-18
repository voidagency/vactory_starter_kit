# drupal/csv_serialization

| | |
|---|---|
| Contrainte composer.json | `4.0.1` (épinglée) |
| Version lockée | `4.0.1` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — seulement format `csv` d'un display `data_export` ; dépendance dure de views_data_export. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif D10 (`ec1ae4e6`) puis épinglage 4.0.1 (`9cbd99aa`) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_appointment | `config/install/views.view.appointments_edit.yml:9,1170-1180` | Dépendance de config, `formats: csv` + `csv_settings` (délimiteur `;`, encodage utf8) |
| (composer) | `composer.lock` | Requis par `drupal/views_data_export` (`~1.4 \|\| ~2.0 \|\| ~3 \|\| ~4`) |

0 occurrence de `csv_serialization` / `Drupal\csv_serialization` dans le PHP custom.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Couvert par le test proposé dans `drupal-views_data_export.md` (assert du contenu CSV avec `;`).

## Notes
- Épinglée en dur (`4.0.1`) : les MAJ de views_data_export peuvent exiger une autre version ; à relâcher en `^4.0`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `2.0-beta3` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` dé-épinglage `2.0-beta3` → `^2.0` — « Update: update not supported contrib modules. »
- 2023-07-12 `ec1ae4e6` montée `^2.0` → `^3.0` — « Fix: #44188 - Drupal 10 compatibility: Update components, config_update, ctools, focal_point a… »
- 2023-07-12 `ec1ae4e6` patch ajouté « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Update components, config_update, ctools, focal_point a… »
- 2023-07-18 `e1d90707` patch retiré « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Remove unused csv_serialization contib module patch »
- 2025-05-26 `9cbd99aa` épinglage exact `^3.0` → `4.0.1` — « feat: upgraded drupal/block_class to 3.0.0, drupal/link_attributes to 2.1.1, drupal/csv_serial… »

## Suivi (2026-11-19)
- Contrat vérifié par `ProfileDependencyContractsTest` (PR #918, mergée). La version figée `4.0.1` est la dernière : contrainte à relâcher (#71222).
