# drupal/facets

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.6` |
| Patches | aucun |
| Statut | Indirect (config facettes) + import PHP mort |
| Risque à la MAJ | Faible/Moyen — 3 facettes sur `default_content_index` via `jsonapi_search_api_facets` ; module non installé par le profil. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_facets | `modules/vactory_decoupled/modules/vactory_decoupled_facets/vactory_decoupled_facets.info.yml:8` | Dépendance `facets:facets` (+ `jsonapi_search_api_facets`) |
| vactory_decoupled_facets | `modules/vactory_decoupled/modules/vactory_decoupled_facets/config/install/facets.facet.content_type.yml:13` | `facet_source_id: jsonapi_search_api_facets:default_content_index` (3 facettes) |
| vactory_locator_decoupled | `modules/vactory_locator/modules/vactory_locator_decoupled/src/Controller/StoreLocator.php:13` | `use Drupal\facets\Exception\Exception` — import inutilisé (les `catch` attrapent `\Exception`) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_decoupled/modules/vactory_decoupled_facets/tests/src/ExistingSite/FacetsJsonApiTest.php`** — GET `/jsonapi/index/default_content_index?filter[content_type]=vactory_page` → `meta.facets` contient `content_type` avec compte.

## Notes
Import mort dans StoreLocator.php:13 : sans effet runtime (pas d'autoload sur `use`), mais trompeur. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2022-11-22 `9246661d` ajout `^2.0` — « Add:#40239- Search with facets »
