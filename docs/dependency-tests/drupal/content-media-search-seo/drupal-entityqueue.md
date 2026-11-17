# drupal/entityqueue

| | |
|---|---|
| Contrainte composer.json | `^1.2` |
| Version lockée | `1.7.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — `EntitySubqueue::load()` dans le générateur JSON:API des dynamic fields + 45 queues en config + filtres Views. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `modules/vactory_decoupled/src/JsonApiGenerator.php:144` | `EntitySubqueue::load($entity_queue)->get('items')` → filtres JSON:API `_subqueue` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Element/DynamicViews.php:241` | `EntityQueue::load()` + liste des queues (l.355) |
| vactory_core | `modules/vactory_core/src/Services/VactoryDevTools.php:126` | `EntitySubqueue::load()` (devtools) |
| modules de contenu | `modules/vactory_news/config/install/views.view.vactory_news.yml` | Config : 10 vues dépendent d'`entityqueue` (relation subqueue) ; 45 `entityqueue.entity_queue.*.yml` |
| info.yml | `vactory_starter_kit.info.yml:135` | Profil ; aussi vactory_governance.info.yml:7, vactory_academy.info.yml:10 |

## Tests existants qui l'exercent
- aucun (JsonApiCollectionTest ne passe pas d'entity queue)

## Tests à écrire
- **[ExistingSite] `modules/vactory_decoupled/tests/src/ExistingSite/JsonApiCollectionEntityQueueTest.php`** — subqueue avec 2 nodes ordonnés, dynamic field collection pointant sur la queue → la réponse JSON:API ne contient que ces 2 nodes. Attrape un changement du champ `items` de `EntitySubqueue`.

## Notes
Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0`
- 2021-01-31 `1be86d53` montée `^1.0` → `^1.2` — « Update: Add v2.1.0 Factory release changes. »
- 2024-10-14 `29a3fd9b` bug côté code #53776 « Bug ordre Entityqueue » corrigé dans `vactory_decoupled/src/JsonApiGenerator.php` (pas de changement de dépendance)
