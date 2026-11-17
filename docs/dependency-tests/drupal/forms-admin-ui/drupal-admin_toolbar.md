# drupal/admin_toolbar

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.4.2` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — UI admin uniquement, aucune API appelée. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:68-72` | Installe `admin_toolbar`, `admin_toolbar_tools`, `admin_toolbar_links_access_filter` (les 2 derniers listés deux fois) |

## Tests existants qui l'exercent
- aucun (installé par `drush si` en CI).

## Tests à écrire
- Aucun dédié. Le smoke test admin proposé (`vactory_core/tests/src/ExistingSite/AdminSmokeTest.php` : GET `/admin`, `/admin/content`, `/admin/structure` en admin → 200) couvre la casse du rendu de la toolbar.

## Notes
- `admin_toolbar_links_access_filter` est marqué obsolète par admin_toolbar 3.x pour D10.3+ (à vérifier) ; doublons dans la liste `install` du profil.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.2`
- 2021-01-31 `1be86d53` montée `^2.2` → `^2.4` — « Update: Add v2.1.0 Factory release changes. »
- 2023-02-10 `a56aaaf5` montée `^2.4` → `^3.0` — « Update: #42408 - Update unsupported modules versions »
