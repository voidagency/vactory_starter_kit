# drupal/simplify_menu

| | |
|---|---|
| Contrainte composer.json | `^3.2` |
| Version lockée | `3.2.0` |
| Patches | « Add callable : filterLanguage » (gist ismail-void `add-filterLanguage-callable.patch`) |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — le service `simplify_menu.menu_items` est remplacé par une sous-classe custom via ServiceProvider (constructeur étendu) et la fonction Twig `simplify_menu()` est utilisée dans 11 templates. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patchs correctifs (#30682 `6f19c294`, toujours appliqué) et #53344 « Patch vs version » (`69891a70`) (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:127` | Installé |
| vactory_core | `vactory_core.info.yml:11` ; `src/VactoryCoreServiceProvider.php:21-25` | `getDefinition('simplify_menu.menu_items')->setClass(Drupal\vactory_core\MenuItems)->addArgument(...)` ×4 |
| vactory_core | `src/MenuItems.php:11,65-72,90,164-168` | `extends Drupal\simplify_menu\MenuItems`, override `simplifyLinks()` (protégée) et `getMenuTree()` |
| vactory_header | `templates/block--vactory-header{1..9}.html.twig` (ex. `block--vactory-header1.html.twig:45`) | `simplify_menu('main')` |
| vactory_espace_prive | `templates/block-espace-prive-user-menu.html.twig:1` | `simplify_menu('vactory-espace-prive-menu')` |
| vactory_toolbox | `templates/vactory-toolbox-block.html.twig:54` | `simplify_menu('toolbox')` |
| vactory_espace_prive, vactory_footer | `vactory_espace_prive.info.yml:8`, `vactory_footer.info.yml:13` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/SimplifyMenuOverrideTest.php`** — modules `system`, `user`, `menu_link_content`, `link`, `simplify_menu`, `vactory_core` (+ deps) ; créer 2 liens dans `main` (parent/enfant) ; `\Drupal::service('simplify_menu.menu_items')` instanceof `Drupal\vactory_core\MenuItems` ; `getMenuTree('main')` → clé `menu_tree` avec `submenu` pour l'enfant. Rendre `{{ simplify_menu('main') }}` via `twig->createTemplate` sans exception. Attrape : changement du constructeur parent (arguments ajoutés positionnellement), de `simplifyLinks()` ou du nom de fonction Twig.

## Notes
- `addArgument()` empile après les arguments du parent : si simplify_menu ajoute un argument constructeur, l'ordre devient faux → TypeError au build du container (site entier en erreur).
- Le callable `filterLanguage` ajouté par le patch n'est référencé nulle part dans le code custom (0 occurrence de `filterLanguage`) : patch possiblement inutile (à vérifier côté thème).
- Patch non appliqué dans le `vendor/` local (pas de `cweagons/composer-patches`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.x-dev`
- 2020-06-29 `22b235d7` patch ajouté « Add support for options. » — « Add: Add profile patches. »
- 2020-06-29 `22b235d7` patch ajouté « Add support for active menu trails. » — « Add: Add profile patches. »
- 2021-01-31 `1be86d53` montée (sortie de dev) `1.x-dev` → `^2.1` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch retiré « Add support for active menu trails. » — « Update: Add v2.1.0 Factory release changes. »
- 2021-02-01 `37e4b7b4` patch modifié (URL) « Add support for options. » — « Fix: Update simplify menu contrib module patch. »
- 2022-01-19 `6f19c294` patch ajouté « Add callable : filterLanguage » — « Fix: #30682- Untranslated menu items are displayed in menus »
- 2023-05-15 `7009653e` patch modifié (URL) « Add support for options. » — « Update: #40654 - Move existing patches to voidagency account »
- 2023-05-15 `7009653e` patch modifié (URL) « Add callable : filterLanguage » — « Update: #40654 - Move existing patches to voidagency account »
- 2023-07-13 `4b48e654` montée `^2.1` → `^3.0@beta` — « Fix: #44188 - Drupal 10 compatibility: Update responsive_preview, twig_tweak, user_registratio… »
- 2024-02-29 `344842b4` patch modifié (URL) « Add callable : filterLanguage » — « fix: simplify menu patch »
- 2024-02-29 `af2c26e6` patch modifié (URL) « Add callable : filterLanguage » — « fix: update patch »
- 2024-08-27 `69891a70` montée `^3.0@beta` → `^3.2` — « fix: #53344 - Simplify menu / isssue Patch vs version »
- 2024-08-27 `69891a70` patch retiré « Add support for options. » — « fix: #53344 - Simplify menu / isssue Patch vs version »

## Suivi (2026-11-19)
- Fragilité B16 corrigée : injection par setters (`addMethodCall`) au lieu des surcharges de constructeur, avec un test (PR #914). `drush cr` obligatoire au déploiement.
