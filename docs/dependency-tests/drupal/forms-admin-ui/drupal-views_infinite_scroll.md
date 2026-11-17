# drupal/views_infinite_scroll

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.2` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — seulement le pager `infinite_scroll` dans 2 vues de modules optionnels. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_mur | `vactory_mur.info.yml:7` ; `config/install/views.view.vactory_mur.yml:51` | `pager: type: infinite_scroll` |
| vactory_social_network | `vactory_social_network.info.yml:9` ; `config/install/views.view.vactory_social_network.yml:67` | idem |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_mur/tests/src/Kernel/ViewsPagerPluginTest.php`** — `plugin.manager.views.pager->hasDefinition('infinite_scroll')`. P3.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-11-11 `ae0e3a00` ajout `1.8.0` — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2022-06-09 `02645c51` montée `1.8.0` → `^1.9` — « Update: update not supported contrib modules. »
- 2022-06-09 `1fdfb044` downgrade `^1.9` → `^1.8` — « Update: update not supported contrib modules. »
- 2023-07-12 `3890cd62` montée `^1.8` → `^2.0` — « Fix: #44188 - Drupal 10 compatibility: Update webform, security_review, stage_file_proxy, chos… »

Note : le downgrade `1fdfb044` corrige `02645c51` dans la même PR #43 avant fusion (net : `1.8.0` → `^1.8`) ; pas de régression publiée → priorité inchangée.
