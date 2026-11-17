# drupal/jsonapi_include

| | |
|---|---|
| Contrainte composer.json | `^1.7` |
| Version lockée | `1.7.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — on force le paramètre `jsonapi_include=1` et `use_include_query: 1` : tout changement du nom de paramètre ou du format aplati casse les « Exposed APIs ». |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_jsonapi_extras | `vactory_jsonapi_extras.info.yml:8` | dépendance |
| vactory_jsonapi_extras | `config/install/jsonapi_include.settings.yml` | `use_include_query: 1` |
| vactory_jsonapi_extras | `src/EventSubscriber/ResourceRequestSubscriber.php:88` | `$request->query->set('jsonapi_include', 1)` si l'API exposée l'active |
| vactory_jsonapi_extras | `src/EventSubscriber/ResourceRequestSubscriber.php:218` | retire `jsonapi_include` des query params (pagination/liens) |
| vactory_jsonapi_extras | `src/Form/ExposedApisForm.php:47` | option `is_jsonapi_include` si module présent |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_jsonapi_extras/tests/src/ExistingSite/ExposedApiIncludeTest.php`** — `exposed_apis` avec `is_jsonapi_include=TRUE` et `include=field_vactory_taxonomy_1` → réponse sans `included`, relation aplatie dans `data[0].field_vactory_taxonomy_1.name`. Sans le flag → format JSON:API standard. Attrape un changement du paramètre ou du format.

## Historique
- 2023-10-27 `86f27f7f` ajout ^1.7 — #47605
- 2023-12-01 PR #429 `2440a266` / #430 `03a38abf` « fix/jsonapi_include_config » (correctif config, pas de paquet)
- Aucun incident de version.
