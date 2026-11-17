# drupal/pathauto

| | |
|---|---|
| Contrainte composer.json | `^1.10` |
| Version lockée | `1.12.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — génération d'alias (URLs front décuplées), services `pathauto.generator` et `pathauto.alias_cleaner`, 19 patterns en config. |
| Couverture actuelle | Partielle |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_multivers | `modules/vactory_multivers/vactory_multivers.module:35` | `pathauto.generator->createEntityAlias($node, 'return')` |
| vactory_help_center | `modules/vactory_help_center/src/Services/HelpCenterHelper.php:114` | `AliasCleanerInterface::cleanString()` (service injecté, vactory_help_center.services.yml:4) |
| modules de contenu | `modules/vactory_news/config/install/pathauto.pattern.pathauto_content_news_pattern.yml` | 19 `pathauto.pattern.*` |
| profil | `config/install/pathauto.settings.yml` | Settings pathauto |
| info.yml | `vactory_starter_kit.info.yml:108` | Profil ; vactory_page.info.yml:7 |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/tests/Functional/MetaTagsTest.php` — lit l'alias d'un node créé (généré par pattern) pour le canonical : couverture indirecte.
- `modules/vactory_decoupled/modules/vactory_decoupled_router/tests/src/Functional/PathTranslatorTest.php` — crée des `PathAlias` à la main (n'exerce pas pathauto).

## Tests à écrire
- **[ExistingSite] `modules/vactory_news/tests/src/ExistingSite/NewsPathautoTest.php`** — node `vactory_news` « Mon Titre » → alias conforme au pattern news (ex. `/actualites/mon-titre`, à lire dans la config) ; changement de titre → alias régénéré.
- **[Kernel] `modules/vactory_help_center/tests/src/Kernel/HelpCenterHelperTest.php`** — hiérarchie de termes « Été & Co » → `cleanString` donne `ete-co`.

## Notes
Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.8`
- 2022-06-09 `02645c51` montée `1.8` → `^1.10` — « Update: update not supported contrib modules. »
