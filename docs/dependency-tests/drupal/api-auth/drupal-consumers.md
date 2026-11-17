# drupal/consumers

| | |
|---|---|
| Contrainte composer.json | `1.24.0` (figée) |
| Version lockée | `1.24.0` |
| Patches | aucun |
| Statut | Indirect (requis par `drupal/simple_oauth`) |
| Risque à la MAJ | Moyen — aucune API appelée dans le code de prod, mais les clients OAuth (`client_id`/secret) sont des entités `consumer` ; version figée alors que simple_oauth ^5.2 accepte d'autres versions. |
| Couverture actuelle | Partielle |
| Priorité test | P1 (P2 → P1 : épinglage exact 1.20.0 « fix consumers version ») |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| (via simple_oauth) | `vactory_decoupled/src/Controller/OneTimeToken.php:146` | `clientRepository->getClientEntity($client_id)->getDrupalEntity()` renvoie un `Consumer` |
| vactory_taxonomy_results | `vactory_taxonomy_results.module:140` | exclusion de l'entité `consumer` |
| tests | `vactory_decoupled_espace_prive/tests/Functional/LoginControllerTest.php:9,285` | création d'un `Consumer` |

## Tests existants qui l'exercent
- `LoginControllerTest.php:187` (`testInvalidConsumer`) et setup (:285) — création/validation d'un consumer avec secret.

## Tests à écrire
- Couvert par les tests simple_oauth (voir fiche simple_oauth). Ajouter dans `OneTimeTokenTest` un cas consumer `is_default`/sans secret si utilisé côté front.

## Notes
- 0 occurrence de `Drupal\consumers\` hors tests. Contrainte figée `1.24.0` dans composer.json : à justifier ou relâcher (à vérifier).

## Historique
- 2025-12-03 `332e0cad` ajout **épinglé** `1.20.0` « fix: fix consumers version »
- 2026-09-17 `f337dbc2` épinglage 1.20.0 → `1.24.0` (même commit qui a monté linkit 7.0.16, rétrogradé ensuite par `776f9ae0` #69237)
