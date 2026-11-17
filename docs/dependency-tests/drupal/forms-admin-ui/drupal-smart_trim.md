# drupal/smart_trim

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.1.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — formatter `smart_trim` dans 19 displays (teaser/card) ; casse = résumé vide ou display invalide. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:152` | Installé |
| vactory_announcements | `vactory_announcements.info.yml:10` | Dépendance |
| vactory_news, vactory_blog, vactory_event, vactory_publication, vactory_testimonials, vactory_job_ads, vactory_announcements, vactory_tender | `config/install/core.entity_view_display.node.*.yml` (19 occurrences, ex. `vactory_news/config/install/core.entity_view_display.node.vactory_news.masonry.yml`) | `type: smart_trim` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/SmartTrimFormatterTest.php`** — formatter `smart_trim` avec `trim_length: 10` sur un body de 50 caractères → rendu ≤ 10 car. + suffixe. P3.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.1`
- 2021-01-31 `1be86d53` montée `^1.1` → `^1.3` — « Update: Add v2.1.0 Factory release changes. »
- 2023-02-10 `a56aaaf5` montée `^1.3` → `^2.0` — « Update: #42408 - Update unsupported modules versions »
