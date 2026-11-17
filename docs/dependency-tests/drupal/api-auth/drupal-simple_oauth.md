# drupal/simple_oauth

| | |
|---|---|
| Contrainte composer.json | `^5.2` |
| Version lockée | `5.2.5` |
| Patches | 3 : « Auth revoke on profile update » (d.o #2946882-34, 2019), « Registered claims error after upgrading from 5.0.5 to 5.2.0 » (gist), « /oauth/jwks accessible without authentication » (gist) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — service d'authentification remplacé par notre sous-classe, contrôleur `/oauth/token` étendu, 2 grants custom, repository user remplacé, serveur OAuth2 reconstruit à la main (`OneTimeToken`) ; tout le login headless en dépend. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Classes/services du paquet que nous étendons ou décorons (risque n°1)
| Élément du paquet | Notre code | Nature |
|---|---|---|
| service `simple_oauth.authentication.simple_oauth` / `SimpleOauthAuthenticationProvider` | `vactory_decoupled/src/VactoryDecoupledServiceProvider.php:19-24` → `vactory_decoupled/src/SimpleOauthAuthenticationProvider.php:17` | `setClass()` + `extends` ; surcharge `authenticate()` (:48) pour `X-Auth-Provider` keycloak/google/facebook, sinon `parent::authenticate()` |
| `Drupal\simple_oauth\Controller\Oauth2Token` | `vactory_decoupled/src/Controller/DecoupledOauth2Token.php:21` (route `/oauth/login-token`, `vactory_decoupled.routing.yml:156`) | `extends`, constructeur `parent::__construct($grant_manager, $client_repository)` (:90), `parent::token()` (:130) |
| `Drupal\simple_oauth\Plugin\Oauth2GrantBase` | `vactory_faceid_auth/src/Plugin/Oauth2Grant/FaceId.php:22`, `vactory_oauth_apikey/src/Plugin/Oauth2Grant/Apikey.php:22` | plugins `@Oauth2Grant` |
| service `simple_oauth.repositories.user` | `vactory_phone_auth_decoupled/src/VactoryPhoneAuthDecoupledServiceProvider.php:21-23` → `Repositories/UserRepository.php:13` | `setClass()` (remplacement complet) |
| `Drupal\simple_oauth\Entities\UserEntity` / `ClientEntity` / `AccessTokenEntity` | `FaceIdGrant.php:6`, `ApikeyGrant.php:6`, `phone UserRepository.php:8`, `OneTimeToken.php:21,158`, `vactory_decoupled.module:26` | instanciation / type-hint |
| route `simple_oauth.userinfo` | `vactory_decoupled/src/Routing/RouteSubscriber.php:58-59` | contrôleur remplacé par `UserInfo::handle` |
| hook `hook_simple_oauth_private_claims_alter` | `vactory_decoupled.module:471`, `vactory_video_ask.module:10` ; ordre forcé `vactory_decoupled.module:1038-1044` (hook_module_implements_alter) | hook |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `vactory_decoupled.info.yml:12` | dépendance |
| vactory_decoupled | `src/Controller/OneTimeToken.php:110-114,146,158-169` | services `simple_oauth.repositories.{user,access_token,refresh_token,client,scope}`, `ClientEntity`, `CryptKey`, `PasswordGrant` League (route `/oauth/one-time-token`) |
| vactory_decoupled | `src/Controller/OneTimeToken.php:100,164,172` | config `simple_oauth.settings`, setting `simple_oauth.key_permissions_check` |
| vactory_decoupled | `src/Controller/DecoupledOauth2Token.php:117-118` | `plugin.manager.oauth2_grant.processor`, `simple_oauth.repositories.client` |
| vactory_decoupled | `config/install/jsonapi_extras.jsonapi_resource_config.oauth2_token--*.yml` (4) | ressources JSON:API des tokens |
| vactory_faceid_auth | `src/Plugin/Oauth2Grant/FaceId.php:88-89,106` | grant custom, repositories, config |
| vactory_oauth_apikey | `src/Plugin/Oauth2Grant/Apikey.php:88-89,106` ; `src/OAuth2/Server/Grant/ApikeyGrant.php:69` | grant custom ; requête SQL directe sur la table `oauth2_token` |
| vactory_phone_auth_decoupled | `src/VactoryPhoneAuthDecoupledServiceProvider.php:21` | remplacement du user repository |
| vactory_video_ask | `vactory_video_ask.module:10` | claims privés |
| profil | `config/install/user.role.authenticated.yml:5,19` | permission `grant simple_oauth codes` |
| vactory_taxonomy_results | `vactory_taxonomy_results.module:149` | exclusion entité `oauth2_token` |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_espace_prive/tests/Functional/LoginControllerTest.php` — `/oauth/login-token` (= `DecoupledOauth2Token` → `Oauth2Token::token`) : login valide (:123), identifiants invalides (:143), compte bloqué (:163), consumer invalide (:187), clés absentes (:209), flood (:250). Génère des clés et un `Consumer`.
- Non couverts : `SimpleOauthAuthenticationProvider` (Bearer standard + keycloak/google/facebook), `/oauth/one-time-token`, grants FaceId/Apikey, repository phone, claims privés (`profile`), `/oauth/userinfo` surchargé.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/OauthBearerAuthTest.php`** — obtenir un token via `/oauth/login-token`, puis GET `/oauth/userinfo` et une ressource JSON:API protégée avec `Authorization: Bearer` → 200 + uid attendu ; sans header → 403. Attrape un changement de constructeur/`authenticate()` de `SimpleOauthAuthenticationProvider` (notre `setClass` ne fournit pas de constructeur).
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/OauthPrivateClaimsTest.php`** — décoder le JWT d'accès (`access_token`) → claim `profile` présent avec les champs de `get_oauth_user_infos()`. Attrape le patch « Registered claims » cassé ou un renommage du hook.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/OneTimeTokenTest.php`** — POST `/oauth/one-time-token` avec client valide → `access_token` + `refresh_token` ; client inconnu → 401. Attrape un changement des repositories / de `ClientEntity::__construct`.
- **[Kernel] `vactory_faceid_auth/tests/src/Kernel/Oauth2GrantPluginsTest.php`** — `plugin.manager.oauth2_grant.processor` découvre `face_id` et `apikey` (si module activé), `getGrantType()` retourne une instance League `GrantTypeInterface`. Attrape un changement d'interface `Oauth2GrantInterface` (simple_oauth 6.x).
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/JwksPublicTest.php`** — GET `/oauth/jwks` anonyme → 200 (vérifie que le patch jwks s'applique toujours).

## Notes
- `VactoryDecoupledServiceProvider.php:21-24` fait `addArgument()` ×3 sur une définition qui a **déjà** ces 3 arguments (`simple_oauth.services.yml:16-19`) → 6 arguments passés, les 3 derniers ignorés. Inoffensif aujourd'hui, casse si le parent change de constructeur.
- `vactory_phone_auth_decoupled` remplace la classe de `simple_oauth.repositories.user` sans hériter de celle du paquet (`implements UserRepositoryInterface`) : toute évolution de la classe d'origine est perdue silencieusement.
- `ApikeyGrant.php:69` interroge la table `oauth2_token` en SQL brut : dépend du schéma.
- Vendor local : la route `simple_oauth.jwks` exige encore `_role: authenticated` (`vendor/drupal/simple_oauth/simple_oauth.routing.yml:101`) → le vendor local n'a **pas** les patches appliqués (pas de `PATCHES.txt`). Vérifs de signature faites sur le code non patché.
- Patch « Auth revoke » daté 2019 (d.o) appliqué sur 5.2.5 : fragile, à vérifier à chaque MAJ.

## Historique
- 2022-03-22 `87baf2b6` ajout ^4.5 — #33569 ; 2022-03-23 `12729f65` → ^5.2
- 2022-04-19 `572d7aaf` patch ajouté « Auth revoke on profile update » (d.o #2946882)
- 2024-05-31 `3d863c2c` patches ajoutés « /oauth/jwks accessible without authentication » et « **Registered claims error after upgrading from 5.0.5 to 5.2.0** » (régression de montée) — #51590
- 2024-06-06 `1c451e54`, 2024-06-07 `1e0fc79e` patches modifiés — #51590
- *hors 3.x* 2025-11-10/11 `bf4234f9`, `f69b1030` → ^6.0, 3 patches retirés (#61846) ; 2026-10-28 `f6dd51bf` → ^6.1 + `simple_oauth_password_grant` — `origin/update/d11`, `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
