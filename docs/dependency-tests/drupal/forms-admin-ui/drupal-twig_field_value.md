# drupal/twig_field_value

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.2` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — aucun filtre `field_value`/`field_label`/`field_raw`/`field_target_entity` utilisé dans les templates du profil. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:114` | Installé |
| vactory_core | `vactory_core.info.yml:8` | Dépendance |

0 occurrence de `|field_value`, `|field_label`, `|field_raw`, `|field_target_entity` dans `modules/**/*.twig`.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun pour le profil. À vérifier : le thème `vactory` (hors de ce dépôt) peut utiliser ces filtres.

## Notes
- Candidat à la suppression des dépendances de vactory_core si le thème ne l'utilise pas (à vérifier).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.2.0`
- 2021-01-31 `1be86d53` montée `^1.2.0` → `^2.0` — « Update: Add v2.1.0 Factory release changes. »
