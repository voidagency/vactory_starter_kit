# drupal/theme_switcher

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.1` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — formulaire d'entité étendu, service négociateur appelé par son id, entity type référencé par un champ ; le module custom définit une fonction dans l'espace de noms `theme_switcher_`. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « Use AND instead of OR » (`7b6c83eb`, #44209) (avant : P2) |

## Où on l'utilise
Installé via `vactory_decoupled` → `vactory_decoupled_theme_switcher` (`vactory_decoupled.info.yml:20`).

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_theme_switcher | `src/Form/ThemeSwitcherRuleForm.php:7,12` | `extends Drupal\theme_switcher\Form\ThemeSwitcherRuleForm` |
| vactory_decoupled_theme_switcher | `vactory_decoupled_theme_switcher.module:22-27` | `hook_entity_type_alter` sur `theme_switcher_rule` (forms add/edit) |
| vactory_decoupled_theme_switcher | `vactory_decoupled_theme_switcher.module:34,65-71` | `hook_decoupled_extra_field_value_alter` : service `theme.negotiator.theme_switcher_negotiator`, tag cache `theme_switcher` |
| vactory_decoupled_theme_switcher | `vactory_decoupled_theme_switcher.module:97,117` | Alter de `theme_switcher_admin_overview_form` ; définit `theme_switcher_conditions_description()` |
| vactory_multivers | `vactory_multivers.info.yml:20` ; `config/install/field.storage.taxonomy_term.field_multivers_theme.yml:6,12` ; `vactory_multivers.module:152` | Champ entity_reference vers `theme_switcher_rule` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_decoupled_theme_switcher/tests/src/ExistingSite/ThemeSwitcherExtraFieldTest.php`** — créer une `theme_switcher_rule` ciblant un node (condition chemin) avec un thème X, appeler le router JSON:API/extra field du node → valeur = X ; cache tag `theme_switcher` présent. Attrape : renommage du négociateur, changement des conditions.
- **[Kernel] `vactory_decoupled_theme_switcher/tests/src/Kernel/RuleFormOverrideTest.php`** — `entity_type.manager->getDefinition('theme_switcher_rule')->getFormClass('edit')` = classe custom, et la classe parente existe.

## Notes
- `theme_switcher_conditions_description()` déclarée par un module tiers dans le préfixe de `theme_switcher` : collision fatale (« Cannot redeclare ») si une version amont ajoute cette fonction.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-05-15 `c112e3dc` ajout `^2.0` — « feat(multi-theme): add theme_switcher to composer.json »
- 2023-05-24 `7b6c83eb` patch ajouté « Use AND instead of OR for theme switcher conditions logic » — « Update: #44209 - Make extra fields computed field cacheable & use AND operator instead of OR f… »
- 2023-07-10 `6918e1b8` patch retiré « Use AND instead of OR for theme switcher conditions logic » — « Update: Remove decoupled theme switcher patch after contrib module update 2.0.1 & Set theme sw… »
