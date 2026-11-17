# drupal/menu_manipulator

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.0.6` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — installé ; aucun appel à `menu_manipulator.menu_tree_manipulators` dans le code custom. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:188` | Installé |

0 occurrence de `menu_manipulator` hors profil. Les manipulateurs utilisés (`vactory_decoupled/src/Controller/MenusController.php:162,167`) sont ceux du core, surchargés par `vactory_core/src/VactoryCoreServiceProvider.php:28`.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun ; le module agit via ses propres hooks (filtrage langue) — à vérifier s'il est réellement configuré.

## Notes
- Fait doublon avec le patch `filterLanguage` de simplify_menu (à vérifier).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2024-02-29 `344842b4` ajout `^3.0` — « fix: simplify menu patch »
