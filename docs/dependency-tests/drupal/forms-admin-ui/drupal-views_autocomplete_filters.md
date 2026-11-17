# drupal/views_autocomplete_filters

| | |
|---|---|
| Contrainte composer.json | `^1.3` |
| Version lockée | `1.7.0` |
| Patches | « D10: jquery once is removed » (`jquery_once-3345731-2.patch`, d.o 2023-03-03) |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — un filtre Views custom hérite de son plugin ; le patch JS cible une version antérieure. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « D10: jquery once is removed » (`614b9330`), toujours appliqué (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_google_places | `src/Plugin/views/filter/VactoryGooglePlacesString.php:6,15,17` | `@ViewsFilter("vactory_google_places")` `extends ViewsAutocompleteFiltersString` |
| vactory_locator | `config/install/views.view.vactory_locator.yml:45,1770,1797,5219` | `plugin_id: views_autocomplete_filters_string`, `autocomplete_filter: 1`, filtre `vactory_google_places` |
| vactory_smart_search | `vactory_smart_search.info.yml:8` ; `config/install/views.view.vactory_smart_search.yml` | Dépendance + filtre autocomplete |
| vactory_faq_prive | `vactory_faq_prive.info.yml:14` ; `config/install/views.view.vactory_faq_prive.yml` | idem |
| vactory_locator | `vactory_locator.info.yml:34` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_google_places/tests/src/Kernel/GooglePlacesFilterTest.php`** — modules `views`, `views_autocomplete_filters`, `vactory_google_places` ; `plugin.manager.views.filter->createInstance('vactory_google_places')` → instance de `ViewsAutocompleteFiltersString`. Attrape : renommage/finalisation de la classe parente.
- **[ExistingSite] `vactory_smart_search/tests/src/ExistingSite/AutocompleteRouteTest.php`** — GET de la route `viewsfilters.autocomplete` (`/views-autocomplete-filters/{view}/{display}/{filter}/{args}`) pour le filtre de `vactory_smart_search` avec `q=te` → JSON. P2.

## Notes
- `vactory_google_places.info.yml` a `dependencies:` vide alors qu'il étend une classe de ce module : fatale si installé seul.
- Le `vendor/` local n'applique pas les patches (pas de `cweagons/composer-patches`) : `js/views-autocomplete-filters-dependent.js:228` appelle encore `.once('autocomplete')` (supprimé en D10). Vérifier dans le projet racine que le patch s'applique sur 1.7.0, sinon il échoue silencieusement selon la config du plugin de patch.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `^1.3` — « Update: Add v2.1.0 Factory release changes. »
- 2023-07-19 `614b9330` patch ajouté « D10: jquery once is removed » — « Fix: #44188 - Drupal 10 compatibility: Patch views_autocomplete_filters contrib module (remove… »
