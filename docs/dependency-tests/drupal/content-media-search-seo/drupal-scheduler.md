# drupal/scheduler

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.1` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — `unpublish_on` écrit par code (annonces) + `third_party_settings.scheduler` dans ~45 fichiers de config node. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_announcements | `modules/vactory_announcements/vactory_announcements.module:165` | `$node->set('unpublish_on', $date_end)` (champ de base scheduler) |
| vactory_content_package | `modules/vactory_content_package/src/Services/ContentPackageManagerInterface.php:70` | Exclut `publish_on`/`unpublish_on` à l'export |
| ~25 modules de contenu | `modules/vactory_news/config/install/node.type.vactory_news.yml` | `third_party_settings.scheduler` + widgets `datetime_timestamp_no_default` en form displays |
| info.yml | `modules/vactory_news/vactory_news.info.yml:9` | Dépendance (aussi announcements, mediatheque, page, social_network) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_announcements/tests/src/ExistingSite/AnnouncementUnpublishOnTest.php`** — annonce avec terme `field_ad_display` (N jours) → `unpublish_on = created + N*86400` ; lancer `scheduler.manager->unpublish()` avec date passée → node dépublié.

## Notes
Plusieurs modules livrent de la config dépendant de `scheduler` sans le déclarer dans leur info.yml (ex. vactory_faq, vactory_event, vactory_glossary, vactory_academy). Le profil n'installe pas `scheduler` directement ; il arrive via `vactory_page` (`vactory_page.info.yml:11`). Activer un de ces modules sans vactory_page ⇒ `UnmetDependenciesException` (à vérifier). Une MAJ qui change le schéma des `third_party_settings.scheduler` casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.1.0`
- 2021-01-31 `1be86d53` montée `^1.1.0` → `^1.3` — « Update: Add v2.1.0 Factory release changes. »
- 2024-03-14 `84b15530` montée `^1.3` → `^2.0` — « Composer(deps): Bump drupal/scheduler from 1.5.0 to 2.0.1 »
