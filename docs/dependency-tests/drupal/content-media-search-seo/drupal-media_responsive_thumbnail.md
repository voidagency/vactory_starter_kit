# drupal/media_responsive_thumbnail

| | |
|---|---|
| Contrainte composer.json | `^1.5` |
| Version lockée | `1.5.0` |
| Patches | aucun |
| Statut | Indirect (formatter en config) |
| Risque à la MAJ | Moyen — formatter `media_responsive_thumbnail` dans 24 view displays ; renommage/schéma = install cassée et rendu image cassé. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_news, event, mediatheque, academy, forums… | `modules/vactory_news/config/install/core.entity_view_display.node.vactory_news.full.yml:60` | `type: media_responsive_thumbnail` (24 occurrences) |
| info.yml | `vactory_starter_kit.info.yml:93` | Profil ; vactory_banner:19, vactory_locator:19 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_news/tests/src/ExistingSite/NewsFullDisplayTest.php`** — node news avec image, rendu `full` → balise `<picture>`/`srcset` présente.

## Notes
Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.1`
- 2021-01-31 `1be86d53` épinglage exact `^1.1` → `1.2` — « Update: Add v2.1.0 Factory release changes. »
- 2023-03-27 `2bf15539` montée `1.2` → `^1.5` — « Update: #43521 - Media reponsibe thumbnail contrib module security update »
