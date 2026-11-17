# commerceguys/addressing

| | |
|---|---|
| Contrainte composer.json | `~1.0` |
| Version lockée | `v1.4.2` |
| Patches | aucun |
| Statut | Utilisé (direct) + transitif (`drupal/address 1.12.0` → `^1.4.2`) |
| Risque à la MAJ | Moyen — enhancer JSON:API qui sérialise les pays ; la contrainte `~1.0` bloque `drupal/address` 2.x (requis pour Drupal 11). |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `modules/vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/VactoryPaysEnhancer.php:5,29,34` | `CommerceGuys\Addressing\Country\CountryRepositoryInterface` injecté |
| vactory_decoupled | `…/VactoryPaysEnhancer.php:47` | service `address.country_repository` (fourni par drupal/address) |
| vactory_decoupled | `…/VactoryPaysEnhancer.php:57-58` | `$countryRepository->get($code)->getName()`, `->getCountryCode()` |
| vactory_decoupled | `modules/vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/VactorySocialNetworksEnhancer.php:5` | `use CountryRepositoryInterface` **inutilisé** (le constructeur prend `SocialMediaLinksPlatformManager`) |
| vactory_business | `modules/vactory_business/config/install/jsonapi_extras.jsonapi_resource_config.node--vactory_business.yml:360` | enhancer `vactory_pays` configuré |

## Tests existants qui l'exercent
- aucun (0 occurrence de `vactory_pays`, `Country` dans les `*Test.php`).

## Tests à écrire
- **[Kernel] `modules/vactory_decoupled/tests/src/Kernel/VactoryPaysEnhancerTest.php`** — modules `address`, `jsonapi_extras`, `vactory_decoupled` ; `plugin.manager.resource_field_enhancer->createInstance('vactory_pays')->undoTransform('MA', new Context())` → `['name' => 'Maroc' (langue fr) / 'Morocco', 'countryCode' => 'MA']`. Code inconnu `'ZZ'` → comportement attendu documenté (exception `UnknownCountryException` aujourd'hui, à vérifier). Attrape : changement d'API `Country::getName()/getCountryCode()`, du service `address.country_repository`.

## Notes
- `vactory_decoupled.info.yml` ne déclare pas `address` alors que l'enhancer exige le service `address.country_repository` ; fonctionne car `address` est dans l'install list du profil (`vactory_starter_kit.info.yml:85`).
- La contrainte racine `~1.0` est redondante avec celle de `drupal/address` et empêchera la montée vers `drupal/address` 2.x (addressing 2.x) ; API 2.x de `CountryRepositoryInterface::get()` / `Country::getName()` — **à vérifier**.

## Historique
- 2020-06-28 `1af659f7` ajout ~1.0 (inchangé sur 3.x)
- *hors 3.x* 2025-03-25 `38a27f42` ~1.0 → ^2.2 ; 2026-10-27 `12711c86` → ^2.1 — `origin/update/d11`, `origin/upgrade/d11`
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
