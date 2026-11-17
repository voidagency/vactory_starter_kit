# drupal/sitemap

| | |
|---|---|
| Contrainte composer.json | `^2.0@beta` |
| Version lockée | `2.0.0-beta4` |
| Patches | aucun |
| Statut | Indirect (install profil uniquement) |
| Risque à la MAJ | Faible — page `/sitemap` HTML activée par le profil, 0 code/config. |
| Couverture actuelle | Aucune |
| Priorité test | — |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:110` | Installé par le profil |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- — (smoke `drush si`)

## Notes
Les 26 occurrences de « sitemap » dans le code concernent xmlsitemap ou `vactory_image_sitemap` (module custom), pas ce paquet. Utilité en mode décuplé à vérifier.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.5.0`
- 2021-01-31 `1be86d53` montée `1.5.0` → `^2.0@beta` — « Update: Add v2.1.0 Factory release changes. »
