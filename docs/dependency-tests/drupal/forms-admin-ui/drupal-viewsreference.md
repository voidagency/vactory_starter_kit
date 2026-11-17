# drupal/viewsreference

| | |
|---|---|
| Contrainte composer.json | `^2.0@beta` |
| Version lockée | `2.0.0-beta7` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — type de champ stocké sur le paragraphe `views_reference` + altération de la structure interne du widget. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « Exposed filters not working for Views in Paragraphs » (`1be86d53` → retiré `2f3c460c`) (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:129` | Installé |
| vactory_paragraphs | `config/install/field.storage.paragraph.field_views_reference.yml`, `field.field.paragraph.views_reference.field_views_reference.yml`, `core.entity_form_display.paragraph.views_reference.default.yml`, `core.entity_view_display.paragraph.views_reference.default.yml` | Type de champ / widget / formatter viewsreference |
| vactory_paragraphs | `vactory_paragraphs.module:192-200` | `hook_field_widget_single_element_form_alter` : manipule `$element['target_id']['#options']`, `['#ajax']['callback']`, `$element['display_id']` |
| vactory_paragraphs | `vactory_paragraphs.module:234,250` | Callbacks `views_reference_remove_disabled_views`, `views_reference_get_enabled_displays` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_paragraphs/tests/src/ExistingSite/ViewsReferenceWidgetTest.php`** — admin, formulaire d'ajout d'un node avec paragraphe `views_reference` : assert présence de `target_id` (select) et `display_id` dans le widget ; sauver un paragraphe référant `vactory_news:block_1` et vérifier que la valeur `display_id` est stockée. Attrape : changement de structure du widget ou du schéma du champ.

## Notes
- `vactory_paragraphs.info.yml` ne déclare pas `viewsreference` alors qu'il livre la storage du champ : installation hors profil impossible (à vérifier).
- Branche beta (`^2.0@beta`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `1.4.0`
- 2021-01-31 `1be86d53` montée (épinglée) `1.4.0` → `1.6.0` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch ajouté « Exposed filters not working for Views in Paragraphs » — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-17 `53038438` montée `1.6.0` → `^2.0@beta` — « Update: Upgrade profile packages to Drupal 9 required versions. »
- 2023-08-12 `2f3c460c` patch retiré « Exposed filters not working for Views in Paragraphs » — « Update: remove unused patch »
