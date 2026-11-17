# voidagency/yaml_editor

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.0` (type `drupal-module`, nom machine `yaml_editor`) |
| Patches | aucun |
| Statut | Indirect (dépendance info.yml + attribut HTML) |
| Risque à la MAJ | Moyen — **doublon** avec `drupal/yaml_editor ^1.2` (lock 1.2.0) : même nom machine, collision d'install possible ; `vactory_dynamic_field` (installé par le profil) en dépend. |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — (P3 → P1 : patch D10 puis fork drupal → b-khouy → voidagency) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_field | `modules/vactory_dynamic_field/vactory_dynamic_field.info.yml:13` | dépendance `yaml_editor` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Form/DynamicFieldSettingsForm.php:80` | `'#attributes' => ['data-yaml-editor' => 'true']` (activation JS de l'éditeur) |
| vactory_migrate_ui | `modules/vactory_migrate/modules/vactory_migrate_ui/vactory_migrate_ui.info.yml:8` | dépendance `yaml_editor` |
| vactory_migrate_ui | `modules/vactory_migrate/modules/vactory_migrate_ui/src/Form/MigrationFormEdit.php:30` | `'#attributes' => ['data-yaml-editor' => 'true']` |

## Tests existants qui l'exercent
- aucun (implicitement : l'install du profil en CI échoue si `yaml_editor` est absent).

## Tests à écrire
- **[ExistingSite] `modules/vactory_dynamic_field/tests/src/ExistingSite/DynamicFieldSettingsYamlEditorTest.php`** — admin → page du `DynamicFieldSettingsForm` → textarea avec `data-yaml-editor="true"` et library `yaml_editor/yaml_editor` attachée (drupalSettings / `<script>`). Attrape : renommage de la library ou de l'attribut entre fork voidagency et drupal.org.

## Notes
- `composer.json` requiert **les deux** : `voidagency/yaml_editor ^2.0` et `drupal/yaml_editor ^1.2` (ajouté au commit `10bbf6f7`, 2024-02-21). Tous deux de type `drupal-module`, nom machine `yaml_editor` → avec les `installer-paths` standards du projet hôte (`modules/contrib/{$name}`) l'un écrase l'autre ; lequel gagne dépend de l'ordre d'install — **à vérifier** sur un projet réel. Contenu différent (`diff` : `js/yaml-editor.js`, `src/Form/ConfigForm.php`, `yaml_editor.module`, `yaml_editor.routing.yml`, `config/install/yaml_editor.config.yml`).
- Historique : `ab4042cd` (2023-07) a remplacé `drupal/yaml_editor ^1.1` par un fork pour D10 ; `drupal/yaml_editor` a été ré-ajouté ensuite. Un des deux est à supprimer.

## Historique
- 2023-07-12 `b182d404` patch ajouté `drupal/yaml_editor` « Drupal 10 compatibility » — #44188
- 2023-07-17 `ab4042cd` **fork** `drupal/yaml_editor` ^1.1 → `b-khouy/yaml_editor` ^2.0 — #44188
- 2024-01-29 `f1c27559` **fork** → `voidagency/yaml_editor` ^2.0
- 2024-02-21 `10bbf6f7` réajout `drupal/yaml_editor` ^1.2 (doublon actuel) — #49503
- *hors 3.x* 2025-12-04 `c88d1dd8` retrait `voidagency/yaml_editor` — `origin/update/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
