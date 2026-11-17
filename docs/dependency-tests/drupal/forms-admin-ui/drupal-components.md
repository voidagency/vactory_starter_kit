# drupal/components

| | |
|---|---|
| Contrainte composer.json | `^3.0@beta` |
| Version lockée | `3.0.0-beta3` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible pour le profil — aucun namespace Twig `components:` déclaré dans les `.info.yml` des modules. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:102` | Installé |

0 clé `components:` dans `modules/**/*.info.yml`. Les includes `@vactory_locator/…`, `@vactory_appointment/…`, `@vactory_quiz/…`, `@vactory_paragraphs/…` utilisent les namespaces de modules natifs du core, pas ce module.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun pour le profil. À vérifier dans le thème `vactory` (hors dépôt) qui pourrait déclarer des namespaces.

## Notes
- Les comptes bruts (« components » 137 occurrences PHP) sont du bruit lexical (DF « components »), pas ce module.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.1.0`
- 2021-01-31 `1be86d53` montée `^1.1.0` → `^2.0` — « Update: Add v2.1.0 Factory release changes. »
- 2023-07-12 `ec1ae4e6` montée `^2.0` → `^3.0@beta` — « Fix: #44188 - Drupal 10 compatibility: Update components, config_update, ctools, focal_point a… »
