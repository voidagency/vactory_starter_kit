# drupal/social_auth

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.1.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — 5 classes du paquet étendues (UserManager, OAuth2ControllerBase, OAuth2Manager, NetworkBase, SocialAuthSettingsForm) ; notre `UserManager` expose des méthodes `protected` du parent utilisées par l'auth headless Google/Facebook/Keycloak. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Classes du paquet que nous étendons (risque n°1)
| Classe du paquet | Notre classe | Remarque |
|---|---|---|
| `Drupal\social_auth\User\UserManager` | `vactory_decoupled/src/UserManager.php:10` (service `vactory_decoupled.user_manager`, `vactory_decoupled.services.yml`) | ré-expose `generateUniqueUsername()`, `userPictureEnabled()`, `downloadProfilePic()` (:13-22) ; constructeur à 10 arguments recopié dans `services.yml` |
| `Drupal\social_auth\Controller\OAuth2ControllerBase` | `vactory_keycloak/src/Controller/KeycloakAuthController.php:18` | `parent::__construct()` à 9 arguments positionnels (:45-47) |
| `Drupal\social_auth\AuthManager\OAuth2Manager` | `vactory_keycloak/src/KeycloakAuthManager.php:13` | `parent::__construct($config, $logger_factory)` (:31) |
| `Drupal\social_auth\Plugin\Network\NetworkBase` | `vactory_keycloak/src/Plugin/Network/KeycloakAuth.php:29` | plugin `type = "social_auth"` |
| `Drupal\social_auth\Form\SocialAuthSettingsForm` | `vactory_keycloak/src/Form/KeycloakAuthSettingsForm.php:17` | formulaire de config |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `vactory_decoupled.info.yml:17` | dépendance |
| vactory_decoupled | `src/SimpleOauthAuthenticationProvider.php:53,114-115,169-170` | `vactory_decoupled.user_manager` (création/MAJ user depuis token Google/Facebook/Keycloak) |
| vactory_keycloak | `vactory_keycloak.info.yml:7` | dépendance |
| vactory_keycloak | `vactory_keycloak.install:8,14,21` | `SocialAuthController::setLoginButtonSettings()` / `deleteLoginButtonSettings()` |
| vactory_keycloak | `src/Controller/KeycloakAuthController.php:58,61` | services `social_auth.user_authenticator`, `social_auth.data_handler` |
| vactory_keycloak | `vactory_keycloak.links.task.yml:4` | `base_route: social_auth.integrations` |
| vactory_social_auth | `vactory_social_auth.info.yml:7` ; `config/optional/social_auth.settings.yml` | dépendance + boutons de login FB/Google |
| vactory_taxonomy_results | `vactory_taxonomy_results.module:150` | exclusion entité `social_auth` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `vactory_decoupled/tests/src/Kernel/SocialAuthUserManagerTest.php`** — instancier `vactory_decoupled.user_manager` (container) : `generateUniqueUsername('john')` deux fois avec un user `john` existant → `john_1` ; `userPictureEnabled()` booléen. Attrape changement de constructeur (10 args) ou passage `private` des méthodes.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/KeycloakBearerAuthTest.php`** — mock HTTP (JWKS keycloak) ou setting de test, requête avec `X-Auth-Provider: keycloak` + JWT signé → user créé/chargé, 200 sur `/oauth/userinfo`. Couvre aussi simple_oauth.
- **[Kernel] `vactory_keycloak/tests/src/Kernel/KeycloakPluginTest.php`** — `plugin.network.manager` découvre `vactory_keycloak` ; `KeycloakAuthController::create($container)` ne lève pas d'erreur. Attrape changement de signature `OAuth2ControllerBase::__construct`.

## Notes
- `KeycloakAuthController` passe 9 arguments positionnels au parent (dont `$renderer` optionnel) : tout ajout/réordonnancement en 3.x+ casse le contrôleur.
- `vactory_social_auth/config/optional/social_auth.settings.yml` référence `modules/contrib/social_auth_*/img/*.svg` : chemin dépendant de l'installer-path (à vérifier).

## Historique
- 2020-06-28 `1af659f7` ajout `2.0-rc2` ; 2021-01-31 `1be86d53` → ^3.0
- 2023-07-14 `bd842c79` retrait `social_auth_microsoft` (« not used ») — #44188
- 2023-10-23 PR #375 `e7d331b6` correctif code #47474 « Fix social auth duplicated accounts issue » (pas de changement de paquet)
- *hors 3.x* `bf4234f9` → ^4.1 ; 2026-10-28 `fd0c200c` portage vactory_keycloak / decoupled user manager — `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
