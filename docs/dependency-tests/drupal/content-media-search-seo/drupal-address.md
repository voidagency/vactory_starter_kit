# drupal/address

| | |
|---|---|
| Contrainte composer.json | `^1.9` |
| Version lockée | `1.12.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — service `address.country_repository` dans une API JSON (`PaysController`) et un enhancer JSON:API ; 3 champs address/address_country. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `modules/vactory_decoupled/src/Controller/PaysController.php:19` | `address.country_repository->getList()` (API pays) |
| vactory_decoupled | `modules/vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/VactoryPaysEnhancer.php:47` | Injection `address.country_repository` |
| vactory_google_places | `modules/vactory_google_places/src/Form/SettingsForm.php:43` | `address.country_repository` |
| vactory_governance | `modules/vactory_governance/config/install/field.storage.node.field_gov_address.yml` | Champ `address` |
| vactory_locator | `modules/vactory_locator/config/install/field.storage.locator_entity.field_locator_adress.yml` | Champ `address` |
| vactory_business | `modules/vactory_business/config/install/field.storage.node.field_business_pays.yml` | Champ `address_country` |
| info.yml | `vactory_starter_kit.info.yml:85` | Profil ; vactory_field.info.yml:8, vactory_locator.info.yml:7 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_decoupled/tests/src/Kernel/PaysEnhancerTest.php`** — enhancer `VactoryPaysEnhancer` sur code `MA` → libellé « Morocco/Maroc » selon langue. Attrape un changement de `CountryRepository` (lib commerceguys/addressing).
- **[ExistingSite] `modules/vactory_decoupled/tests/src/ExistingSite/PaysControllerTest.php`** — GET `/api/available-pays` (`vactory_decoupled.routing.yml:100`) → JSON non vide contenant `MA` et `FR`.

## Notes
Dépend aussi de `commerceguys/addressing` (hors lot). Une MAJ qui change le schéma du type de champ / plugin (stockage, settings, id de widget/formatter) casse `drush si vactory_starter_kit` ou l'activation des modules qui livrent cette config (`config/install`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.8`
- 2021-01-31 `1be86d53` montée `^1.8` → `^1.9` — « Update: Add v2.1.0 Factory release changes. »
