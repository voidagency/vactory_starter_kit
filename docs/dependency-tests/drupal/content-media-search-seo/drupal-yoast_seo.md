# drupal/yoast_seo

| | |
|---|---|
| Contrainte composer.json | `^2.0.0-alpha4` |
| Version lockée | `2.0.0-alpha10` |
| Patches | aucun |
| Statut | Indirect (type de champ en config) |
| Risque à la MAJ | Moyen — champ `field_vactory_seo_status` (type `yoast_seo`) sur ~18 types de contenu ; version **alpha** dont le schéma peut changer. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_field | `modules/vactory_field/config/install/field.storage.node.field_vactory_seo_status.yml:10` | `type: yoast_seo` |
| ~18 modules de contenu (page, news, event, faq, blog, academy, governance, job_ads…) | `modules/vactory_page/config/install/field.field.node.vactory_page.field_vactory_seo_status.yml` | field.field + form/view displays (54 fichiers de config) |
| info.yml | `vactory_starter_kit.info.yml:145` | Profil ; vactory_field:19, vactory_job_ads:10 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_page/tests/src/ExistingSite/NodeFormSeoTest.php`** — GET `/node/add/vactory_page` en admin → 200, widget yoast présent ; sauver un node → pas d'erreur.

## Notes
0 usage PHP. Alpha : passage alpha→beta/stable susceptible de changer le schéma de champ. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.0.0-alpha3`
- 2021-01-31 `1be86d53` réécriture contrainte `^2.0.0-alpha3` → `^2.0.0-alpha4` — « Update: Add v2.1.0 Factory release changes. »
