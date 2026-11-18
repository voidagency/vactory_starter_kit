# drupal/coffee

| | |
|---|---|
| Contrainte composer.json | `1.x-dev@dev` |
| Version lockée | `dev-1.x` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — UI admin ; mais branche dev non figée. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « Could not load data » (`1be86d53`), contrainte `1.x-dev@dev` (`ae0e3a00`), signalé vulnérable #67434 (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:101` | Installé |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun dédié (smoke admin, cf. `drupal-admin_toolbar.md`).

## Notes
- 0 occurrence de `coffee` dans le code custom hors icône CSS `vactory_icon` (sans rapport). Contrainte `1.x-dev@dev` : à épingler sur une release.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0`
- 2021-01-31 `1be86d53` patch ajouté « Fix Coffee : Could not load data, please refresh the page. » — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-11 `ae0e3a00` passage branche dev `^1.0` → `1.x-dev@dev` — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2021-11-11 `ae0e3a00` patch retiré « Fix Coffee : Could not load data, please refresh the page. » — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2026-07-31 alerte sécurité #67434 (Nouveau) : scan OSV signale `drupal/coffee` vulnérable ; contrainte toujours `1.x-dev@dev`

## Suivi (2026-11-19)
- Contrat vérifié par `ProfileDependencyContractsTest` (PR #918, mergée).
