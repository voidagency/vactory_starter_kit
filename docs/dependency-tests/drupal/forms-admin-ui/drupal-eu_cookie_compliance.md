# drupal/eu_cookie_compliance

| | |
|---|---|
| Contrainte composer.json | `^1.14` |
| Version lockée | `1.24.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — bannière côté rendu Drupal ; schéma de config volumineux (une MAJ peut invalider la config livrée). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:128` ; `config/install/eu_cookie_compliance.settings.yml` | Installé + réglages |

0 occurrence dans le code custom.

## Tests existants qui l'exercent
- aucun (hormis `drush si` qui valide l'import de la config).

## Tests à écrire
- Aucun dédié. P3.

## Notes
- En découplé, la bannière est normalement gérée par le front : utilité à vérifier.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.9`
- 2021-01-31 `1be86d53` montée `^1.9` → `^1.14` — « Update: Add v2.1.0 Factory release changes. »
