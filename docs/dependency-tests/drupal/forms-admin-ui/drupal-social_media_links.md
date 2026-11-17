# drupal/social_media_links

| | |
|---|---|
| Contrainte composer.json | `^2.7.0` |
| Version lockée | `2.9.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — le plugin manager des plateformes est consommé par un endpoint API et un enhancer JSON:API du front ; type de champ stocké. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:111-112` ; `config/install/block.block.socialmedialinks.yml` | Installe `social_media_links`, `social_media_links_field` ; bloc `social_media_links_block` |
| vactory_decoupled | `src/Controller/SocialMediaController.php:19` ; `vactory_decoupled.routing.yml:108-111` | GET `/api/available-social-media` : `plugin.manager.social_media_links.platform->getPlatforms()` |
| vactory_decoupled | `src/Plugin/jsonapi/FieldEnhancer/VactorySocialNetworksEnhancer.php:8,16,48,58` | Enhancer `vactory_social_networks` (`SocialMediaLinksPlatformManager::getPlatforms()`) |
| vactory_governance, vactory_business | `config/install/field.storage.node.field_gov_social_media.yml`, `core.entity_*_display.node.vactory_{governance,business}.*.yml` | Type `social_media_links_field`, formatter `social_media_links_field_default` |
| vactory_business | `vactory_business.info.yml:22` | Dépendance `social_media_links:social_media_links_field` |
| vactory_taxonomy_results, vactory_entity_canonical_access | `src/TermResultCountListBuilder.php:11`, `src/Form/SettingsForm.php:9` | `use Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Drupal;` (import parasite, inutilisé) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/SocialMediaApiTest.php`** — GET `/api/available-social-media` → 200, JSON contenant au moins `facebook`, `twitter`/`x`, `linkedin` avec leurs clés attendues. Attrape : changement de `getPlatforms()` (structure du tableau, ids de plateformes).
- **[Kernel] `vactory_decoupled/tests/src/Kernel/SocialNetworksEnhancerTest.php`** — `undoTransform()` (→ `doUndoTransform()`, `VactorySocialNetworksEnhancer.php:55`) sur une valeur de champ `social_media_links_field` → plateformes enrichies (libellé, url de base) attendues. P2.

## Notes
- Les `use …\Platform\Drupal;` masquent la classe globale `\Drupal` dans ces 2 fichiers : tout futur appel non qualifié `Drupal::service()` y casserait. À supprimer.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.6.0`
- 2021-01-31 `1be86d53` montée `^2.6.0` → `^2.7.0` — « Update: Add v2.1.0 Factory release changes. »
