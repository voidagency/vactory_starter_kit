# drupal/auto_entityqueue

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.3.0` |
| Patches | aucun |
| Statut | Indirect (config `third_party_settings`) |
| Risque à la MAJ | Faible/Moyen — 21 queues configurées avec `third_party_settings.auto_entityqueue` ; un changement de schéma casse l'install de ces modules. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « Access check for adding to entity queue fails » (`72e5d710`, #45762) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:136` | Installé par le profil |
| vactory_event, academy, blog, governance, job_ads, press_release, publication, tender | `modules/vactory_event/config/install/entityqueue.entity_queue.vactory_block_two_columns.yml:23` | `third_party_settings.auto_entityqueue` (21 fichiers) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_event/tests/src/ExistingSite/AutoEntityQueueTest.php`** — créer un `vactory_event` publié → il apparaît dans la subqueue `vactory_block_two_columns`.

## Notes
Aucune dépendance `auto_entityqueue` déclarée dans les info.yml des modules qui livrent cette config (seul le profil l'active) — install isolée d'un module hors profil à vérifier. Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.0.0`
- 2021-01-31 `1be86d53` montée `^1.0.0` → `^2.0` — « Update: Add v2.1.0 Factory release changes. »
- 2023-07-24 `72e5d710` patch ajouté « Access check for adding to entity queue fails » — « Update: #45762 - Test dynamic migrate custom module & fix related Drupal 10 compatibility issu… »
- 2023-11-17 `c71d0744` patch retiré « Access check for adding to entity queue fails » — « Update: Remove unused patch for auto_entityqueue »
