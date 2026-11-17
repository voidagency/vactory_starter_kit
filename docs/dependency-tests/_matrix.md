# Matrice — dépendances composer.json → usage → tests

Généré depuis `_partials/*.md` (audit du 2026-11-17, branche `3.x`, ticket Redmine #71097). Une ligne par paquet de `require` (hors `php`).

Légende — **Statut** : Utilisé (code PHP/Twig/JS du profil) · Indirect (config, dépendance `.info.yml`, transitif) · Non utilisé. **Priorité** : P1 code critique non/peu testé · P2 impact moyen · P3 cosmétique/admin · — rien à tester.

## Libs PHP + voidagency

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Incidents historiques | Fiche |
|---|---|---|---|---|---|---|---|---|
| firebase/php-jwt | Utilisé | 3 appels (1 fichier) | vactory_decoupled | Aucune | Élevé | P1 | — (correctif sécurité code `380687ec`) | [libs/firebase-php-jwt.md](libs/firebase-php-jwt.md) |
| phpoffice/phpspreadsheet | Utilisé | 4 fichiers, ~30 appels | vactory_page_import, vactory_satisfaction, vactory_seo_noindex | Aucune | Moyen | P1 | — (bump 2.2 dependabot non fusionné) | [libs/phpoffice-phpspreadsheet.md](libs/phpoffice-phpspreadsheet.md) |
| mailchimp/marketing | Utilisé | 2 fichiers, 6 appels | vactory_webform_mailchimp_handler, vactory_mailchimp_newsletter | Aucune | Moyen | P1 | — | [libs/mailchimp-marketing.md](libs/mailchimp-marketing.md) |
| stevenmaguire/oauth2-keycloak | Utilisé | 4 fichiers | vactory_keycloak | Aucune | Moyen | P1 | remplacement de `pviojo/oauth2-keycloak` (« unsupported ») `8629f204` | [libs/stevenmaguire-oauth2-keycloak.md](libs/stevenmaguire-oauth2-keycloak.md) |
| mpdf/mpdf | Utilisé | 2 fichiers | vactory_html2pdf, vactory_quiz_certificat | Aucune | Moyen | P2 | — (correctif code temp dir `26860b66`) | [libs/mpdf-mpdf.md](libs/mpdf-mpdf.md) |
| commerceguys/addressing | Utilisé | 1 plugin + 1 config | vactory_decoupled, vactory_business | Aucune | Moyen | P2 | — | [libs/commerceguys-addressing.md](libs/commerceguys-addressing.md) |
| symfony/finder | Utilisé | 3 fichiers runtime + 1 script | vactory_dynamic_field, vactory_dynamic_field_dummy, scripts/githooks | Aucune | Faible (API) / Élevé (contrainte bloque D11) | P2 | — | [libs/symfony-finder.md](libs/symfony-finder.md) |
| voidagency/cloudinary | Utilisé | ~6 fichiers PHP + 3 configs | vactory_cloudinary, vactory_banner, vactory_dynamic_field, vactory_onboarding_* | Aucune | Élevé | P1 | retrait/réajout `bc26063a`/`32bc6377`, fork ×2 `5245d29c`, `f1c27559`, patch ×4 (D9, media library, D10, Symfony 6) | [libs/voidagency-cloudinary.md](libs/voidagency-cloudinary.md) |
| voidagency/jquery_colorpicker | Utilisé | 1 PHP + 7 info.yml + ~10 displays + 5 widgets DF | vactory_core, vactory_paragraphs, vactory_dynamic_field, vactory_banner, vactory_annual_report, … | Aucune | Moyen | P1 | fork ×3 (b-khouy `44ac302b`, voidagency `f1c27559`, issue D11 `ad27bf3e` hors 3.x), pin 5.0.0 `39fc2b27`, patch ×5 (dont default color `ff4213e0`→`b9bcd537`), suppression `dcc26dea` hors 3.x | [libs/voidagency-jquery_colorpicker.md](libs/voidagency-jquery_colorpicker.md) |
| voidagency/shortcode | Utilisé | 1 plugin | vactory_ads | Aucune | Faible | P2 | patch D10 `f97c0dae`, fork ×2 `a5838923`, `f1c27559` | [libs/voidagency-shortcode.md](libs/voidagency-shortcode.md) |
| voidagency/yaml_editor | Indirect | 2 info.yml + 2 attributs | vactory_dynamic_field, vactory_migrate_ui | Aucune | Moyen (doublon `drupal/yaml_editor`) | P2 | patch D10 `b182d404`, fork ×2 `ab4042cd`, `f1c27559` (+ `drupal/yaml_editor` réajouté `10bbf6f7`) | [libs/voidagency-yaml_editor.md](libs/voidagency-yaml_editor.md) |
| league/csv | Indirect | 1 `use` mort + 17 migrations csv + 1 vue | 12 modules (migrations), vactory_appointment | Aucune | Faible | P3 | — (épinglé 9.16.0 dès l'ajout `9cbd99aa`, sans incident) | [libs/league-csv.md](libs/league-csv.md) |
| google/apiclient | Non utilisé | 0 | — | — | Faible (mais tire google/auth, apiclient-services, phpseclib) | — | — | 0 occurrence de `Google_`, `Google\`, `google_client` dans modules/src/scripts ; aucun paquet du lock ne le requiert |
| commerceguys/intl | Non utilisé | 0 | — | — | Faible | — | — | 0 occurrence de `CommerceGuys\Intl` ; aucun paquet du lock ne le requiert |
| webflo/drupal-finder | Non utilisé (transitif) | 0 | — | — | Faible | — | — | 0 occurrence de `DrupalFinder` ; requis par `drupal/upgrade_status` et `mglaman/phpstan-drupal` |
| masterminds/html5 | Non utilisé (transitif) | 0 | — | — | Faible | — | retrait `e0f2dfd8`, réajout `1be86d53`, **downgrade** 2.3.1 → 2.3.0 `48493173` (aligner sur core-recommended), `308ccdd9` ^2.3 | 0 occurrence de `Masterminds`, `new HTML5` ; requis par `drupal/core` (`^2.7`), `lullabot/amp`, `querypath/querypath` |
| lullabot/amp | Non utilisé (transitif) | 0 | — | — | Faible | — | *hors 3.x* pin 2.0.3 `f3896911` « Fix lullabot/amp 2.0.4 compatibility error » (origin/2.x) | 0 occurrence de `Lullabot` ; requis par `drupal/amp`, lui-même commenté dans le profil (`vactory_starter_kit.info.yml:149` « handled in fontend ») |
| voidagency/easy_install | Non utilisé | 0 | — | — | Faible | — | patch D10 `ec1ae4e6`, fork b-khouy `3ea0e16e` → voidagency `f1c27559`, *hors 3.x* fork d'issue D11 `429b9d5b` | 0 occurrence de `easy_install` hors composer.json (modules, config, profil) |
| voidagency/mobile_native_share | Non utilisé | 0 | — | — | Faible | — | patch D10 `e584610a`, fork `651acf89` → `f1c27559`, *hors 3.x* « remove the fork » `fc3ea2db` | 0 occurrence de `mobile_native_share` hors composer.json |
| voidagency/amptheme | Non utilisé | 0 | — | — | Faible | — | patch D10 `ca1115c4`, fork `75a58335` → `f1c27559`, `drupal/amptheme` réajouté `10bbf6f7` (doublon) | seule trace : commentaire `vactory_starter_kit.info.yml:196` ; **doublon** avec `drupal/amptheme ^3.1` (même nom de thème `amptheme`, collision d'install possible) |

## JSON:API, OAuth, auth, sécurité

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Incidents historiques | Fiche |
|---|---|---|---|---|---|---|---|---|
| drupal/jsonapi_extras | Utilisé | 13 classes étendues + 2 services/API + 85 configs ressources + 12 info.yml | vactory_decoupled (+webform), vactory_jsonapi_extras, vactory_sondage, vactory_diff_content, 25 modules métier (config) | Partielle | Élevé | P1 | patch Symfony 6 ajouté/retiré `d6862dbb`/`74327c95`, patch sous clé erronée `drupal/jsonapi_extra` `9904f3e2` | [fiche](drupal/api-auth/drupal-jsonapi_extras.md) |
| drupal/jsonapi_search_api | Utilisé | 1 classe (`IndexResource`) + 3 configs facets + 1 info | vactory_jsonapi_extras, vactory_decoupled_facets | Aucune | Moyen | P1 | patch cache facettes ajouté/retiré 5× (`90415c26`, `505b3787`, `502ff49b`, `2802e861`, `a12eae5c`), pin 1.0.0-rc3 `76ae483f` « version vs patch » | [fiche](drupal/api-auth/drupal-jsonapi_search_api.md) |
| drupal/jsonapi_user_resources | Indirect | 1 (info.yml) | vactory_decoupled | Aucune | Faible | P3 | — (alpha4 épinglé à l'ajout) | [fiche](drupal/api-auth/drupal-jsonapi_user_resources.md) |
| drupal/jsonapi_cross_bundles | Utilisé | 2 décorateurs empilés + 1 `instanceof` + 3 info | vactory_jsonapi_cross_bundles, vactory_decoupled, vactory_decoupled_cross_content, vactory_diff | Partielle | Élevé | P1 | patch gist `74528ad0` ; montée ^1.2 pour bug cache #60771 | [fiche](drupal/api-auth/drupal-jsonapi_cross_bundles.md) |
| drupal/jsonapi_include | Utilisé | 4 (subscriber, form, config, info) | vactory_jsonapi_extras | Aucune | Moyen | P2 | — | [fiche](drupal/api-auth/drupal-jsonapi_include.md) |
| drupal/decoupled_router | Indirect | 2 (info.yml) | vactory_decoupled, vactory_decoupled_router | Aucune (paquet) | Faible | P3 | — | [fiche](drupal/api-auth/drupal-decoupled_router.md) |
| drupal/simple_oauth | Utilisé | ~25 (2 classes étendues, 2 services remplacés, 2 grants, 1 route surchargée, 2 hooks, 4 configs) | vactory_decoupled, vactory_faceid_auth, vactory_oauth_apikey, vactory_phone_auth_decoupled, vactory_video_ask | Partielle | Élevé | P1 | patch ×3 dont « Registered claims error after upgrading 5.0.5 → 5.2.0 » `3d863c2c` | [fiche](drupal/api-auth/drupal-simple_oauth.md) |
| drupal/simple_oauth_revoke | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `simple_oauth_revoke`, `oauth/revoke` (PHP, yml, config, info.yml) |
| drupal/consumers | Indirect | 2 (+ test) | via simple_oauth ; vactory_taxonomy_results | Partielle | Moyen | P1 | pin 1.20.0 `332e0cad` « fix consumers version », pin 1.24.0 `f337dbc2` | [fiche](drupal/api-auth/drupal-consumers.md) |
| drupal/oauth_login_oauth2 | Utilisé | 4 (classe instanciée, route, config, session) | vactory_sso | Aucune | Élevé | P1 | patch session `22b235d7` réécrit 4× | [fiche](drupal/api-auth/drupal-oauth_login_oauth2.md) |
| drupal/social_api | Utilisé | 4 | vactory_keycloak, vactory_user, vactory_social_auth | Aucune | Moyen | P2 | — | [fiche](drupal/api-auth/drupal-social_api.md) |
| drupal/social_auth | Utilisé | 5 classes étendues + 3 services + 2 info | vactory_decoupled, vactory_keycloak, vactory_social_auth | Aucune | Élevé | P1 | — (correctif code #47474 `e7d331b6`) | [fiche](drupal/api-auth/drupal-social_auth.md) |
| drupal/social_auth_facebook | Indirect | 2 configs + 1 info | vactory_social_auth | Aucune | Faible | P3 | — | [fiche](drupal/api-auth/drupal-social_auth_facebook.md) |
| drupal/social_auth_google | Indirect | 2 configs + 1 info | vactory_social_auth | Aucune | Faible | P3 | — | [fiche](drupal/api-auth/drupal-social_auth_google.md) |
| drupal/email_registration | Utilisé | 3 (2 fonctions ×2 appels, 1 config) | vactory_decoupled, vactory_decoupled_espace_prive, vactory_user | Aucune | Moyen | P1 | patch auth mail/OAuth2 `9eb3ca69` (retiré `ab59b8f6`) | [fiche](drupal/api-auth/drupal-email_registration.md) |
| drupal/persistent_login | Indirect | 1 (info.yml) + profil | vactory_user | Aucune | Faible | P3 | — | [fiche](drupal/api-auth/drupal-persistent_login.md) |
| drupal/nocurrent_pass | Indirect | profil seulement | — | Aucune | Faible | P2 | patch D9 `1be86d53`, patch correctif « Settings are not applied » (retiré `0648d2b8`, `312057d7`) | [fiche](drupal/api-auth/drupal-nocurrent_pass.md) |
| drupal/user_registrationpassword | Utilisé | ~10 (constantes, fonction `_…mail_notify`, mail ID, 3 clés config, token) | vactory_decoupled, vactory_decoupled_espace_prive | Aucune | Élevé | P1 | — | [fiche](drupal/api-auth/drupal-user_registrationpassword.md) |
| drupal/password_policy | Utilisé | 2 (service validator, contrainte) + 2 configs | vactory_decoupled_espace_prive, profil | Aucune | Moyen | P1 | patch « field_last_password_reset is unknown » `942502c0` | [fiche](drupal/api-auth/drupal-password_policy.md) |
| drupal/login_security | Non utilisé | 0 | — | — | — | — | retiré du profil `8e3a870f`, `advban` ajouté « for login_security » `6312f50f` (2026-04-20) | Commenté dans `vactory_starter_kit.info.yml:137` ; 0 occurrence de `login_security` ailleurs |
| drupal/flood_control | Utilisé | 3 (form étendu, route remplacée, `moduleExists`) | vactory_flood_control, vactory_decoupled | Partielle | Moyen | P2 | — | [fiche](drupal/api-auth/drupal-flood_control.md) |
| drupal/captcha | Utilisé | ~8 (SQL direct `captcha_sessions`, constante, permission, 5 configs) | vactory_decoupled_webform, vactory_decoupled, vactory_job_ads, 4 webforms | Partielle | Élevé | P1 | patch `cef2d01a`, fork `bkhouy/captcha` `343123cd`, revert `65d3d511` | [fiche](drupal/api-auth/drupal-captcha.md) |
| drupal/recaptcha | Utilisé | 4 (classe `Drupal8Post` ×2, contrainte entité, 4 webforms) | vactory_decoupled | Partielle | Élevé | P1 | fork `bkhouy/recaptcha` `52b8aa35`, revert `65d3d511` | [fiche](drupal/api-auth/drupal-recaptcha.md) |
| drupal/seckit | Indirect | 2 (profil, drush dev) | vactory_core | Aucune | Faible | P3 | — | [fiche](drupal/api-auth/drupal-seckit.md) |
| drupal/shield | Utilisé | 2 (contrôleur `/api/check-shield`, config profil) | vactory_decoupled, profil | Aucune | Moyen | P2 | — | [fiche](drupal/api-auth/drupal-shield.md) |
| drupal/security_review | Utilisé | 7 checks + form étendu + route + 2 lectures config | vactory_security_review, vactory_decoupled, vactory_devtools | Aucune | Moyen | P3 | — | [fiche](drupal/api-auth/drupal-security_review.md) |
| drupal/advban | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `advban` (PHP, yml, config, info.yml) ; absent de `vendor/drupal/` |
| drupal/domain | Utilisé (optionnel) | 2 (guardés par `moduleExists`) | vactory_menu_breadcrumb, vactory_extended_seo | Aucune | Faible | P2 | patch correctif « no context: resolve null given » (≤ `ae0e3a00`, retiré `ca1115c4`) | [fiche](drupal/api-auth/drupal-domain.md) |
| drupal/entity | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `Drupal\entity\`, `entity:entity`, `entity.query_access` ; aucun paquet du lock ne le requiert |
| drupal/ctools | Indirect | profil seulement | — | Aucune | Faible | P2 | pin ~3.9.0 `7fb17f4d` « Ctools 3.10.x version error » | [fiche](drupal/api-auth/drupal-ctools.md) |
| drupal/token | Utilisé | 8 (`token_tree_link`) + 3 info | vactory_jsonapi_extras, vactory_dynamic_field, vactory_notifications, vactory_datalayer_*, vactory_wisepops, vactory_quiz_certificat | Aucune | Faible | P3 | — | [fiche](drupal/api-auth/drupal-token.md) |
| drupal/views_json_source | Indirect | 1 vue (3 champs) + 1 info | vactory_wysiwyg_301to200 | Aucune | Faible | P3 | — | [fiche](drupal/api-auth/drupal-views_json_source.md) |
| drupal/default_content | Indirect | 9 info.yml + 223 fichiers `content/` | 7 modules *_demo, vactory_welcome, vactory_report_content | Aucune | Moyen | P3 | — | [fiche](drupal/api-auth/drupal-default_content.md) |
| drupal/single_content_sync | Utilisé | 1 plugin + 2 services + 2 info | vactory_single_content_sync_extra, vactory_diff | Partielle | Moyen | P2 | — | [fiche](drupal/api-auth/drupal-single_content_sync.md) |
| drupal/health_check | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `health_check`, `healthcheck` (modules, config, scripts, Dockerfile) |
| drupal/memcache | Indirect | profil seulement | — | Aucune | Moyen (infra) | P3 | — | [fiche](drupal/api-auth/drupal-memcache.md) |

## Contenu, médias, search, SEO

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Incidents historiques | Fiche |
|---|---|---|---|---|---|---|---|---|
| drupal/paragraphs | Utilisé | ~25 PHP + 35 config | vactory_core, vactory_paragraphs, vactory_decoupled, vactory_dynamic_field, vactory_page_import, vactory_content_package, vactory_paragraph_clipboard, 9× .install | Partielle | Élevé | P1 | patch `035dd9f3` | [fiche](drupal/content-media-search-seo/drupal-paragraphs.md) |
| drupal/entity_reference_revisions | Utilisé | ~20 PHP (`target_revision_id`) + 29 config | vactory_field, vactory_decoupled, vactory_content_collector, types de contenu | Partielle | Élevé | P1 | — | [fiche](drupal/content-media-search-seo/drupal-entity_reference_revisions.md) |
| drupal/paragraphs_edit | Utilisé | 4 | vactory_dynamic_field | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-paragraphs_edit.md) |
| drupal/block_field | Utilisé | 2 PHP + 15 config | vactory_core, vactory_field, vactory_paragraphs, vactory_banner | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-block_field.md) |
| drupal/block_class | Indirect | 5 (info.yml) | profil, vactory_header/banner/footer/search_overlay | Aucune | Faible | P3 | pin `9cbd99aa` | [fiche](drupal/content-media-search-seo/drupal-block_class.md) |
| drupal/block_list_override | Indirect | 1 (profil) | profil | Aucune | Faible | P2 | patch `cbe92e58`; retrait `07b911d9`; fork `07b911d9` `f1c27559`; remplacement `07b911d9` `f1c27559` `7759f845` | [fiche](drupal/content-media-search-seo/drupal-block_list_override.md) |
| drupal/entityqueue | Utilisé | 3 PHP + 55 config | vactory_decoupled, vactory_dynamic_field, vactory_core, 10 modules de contenu | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-entityqueue.md) |
| drupal/auto_entityqueue | Indirect | 21 config | 8 modules de contenu | Aucune | Faible | P1 | patch `72e5d710` | [fiche](drupal/content-media-search-seo/drupal-auto_entityqueue.md) |
| drupal/inline_entity_form | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `inline_entity_form` (code, config, info.yml) |
| drupal/field_group | Indirect | 12 config | vactory_paragraphs, vactory_locator, +5 | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-field_group.md) |
| drupal/address | Utilisé | 3 PHP + 12 config | vactory_decoupled, vactory_google_places, vactory_locator, vactory_governance, vactory_business | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-address.md) |
| drupal/faqfield | Indirect | 4 config | vactory_faq | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-faqfield.md) |
| drupal/time_range | Indirect | 1 config | vactory_welcome | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-time_range.md) |
| drupal/geofield | Utilisé | 2 PHP + 7 config | vactory_locator(_decoupled), vactory_content_localizer | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-geofield.md) |
| drupal/scheduler | Utilisé | 1 PHP + ~45 config | vactory_announcements, ~25 modules de contenu | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-scheduler.md) |
| drupal/workbench_moderation | Indirect | 1 config | vactory_toolbox | Aucune | Moyen | P3 | — | [fiche](drupal/content-media-search-seo/drupal-workbench_moderation.md) |
| drupal/flag | Utilisé | ~12 PHP + 1 config | vactory_decoupled_flag, vactory_academy, vactory_points, vactory_ntcc, vactory_social_network | Aucune | Élevé | P1 | patch `894e4058` | [fiche](drupal/content-media-search-seo/drupal-flag.md) |
| drupal/rate | Utilisé | ~7 | vactory_rate, vactory_academy | Aucune | Moyen | P1 | patch `22b235d7` `1be86d53` `a176f28d` `6915227d` `26bcbc6f`; downgrade `a176f28d` `6915227d`; revert `a176f28d` `6915227d`; pin `c176cf9d` | [fiche](drupal/content-media-search-seo/drupal-rate.md) |
| drupal/votingapi | Utilisé | 2 fichiers PHP | vactory_rate | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-votingapi.md) |
| drupal/merge_translations | Indirect | 1 (profil) | profil | Aucune | Faible | P2 | patch `1cbde7df`; retrait `8a9ab9ce`; fork `8a9ab9ce` `f1c27559`; remplacement `8a9ab9ce` `f1c27559` `f337dbc2` | [fiche](drupal/content-media-search-seo/drupal-merge_translations.md) |
| drupal/lang_dropdown | Utilisé | 1 PHP + profil | vactory_core | Aucune | Faible | P1 | patch `4733f4b0` | [fiche](drupal/content-media-search-seo/drupal-lang_dropdown.md) |
| drupal/file_entity | Utilisé | 4 PHP + 12 config | vactory_cloudinary, vactory_private_files_decoupled, profil | Aucune | Élevé | P1 | patch `ca604782` `0965d660`; régression patch `22449f5b` | [fiche](drupal/content-media-search-seo/drupal-file_entity.md) |
| drupal/focal_point | Indirect | 18 config | profil (image styles), vactory_media_image_focal_point, +4 | Aucune | Moyen | P2 | patch `1be86d53` | [fiche](drupal/content-media-search-seo/drupal-focal_point.md) |
| drupal/crop | Indirect | 2 config + profil | vactory_decoupled, vactory_dynamic_field_video_help | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-crop.md) |
| drupal/image_effects | Indirect | 1 (info.yml) | vactory_decoupled_image_styles | Aucune | Faible | — | — | [fiche](drupal/content-media-search-seo/drupal-image_effects.md) |
| drupal/imagemagick | Indirect | 1 (info.yml) | vactory_decoupled_image_styles | Aucune | Faible | — | — | [fiche](drupal/content-media-search-seo/drupal-imagemagick.md) |
| drupal/media_responsive_thumbnail | Indirect | 24 config | 13 modules de contenu | Aucune | Moyen | P3 | pin `1be86d53` | [fiche](drupal/content-media-search-seo/drupal-media_responsive_thumbnail.md) |
| drupal/media_library_form_element | Utilisé | ~15 PHP | vactory_dynamic_field, vactory_locator, vactory_video_ask, vactory_satisfaction, vactory_sondage | Partielle | Élevé | P1 | patch `1be86d53` `ac773590` | [fiche](drupal/content-media-search-seo/drupal-media_library_form_element.md) |
| drupal/dropzonejs | Utilisé | 1 PHP | vactory_core | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-dropzonejs.md) |
| drupal/tinypng | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `tinypng` (code, config, info.yml) |
| drupal/filefield_paths | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `filefield_paths` |
| drupal/entity_embed | Indirect | 1 config | vactory_dynamic_field_unitegallery | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-entity_embed.md) |
| drupal/video_embed_field | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `video_embed`/`VideoEmbed` hors 2 README (vactory_testimonials, vactory_academy) |
| drupal/search_api | Utilisé | ~10 PHP + 9 config | vactory_decoupled_search, vactory_search, vactory_frequent_searches, vactory_jsonapi_extras, vactory_faq, vactory_forums | Partielle | Élevé | P1 | patch `1be86d53` `07765dd6` `61a5887c` `826865f2` | [fiche](drupal/content-media-search-seo/drupal-search_api.md) |
| drupal/search_api_autocomplete | Utilisé | 1 PHP + 2 config | vactory_search_autosuggestions, vactory_faq | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-search_api_autocomplete.md) |
| drupal/search_api_exclude_entity | Indirect | 6 config | vactory_field, vactory_page, vactory_search, vactory_faq | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-search_api_exclude_entity.md) |
| drupal/search_api_ai | Utilisé | 1 fichier PHP | vactory_decoupled_search_ai | Aucune | Élevé | P1 | patch `b1629c16` | [fiche](drupal/content-media-search-seo/drupal-search_api_ai.md) |
| drupal/facets | Indirect | 3 config + 1 import mort | vactory_decoupled_facets, vactory_locator_decoupled | Aucune | Faible | P3 | — | [fiche](drupal/content-media-search-seo/drupal-facets.md) |
| drupal/metatag | Utilisé | ~15 PHP + 42 config | vactory_decoupled, vactory_decoupled_search, vactory_locator_decoupled, vactory_extended_seo, vactory_core, vactory_field, profil | Partielle | Élevé | P1 | — | [fiche](drupal/content-media-search-seo/drupal-metatag.md) |
| drupal/yoast_seo | Indirect | 54 config | vactory_field + ~18 modules de contenu | Aucune | Moyen | P3 | — | [fiche](drupal/content-media-search-seo/drupal-yoast_seo.md) |
| drupal/pathauto | Utilisé | 2 PHP + 20 config | vactory_multivers, vactory_help_center, modules de contenu, profil | Partielle | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-pathauto.md) |
| drupal/redirect | Utilisé | 1 PHP + config | vactory_decoupled_router, vactory_core, profil | Aucune | Élevé | P1 | — | [fiche](drupal/content-media-search-seo/drupal-redirect.md) |
| drupal/path_redirect_import | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `path_redirect_import` |
| drupal/xmlsitemap | Utilisé | 3 PHP + config | vactory_core, profil, vactory_dynamic_field_dummy | Aucune | Moyen | P2 | pin `1be86d53` | [fiche](drupal/content-media-search-seo/drupal-xmlsitemap.md) |
| drupal/sitemap | Indirect | 1 (profil) | profil | Aucune | Faible | — | — | [fiche](drupal/content-media-search-seo/drupal-sitemap.md) |
| drupal/google_news_sitemap | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `googlenews_sitemap`/`google_news_sitemap`/`googlenews`; version `dev-1.0.x` instable |
| drupal/hreflang | Utilisé | 3 | vactory_hreflang, profil | Aucune | Moyen | P2 | — | [fiche](drupal/content-media-search-seo/drupal-hreflang.md) |
| drupal/google_tag | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `google_tag`/`googletag` |
| drupal/amp | Utilisé | 4 | vactory_amp (non installé par le profil), vactory_dynamic_field | Aucune | Moyen | P1 | patch `894e4058` | [fiche](drupal/content-media-search-seo/drupal-amp.md) |
| drupal/amptheme | Non utilisé | 0 | — | — | — | — | patch `ca1115c4`; retrait `75a58335`; fork `75a58335` `f1c27559`; remplacement `75a58335` `f1c27559` | Seule trace : commentaire `vactory_starter_kit.info.yml:196` (`# - amptheme > handled in fontend`) |
| drupal/rdf | Indirect | 2 (profil + config) | profil | Aucune | Moyen | P3 | — | [fiche](drupal/content-media-search-seo/drupal-rdf.md) |

## Formulaires, migrate, views, admin, UI

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Incidents historiques | Fiche |
|---|---|---|---|---|---|---|---|---|
| drupal/webform | Utilisé | ~70 (16 info.yml, 15 config, 6 handlers, controller, normalizer, ~15 .module) | vactory_decoupled_webform, vactory_webform_custom (+6 sous-modules), vactory_webform_anonymize, vactory_webform_typesafe, vactory_event_relance, vactory_job_ads, vactory_tender, … | Partielle | Élevé | P1 | patch `1be86d53` | [fiche](drupal/forms-admin-ui/drupal-webform.md) |
| drupal/webform_import_tab | Indirect | 1 | profil | Aucune | Moyen (dev branch) | P3 | — | [fiche](drupal/forms-admin-ui/drupal-webform_import_tab.md) |
| drupal/mailsystem | Indirect | 6 | profil (config), docblocks ×4 | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-mailsystem.md) |
| drupal/symfony_mailer | Utilisé | 5 | vactory_core, vactory_decoupled, vactory_multivers, profil | Aucune | Élevé | P1 | remplacement `f3a80371` | [fiche](drupal/forms-admin-ui/drupal-symfony_mailer.md) |
| drupal/mailchimp | Utilisé | 9 | vactory_mailchimp, vactory_mailchimp_newsletter | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-mailchimp.md) |
| drupal/onesignal | Utilisé | 4 | vactory_onesignal | Aucune | Moyen | P2 | fork `0749699f` | [fiche](drupal/forms-admin-ui/drupal-onesignal.md) |
| drupal/openai | Utilisé | 1 | vactory_decoupled_search_ai | Aucune | Élevé (déjà cassé : `openai_embeddings.pinecone_client` absent en beta4) | P2 | — | [fiche](drupal/forms-admin-ui/drupal-openai.md) |
| drupal/migrate_plus | Utilisé | ~30 (8 PHP, 4 info.yml, 17 migrations livrées) | vactory_migrate (+dynamic_import, migrate_plugin, migrate_ui), vactory_locator, vactory_user_migrate, 10 modules contenu | Aucune | Élevé | P1 | — | [fiche](drupal/forms-admin-ui/drupal-migrate_plus.md) |
| drupal/migrate_tools | Utilisé | 7 | vactory_migrate, vactory_migrate_ui, vactory_dynamic_import, vactory_locator, vactory_user_migrate | Aucune | Élevé | P1 | pin `1be86d53`; patch `ae0e3a00` | [fiche](drupal/forms-admin-ui/drupal-migrate_tools.md) |
| drupal/migrate_source_csv | Utilisé | ~23 (3 PHP, 4 info.yml, 17 migrations) | vactory_migrate*, vactory_locator, vactory_user_migrate, modules contenu | Aucune | Élevé | P1 | — | [fiche](drupal/forms-admin-ui/drupal-migrate_source_csv.md) |
| drupal/migrate_file | Utilisé | 5 | vactory_migrate, vactory_dynamic_import, vactory_migrate_plugin | Aucune | Élevé | P1 | — | [fiche](drupal/forms-admin-ui/drupal-migrate_file.md) |
| drupal/taxonomy_import | Non utilisé | 0 | — (seulement `vactory_event/README.md:5-6` : « activer puis désactiver ») | — | — | — | — | 0 occurrence dans info.yml, config, PHP, twig |
| drupal/csv_serialization | Indirect | 1 | vactory_appointment (config) | Aucune | Faible | P1 | patch `ec1ae4e6`; pin `9cbd99aa` | [fiche](drupal/forms-admin-ui/drupal-csv_serialization.md) |
| drupal/views_data_export | Utilisé | 4 | vactory_appointment, vactory_migrate_ui | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-views_data_export.md) |
| drupal/views_bulk_operations | Utilisé | 4 | vactory_locator | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-views_bulk_operations.md) |
| drupal/views_bulk_edit | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `views_bulk_edit` (info.yml, config, PHP) |
| drupal/views_autocomplete_filters | Utilisé | 7 | vactory_google_places, vactory_locator, vactory_smart_search, vactory_faq_prive | Aucune | Moyen (patch JS once) | P1 | patch `614b9330` | [fiche](drupal/forms-admin-ui/drupal-views_autocomplete_filters.md) |
| drupal/views_infinite_scroll | Indirect | 4 | vactory_mur, vactory_social_network | Aucune | Faible | P3 | downgrade `1fdfb044` | [fiche](drupal/forms-admin-ui/drupal-views_infinite_scroll.md) |
| drupal/views_url_alias | Indirect | 2 | profil (vue `content`) | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-views_url_alias.md) |
| drupal/viewsreference | Utilisé | 6 | vactory_paragraphs, profil | Aucune | Moyen | P1 | pin `1be86d53`; patch `1be86d53` | [fiche](drupal/forms-admin-ui/drupal-viewsreference.md) |
| drupal/draggableviews | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `draggableviews` hors `translations/*.po` |
| drupal/better_exposed_filters | Utilisé | ~31 (25 vues, 5 info.yml, 1 .module) | vactory_views + 25 modules à vues | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-better_exposed_filters.md) |
| drupal/admin_toolbar | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-admin_toolbar.md) |
| drupal/coffee | Indirect | 1 | profil | Aucune | Faible | P1 | patch `1be86d53` | [fiche](drupal/forms-admin-ui/drupal-coffee.md) |
| drupal/gin | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `gin` (thème non listé dans `themes:` du profil, admin = claro via `config/install/system.theme.yml:1`) |
| drupal/seven | Utilisé (installeur) | 2 | profil (`distribution.install.theme`), vactory_appointment (code mort) | Partielle (`drush si` CI) | Moyen | P3 | — | [fiche](drupal/forms-admin-ui/drupal-seven.md) |
| drupal/classy | Indirect | 16 | 15 modules (`attach_library('classy/node')`) | Aucune | Moyen | P3 | — | [fiche](drupal/forms-admin-ui/drupal-classy.md) |
| drupal/bartik | Non utilisé | 0 | — | — | — | — | — | 0 occurrence ; seul `vendor/drupal/amptheme/bartik_amp` en fait sa base theme, AMP commenté dans le profil |
| drupal/quickedit | Non utilisé | 0 | — | — | — | — | — | Commenté `vactory_starter_kit.info.yml:25` ; seule trace : clé d'annotation `quickedit` dans `vactory_field/src/Plugin/Field/FieldFormatter/VactoryExcerptFormatter.php:20` (inerte) |
| drupal/theme_switcher | Utilisé | 7 | vactory_decoupled_theme_switcher, vactory_multivers | Aucune | Moyen | P1 | patch `7b6c83eb` | [fiche](drupal/forms-admin-ui/drupal-theme_switcher.md) |
| drupal/admin_feedback | Utilisé | 6 | vactory_content_feedback, vactory_points | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-admin_feedback.md) |
| drupal/styleguide | Indirect | 1 | vactory_devtools | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-styleguide.md) |
| drupal/devel | Indirect | 3 | vactory_devtools, vactory_core (drush), profil (config mailsystem) | Aucune | Faible | P3 | pin `1be86d53` | [fiche](drupal/forms-admin-ui/drupal-devel.md) |
| drupal/upgrade_status | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `upgrade_status` (info.yml, config, PHP) |
| drupal/backup_migrate | Indirect | 5 | profil, vactory_decoupled (4 resource configs JSON:API désactivées) | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-backup_migrate.md) |
| drupal/config_update | Indirect | 0 (transitif via features) | — | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-config_update.md) |
| drupal/features | Indirect | ~28 (23 `*.features.yml`, 2 info.yml) | profil, vactory_decoupled, vactory_social_auth | Aucune | Faible | P3 | patch `22b235d7` | [fiche](drupal/forms-admin-ui/drupal-features.md) |
| drupal/ultimate_cron | Indirect | 8 | 6 modules (jobs), vactory_decoupled, profil | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-ultimate_cron.md) |
| drupal/stage_file_proxy | Indirect | 2 | vactory_devtools, vactory_core (drush) | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-stage_file_proxy.md) |
| drupal/log_stdout | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-log_stdout.md) |
| drupal/yaml_editor | Utilisé | 4 | vactory_dynamic_field, vactory_migrate_ui | Aucune | Faible | P2 | patch `b182d404`; retrait `ab4042cd`; fork `ab4042cd` `f1c27559`; remplacement `ab4042cd` `f1c27559` | [fiche](drupal/forms-admin-ui/drupal-yaml_editor.md) |
| drupal/components | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-components.md) |
| drupal/twig_tweak | Utilisé | ~155 (145 templates `image_style`, 7 `drupal_view`/`drupal_config`/`file_url`, 3 PHP) | vactory_core (service `vactory`), vactory_dynamic_field, vactory_frequent_searches, ~10 modules à templates | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-twig_tweak.md) |
| drupal/twig_field_value | Indirect | 2 | profil, vactory_core (info.yml) | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-twig_field_value.md) |
| drupal/twig_vardumper | Indirect | 2 | vactory_devtools, vactory_core (drush) | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-twig_vardumper.md) |
| drupal/colorapi | Utilisé | ~20 | vactory_field, vactory_paragraphs, vactory_banner, vactory_annual_report, vactory_paragraphs_bg_wrapper, vactory_content_package | Aucune | Moyen | P1 | patch `1be86d53` `ae0e3a00` `39fc2b27` (montée couplée au fork `voidagency/jquery_colorpicker`) | [fiche](drupal/forms-admin-ui/drupal-colorapi.md) |
| drupal/chosen | Non utilisé | 0 | — | — | — | — | — | 0 occurrence de `chosen` comme module ; seule trace : `jquery.chosen` exclu dans `vactory_webform_custom/config/optional/webform.settings.yml:327` |
| drupal/improved_multi_select | Indirect | 7 | profil, vactory_notifications, vactory_cross_content, vactory_google_places, vactory_entity_canonical_access, vactory_points | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-improved_multi_select.md) |
| drupal/jquery_ui_checkboxradio | Non utilisé | 0 | — | — | — | — | — | 0 occurrence ; requis seulement par `webform_jqueryui_buttons` (non activé) |
| drupal/multiple_fields_remove_button | Indirect | 1 | vactory_quiz | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-multiple_fields_remove_button.md) |
| drupal/textfield_counter | Utilisé | 6 | vactory_core, vactory_dynamic_field, vactory_welcome, vactory_content_package | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-textfield_counter.md) |
| drupal/allowed_formats | Indirect | 7 | vactory_business, vactory_project_progress (+ help_center, seo) | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-allowed_formats.md) |
| drupal/linkit | Utilisé | 8 | vactory_dynamic_field, profil (filtres/éditeurs), vactory_decoupled | Aucune | Moyen | P1 | pin `dc656dbf` `f337dbc2`; downgrade `776f9ae0` | [fiche](drupal/forms-admin-ui/drupal-linkit.md) |
| drupal/link_target | Indirect | 1 | profil | Aucune | Faible | P1 | patch `1be86d53` `ca604782` | [fiche](drupal/forms-admin-ui/drupal-link_target.md) |
| drupal/link_attributes | Non utilisé | 0 | — | — | — | — | pin `9cbd99aa` | Module non installé ; seule trace défensive `vactory_core.module:308-311` (unset du widget s'il existe) ; les clés `link_attributes: {}` des displays sont un réglage de social_media_links |
| drupal/editor_advanced_link | Indirect | 3 | profil (éditeurs) | Aucune | Faible | P3 | pin `63d2b3f6` | [fiche](drupal/forms-admin-ui/drupal-editor_advanced_link.md) |
| drupal/menu_link_attributes | Utilisé | 3 | profil, vactory_core | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-menu_link_attributes.md) |
| drupal/menu_item_extras | Utilisé | 6 | vactory_decoupled, vactory_toolbox, vactory_header | Partielle (BreadcrumbTest, indirect) | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-menu_item_extras.md) |
| drupal/simplify_menu | Utilisé | 16 (11 templates, 2 PHP, 3 info.yml) | vactory_core, vactory_header, vactory_espace_prive, vactory_toolbox, vactory_footer | Aucune | Moyen | P1 | patch `22b235d7` `6f19c294` | [fiche](drupal/forms-admin-ui/drupal-simplify_menu.md) |
| drupal/menu_manipulator | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-menu_manipulator.md) |
| drupal/smart_trim | Indirect | ~20 (19 displays) | 8 modules contenu | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-smart_trim.md) |
| drupal/responsive_preview | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-responsive_preview.md) |
| drupal/responsive_favicons | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-responsive_favicons.md) |
| drupal/minifyhtml | Indirect | 1 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-minifyhtml.md) |
| drupal/advagg | Indirect | 5 | profil (install + 4 configs) | Aucune | Moyen | P1 | patch `1be86d53` `894e4058` | [fiche](drupal/forms-admin-ui/drupal-advagg.md) |
| drupal/eu_cookie_compliance | Indirect | 2 | profil | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-eu_cookie_compliance.md) |
| drupal/addtoany | Utilisé | ~150 (149 displays, 2 hooks, 2 templates) | vactory_core + ~35 modules contenu | Aucune | Faible | P3 | — | [fiche](drupal/forms-admin-ui/drupal-addtoany.md) |
| drupal/social_media_links | Utilisé | ~16 | vactory_decoupled, vactory_governance, vactory_business, profil | Aucune | Moyen | P2 | — | [fiche](drupal/forms-admin-ui/drupal-social_media_links.md) |
