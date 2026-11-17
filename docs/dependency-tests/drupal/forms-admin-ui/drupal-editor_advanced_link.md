# drupal/editor_advanced_link

| | |
|---|---|
| Contrainte composer.json | `2.3.4` (épinglée) |
| Version lockée | `2.3.4` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — plugin CKEditor5 référencé en config ; une MAJ qui renomme le plugin casse l'enregistrement des formats texte. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:98` | Installé |
| profil | `config/install/editor.editor.basic_html.yml:61`, `config/install/editor.editor.full_html.yml:61` | `editor_advanced_link_link` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_core/tests/src/ExistingSite/EditorConfigValidTest.php`** — pour `basic_html` et `full_html`, `Editor::load()->getTypedData()->validate()` → 0 violation (plugins CKEditor5 `editor_advanced_link_link`, `linkit_extension` existants). Mutualise linkit. P3.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.8`
- 2023-07-13 `ab59b8f6` montée `^1.8` → `^2.2` — « Fix: #44188 - Drupal 10 compatibility: Update addtoany, advagg, better_exposed_filters, block_… »
- 2026-09-17 `63d2b3f6` épinglage exact `^2.2` → `2.3.4` — « refactor: update editor advanced link version to 2.3.4 »
