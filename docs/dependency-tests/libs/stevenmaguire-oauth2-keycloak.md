# stevenmaguire/oauth2-keycloak

| | |
|---|---|
| Contrainte composer.json | `^5.0` |
| Version lockée | `5.1.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — login SSO (flux authorization_code) via le provider League ; module optionnel, non installé par le profil. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (auth) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_keycloak | `modules/vactory_keycloak/src/Plugin/Network/KeycloakAuth.php:9,43` | `use Stevenmaguire\OAuth2\Client\Provider\Keycloak` ; `class_exists()` sur le FQCN |
| vactory_keycloak | `…/KeycloakAuth.php:52-68` | `new Keycloak(['authServerUrl','realm','clientId','clientSecret','redirectUri'], ['httpClient' => new GuzzleHttp\Client(['verify' => FALSE])])` |
| vactory_keycloak | `modules/vactory_keycloak/src/KeycloakAuthManager.php:39` | `$client->getAccessToken('authorization_code', ['code' => …])` (méthode League `AbstractProvider`) |
| vactory_keycloak | `…/KeycloakAuthManager.php:53` | `$client->getResourceOwner($token)` → `KeycloakResourceOwner` |
| vactory_keycloak | `…/KeycloakAuthManager.php:71,87` | `getAuthorizationUrl([...])`, `getState()` |
| vactory_keycloak | `modules/vactory_keycloak/src/Controller/KeycloakAuthController.php:79-98` | `KeycloakResourceOwner::getEmail()`, `getName()`, `getId()` |
| vactory_keycloak | `modules/vactory_keycloak/vactory_keycloak.info.yml` | dépendance `social_auth:social_auth` (le provider est branché comme Network plugin Social API) |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `modules/vactory_keycloak/tests/src/Kernel/KeycloakAuthNetworkTest.php`** — config `vactory_keycloak.settings` (server URL, realm, client id/secret) → `plugin.network.manager->createInstance('vactory_keycloak')->getSdk()` est un `Keycloak` ; `getAuthorizationUrl()` contient `{server}/realms/{realm}/protocol/openid-connect/auth` et `client_id`. Attrape : changement des options du constructeur, du format d'URL (`/auth` préfixe selon `version`).
- **[Unit] `modules/vactory_keycloak/tests/src/Unit/KeycloakAuthManagerTest.php`** — `Keycloak` mocké : `getResourceOwner()` retourne un `KeycloakResourceOwner(['sub'=>'1','email'=>'a@b.c','name'=>'A'])` → `getUserInfo()` renvoie l'objet ; `getEmail()`/`getId()` lus correctement. Attrape : renommage d'accesseurs du ResourceOwner.

## Notes
- Option `version` du provider non passée (`vendor/stevenmaguire/oauth2-keycloak/src/Provider/Keycloak.php:57,77`) : influe sur scopes/logout pour Keycloak ≥ 20 — **à vérifier** selon la version serveur.
- `noCertVerification()` → `verify => FALSE` (KeycloakAuth.php:62-65) : désactive TLS, à surveiller.
- Ce paquet tire `firebase/php-jwt ^6.0` et `league/oauth2-client ^2.0` (lock 2.7.0).
- Le flux Keycloak « décodé » de `vactory_decoupled` (`SimpleOauthAuthenticationProvider`) n'utilise **pas** ce paquet mais `firebase/php-jwt` directement.
- `vactory_keycloak` absent de `vactory_starter_kit.info.yml` (install list) → test Kernel.

## Historique
- 2023-12-25 `8629f204` **remplacement** `pviojo/oauth2-keycloak` ^2.0 (« unsupported package ») → `stevenmaguire/oauth2-keycloak` ^5.0, adaptation `vactory_keycloak`
- *hors 3.x* 2026-10-26 `2bdbba07` montée ^5.0 → ^6.0 — `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
