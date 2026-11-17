# drupal/yaml_editor

| | |
|---|---|
| Contrainte composer.json | `^1.2` |
| Version lockée | `1.2.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible — simple attribut `data-yaml-editor` sur 2 textareas admin. |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — — historique : fork b-khouy → voidagency (`ab4042cd`, `f1c27559`) + patch D10 ; double déclaration drupal/ + voidagency/ actuelle (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_field | `vactory_dynamic_field.info.yml:13` ; `src/Form/DynamicFieldSettingsForm.php:80` | `'#attributes' => ['data-yaml-editor' => 'true']` |
| vactory_migrate_ui | `vactory_migrate_ui.info.yml:8` ; `src/Form/MigrationFormEdit.php:30` | idem |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun dédié (régression purement visuelle) ; au besoin un ExistingSite qui vérifie que la librairie `yaml_editor/yaml_editor` est attachée sur le formulaire `DynamicFieldSettingsForm`. P3.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-11-11 `ae0e3a00` ajout `^1.1` — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2023-07-12 `b182d404` patch ajouté « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Yaml editor contrib module D10 compatibility patch »
- 2023-07-17 `ab4042cd` retrait `^1.1` retiré — « Fix: #44188 - Drupal 10 compatibility: Fix yaml_editor contrib module D10 composer dependencies »
- 2023-07-17 `ab4042cd` patch retiré « Drupal 10 compatibility » — « Fix: #44188 - Drupal 10 compatibility: Fix yaml_editor contrib module D10 composer dependencies »
- 2023-07-17 `ab4042cd` fork / remplacement `drupal/yaml_editor ^1.1` + patch D10 → `b-khouy/yaml_editor ^2.0` — #44188
- 2024-01-29 `f1c27559` fork / remplacement `b-khouy/` → `voidagency/yaml_editor ^2.0`
- 2024-02-21 `10bbf6f7` réajout `^1.2` — « Update: #49503 - Update modules »
- 2024-02-21 `10bbf6f7` réajout en doublon `drupal/yaml_editor ^1.2` ajouté **sans retirer** `voidagency/yaml_editor ^2.0` (#49503) — les deux sont toujours dans `composer.json` (l.166, l.172) et `composer.lock` (1.2.0 et 2.0.0)
