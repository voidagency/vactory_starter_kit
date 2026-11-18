# drupal/lang_dropdown

| | |
|---|---|
| Contrainte composer.json | `^2.0@beta` |
| Version lockée | `2.1.0` |
| Patches | aucun |
| Statut | Utilisé (form_alter) |
| Risque à la MAJ | Faible — `vactory_core` réécrit l'`#action` du formulaire `lang_dropdown_form`. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « Fix TypeError: array_keys() » (`4733f4b0`) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_core | `modules/vactory_core/vactory_core.module:262` | `hook_form_alter` sur `lang_dropdown_form` (`#action` filtré par Xss) |
| profil | `vactory_starter_kit.info.yml:95` | Installé par le profil |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- — (usage front Drupal classique ; en mode décuplé probablement inutile)

## Notes
Contrainte `@beta` alors que 2.1.0 stable est lockée.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.0.0-beta3`
- 2021-01-31 `1be86d53` réécriture contrainte `^2.0.0-beta3` → `^2.0@beta` — « Update: Add v2.1.0 Factory release changes. »
- 2023-04-25 `4733f4b0` patch ajouté « Fix TypeError: array_keys(): Argument #1 ($array) must be of type array » — « Fix: Language dropdown errors on V8 »
- 2023-07-10 `bfdc43c2` patch retiré « Fix TypeError: array_keys(): Argument #1 ($array) must be of type array » — « Update: Remove lang_dropdown unused patch »

## Suivi (2026-11-19)
- Contrat vérifié par `ProfileDependencyContractsTest` (PR #918, mergée).
