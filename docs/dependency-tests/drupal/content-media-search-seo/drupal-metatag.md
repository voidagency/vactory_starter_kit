# drupal/metatag

| | |
|---|---|
| Contrainte composer.json | `^2.2` |
| Version lockée | `2.2.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — toutes les métas des pages décuplées (champ calculé `internal_metatag`, enhancer JSON:API, recherche, locator) passent par `metatag.manager` et des fonctions procédurales du module. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `modules/vactory_decoupled/src/Plugin/Field/InternalNodeMetatagsFieldItemList.php:113` | `tagsFromEntity()`, `hook_metatags_alter` (via moduleHandler), `generateRawElements()` (l.125) |
| vactory_decoupled | `modules/vactory_decoupled/src/VactoryDecoupledHelper.php:50` | Storage `metatag_defaults`, `getGlobalMetatags()` (l.60), `getSpecialMetatags()` (l.69), `metatag_get_route_entity()` (l.77) |
| vactory_decoupled | `modules/vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/VactoryMetaTagEnhancer.php:84` | `metatag_get_default_tags()`, `tagsFromEntity()` (l.85), `generateRawElements()` (l.95) |
| vactory_decoupled | `modules/vactory_decoupled/vactory_decoupled.services.yml:87` | Injection `@metatag.manager` |
| vactory_decoupled_search | `modules/vactory_decoupled/modules/vactory_decoupled_search/src/Controller/SearchController.php:225` | `metatag_get_default_tags()` + `generateRawElements()` (l.227) |
| vactory_locator_decoupled | `modules/vactory_locator/modules/vactory_locator_decoupled/src/Controller/StoreLocator.php:224` | `metatag_get_view_tags()` (sous-module metatag_views) + `generateRawElements()` |
| vactory_extended_seo | `modules/vactory_extended_seo/src/Plugin/Field/ExtendedSeoFieldItemList.php:73` | `metatag.manager` |
| vactory_core | `modules/vactory_core/vactory_core.module:277` | `metatag_data_decode()` sur `field_vactory_meta_tags` |
| vactory_core | `modules/vactory_core/src/Form/OpenGraphMetaDefaultImage.php:48` | `metatag.token->tokenBrowser()` |
| vactory_field | `modules/vactory_field/config/install/field.storage.node.field_vactory_meta_tags.yml:10` | Champ `type: metatag` |
| profil | `config/install/metatag.metatag_defaults.global.yml` | 7 `metatag_defaults` (global, front, node, taxonomy_term, user, 403, 404) |
| info.yml | `vactory_starter_kit.info.yml:120` | Profil + `metatag_open_graph` (l.122), `metatag_views` (l.123), `metatag_twitter_cards` (l.124), `metatag_facebook` (l.121) |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/tests/Functional/MetaTagsTest.php` — `internal_metatag` présent en JSON:API d'un node `vactory_page`, chaque tag a `href` ou `content`, `canonical_url` = baseUrl/langcode/alias ; homepage canonical = baseUrl/langcode.
- Non couvert : surcharge par `field_vactory_meta_tags`, defaults par bundle (`node__vactory_page`), og:image, métas de `/api/search` et du locator, `hook_metatags_alter`.

## Tests à écrire
- **[ExistingSite] étendre `MetaTagsTest::testEntityOverride`** — node avec `field_vactory_meta_tags` = `{title: "Custom", description: "Desc"}` → `internal_metatag` contient `title=Custom` et `description=Desc`. Attrape un changement de sérialisation (`metatag_data_decode`, JSON vs serialize en 2.x).
- **[ExistingSite] `MetaTagsTest::testOpenGraph`** — node avec image → tag `og_image` avec URL absolue.
- **[ExistingSite] `SearchControllerTest::testMetatags`** — `/api/search?q=x` renvoie les métas par défaut (assert clé + `title`).
- **[Kernel] `modules/vactory_decoupled/tests/src/Kernel/VactoryDecoupledHelperTest.php`** — `metatagGetDefaultTags($node)` fusionne global + node + bundle dans le bon ordre de priorité.

## Notes
- Fonctions procédurales `metatag_get_default_tags()`, `metatag_get_route_entity()`, `metatag_data_decode()`, `metatag_get_view_tags()` : API non garanties stables entre mineures (à vérifier lesquelles sont dépréciées en 2.x).
- `vactory_locator_decoupled` utilise `metatag_get_view_tags()` (metatag_views) et `vactory_decoupled` injecte `metatag.manager` sans déclarer `metatag` dans leurs info.yml (dépendance implicite via vactory_field / profil).
- `MetaTagsTest` : namespace `Drupal\vactory_decoupled\Functional` et dossier `tests/Functional/` — à vérifier la découverte CI.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.13`
- 2021-01-31 `1be86d53` montée `^1.13` → `^1.14` — « Update: Add v2.1.0 Factory release changes. »
- 2026-09-17 `f337dbc2` montée `^1.14` → `^2.2` — « Update: upgrade profile dependencies and support Metatag 2 »
