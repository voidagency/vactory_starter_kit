# drupal/jsonapi_extras

| | |
|---|---|
| Contrainte composer.json | `^3.20` |
| Version lockée | `3.24.0` |
| Patches | 1 déclaré mais **jamais appliqué** : clé `drupal/jsonapi_extra` (sans « s ») → « Default field enhancer for fields » (composer.json:250) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — 11 plugins `ResourceFieldEnhancer` étendent `ResourceFieldEnhancerBase`, 85 configs `jsonapi_resource_config` dépendent du schéma, accès par rôle injecté dans les routes JSON:API via third-party settings. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Classes/services du paquet que nous étendons ou décorons (risque n°1)
| Classe / service du paquet | Notre code | Nature |
|---|---|---|
| `Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase` | 11 enhancers : `vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/*` (VactoryMetaTag, VactoryFileImage, VactoryFileDocument, VactoryLink, VactoryLangCode, VactoryBlocksField, VactorySocialNetworks, VactoryPays, VactoryApplyTokens, VactoryDynamicField, EditLiveModeParagraphTitle), `vactory_decoupled_webform/.../VactoryWebformElements.php:20`, `vactory_sondage/.../SondageOptionsEnhancer.php:22` | `extends` (signatures `doUndoTransform` / `doTransform` / `getOutputJsonSchema` / `getSettingsForm`) |
| `Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType` | `vactory_decoupled/src/Element/JsonApiCollectionElement.php:7,259-261` (`getJsonapiResourceConfig()`) ; idem `JsonApiCrossBundlesElement.php:138` | `instanceof` + API |
| `Drupal\jsonapi_extras\Entity\JsonapiResourceConfig` | `vactory_decoupled/vactory_decoupled.module:19,943` (entity builder) | type-hint + third-party settings |
| service `jsonapi_extras.entity.to_jsonapi` | `vactory_decoupled/src/Controller/VccController.php:394`, `BlocksController.php:149` | appel direct |
| config `jsonapi_extras.settings:path_prefix` | `vactory_diff_content/src/Service/JsonApiPathHelper.php:39`, `vactory_core/tests/src/Functional/VactoryExistingSiteBase.php:181` | lecture config |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `vactory_decoupled.info.yml:9-10` | dépendance `jsonapi_extras`, `jsonapi_defaults` |
| vactory_decoupled | `src/Plugin/jsonapi/FieldEnhancer/VactoryFileImageEnhancer.php:22` | plugin `@ResourceFieldEnhancer` `vactory_file_image` (92 usages en config) |
| vactory_decoupled | `src/Plugin/jsonapi/FieldEnhancer/VactoryLinkEnhancer.php:24` | enhancer `vactory_link` (47 usages config) |
| vactory_decoupled | `src/Plugin/jsonapi/FieldEnhancer/VactoryFileDocumentEnhancer.php:24` | enhancer `vactory_file_document` (14) |
| vactory_decoupled | `src/Plugin/jsonapi/FieldEnhancer/VactoryMetaTagEnhancer.php:23` | enhancer `vactory_metatag` (11) |
| vactory_decoupled | `src/Plugin/jsonapi/FieldEnhancer/VactoryDynamicFieldEnhancer.php:20` | enhancer `vactory_dynamic_field` (4) |
| vactory_decoupled | `vactory_decoupled.module:898` / `:943` | `hook_form_jsonapi_resource_config_edit_form_alter` + entity builder → third-party `collection_roles` / `individual_roles` |
| vactory_decoupled | `src/Routing/RouteSubscriber.php:30-55` | charge `jsonapi_resource_config` par `resourceType`, ajoute `_role` aux routes `jsonapi.*` (contrôle d'accès) |
| vactory_decoupled | `src/Element/JsonApiCollectionElement.php:255-261` | lit la config de ressource (chemin, nom public) |
| vactory_decoupled | `src/Controller/VccController.php:394` | `jsonapi_extras.entity.to_jsonapi` (route `/_vcc` active) |
| vactory_decoupled | `config/install/jsonapi_extras.settings.yml` | `path_prefix: api`, `include_count: true` |
| vactory_decoupled | `config/install/jsonapi_extras.jsonapi_resource_config.*.yml` (60 fichiers) | config ressources (dont oauth2_token, captcha_point) |
| 25 modules métier | `config/install/jsonapi_extras.jsonapi_resource_config.node--*.yml` (25 fichiers) | config ressources ; 6 avec `jsonapi_defaults` |
| vactory_jsonapi_extras | `vactory_jsonapi_extras.info.yml:7` | dépendance ; « Exposed APIs » (routes dynamiques `Routes.php`) |
| vactory_sondage | `src/Plugin/jsonapi/FieldEnhancer/SondageOptionsEnhancer.php:22` | enhancer |
| vactory_business, vactory_multivers, vactory_newsletter, vactory_pwa, vactory_publication, vactory_dynamic_field_video_help, vactory_testimonials | `*.info.yml` | dépendance seule |
| vactory_diff_content | `src/Service/JsonApiPathHelper.php:39` | `jsonapi_extras.settings:path_prefix` |

## Tests existants qui l'exercent
- `modules/vactory_core/tests/src/Functional/VactoryExistingSiteBase.php:181` — base de tous les tests ExistingSite : préfixe lu depuis `jsonapi_extras.settings` (`fetchNodeJsonApi`).
- `modules/vactory_decoupled/tests/Functional/MetaTagsTest.php:29,68` — attribut `internal_metatag` sur node JSON:API (enhancer `vactory_metatag`).
- `modules/vactory_decoupled/tests/Functional/DynamicFieldMediaTest.php:49,156,257` — sortie image/fichier/vidéo d'un DF via JSON:API (`_default`, `meta.width/height`).
- `modules/vactory_decoupled/tests/Functional/JsonApiCollectionTest.php:83` — paragraphe `json_api_collection` (ConfigurableResourceType).
- `modules/vactory_decoupled/modules/vactory_decoupled_cross_content/tests/Functional/DecoupledCrossContentTest.php:74,231` — élément hérité de `JsonApiCollectionElement`.
- `InternalBlocksTest`, `BreadcrumbTest`, `WebformParagraphTest` — utilisent `fetchNodeJsonApi` (prefixe), pas les enhancers en propre.
- Non couverts : enhancers `vactory_file_image` (en dehors des DF), `vactory_link`, `vactory_file_document`, `vactory_langcode`, `vactory_pays`, `vactory_social_networks`, accès par rôle (`RouteSubscriber`), `/_vcc`.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/JsonApiEnhancersTest.php`** — node `vactory_news` avec image + lien → GET `/api/node/vactory_news/{uuid}` : assert structure `vactory_file_image` (`_default`, `meta`), `vactory_link` (url résolue, attributs), `vactory_langcode`. Attrape un changement de signature/flux de `ResourceFieldEnhancerBase` ou de `transform()`.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/JsonApiResourceRolesTest.php`** — poser `collection_roles` = `[editor]` sur une `jsonapi_resource_config`, rebuild routes → anonyme 403, editor 200 sur la collection. Attrape une régression de chargement des configs (`loadByProperties(['resourceType'])`) ou de schéma third-party.
- **[Kernel] `vactory_decoupled/tests/src/Kernel/ResourceConfigSchemaTest.php`** — installer jsonapi_extras + vactory_decoupled, importer les 85 `jsonapi_resource_config` et valider le schéma (`TypedConfigManager`/ `SchemaCheckTrait`). Attrape les changements de schéma lors d'une MAJ.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/VccControllerTest.php`** — GET `/_vcc?...` sur un node → JSON non vide via `jsonapi_extras.entity.to_jsonapi`.

## Notes
- Patch « Default field enhancer for fields » déclaré sous `drupal/jsonapi_extra` (composer.json:250) : nom de paquet inexistant → composer-patches l'ignore. 1897 entrées `enhancer: id: ''` dans les configs ; impact réel du patch manquant **à vérifier**.
- `BlocksController` (`jsonapi_extras.entity.to_jsonapi`) : sa route `/_blocks` est commentée (`vactory_decoupled.routing.yml:28`) — code potentiellement mort, à vérifier.
- `vactory_testimonials.info.yml:9` dépend de `jsonapi_defaults` sans préfixe de projet (fonctionne, mais incohérent avec `vactory_sondage`).

## Historique
- 2022-03-22 `87baf2b6` ajout ^3.20 — #33569
- 2022-10-02 `9904f3e2`/`549ec82f` patch « Default field enhancer for fields » ajouté sous la clé erronée `drupal/jsonapi_extra` (jamais appliqué ; Redmine #71101)
- 2023-07-22 `d6862dbb` patch ajouté « Symfony 6 compatibility » (d.o #3349731) — #44188
- 2023-11-17 `74327c95` patch retiré (« unused »)
- Redmine #54428 « Pacth / Vactory jsonapi extras » : InputBag::get() scalar (Symfony 6) corrigé dans `vactory_jsonapi_extras` (PR #756 `3ae6dcbd`)
