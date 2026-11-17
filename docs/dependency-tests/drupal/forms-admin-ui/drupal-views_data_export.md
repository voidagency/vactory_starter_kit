# drupal/views_data_export

| | |
|---|---|
| Contrainte composer.json | `^1.0.0` |
| Version lockée | `1.4.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — export CSV des rendez-vous et validation d'ID de plugin `data_export` dans l'UI d'import/export. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_appointment | `vactory_appointment.info.yml:10` ; `config/install/views.view.appointments_edit.yml:1162,1170` | Display `display_plugin: data_export`, style `data_export` |
| vactory_migrate_ui | `vactory_migrate_ui.info.yml:10` ; `src/Form/ViewsExportDataSettings.php:336` | Vérifie `$view->getDisplay()->getPluginId() === 'data_export'` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_appointment/tests/src/ExistingSite/AppointmentsCsvExportTest.php`** — créer 2 rendez-vous, admin GET du chemin du display `data_export` de `appointments_edit` → 200, `Content-Type: text/csv`, 1re ligne = en-têtes séparés par `;`, 2 lignes de données. Attrape : changement des plugins display/style, de csv_serialization.
- **[Kernel] `vactory_migrate_ui/tests/src/Kernel/DataExportPluginIdTest.php`** — `plugin.manager.views.display->hasDefinition('data_export')` → TRUE. Attrape : renommage d'ID (validation du formulaire toujours en échec sinon).

## Notes
- Contrainte `^1.0.0` : la 1.x est la seule branche D10 utilisée ; une MAJ vers une future 2.x n'est pas couverte par la contrainte.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0-beta3`
- 2021-01-31 `1be86d53` réécriture contrainte `^1.0.0-beta3` → `^1.0.0` — « Update: Add v2.1.0 Factory release changes. »
