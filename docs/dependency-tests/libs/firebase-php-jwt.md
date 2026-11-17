# firebase/php-jwt

| | |
|---|---|
| Contrainte composer.json | `^6.0` |
| Version lockée | `v6.10.0` |
| Patches | aucun |
| Statut | Utilisé (direct) + transitif (`google/apiclient ~6.0`, `google/auth ^6.0`, `stevenmaguire/oauth2-keycloak ^6.0`) |
| Risque à la MAJ | Élevé — vérification de signature des JWT Keycloak sur l'authentification API ; dépend d'un message d'exception de la lib. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `modules/vactory_decoupled/src/SimpleOauthAuthenticationProvider.php:6-7` | `use Firebase\JWT\JWK; use Firebase\JWT\JWT;` |
| vactory_decoupled | `…/SimpleOauthAuthenticationProvider.php:301` | `JWK::parseKeySet($jwks, 'RS256')` (2ᵉ argument `$defaultAlg`) |
| vactory_decoupled | `…/SimpleOauthAuthenticationProvider.php:302` | `JWT::$leeway = 30` (propriété statique publique) |
| vactory_decoupled | `…/SimpleOauthAuthenticationProvider.php:303` | `JWT::decode($jwt, $keys)` (signature v6 : tableau de `Key`) |
| vactory_decoupled | `…/SimpleOauthAuthenticationProvider.php:202-205` | `catch (\UnexpectedValueException)` + `strpos($exception->getMessage(), '"kid"')` pour déclencher le rafraîchissement JWKS |
| vactory_decoupled | `…/SimpleOauthAuthenticationProvider.php:57-58` | point d'entrée : header `X-Auth-Provider: keycloak` → `authenticateKeycloak()` |
| vactory_decoupled | `modules/vactory_decoupled/src/VactoryDecoupledServiceProvider.php:19-24` | remplace la classe du service `simple_oauth.authentication.simple_oauth` |

Seul usage PHP direct : 0 autre occurrence de `Firebase\`, `JWT::`, `JWK::` dans `modules/`, `src/` (hors `mae_group_poc`). `FirebaseKeyForm`/`FirebaseKeyController` (vactory_decoupled) concernent Firebase Cloud Messaging, pas cette lib.

## Tests existants qui l'exercent
- aucun (0 occurrence de `keycloak`/`X-Auth-Provider` dans les `*Test.php`).

## Tests à écrire
- **[Kernel] `modules/vactory_decoupled/tests/src/Kernel/KeycloakJwtAuthenticationTest.php`** — générer une paire RSA (openssl) ; `new Settings(['KEYCLOAK_ISSUER' => 'https://sso.test/realms/r', …])` ; pré-remplir le bin `vactory_decoupled_oauth` clé `keycloak_jwks:https://sso.test/realms/r/protocol/openid-connect/certs` avec le JWKS (`kid`, `use: sig`) ; appeler `\Drupal::service('simple_oauth.authentication.simple_oauth')->authenticate($request)` avec `Authorization: Bearer <jwt>` + `X-Auth-Provider: keycloak`. Cas :
  - token RS256 valide → `User` retourné, `preferred_username` = nom ;
  - `exp` dépassé de > 30 s → `UnauthorizedHttpException` ; dépassé de < 30 s → accepté (leeway) ;
  - `alg: none` et HS256 signé avec la clé publique → 401 ;
  - `iss` différent → 401 ; `KEYCLOAK_AUDIENCE` non présent dans `aud`/`azp` → 401 ;
  - `kid` inconnu → tente un refresh (mock `http_client` retournant le JWKS) puis accepte ; 2ᵉ refresh dans les 60 s → 401 (throttle).
  Attrape : changement de signature `JWT::decode`/`JWK::parseKeySet`, suppression de `JWT::$leeway`, changement du message `"kid" invalid…` (l.479 de `vendor/firebase/php-jwt/src/JWT.php`) qui casserait la rotation de clés.

## Notes
- **Contrainte trop large** : `JWK::parseKeySet($jwks, $defaultAlg)` n'existe que depuis **6.2.0** (CHANGELOG `vendor/firebase/php-jwt/CHANGELOG.md` l.95-98 : « Added `$defaultAlg` parameter to `JWT::parseKey` and `JWT::parseKeySet` »). `^6.0` autorise 6.0/6.1 où les clés sans `alg` seraient rejetées. Remonter à `^6.2`.
- Logique métier liée au **texte** d'une exception (`'"kid"'`, l.204) : un changement de message dans une version mineure désactive silencieusement le refresh JWKS (tokens rejetés après rotation de clé Keycloak).
- `JWT::$leeway` est global au process : toute autre utilisation de la lib dans la même requête hérite de 30 s.
- Une version 7.x de php-jwt existe peut-être — **à vérifier** ; elle serait de toute façon bloquée par `google/apiclient ~6.0` et `stevenmaguire/oauth2-keycloak ^6.0`.
- `lcobucci/jwt` (`DataSet`, l.8 et l.260) est aussi utilisé dans ce fichier (hors lot).

## Historique
- 2023-12-25 `8629f204` montée ^4.0 → ^6.0 + remplacement `pviojo/oauth2-keycloak` (« unsupported ») par `stevenmaguire/oauth2-keycloak`
- 2026-09-24 `380687ec` correctif sécurité code (`vactory_decoupled`) : la branche Keycloak ne vérifiait ni signature ni expiration du JWT (token forgé ⇒ uid 1) — pas un changement de paquet
- *hors 3.x* 2026-10-26 `2bdbba07` montée ^6.0 → ^7.0 (CVE-2025-45769) — `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
