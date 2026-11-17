# drupal/migrate_file

| | |
|---|---|
| Contrainte composer.json | `^2.1` |
| Version lockée | `2.1.2` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — 2 process plugins custom héritent de `FileImport` et appellent son constructeur à 6 arguments. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_migrate | `src/Plugin/migrate/process/MediaImport.php:13,132-169` | `@MigrateProcessPlugin("media_import")` `extends FileImport`, `parent::__construct(…, $stream_wrappers, $file_system, $download_plugin)`, `parent::transform()` |
| vactory_migrate | `src/Plugin/migrate/process/RemoteVideoImport.php:9,24-33` | `@MigrateProcessPlugin("remote_video_import")` `extends FileImport` |
| vactory_dynamic_import | `src/Service/DynamicImportHelpers.php:360` | Process généré `plugin: file_import` |
| vactory_migrate, vactory_migrate_plugin | `vactory_migrate.info.yml:10` ; `vactory_migrate_plugin.info.yml:8` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_migrate/tests/src/Kernel/MediaImportProcessTest.php`** — modules `file`, `media`, `image`, `migrate`, `migrate_file`, `vactory_migrate` ; créer un type de média image ; `plugin.manager.migrate.process->createInstance('media_import', [...])` puis `transform('public://fixture.png', …)` avec fichier local → assert un média créé référencant le fichier. Idem `file_import` seul. Attrape : changement du constructeur/`create()` de `FileImport`.

## Notes
- `MediaImport::__construct` recopie la signature parente : toute évolution amont casse à l'instanciation (erreur fatale à l'import, pas à l'install).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-06-19 `3f46f492` ajout `^2.1` — « Add: #45165 - Add vactory migrate plugin custom module & Add migrate file contrib module »
