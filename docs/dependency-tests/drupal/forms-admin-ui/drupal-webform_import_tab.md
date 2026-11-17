# drupal/webform_import_tab

| | |
|---|---|
| Contrainte composer.json | `1.0.x-dev@dev` |
| Version lockée | `dev-1.0.x` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Moyen — branche dev non figée : chaque `composer update` peut tirer un commit arbitraire ; le module étend `ConfigSingleImportForm` du core (classe interne). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:191` | Installé par le profil (onglet admin d'import YAML de webform) |

0 occurrence de `webform_import_tab` / `WebformImport` dans le code custom.

## Tests existants qui l'exercent
- aucun (hormis `drush si` en CI qui l'installe).

## Tests à écrire
- **[ExistingSite] `vactory_webform_custom/tests/src/ExistingSite/AdminWebformRoutesTest.php`** — en admin, GET de la route d'import fournie par le module (`webform_import_tab.routing.yml`) → 200. Attrape : casse du formulaire suite à MAJ core/dev. P3.

## Notes
- Dépendance `dev` sans hash figé dans la contrainte : à épingler sur un commit ou une release.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2025-10-02 `e0b313ca` ajout `1.0.x-dev@dev` — « feat: #61189 - add module webform_import_tab »
