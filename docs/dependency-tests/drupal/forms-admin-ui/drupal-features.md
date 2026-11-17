# drupal/features

| | |
|---|---|
| Contrainte composer.json | `^3.11.0` |
| Version lockée | `3.14.0` |
| Patches | « Export config translation » (`features_export-config-translation-2869336-2.patch`, d.o) |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — workflow dev (export de config) ; pas d'API appelée au runtime. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:66` | Installé (features_ui commenté) |
| vactory_decoupled, vactory_social_auth | `vactory_decoupled.info.yml:18`, `vactory_social_auth.info.yml:10` | Dépendance |
| 23 modules | `*.features.yml` (ex. `vactory_decoupled_webform/vactory_decoupled_webform.features.yml`) | Marqueurs de package features |
| vactory_diff_config_client | `src/Service/FeatureDetectionService.php:271-272` | Lit l'existence de `<module>.features.yml` (fichier, pas l'API) |
| vactory_core, vactory_devtools | `vactory_core.drush.inc:47`, `vactory_devtools.info.yml:15,28` | `features_ui` en mode dev |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun test runtime pertinent. Vérification manuelle du patch à chaque MAJ (export de traductions de config).

## Notes
- Patch non appliqué dans le `vendor/` local (pas de `cweagons/composer-patches`) — à vérifier côté projet racine. Patch ancien (issue 2869336) : risque de non-application silencieuse.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^3.7.0`
- 2020-06-29 `22b235d7` patch ajouté « Export config translation » — « Add: Add profile patches. »
- 2021-01-31 `1be86d53` montée `^3.7.0` → `^3.11.0` — « Update: Add v2.1.0 Factory release changes. »
