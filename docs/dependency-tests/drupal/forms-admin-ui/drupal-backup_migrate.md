# drupal/backup_migrate

| | |
|---|---|
| Contrainte composer.json | `^5.1` |
| Version lockée | `dev-5.1.x` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Moyen — branche dev lockée ; ses entités de config sont explicitement masquées de JSON:API par config. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:99` | Installé |
| vactory_decoupled | `config/install/jsonapi_extras.jsonapi_resource_config.backup_migrate_{destination,schedule,settings,source}--*.yml` (l.5-7) | Ressources JSON:API `disabled: true` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/JsonApiHiddenResourcesTest.php`** — en anonyme et en admin, GET `/jsonapi/backup_migrate_destination/backup_migrate_destination` (et schedule/settings/source, `ultimate_cron_job`) → 404. Attrape : renommage d'entity type/bundle après MAJ qui rendrait la `jsonapi_resource_config` orpheline et la ressource exposée.

## Notes
- Contrainte `^5.1` mais lock sur `dev-5.1.x` (pas de release stable figée) : à épingler.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^4.1`
- 2021-01-31 `1be86d53` montée `^4.1` → `^5.0@RC` — « Update: Add v2.1.0 Factory release changes. »
- 2022-06-09 `02645c51` montée `^5.0@RC` → `^5.1` — « Update: update not supported contrib modules. »
