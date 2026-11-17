# drupal/improved_multi_select

| | |
|---|---|
| Contrainte composer.json | `^1.7` |
| Version lockée | `1.8.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — seulement la config `improved_multi_select.settings` (`url`) modifiée à l'install de modules. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:87` ; `config/install/improved_multi_select.settings.yml:1-10` | Installé + réglages (`url`, `selectors`…) |
| vactory_notifications | `vactory_notifications.info.yml:8` ; `vactory_notifications.install:27-30` | Ajoute des URLs à `improved_multi_select.settings:url` |
| vactory_google_places, vactory_entity_canonical_access, vactory_points | `vactory_google_places.install:12-13,28-29`, `vactory_entity_canonical_access.install:12-13,28-29`, `vactory_points.install:12-13,28-29` | idem (si module actif) |
| vactory_cross_content | `vactory_cross_content.info.yml:7` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun dédié (UI JS admin). P3.

## Notes
- Les hooks d'install supposent la clé `url` (chaîne multi-ligne) : à revérifier si le schéma de config change.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.7`
- 2026-10-28 `fa887595` patch (branche non fusionnée) `origin/upgrade/d11` ajoute le patch « Drupal 11 compatibility » (issue 3451141)
