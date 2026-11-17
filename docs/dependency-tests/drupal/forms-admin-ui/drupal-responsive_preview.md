# drupal/responsive_preview

| | |
|---|---|
| Contrainte composer.json | `^2.1` |
| Version lockée | `2.1.1` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — UI admin (toolbar). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:142` | Installé |

0 autre occurrence.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun (smoke admin, cf. `drupal-admin_toolbar.md`).

## Notes
- Peu utile en mode découplé (prévisualise le thème Drupal, pas le front Next.js) — à vérifier.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0-beta1`
- 2021-01-31 `1be86d53` réécriture contrainte `^1.0.0-beta1` → `^1.0.0` — « Update: Add v2.1.0 Factory release changes. »
- 2023-07-13 `4b48e654` montée `^1.0.0` → `^2.1` — « Fix: #44188 - Drupal 10 compatibility: Update responsive_preview, twig_tweak, user_registratio… »
