# drupal/link_target

| | |
|---|---|
| Contrainte composer.json | `^1.2.0` |
| Version lockée | `1.4.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — installé mais aucun widget `link_target_field_widget` en config. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patchs correctifs « Drupal 9 readiness » et « Fix handling request link target widget » (#49460, erreur 500, `ca604782`) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:140` | Installé |

Les occurrences de `link_target` dans les templates (`vactory_header/templates/FullMegaMenuMacro.html.twig:28`, widgets DF `widgets/*/template.html.twig`) sont des variables Twig locales, sans lien avec le module. 0 widget `link_target_field_widget` dans `config/` et `modules/**/config`.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun.

## Notes
- Probablement inutile : candidat à la suppression (à vérifier sur les sites dérivés qui l'auraient configuré).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.2.0`
- 2021-01-31 `1be86d53` patch ajouté « Drupal 9 readiness » — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-11 `ae0e3a00` patch retiré « Drupal 9 readiness » — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2024-02-16 `ca604782` patch ajouté « Fix handling request link target widget » — « Fix: #49460 &&  #49035 - request link with target widget && missing bundle entity, entity type… »
- 2024-04-23 `23c6551b` patch retiré « Fix handling request link target widget » — « fix: remove link target widget patch »
