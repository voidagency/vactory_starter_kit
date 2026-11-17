# drupal/ultimate_cron

| | |
|---|---|
| Contrainte composer.json | `^2.0@alpha` |
| Version lockée | `2.0.0-alpha7` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Moyen — 6 jobs cron livrés en config (rappels, sitemap images, import auto…) ; si le schéma de `ultimate_cron.job` change, crons silencieusement inactifs. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:115` | Installé |
| vactory_decoupled | `vactory_decoupled.info.yml:22` ; `config/install/jsonapi_extras.jsonapi_resource_config.ultimate_cron_job--ultimate_cron_job.yml:7` | Dépendance ; ressource JSON:API masquée |
| vactory_reminder, vactory_espace_prive, vactory_form_autosave, vactory_image_sitemap, vactory_frequent_searches, vactory_migrate_ui | `config/install/ultimate_cron.job.*_cron.yml` | Jobs (`module`, `callback: <module>_cron`) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_core/tests/src/ExistingSite/UltimateCronJobsTest.php`** — charger les `ultimate_cron_job` des modules actifs : `status` TRUE et callback `is_callable` ; lancer `$job->run()` du job `vactory_reminder_cron` sans exception. Attrape : changement de schéma/de launcher.
- Test JSON:API masqué : cf. `drupal-backup_migrate.md`.

## Notes
- Contrainte alpha (`^2.0@alpha`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.0.0-alpha4`
- 2021-01-31 `1be86d53` réécriture contrainte `^2.0.0-alpha4` → `^2.0.0-alpha5` — « Update: Add v2.1.0 Factory release changes. »
- 2024-02-21 `10bbf6f7` réécriture contrainte `^2.0.0-alpha5` → `^2.0@alpha` — « Update: #49503 - Update modules »
