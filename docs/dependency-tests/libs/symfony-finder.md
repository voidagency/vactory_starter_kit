# symfony/finder

| | |
|---|---|
| Contrainte composer.json | `~4.0\|~5.0\|~6.0` |
| Version lockée | `v6.4.27` |
| Patches | aucun |
| Statut | Utilisé (direct) + transitif (`drupal/core 10.5.7` → `^6.4`, `mglaman/phpstan-drupal`) |
| Risque à la MAJ | Faible pour l'API (Finder stable) ; Élevé pour la contrainte, qui exclut Symfony 7 donc Drupal 11. |
| Couverture actuelle | Aucune (pas de test appelant `getWidgetsList`/`getModalWidgetsList`) |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/WidgetsManager.php:14` | `use Symfony\Component\Finder\Finder` |
| vactory_dynamic_field | `…/WidgetsManager.php:101-110` (`getWidgetsList`) | `new Finder()`, `depth('== 1')->files()->name('settings.yml')->in($path)` ; `SplFileInfo::getRelativePath()`, `getRealPath()`, `getContents()`, `getPath()` |
| vactory_dynamic_field | `…/WidgetsManager.php:180-190` (`getModalWidgetsList`) | idem |
| vactory_dynamic_field_dummy | `modules/vactory_dynamic_field/modules/vactory_dynamic_field_dummy/src/Commands/DynamicFieldDummyCommands.php:9,65,75-78` | idem (commande drush) |
| scripts (hors runtime) | `scripts/githooks/lint/Blocks.php:7-8,12-21` | `Finder` + `SplFileInfo`, `ignoreUnreadableDirs/ignoreDotFiles/ignoreVCS/filter/name/in/hasResults` (hook pre-commit, `scripts/githooks/pre_commit.sh:18`) |

Appelants de `WidgetsManager` (service `vactory_dynamic_field.vactory_provider_manager`) : `ModalForm.php:173,752`, `StaticWidgetsListController.php:54`, `PendingContentDashboard.php:105`, `DynamicFieldScreenshot.php:83`, `DuplicateParagraphController.php:144`, `DynamicFieldConfigExport.php:58`, `WidgetTwigTemplateCheck.php:68`, etc.

## Tests existants qui l'exercent
- aucun directement. Les tests Functional DF (`InternalBlocksTest`, `DynamicFieldMediaTest`…) passent par `loadWidgetById()` qui n'utilise pas Finder.

## Tests à écrire
- **[Kernel] `modules/vactory_dynamic_field/tests/src/Kernel/WidgetsManagerDiscoveryTest.php`** — activer `vactory_dynamic_field` + un sous-module de widgets (ex. `vactory_dynamic_field_default`) → `getWidgetsList()` contient la plateforme et un widget connu (`vactory_dynamic_field_default:25` → clés `name`, `uuid`, `screenshot`) ; `getModalWidgetsList(['vactory_dynamic_field_default' => …])` filtre les autres. Attrape : changement de comportement `depth('== 1')`, `getRelativePath()`.

## Notes
- **Contrainte bloquante** : `~4.0|~5.0|~6.0` interdit Symfony 7, requis par Drupal 11. À élargir (`^6.4 || ^7`) ou supprimer (déjà fourni par `drupal/core`).
- `~4.0` / `~5.0` sont inatteignables avec drupal/core 10 (`^6.4`) — bornes mortes.

## Historique
- 2020-06-28 `1af659f7` ajout ~2.8|~3.0|~4.0
- 2023-07-18 `30ccde8d` montée → ~4.0|~5.0|~6.0 — #44188 (D10)
- *hors 3.x* 2025-12-08 `b00b4af1` ajout ~7.0 (D11) — `origin/update/d11`
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
