# drupal/menu_link_attributes

| | |
|---|---|
| Contrainte composer.json | `^1.1.0` |
| Version lockée | `1.3.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible — un form_alter suppose la structure `$form['options']['attributes']['id']` ajoutée par ce module sur le formulaire de lien de menu. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:106` ; `config/install/menu_link_attributes.config.yml` | Installé + attributs disponibles (`class`, `wrapper_class`, …) |
| vactory_core | `vactory_core.module:253,294-306` (`vactory_core_form_alter`) | Si `$form['options']['attributes']['id']` vide → génère `menu-link-<timestamp>` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_core/tests/src/ExistingSite/MenuLinkFormTest.php`** — admin GET `/admin/structure/menu/manage/main/add` → champ `options[attributes][id]` présent et pré-rempli `menu-link-…`. Attrape : changement de structure du formulaire. P3.

## Notes
- La clé `id` est bien déclarée dans `config/install/menu_link_attributes.config.yml:8` ; elle doit y rester pour que le form_alter ait un effet.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0`
- 2021-01-31 `1be86d53` montée `^1.0.0` → `^1.1.0` — « Update: Add v2.1.0 Factory release changes. »
