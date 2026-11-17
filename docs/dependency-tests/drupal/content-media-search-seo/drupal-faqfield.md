# drupal/faqfield

| | |
|---|---|
| Contrainte composer.json | `^7.0` |
| Version lockée | `7.1.0` |
| Patches | aucun |
| Statut | Indirect (type de champ en config) |
| Risque à la MAJ | Faible/Moyen — champ `field_faq` de `vactory_faq` ; MAJ de schéma = install/migration cassée. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_faq | `modules/vactory_faq/config/install/field.storage.node.field_faq.yml` | Champ `faqfield` + form/view displays (4 fichiers) |
| info.yml | `vactory_starter_kit.info.yml:81` | Profil ; vactory_faq.info.yml:6, vactory_faq_prive.info.yml:6 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_faq/tests/src/ExistingSite/FaqFieldTest.php`** — node `vactory_faq` avec 2 Q/R → JSON:API expose `field_faq` avec `question`/`answer`.

## Notes
0 usage PHP de `field_faq`. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.1.0`
- 2021-01-31 `1be86d53` montée `^1.1.0` → `^7.0` — « Update: Add v2.1.0 Factory release changes. »
