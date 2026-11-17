# drupal/jsonapi_cross_bundles

| | |
|---|---|
| Contrainte composer.json | `^1.2` |
| Version lockée | `1.2.0` |
| Patches | « Fix jsonapi cross bundles » (gist ismail-void) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — empilement de décorateurs sur `jsonapi.field_resolver` (le paquet + le nôtre, priorité -1) et `instanceof CrossBundlesResourceType` ; patch maison. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Classes/services du paquet que nous étendons ou décorons
| Élément du paquet | Notre code | Nature |
|---|---|---|
| `jsonapi_cross_bundles.cross_bundle_field_resolver` (décore `jsonapi.field_resolver`) | `vactory_jsonapi_cross_bundles.services.yml:2-8` → `VactoryFieldResolver` (décore aussi `jsonapi.field_resolver`, `decoration_priority: -1`) | chaîne de décorateurs : notre classe `extends Drupal\jsonapi\Context\FieldResolver` (core) et reçoit l'inner du paquet |
| `jsonapi.include_resolver` (core) | `vactory_jsonapi_cross_bundles.services.yml:16-22` → `VactoryIncludeResolver extends IncludeResolver` | décorateur |
| `Drupal\jsonapi_cross_bundles\ResourceType\CrossBundlesResourceType` | `vactory_decoupled/src/Element/JsonApiCrossBundlesElement.php:9,143` | `instanceof` |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_jsonapi_cross_bundles | `vactory_jsonapi_cross_bundles.info.yml:7` | dépendance |
| vactory_jsonapi_cross_bundles | `src/VactoryFieldResolver.php:19` | décorateur field resolver |
| vactory_jsonapi_cross_bundles | `src/VactoryIncludeResolver.php:13,33` | décorateur include resolver (`resolveInternalIncludePaths` surchargée) |
| vactory_decoupled | `src/Element/JsonApiCrossBundlesElement.php:16,143` | élément de formulaire DF `json_api_cross_bundles` |
| vactory_decoupled_cross_content | `vactory_decoupled_cross_content.info.yml:8` | dépend de `vactory_jsonapi_cross_bundles` |
| vactory_diff | `vactory_diff.info.yml:10` | dépend de `vactory_jsonapi_cross_bundles` |

## Tests existants qui l'exercent
- `modules/vactory_jsonapi_cross_bundles/tests/Functional/VactoryCrossBundlesTest.php:88` — paragraphe `json_api_cross_bundles` (news + publication) → sortie JSON:API de la page.

## Tests à écrire
- **[ExistingSite] `vactory_jsonapi_cross_bundles/tests/src/ExistingSite/CrossBundleCollectionFilterTest.php`** — GET `/api/node?filter[field_xxx]=…&include=…&sort=-created` (ressource cross-bundle) avec 1 news + 1 publication → 2 résultats, champ filtré présent dans les 2 bundles, `include` résolu. Attrape une rupture de la chaîne `CrossBundleFieldResolver` → `VactoryFieldResolver`.
- **[Kernel] `vactory_jsonapi_cross_bundles/tests/src/Kernel/DecoratorChainTest.php`** — `\Drupal::service('jsonapi.field_resolver')` est `VactoryFieldResolver` et son inner est `CrossBundleFieldResolver`. Attrape un changement de priorité/ID de service du paquet.

## Notes
- Le test existant est dans `tests/Functional/` avec le namespace `Drupal\vactory_jsonapi_cross_bundles\Tests\Functional` (non standard PSR-4 `Drupal\Tests\...`) ; découvert par la CI car phpunit est lancé sur tout le profil (`entrypoint.sh:207`), à vérifier pour l'autoload.

## Historique
- 2023-08-02 `74528ad0` ajout ^1.1 + patch gist « Fix jsonapi cross bundles »
- 2025-10-30 `2ab81ae3` montée ^1.1 → ^1.2 — #60771 « Fix / Cache Cross bundle » : erreur `getTypeName() on null`, cache partagé avec jsonapi écrasé (ressources génériques disparaissent jusqu'au vidage du cache)
- Redmine #60473 : test `VactoryCrossBundlesTest.php` ajouté (DF cross-bundles).
