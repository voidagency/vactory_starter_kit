# drupal/views_url_alias

| | |
|---|---|
| Contrainte composer.json | `^3.1` |
| Version lockée | `3.1.2` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — une relation/un filtre dans la vue admin `content` du profil ; si le plugin disparaît la vue admin `/admin/content` casse. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:190` | Installé |
| profil | `config/install/views.view.content.yml:7,475-477,634-640` | Relation `views_url_alias` + filtre `alias` sur table `views_url_alias` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_core/tests/src/ExistingSite/AdminContentViewTest.php`** — admin GET `/admin/content?alias=foo` → 200 sans « broken handler ». P3.

## Notes
- Le module maintient sa propre table : contenu existant à réindexer après MAJ (à vérifier selon release notes).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2024-07-11 `481e4181` ajout `^3.1` — « feat: #52383 - Filtre par Alias »
