# drupal/decoupled_router

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.4` |
| Patches | aucun |
| Statut | Indirect (dépendance seulement) |
| Risque à la MAJ | Faible — aucune API appelée : `/api/router` est réimplémenté par `vactory_decoupled_router` (`PathTranslator` étend `ControllerBase`). Le module reste activé et expose `/router/translate-path`. |
| Couverture actuelle | Aucune (le paquet) ; Bonne (notre routeur) |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `vactory_decoupled.info.yml:7` | dépendance |
| vactory_decoupled_router | `vactory_decoupled_router.info.yml:7` | dépendance |
| vactory_decoupled_router | `src/Controller/PathTranslator.php:462` | simple commentaire `@todo` |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_router/tests/src/Functional/PathTranslatorTest.php:49,112,190,220,267` — teste **notre** route `vactory_decoupled_router.path_translation` (`/api/router`), pas le paquet.

## Tests à écrire
- Aucun spécifique. Le `PathTranslatorTest` existant suffit à détecter une casse indirecte (install du module).

## Notes
- 0 occurrence de `Drupal\decoupled_router\`, `decoupled_router.` (service), `PathTranslatorEvent`. Dépendance probablement historique : candidat à retrait (à vérifier : front qui appellerait `/router/translate-path`).

## Historique
- 2022-03-23 `12729f65` ajout ^2.0
- 2023-12-13 `cb84fa18`, 2024-08-22 PR #711 `080de455` correctifs côté `vactory_decoupled_router` (code)
- Aucun incident de version.
