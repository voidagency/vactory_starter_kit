# drupal/menu_item_extras

| | |
|---|---|
| Contrainte composer.json | `^2.13` |
| Version lockée | `2.19.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — l'invalidation du cache Next.js des menus repose sur `instanceof MenuItemExtrasMenuLinkContent` ; si la classe d'entité change, les menus front ne sont plus invalidés, sans erreur. |
| Couverture actuelle | Partielle (indirecte) |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:94` | Installé (remplace la classe de l'entité `menu_link_content`) |
| vactory_decoupled | `vactory_decoupled.module:34,746-750` | `hook_entity_update` : `instanceof MenuItemExtrasMenuLinkContent` → `clear_next_cache(['menu' => bundle, 'invalidate' => 'menu'])` |
| vactory_decoupled | `vactory_decoupled.module:805-811` | `hook_entity_delete` : idem |
| vactory_decoupled | `src/Plugin/Field/InternalNodeEntityBreadcrumbFieldItemList.php:260,308` ; `src/MenuBasedBreadcrumbBuilder.php:224` | Docblocks `@var MenuItemExtrasMenuLinkContent` |
| vactory_toolbox | `config/install/core.entity_form_display.menu_link_content.toolbox.default.yml:14,44` | Widget `menu_item_extras_view_mode_selector_select` |
| vactory_header | `vactory_header.info.yml:10` | Dépendance |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_breadcrumb/tests/Functional/BreadcrumbTest.php` — crée des `MenuLinkContent` (donc la classe surchargée) ; n'assert rien sur l'invalidation.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/MenuCacheInvalidationTest.php`** — `MenuLinkContent::create([...menu_name => 'main'])` → assert `instanceof \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent` ; mocker le client HTTP de `clear_next_cache` (ou vérifier un log/état) puis save/delete → appel avec `menu=main`. Attrape : changement de classe d'entité qui désactiverait silencieusement l'invalidation.

## Notes
- `vactory_decoupled.info.yml` ne déclare pas `menu_item_extras` mais importe la classe (`use` non bloquant ; `instanceof` sur classe absente = FALSE silencieux).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.11`
- 2021-01-31 `1be86d53` montée `^2.11` → `^2.13` — « Update: Add v2.1.0 Factory release changes. »
