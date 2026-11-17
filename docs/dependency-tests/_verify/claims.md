# Affirmations à vérifier (passe B)

Chaque ligne est une **affirmation** produite par une recon automatique. Elle peut être fausse. Repo : `/Users/user/dev/vactory_starter_kit`, branche `3.x`. Le `vendor/` local est désynchronisé du `composer.lock` et sans patches appliqués : quand une affirmation dépend d'une version, raisonner sur la version **lockée** (`composer.lock`), et lire le code de la lib dans `vendor/` seulement si la version installée (`vendor/composer/installed.json`) correspond.

## Groupe 1 — Bugs dans notre code

| ID | Affirmation |
|---|---|
| B01 | `modules/vactory_core/vactory_core.module` ~l.428 : la clé `acccess` est mal orthographiée dans `hook_xmlsitemap_link_alter`, donc sans effet. |
| B02 | `modules/vactory_dynamic_field/vactory_dynamic_field.install` ~l.27 : dans `update_9500`, la condition est inversée ; `paragraphs_edit` n'est jamais installé. |
| B03 | `modules/vactory_sso/vactory_sso.module` ~l.38 : `oauth_login_oauth2Controller` n'existe pas dans `drupal/oauth_login_oauth2` 2.6.0 (même avec le patch composer déclaré) → erreur fatale à la connexion SSO. |
| B04 | `composer.json` ~l.250 : patch déclaré sous `drupal/jsonapi_extra` (sans s) → jamais appliqué ; `vactory_decoupled_jsonapi_default_enhancers_info()` est mort. |
| B05 | `vactory_decoupled_search_ai` `SearchAiController` : service `openai_embeddings.pinecone_client` et classe `Drupal\openai_embeddings\Http\PineconeClient` absents de `drupal/openai` 1.0.0-beta4 → `/api/search-ai` en 500. |
| B06 | `vactory_html2pdf` : `array_merge` sur la config `vactory_html2pdf_font_dirs`, NULL tant que le formulaire de réglages n'a pas été enregistré → `TypeError` en PHP 8. |
| B07 | Handler webform Mailchimp (`mailchimp/marketing`) : `catch (RequestException)` ne capte pas `GuzzleHttp\Exception\ConnectException` → panne réseau = soumission webform en erreur. |
| B08 | `DecoupledOauth2Token.php` ~l.206 lit la clé de config `failed_auth_log` alors que le formulaire enregistre `log_failed_auth` → log jamais actif. |
| B09 | `SimpleOauthAuthenticationProvider` (vactory_decoupled) : le rafraîchissement des clés Keycloak se déclenche en cherchant `"kid"` dans le message d'exception de firebase/php-jwt. |
| B10 | `vactory_toolbox` livre une config qui dépend de `workbench_moderation` alors que le profil utilise `content_moderation`. |
| B11 | `vactory_appointment.module` ~l.318 teste le thème admin `== 'seven'` ; le thème admin du profil est `claro` → branche morte. |
| B12 | `vactory_webform_storage` implémente un hook `post_handler_submission` qui n'existe que via le patch webform déclaré dans composer.json. |
| B13 | Le JS de `views_autocomplete_filters` (version lockée, sans patch) utilise `.once()` jQuery, retiré en Drupal 10. |
| B14 | `config/install/shield.settings.yml` contient des identifiants Basic Auth en clair. |
| B15 | `vactory_decoupled_flag` utilise flag sans le déclarer dans son `.info.yml` ; `vactory_decoupled_search_ai` utilise `search_api_pinecone` sans le déclarer ni l'avoir dans composer.json. |
| B16 | Le service `simplify_menu.menu_items` est surchargé avec des `addArgument()` positionnels (dépend de la signature du constructeur amont). |

## Groupe 2 — Contraintes composer et versions

| ID | Affirmation |
|---|---|
| C01 | Le code utilise des coordonnées en tableau (`setCellValue([c,r])`, `getCell([c,r])`, `getStyle([c1,r1,c2,r2])`) de phpoffice/phpspreadsheet, apparues en 1.23.0 ; la contrainte `^1.20` est trop basse. |
| C02 | Le code appelle `JWK::parseKeySet($jwks, 'RS256')` (2e argument) de firebase/php-jwt, apparu en 6.2 ; la contrainte `^6.0` est trop basse. |
| C03 | `symfony/finder: ~4.0|~5.0|~6.0` exclut Symfony 7 donc Drupal 11. |
| C04 | `commerceguys/addressing: ~1.0` empêche `drupal/address` 2.x. |
| C05 | `drupal/yaml_editor` et `voidagency/yaml_editor` fournissent le même nom machine de module ; idem `drupal/amptheme` / `voidagency/amptheme`. |
| C06 | `drupal/decoupled_router` et `drupal/jsonapi_user_resources` ne sont utilisés que comme dépendances `.info.yml` ; `/api/router` est réimplémenté par notre code. |

## Groupe 3 — CI

| ID | Affirmation |
|---|---|
| D01 | La CI (`~/dev/vactory-test-ci/entrypoint.sh` + `phpunit.xml`) lance phpunit sur le chemin du profil, donc les tests dans `tests/Functional/` (hors `tests/src/`) sont exécutés. |
| D02 | `SIMPLETEST_DB` y est une valeur factice → les 2 tests `KernelTestBase` du profil ne peuvent pas s'installer en CI. |
