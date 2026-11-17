# Matrice partielle — lot api-auth

Vendor local sans patches appliqués (pas de `PATCHES.txt`, route jwks non patchée) : vérifications de signatures faites sur le code contrib non patché.

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Fiche |
|---|---|---|---|---|---|---|---|
| drupal/jsonapi_extras | Utilisé | 13 classes étendues + 2 services/API + 85 configs ressources + 12 info.yml | vactory_decoupled (+webform), vactory_jsonapi_extras, vactory_sondage, vactory_diff_content, 25 modules métier (config) | Partielle | Élevé | P1 | [fiche](../drupal/api-auth/drupal-jsonapi_extras.md) |
| drupal/jsonapi_search_api | Utilisé | 1 classe (`IndexResource`) + 3 configs facets + 1 info | vactory_jsonapi_extras, vactory_decoupled_facets | Aucune | Moyen | P2 | [fiche](../drupal/api-auth/drupal-jsonapi_search_api.md) |
| drupal/jsonapi_user_resources | Indirect | 1 (info.yml) | vactory_decoupled | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-jsonapi_user_resources.md) |
| drupal/jsonapi_cross_bundles | Utilisé | 2 décorateurs empilés + 1 `instanceof` + 3 info | vactory_jsonapi_cross_bundles, vactory_decoupled, vactory_decoupled_cross_content, vactory_diff | Partielle | Élevé | P1 | [fiche](../drupal/api-auth/drupal-jsonapi_cross_bundles.md) |
| drupal/jsonapi_include | Utilisé | 4 (subscriber, form, config, info) | vactory_jsonapi_extras | Aucune | Moyen | P2 | [fiche](../drupal/api-auth/drupal-jsonapi_include.md) |
| drupal/decoupled_router | Indirect | 2 (info.yml) | vactory_decoupled, vactory_decoupled_router | Aucune (paquet) | Faible | P3 | [fiche](../drupal/api-auth/drupal-decoupled_router.md) |
| drupal/simple_oauth | Utilisé | ~25 (2 classes étendues, 2 services remplacés, 2 grants, 1 route surchargée, 2 hooks, 4 configs) | vactory_decoupled, vactory_faceid_auth, vactory_oauth_apikey, vactory_phone_auth_decoupled, vactory_video_ask | Partielle | Élevé | P1 | [fiche](../drupal/api-auth/drupal-simple_oauth.md) |
| drupal/simple_oauth_revoke | Non utilisé | 0 | — | — | — | — | 0 occurrence de `simple_oauth_revoke`, `oauth/revoke` (PHP, yml, config, info.yml) |
| drupal/consumers | Indirect | 2 (+ test) | via simple_oauth ; vactory_taxonomy_results | Partielle | Moyen | P2 | [fiche](../drupal/api-auth/drupal-consumers.md) |
| drupal/oauth_login_oauth2 | Utilisé | 4 (classe instanciée, route, config, session) | vactory_sso | Aucune | Élevé | P1 | [fiche](../drupal/api-auth/drupal-oauth_login_oauth2.md) |
| drupal/social_api | Utilisé | 4 | vactory_keycloak, vactory_user, vactory_social_auth | Aucune | Moyen | P2 | [fiche](../drupal/api-auth/drupal-social_api.md) |
| drupal/social_auth | Utilisé | 5 classes étendues + 3 services + 2 info | vactory_decoupled, vactory_keycloak, vactory_social_auth | Aucune | Élevé | P1 | [fiche](../drupal/api-auth/drupal-social_auth.md) |
| drupal/social_auth_facebook | Indirect | 2 configs + 1 info | vactory_social_auth | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-social_auth_facebook.md) |
| drupal/social_auth_google | Indirect | 2 configs + 1 info | vactory_social_auth | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-social_auth_google.md) |
| drupal/email_registration | Utilisé | 3 (2 fonctions ×2 appels, 1 config) | vactory_decoupled, vactory_decoupled_espace_prive, vactory_user | Aucune | Moyen | P1 | [fiche](../drupal/api-auth/drupal-email_registration.md) |
| drupal/persistent_login | Indirect | 1 (info.yml) + profil | vactory_user | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-persistent_login.md) |
| drupal/nocurrent_pass | Indirect | profil seulement | — | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-nocurrent_pass.md) |
| drupal/user_registrationpassword | Utilisé | ~10 (constantes, fonction `_…mail_notify`, mail ID, 3 clés config, token) | vactory_decoupled, vactory_decoupled_espace_prive | Aucune | Élevé | P1 | [fiche](../drupal/api-auth/drupal-user_registrationpassword.md) |
| drupal/password_policy | Utilisé | 2 (service validator, contrainte) + 2 configs | vactory_decoupled_espace_prive, profil | Aucune | Moyen | P1 | [fiche](../drupal/api-auth/drupal-password_policy.md) |
| drupal/login_security | Non utilisé | 0 | — | — | — | — | Commenté dans `vactory_starter_kit.info.yml:137` ; 0 occurrence de `login_security` ailleurs |
| drupal/flood_control | Utilisé | 3 (form étendu, route remplacée, `moduleExists`) | vactory_flood_control, vactory_decoupled | Partielle | Moyen | P2 | [fiche](../drupal/api-auth/drupal-flood_control.md) |
| drupal/captcha | Utilisé | ~8 (SQL direct `captcha_sessions`, constante, permission, 5 configs) | vactory_decoupled_webform, vactory_decoupled, vactory_job_ads, 4 webforms | Partielle | Élevé | P1 | [fiche](../drupal/api-auth/drupal-captcha.md) |
| drupal/recaptcha | Utilisé | 4 (classe `Drupal8Post` ×2, contrainte entité, 4 webforms) | vactory_decoupled | Partielle | Élevé | P1 | [fiche](../drupal/api-auth/drupal-recaptcha.md) |
| drupal/seckit | Indirect | 2 (profil, drush dev) | vactory_core | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-seckit.md) |
| drupal/shield | Utilisé | 2 (contrôleur `/api/check-shield`, config profil) | vactory_decoupled, profil | Aucune | Moyen | P2 | [fiche](../drupal/api-auth/drupal-shield.md) |
| drupal/security_review | Utilisé | 7 checks + form étendu + route + 2 lectures config | vactory_security_review, vactory_decoupled, vactory_devtools | Aucune | Moyen | P3 | [fiche](../drupal/api-auth/drupal-security_review.md) |
| drupal/advban | Non utilisé | 0 | — | — | — | — | 0 occurrence de `advban` (PHP, yml, config, info.yml) ; absent de `vendor/drupal/` |
| drupal/domain | Utilisé (optionnel) | 2 (guardés par `moduleExists`) | vactory_menu_breadcrumb, vactory_extended_seo | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-domain.md) |
| drupal/entity | Non utilisé | 0 | — | — | — | — | 0 occurrence de `Drupal\entity\`, `entity:entity`, `entity.query_access` ; aucun paquet du lock ne le requiert |
| drupal/ctools | Indirect | profil seulement | — | Aucune | Faible | — | [fiche](../drupal/api-auth/drupal-ctools.md) |
| drupal/token | Utilisé | 8 (`token_tree_link`) + 3 info | vactory_jsonapi_extras, vactory_dynamic_field, vactory_notifications, vactory_datalayer_*, vactory_wisepops, vactory_quiz_certificat | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-token.md) |
| drupal/views_json_source | Indirect | 1 vue (3 champs) + 1 info | vactory_wysiwyg_301to200 | Aucune | Faible | P3 | [fiche](../drupal/api-auth/drupal-views_json_source.md) |
| drupal/default_content | Indirect | 9 info.yml + 223 fichiers `content/` | 7 modules *_demo, vactory_welcome, vactory_report_content | Aucune | Moyen | P3 | [fiche](../drupal/api-auth/drupal-default_content.md) |
| drupal/single_content_sync | Utilisé | 1 plugin + 2 services + 2 info | vactory_single_content_sync_extra, vactory_diff | Partielle | Moyen | P2 | [fiche](../drupal/api-auth/drupal-single_content_sync.md) |
| drupal/health_check | Non utilisé | 0 | — | — | — | — | 0 occurrence de `health_check`, `healthcheck` (modules, config, scripts, Dockerfile) |
| drupal/memcache | Indirect | profil seulement | — | Aucune | Moyen (infra) | P3 | [fiche](../drupal/api-auth/drupal-memcache.md) |

## Constats transverses
- Patch `drupal/jsonapi_extra` (composer.json:250) : nom de paquet erroné → jamais appliqué à `drupal/jsonapi_extras`.
- `vactory_sso.module:8,38` instancie `Drupal\oauth_login_oauth2\Controller\oauth_login_oauth2Controller`, classe absente de oauth_login_oauth2 2.6.0 (seule `miniorange_oauth_clientController`) → fatal probable au login SSO (à vérifier sur vendor patché).
- `DecoupledOauth2Token.php:206` lit `security_review.checks:failed_auth_log`, la clé enregistrée est `log_failed_auth` → log de succès jamais actif.
- Décoration empilée sur `jsonapi.field_resolver` (jsonapi_cross_bundles + `VactoryFieldResolver` prio -1).
- `captcha_sessions` écrit/lu en SQL brut (`WebformController.php:284`, `vactory_decoupled_webform.module:75`).
- Dépendances implicites non déclarées : `email_registration` (vactory_decoupled*), `jsonapi_search_api` (vactory_jsonapi_extras), `oauth_login_oauth2` (vactory_sso).
