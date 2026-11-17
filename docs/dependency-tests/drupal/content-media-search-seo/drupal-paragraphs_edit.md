# drupal/paragraphs_edit

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.0.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — le dashboard de contenus en attente construit en dur l'URL de la route du module ; le widget dynamique teste son nom de route. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_dynamic_field | `modules/vactory_dynamic_field/vactory_dynamic_field.info.yml:14` | Dépendance de module |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Plugin/Field/FieldWidget/VactoryDynamicWidget.php:100` | Teste `$route_name === 'paragraphs_edit.edit_form'` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Controller/PendingContentDashboard.php:172` | URL en dur `/paragraphs_edit/node/{id}/paragraphs/{pid}/edit` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/vactory_dynamic_field.install:27` | Installe `paragraphs_edit` si… déjà installé (voir notes) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_dynamic_field/tests/src/ExistingSite/ParagraphsEditRouteTest.php`** — `Url::fromRoute('paragraphs_edit.edit_form', [...])` existe et son chemin correspond au motif codé en dur dans `PendingContentDashboard.php:172` ; GET en admin sur un paragraph `vactory_component` → 200 et widget dynamique rendu.

## Notes
- `vactory_dynamic_field.install:27` : `if (moduleExists('paragraphs_edit')) { install(['paragraphs_edit']); }` — condition inversée, sans effet (le module est de toute façon dépendance dans l'info.yml).
- Nom de route et chemin codés en dur : un renommage côté module casse silencieusement le lien.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `^2.0` — « Update: Add v2.1.0 Factory release changes. »
- 2024-02-21 `10bbf6f7` montée `^2.0` → `^3.0` — « Update: #49503 - Update modules »
