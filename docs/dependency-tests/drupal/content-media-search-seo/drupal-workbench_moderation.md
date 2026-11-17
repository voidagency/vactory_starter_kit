# drupal/workbench_moderation

| | |
|---|---|
| Contrainte composer.json | `^1.6` |
| Version lockée | `1.7.0` |
| Patches | aucun |
| Statut | Indirect (une dépendance de config) |
| Risque à la MAJ | Moyen — pas installé par le profil (qui utilise `content_moderation`), mais `vactory_toolbox` livre une config qui en dépend. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_toolbox | `modules/vactory_toolbox/config/install/core.entity_form_display.block_content.vactory_toolbox.default.yml:13` | `dependencies.module: workbench_moderation` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_toolbox/tests/src/Kernel/ToolboxInstallTest.php`** — `enableModules(['vactory_toolbox'])` + `installConfig` → pas d'`UnmetDependenciesException`. Révèle la dépendance parasite.

## Notes
- `vactory_toolbox.info.yml` ne déclare pas `workbench_moderation` : son activation devrait échouer (config à dépendance non satisfaite) sauf si le module est déjà actif — à vérifier.
- `workbench_moderation` est incompatible avec `content_moderation` (installé par le profil, `vactory_starter_kit.info.yml:189`) — à vérifier ; candidat à suppression de composer.json après nettoyage de cette dépendance de config.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-12-21 `e0182126` ajout `^1.6` — « Add: #30704 - Test then add Workbench moderation contrib module. »
