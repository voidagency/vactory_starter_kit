# drupal/search_api_autocomplete

| | |
|---|---|
| Contrainte composer.json | `^1.4` |
| Version lockée | `1.8.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible/Moyen — service helper appelé sur le formulaire de recherche + 2 configs `search_api_autocomplete.search.*`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_search_autosuggestions | `modules/vactory_search_autosuggestions/vactory_search_autosuggestions.module:20` | `search_api_autocomplete.helper->alterElement($form['search_api_fulltext'], $search)` |
| vactory_search_autosuggestions | `modules/vactory_search_autosuggestions/config/optional/search_api_autocomplete.search.vactory_search.yml` | Config autocomplete |
| vactory_faq | `modules/vactory_faq/config/install/search_api_autocomplete.search.vactory_faq.yml` | Config autocomplete FAQ |
| info.yml | `modules/vactory_faq/vactory_faq.info.yml:10` | Dépendance ; vactory_search_autosuggestions:7 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_search_autosuggestions/tests/src/ExistingSite/AutocompleteTest.php`** — GET route `search_api_autocomplete.autocomplete` pour `vactory_search` avec `q=Dru` → JSON contenant une suggestion.

## Notes
Non installé par le profil.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.3`
- 2021-01-31 `1be86d53` montée `^1.3` → `^1.4` — « Update: Add v2.1.0 Factory release changes. »
