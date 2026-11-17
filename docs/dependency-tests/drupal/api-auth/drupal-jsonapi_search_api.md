# drupal/jsonapi_search_api

| | |
|---|---|
| Contrainte composer.json | `1.0.0-rc3` (figée) |
| Version lockée | `1.0.0-rc3` |
| Patches | « Modifying a facet does not invalidate caches » (gist voidagency, issue 3176987) |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — on référence la classe `IndexResource` dans des routes dynamiques et la source de facettes `jsonapi_search_api_facets:*` ; version RC figée + patch cache. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (P2 → P1 : patch correctif ajouté/retiré 5×, épinglage 1.0.0-rc3 « version vs patch ») |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_jsonapi_extras | `src/Routing/Routes.php:8,137` | `_jsonapi_resource => IndexResource::class` pour les « Exposed APIs » de type index Search API |
| vactory_decoupled_facets | `vactory_decoupled_facets.info.yml:7` | dépendance `jsonapi_search_api_facets` |
| vactory_decoupled_facets | `config/install/facets.facet.content_type.yml:13,26` | `facet_source_id: jsonapi_search_api_facets:default_content_index`, widget `jsonapi_search_api` |
| vactory_decoupled_facets | `config/install/facets.facet.years_authored_on.yml:13,26` / `facets.facet.authored_on_months.yml:13,26` | idem |

## Tests existants qui l'exercent
- aucun (`SearchControllerTest` exerce `vactory_decoupled_search` / search_api, pas `jsonapi_search_api`).

## Tests à écrire
- **[ExistingSite] `vactory_jsonapi_extras/tests/src/ExistingSite/ExposedSearchIndexApiTest.php`** — créer une entité `exposed_apis` sur `default_content_index`, indexer 2 nodes, GET le chemin exposé → 200, `data` contient les 2 nodes, `meta.count`. Attrape un renommage/changement de constructeur de `IndexResource`.
- **[ExistingSite] `vactory_decoupled_facets/tests/src/ExistingSite/FacetsJsonApiTest.php`** — GET index avec facette `content_type` → `meta.facets` présent ; modifier la facette → réponse invalidée (vérifie le patch cache).

## Notes
- `vactory_jsonapi_extras.info.yml` ne déclare **pas** `jsonapi_search_api` (ni `jsonapi_resources`) alors que `Routes.php:8` importe `IndexResource` : dépendance implicite (à vérifier si le code est atteint sans le module).

## Historique
- 2022-11-22 `9246661d` ajout ^1.0@RC — #40239
- 2022-12-20 `90415c26` patch ajouté « Add each facet as a cache dependency » (d.o #3176987)
- 2023-01-26 `505b3787` patch retiré (« unused »)
- 2023-03-24 `502ff49b` patch ajouté « Invalide caches when facets changes » (hotfix PR #203 `0391e6ae`)
- 2023-04-08 `2802e861` patch retiré (« unapplyed patch »)
- 2023-04-10 `a12eae5c` patch réajouté « Modifying a facet does not invalidate caches » ; 2023-05-15 `7009653e` déplacé gist voidagency — #40654
- 2024-12-12 `76ae483f` **épinglage** ^1.0@RC → `1.0.0-rc3` « fix: jsonapi_search_api (version vs patch) »
- *hors 3.x* 2025-11-10 `bf4234f9` désépinglé ^1.0@RC (#61846) ; 2026-10-26 `a7b2d703` → `1.0.0-rc5`, patch rebasé en local — `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
