# drupal/search_api

| | |
|---|---|
| Contrainte composer.json | `^1.18` |
| Version lockée | `1.41.0` |
| Patches | 1 — « Decode HTML in search API rendered items » (gist ismail-void, `search-api-rendered-item-decode-html.patch`) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — API de recherche JSON (`/api/search`) construit la requête via l'API `Index::query()`, 3 index + serveur DB en config, vues search_api, patch externe (gist) sur le processeur `rendered_item`. |
| Couverture actuelle | Partielle |
| Priorité test | P1 (confirmée par l'historique : patchs correctifs #54411, retiré/réajouté `138a633e`/`826865f2`) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_search | `modules/vactory_decoupled/modules/vactory_decoupled_search/src/Controller/SearchController.php:132` | `$index->query([limit, offset])`, `keys()` (l.138), `setLanguages()` (l.139), `sort('search_api_relevance')` (l.140), `execute()`, `getResultItems()`, `getFields()`, `getExcerpt()` (l.193) |
| vactory_frequent_searches | `modules/vactory_search/modules/vactory_frequent_searches/src/Plugin/Block/FrequentSearchesBlock.php:131` | `Index::load()` ; `Index::loadMultiple()` (FrequentSearchesAdminForm.php:790, FrequentSearchesDerivative.php:46) |
| vactory_jsonapi_extras | `modules/vactory_jsonapi_extras/src/Routing/Routes.php:113` | Routes dynamiques sur `search_api_index` + `jsonapi_search_api` `IndexResource` (l.8), param `entity:search_api_index` (l.142) |
| vactory_smart_search | `modules/vactory_search/modules/vactory_smart_search/src/Services/VactorySmartSearchManager.php:46` | Lecture/modif des settings de l'index |
| vactory_search | `modules/vactory_search/config/install/search_api.index.default_content_index.yml:50` | Index `default_content_index` : champ `rendered_item` (l.50), processors `content_access`, `highlight`, `html_filter`, `ignorecase`, `tokenizer`, `transliteration`, `search_api_exclude_entity_processor` (l.158) |
| vactory_search | `modules/vactory_search/config/install/search_api.server.default_server.yml:12` | `backend: search_api_db` |
| vactory_search / smart_search / faq | `modules/vactory_search/config/install/views.view.vactory_search.yml:17` | Vues `base_table: search_api_index_*` (3 vues) |
| vactory_faq / vactory_forums | `modules/vactory_faq/config/install/search_api.index.vactory_faq_index.yml` | Index FAQ et forum |
| vactory_decoupled | `modules/vactory_decoupled/config/install/jsonapi_extras.jsonapi_resource_config.search_api_index--search_api_index.yml` | Ressources JSON:API index/server/task |
| info.yml | `vactory_starter_kit.info.yml:117` | Profil (+ `search_api_db` l.118) ; vactory_search:8, vactory_faq:11, vactory_frequent_searches:5 |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_search/tests/Functional/SearchControllerTest.php` — reconfigure `default_content_index` (bundle vactory_page), indexe, puis `/api/search` : résultats, 0 résultat, `q` vide → 400, pagination, exclusion des non publiés. Couvre `query()/keys()/execute()` + backend DB + `content_access`.
- Non couvert : effet du patch (entités HTML décodées dans `rendered_item`/excerpt), `setLanguages` (multilingue), processor exclude_entity, vues search_api, routes `vactory_jsonapi_extras`.

## Tests à écrire
- **[ExistingSite] étendre `SearchControllerTest`** — node titre/corps contenant `L&#039;été &amp; co` → l'`excerpt` retourné contient `L'été & co` (pas d'entité). Attrape la perte/non-application du patch gist à la MAJ.
- **[ExistingSite] `SearchControllerTest::testLanguageFilter`** — node FR + traduction AR, recherche en `/ar/api/search` → seule la traduction AR.
- **[ExistingSite] `SearchControllerTest::testExcludeFromSearch`** — node avec `field_exclude_from_search=1` → absent des résultats (voir fiche search_api_exclude_entity).
- **[Kernel] `modules/vactory_search/tests/src/Kernel/DefaultIndexConfigTest.php`** — installer la config de vactory_search → `Index::load('default_content_index')` a tous ses processors instanciables (`getProcessors()` sans plugin manquant). Attrape un renommage de processor.

## Notes
- Patch hébergé sur un **gist personnel** : fragile (disponibilité, ré-application sur 1.4x). La contrainte `^1.18` est très large vs lock 1.41.0.
- `SearchControllerTest` est sous `tests/Functional/` (hors `tests/src/`) — à vérifier que la CI le découvre ; il se `markTestSkipped` si l'index manque (masque une install cassée).
- `SearchController.php:11,15` : imports commentés de `SearchApiQuery`/`Index`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.16`
- 2021-01-31 `1be86d53` montée `^1.16` → `^1.18` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch ajouté « Fix conflic with webprofiler: instance of EntityManagerWrapper given. » — « Update: Add v2.1.0 Factory release changes. »
- 2021-02-02 `3da4b0a7` patch retiré « Fix conflic with webprofiler: instance of EntityManagerWrapper given. » — « Update: Remove search_api contrib patch(Applied in 1.19 module version). »
- 2025-02-03 `07765dd6` patch ajouté « Exclude paragraphs and decode HTML in search API rendered items » — « feat:54411 - create patch for search api rendered item to exclude paragraphs, decode results »
- 2025-02-03 `d702d4e5` patch modifié (URL) « Exclude paragraphs and decode HTML in search API rendered items » — « feat:54411 - change url gist »
- 2025-02-04 `61a5887c` patch ajouté « Decode HTML in search API rendered items » — « feat:54411 - create patch to deocde html result »
- 2025-02-04 `61a5887c` patch retiré « Exclude paragraphs and decode HTML in search API rendered items » — « feat:54411 - create patch to deocde html result »
- 2025-02-06 `a8f42109` patch modifié (URL) « Decode HTML in search API rendered items » — « change URL Patch to voidagency »
- 2025-02-07 `31cbe867` patch modifié (URL) « Decode HTML in search API rendered items » — « Update composer.json patch link »
- 2025-12-08 `138a633e` patch retiré « Decode HTML in search API rendered items » — « fix: search api module »
- 2025-12-08 `826865f2` patch ajouté « Decode HTML in search API rendered items » — « update: search api module patch »
- 2025-12-08 `138a633e` patch retiré puis réajouté le même jour `138a633e` « fix: search api module » retire le patch #54411, `826865f2` le réajoute depuis un autre gist personnel (`ismail-void`)
