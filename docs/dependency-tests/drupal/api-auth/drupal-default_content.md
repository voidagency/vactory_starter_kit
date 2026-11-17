# drupal/default_content

| | |
|---|---|
| Contrainte composer.json | `^2.0@alpha` |
| Version lockée | `2.0.0-alpha2` |
| Patches | aucun |
| Statut | Indirect (dépendance + fichiers de contenu démo) |
| Risque à la MAJ | Moyen — 9 modules démo/welcome importent du contenu à l'install (`content/` : ~223 fichiers) ; format d'export alpha susceptible de changer. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_news_demo | `vactory_news_demo.info.yml:7` + `content/` (41 fichiers) | import à l'install |
| vactory_academy_demo | `vactory_academy_demo.info.yml:7` + `content/` (47) | idem |
| vactory_publication_demo | `vactory_publication_demo.info.yml:7` + `content/` (38) | idem |
| vactory_event_demo | `vactory_event_demo.info.yml:7` + `content/` (36) | idem |
| vactory_job_ads_demo | `vactory_job_ads_demo.info.yml:7` + `content/` (21) | idem |
| vactory_glossary_demo | `vactory_glossary_demo.info.yml:6` + `content/` (14) | idem |
| vactory_tender_demo | `vactory_tender_demo.info.yml:7` + `content/` (12) | idem |
| vactory_report_content | `vactory_report_content.info.yml:7` + `content/` (10) | idem |
| vactory_welcome | `vactory_welcome.info.yml:8` + `content/` (4) | idem |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_news/modules/vactory_news_demo/tests/src/ExistingSite/DemoContentImportTest.php`** — installer `vactory_news_demo` → nombre de nodes `vactory_news` ≥ nombre de fichiers `content/node/*.yml`. Attrape une incompatibilité de format d'import (alpha). Répliquable par module démo, un seul suffit comme sentinelle.

## Notes
- Les occurrences `default_content_index` (index Search API) sont sans rapport avec ce paquet.
- `vactory_footer.info.yml:10` : dépendance commentée.

## Historique
- 2022-05-26 `18d46f92` ajout ^2.0@alpha — #35444
- Aucun incident.
