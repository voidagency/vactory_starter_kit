# drupal/openai

| | |
|---|---|
| Contrainte composer.json | `^1.0@beta` |
| Version lockée | `1.0.0-beta4` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — le code dépend d'une classe/service qui n'existe déjà plus dans la version lockée. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
Module optionnel `vactory_decoupled_search_ai` (non installé par le profil).

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_search_ai | `src/Controller/SearchAiController.php:15,93` | `Drupal\openai\Utility\StringHelper::prepareText()` |
| vactory_decoupled_search_ai | `src/Controller/SearchAiController.php:16,78,112` | Service `openai.client` (`OpenAI\Client`, `->embeddings()->create()`) |
| vactory_decoupled_search_ai | `src/Controller/SearchAiController.php:17,79,135` | `Drupal\openai_embeddings\Http\PineconeClient` + service `openai_embeddings.pinecone_client` |
| vactory_decoupled_search_ai | `vactory_decoupled_search_ai.routing.yml:2` | Route `/api/search-ai` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_decoupled_search_ai/tests/src/Kernel/SearchAiControllerWiringTest.php`** — activer le module et ses dépendances, `SearchAiController::create($container)` → ne doit pas lever d'exception ; assert `class_exists(StringHelper::class)`. Attraperait dès aujourd'hui l'erreur ci-dessous.

## Notes
- Dans `vendor/drupal/openai` 1.0.0-beta4, `modules/openai_embeddings/src/Http/` n'existe pas et aucun service `openai_embeddings.pinecone_client` n'est déclaré : la route `/api/search-ai` lève une erreur au `create()` avec le lock actuel (constaté sur le vendor local).
- `vactory_decoupled_search_ai.info.yml` ne déclare pas `openai`/`openai_embeddings` ; il les obtient via `search_api_ai` (`search_api_ai.info.yml:7`, `search_api_pinecone.info.yml:7`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2024-01-04 `e17f1392` ajout `1.0.0-alpha17` — « refactor: openai contrib module version »
- 2024-02-21 `10bbf6f7` dé-épinglage `1.0.0-alpha17` → `^1.0@beta` — « Update: #49503 - Update modules »
