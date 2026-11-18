# drupal/block_class

| | |
|---|---|
| Contrainte composer.json | `3.0.0` |
| Version lockée | `3.0.0` |
| Patches | aucun |
| Statut | Indirect (dépendance de modules uniquement) |
| Risque à la MAJ | Faible — aucune config `third_party_settings.block_class` ni code ; seule l'activation est requise. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:100` | Installé par le profil |
| vactory_header / vactory_banner / vactory_footer / vactory_search_overlay | `modules/vactory_header/vactory_header.info.yml:8` | Dépendance déclarée (aussi vactory_banner.info.yml:8, vactory_footer.info.yml:8, vactory_search_overlay.info.yml:8) |

## Tests existants qui l'exercent
- aucun (sauf l'install du profil en CI)

## Tests à écrire
- — (le smoke `drush si` de la CI suffit)

## Notes
Version figée à `3.0.0` (pas de `^`). Aucune trace de classes CSS de bloc en config du profil : dépendance peut-être superflue (à vérifier côté sites).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.2`
- 2021-01-31 `1be86d53` montée `^1.2` → `^1.3.0` — « Update: Add v2.1.0 Factory release changes. »
- 2023-07-13 `ab59b8f6` montée `^1.3.0` → `^2.0` — « Fix: #44188 - Drupal 10 compatibility: Update addtoany, advagg, better_exposed_filters, block_… »
- 2025-05-26 `9cbd99aa` épinglage exact `^2.0` → `3.0.0` — « feat: upgraded drupal/block_class to 3.0.0, drupal/link_attributes to 2.1.1, drupal/csv_serial… »

## Suivi (2026-11-19)
- ⚠️ Figé en `3.0.0`, alors que la **4.0.1 est une release de sécurité** (passage 3 → 4). À traiter en priorité (#71222).
