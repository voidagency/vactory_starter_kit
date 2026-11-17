# drupal/field_group

| | |
|---|---|
| Contrainte composer.json | `^4.0` |
| Version lockée | `4.0.0` |
| Patches | aucun |
| Statut | Indirect (config form displays) |
| Risque à la MAJ | Faible — `third_party_settings.field_group` dans 12 form displays ; MAJ de schéma = install cassée. |
| Couverture actuelle | Aucune (smoke install) |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_paragraphs | `modules/vactory_paragraphs/config/install/core.entity_form_display.paragraph.vactory_component.default.yml` | Groupes de champs (5 form displays paragraph) |
| vactory_locator, governance, annual_report, banner, faq_prive, publication_prive | `modules/vactory_locator/config/install/core.entity_form_display.locator_entity.vactory_locator.default.yml` | Config form displays |
| info.yml | `vactory_starter_kit.info.yml:84` | Profil ; aussi vactory_paragraphs.info.yml:12, vactory_dynamic_field.info.yml:10, vactory_locator.info.yml:10 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] (smoke) `modules/vactory_paragraphs/tests/src/ExistingSite/ParagraphsWidgetFormTest.php`** — couvert par le test de formulaire node proposé dans la fiche paragraphs (page d'ajout rendue sans erreur).

## Notes
Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^3.0`
- 2021-01-31 `1be86d53` montée `^3.0` → `^3.1` — « Update: Add v2.1.0 Factory release changes. »
- 2026-09-17 `f337dbc2` montée `^3.1` → `^4.0` — « Update: upgrade profile dependencies and support Metatag 2 »
