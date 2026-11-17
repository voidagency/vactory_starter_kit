# Matrice partielle — lot `libs`

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Fiche |
|---|---|---|---|---|---|---|---|
| firebase/php-jwt | Utilisé | 3 appels (1 fichier) | vactory_decoupled | Aucune | Élevé | P1 | [libs/firebase-php-jwt.md](../libs/firebase-php-jwt.md) |
| phpoffice/phpspreadsheet | Utilisé | 4 fichiers, ~30 appels | vactory_page_import, vactory_satisfaction, vactory_seo_noindex | Aucune | Moyen | P1 | [libs/phpoffice-phpspreadsheet.md](../libs/phpoffice-phpspreadsheet.md) |
| mailchimp/marketing | Utilisé | 2 fichiers, 6 appels | vactory_webform_mailchimp_handler, vactory_mailchimp_newsletter | Aucune | Moyen | P1 | [libs/mailchimp-marketing.md](../libs/mailchimp-marketing.md) |
| stevenmaguire/oauth2-keycloak | Utilisé | 4 fichiers | vactory_keycloak | Aucune | Moyen | P1 | [libs/stevenmaguire-oauth2-keycloak.md](../libs/stevenmaguire-oauth2-keycloak.md) |
| mpdf/mpdf | Utilisé | 2 fichiers | vactory_html2pdf, vactory_quiz_certificat | Aucune | Moyen | P2 | [libs/mpdf-mpdf.md](../libs/mpdf-mpdf.md) |
| commerceguys/addressing | Utilisé | 1 plugin + 1 config | vactory_decoupled, vactory_business | Aucune | Moyen | P2 | [libs/commerceguys-addressing.md](../libs/commerceguys-addressing.md) |
| symfony/finder | Utilisé | 3 fichiers runtime + 1 script | vactory_dynamic_field, vactory_dynamic_field_dummy, scripts/githooks | Aucune | Faible (API) / Élevé (contrainte bloque D11) | P2 | [libs/symfony-finder.md](../libs/symfony-finder.md) |
| voidagency/cloudinary | Utilisé | ~6 fichiers PHP + 3 configs | vactory_cloudinary, vactory_banner, vactory_dynamic_field, vactory_onboarding_* | Aucune | Élevé | P2 | [libs/voidagency-cloudinary.md](../libs/voidagency-cloudinary.md) |
| voidagency/jquery_colorpicker | Utilisé | 1 PHP + 7 info.yml + ~10 displays + 5 widgets DF | vactory_core, vactory_paragraphs, vactory_dynamic_field, vactory_banner, vactory_annual_report, … | Aucune | Moyen | P2 | [libs/voidagency-jquery_colorpicker.md](../libs/voidagency-jquery_colorpicker.md) |
| voidagency/shortcode | Utilisé | 1 plugin | vactory_ads | Aucune | Faible | P3 | [libs/voidagency-shortcode.md](../libs/voidagency-shortcode.md) |
| voidagency/yaml_editor | Indirect | 2 info.yml + 2 attributs | vactory_dynamic_field, vactory_migrate_ui | Aucune | Moyen (doublon `drupal/yaml_editor`) | P3 | [libs/voidagency-yaml_editor.md](../libs/voidagency-yaml_editor.md) |
| league/csv | Indirect | 1 `use` mort + 17 migrations csv + 1 vue | 12 modules (migrations), vactory_appointment | Aucune | Faible | P3 | [libs/league-csv.md](../libs/league-csv.md) |
| google/apiclient | Non utilisé | 0 | — | — | Faible (mais tire google/auth, apiclient-services, phpseclib) | — | 0 occurrence de `Google_`, `Google\`, `google_client` dans modules/src/scripts ; aucun paquet du lock ne le requiert |
| commerceguys/intl | Non utilisé | 0 | — | — | Faible | — | 0 occurrence de `CommerceGuys\Intl` ; aucun paquet du lock ne le requiert |
| webflo/drupal-finder | Non utilisé (transitif) | 0 | — | — | Faible | — | 0 occurrence de `DrupalFinder` ; requis par `drupal/upgrade_status` et `mglaman/phpstan-drupal` |
| masterminds/html5 | Non utilisé (transitif) | 0 | — | — | Faible | — | 0 occurrence de `Masterminds`, `new HTML5` ; requis par `drupal/core` (`^2.7`), `lullabot/amp`, `querypath/querypath` |
| lullabot/amp | Non utilisé (transitif) | 0 | — | — | Faible | — | 0 occurrence de `Lullabot` ; requis par `drupal/amp`, lui-même commenté dans le profil (`vactory_starter_kit.info.yml:149` « handled in fontend ») |
| voidagency/easy_install | Non utilisé | 0 | — | — | Faible | — | 0 occurrence de `easy_install` hors composer.json (modules, config, profil) |
| voidagency/mobile_native_share | Non utilisé | 0 | — | — | Faible | — | 0 occurrence de `mobile_native_share` hors composer.json |
| voidagency/amptheme | Non utilisé | 0 | — | — | Faible | — | seule trace : commentaire `vactory_starter_kit.info.yml:196` ; **doublon** avec `drupal/amptheme ^3.1` (même nom de thème `amptheme`, collision d'install possible) |
