# drupal/search_api_ai

| | |
|---|---|
| Contrainte composer.json | `^1.0@alpha` |
| Version lockée | `1.0.0-alpha2` |
| Patches | 1 — « Seach ai pinecone free account issue » (gist khalidbouhouch77, `search-api-pineconebackend-namespace-lang.patch`) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — module alpha patché ; le contrôleur `/api/search-ai` dépend de la classe interne `SearchApiPineconeBackend::getNamespace()` du sous-module `search_api_pinecone`. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « pinecone free account issue » (`b1629c16`), toujours appliqué (gist perso) (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_search_ai | `modules/vactory_decoupled/modules/vactory_decoupled_search/modules/vactory_decoupled_search_ai/src/Controller/SearchAiController.php:14` | `use Drupal\search_api_pinecone\...\SearchApiPineconeBackend` |
| vactory_decoupled_search_ai | `modules/vactory_decoupled/modules/vactory_decoupled_search/modules/vactory_decoupled_search_ai/src/Controller/SearchAiController.php:103` | `Index::load('ai_search')->getServerInstance()->getBackend()`, `assert(instanceof SearchApiPineconeBackend)`, `getNamespace($index)` (l.107) |
| vactory_decoupled_search_ai | `modules/vactory_decoupled/modules/vactory_decoupled_search/modules/vactory_decoupled_search_ai/vactory_decoupled_search_ai.info.yml:7` | Dépendance `search_api_ai` (pas `search_api_pinecone`) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_decoupled/modules/vactory_decoupled_search/modules/vactory_decoupled_search_ai/tests/src/Kernel/PineconeNamespaceTest.php`** — server avec backend `search_api_pinecone` + index `ai_search` → `getNamespace($index)` renvoie le format attendu par le patch (avec langue si c'est l'objet du patch — à vérifier). Attrape une non-application du patch ou un changement de signature.
- **[ExistingSite] test `/api/search-ai` sans `q`** → `{status: 400, resources: []}` (pas d'appel OpenAI).

## Notes
- Version **alpha**, patch sur gist personnel. Dans le vendor local `getNamespace()` (`SearchApiPineconeBackend.php:188`) ne contient pas de langue : patch non appliqué localement ou portant ailleurs — à vérifier.
- Dépend aussi de `openai`/`openai_embeddings` (hors lot). Non installé par le profil.
- Info.yml ne déclare pas `search_api_ai:search_api_pinecone` alors que la classe est utilisée.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-11-16 `7ebf9ebd` ajout `^1.0@alpha` — « feat: search ai - chatbox widget »
- 2023-11-16 `b1629c16` patch ajouté « Seach ai pinecone free account issue » — « fix: add patch to fix free account issue during tests »
