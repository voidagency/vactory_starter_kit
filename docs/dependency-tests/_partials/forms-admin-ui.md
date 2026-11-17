# Matrice partielle — lot `forms-admin-ui`

Analyse sur `3.x` (lock courant). « Nb usages » = nombre de fichiers custom (code + config + info.yml) qui référencent le paquet. Fiches : `drupal/forms-admin-ui/`.

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Fiche |
|---|---|---|---|---|---|---|---|
| drupal/webform | Utilisé | ~70 (16 info.yml, 15 config, 6 handlers, controller, normalizer, ~15 .module) | vactory_decoupled_webform, vactory_webform_custom (+6 sous-modules), vactory_webform_anonymize, vactory_webform_typesafe, vactory_event_relance, vactory_job_ads, vactory_tender, … | Partielle | Élevé | P1 | [fiche](../drupal/forms-admin-ui/drupal-webform.md) |
| drupal/webform_import_tab | Indirect | 1 | profil | Aucune | Moyen (dev branch) | P3 | [fiche](../drupal/forms-admin-ui/drupal-webform_import_tab.md) |
| drupal/mailsystem | Indirect | 6 | profil (config), docblocks ×4 | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-mailsystem.md) |
| drupal/symfony_mailer | Utilisé | 5 | vactory_core, vactory_decoupled, vactory_multivers, profil | Aucune | Élevé | P1 | [fiche](../drupal/forms-admin-ui/drupal-symfony_mailer.md) |
| drupal/mailchimp | Utilisé | 9 | vactory_mailchimp, vactory_mailchimp_newsletter | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-mailchimp.md) |
| drupal/onesignal | Utilisé | 4 | vactory_onesignal | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-onesignal.md) |
| drupal/openai | Utilisé | 1 | vactory_decoupled_search_ai | Aucune | Élevé (déjà cassé : `openai_embeddings.pinecone_client` absent en beta4) | P2 | [fiche](../drupal/forms-admin-ui/drupal-openai.md) |
| drupal/migrate_plus | Utilisé | ~30 (8 PHP, 4 info.yml, 17 migrations livrées) | vactory_migrate (+dynamic_import, migrate_plugin, migrate_ui), vactory_locator, vactory_user_migrate, 10 modules contenu | Aucune | Élevé | P1 | [fiche](../drupal/forms-admin-ui/drupal-migrate_plus.md) |
| drupal/migrate_tools | Utilisé | 7 | vactory_migrate, vactory_migrate_ui, vactory_dynamic_import, vactory_locator, vactory_user_migrate | Aucune | Élevé | P1 | [fiche](../drupal/forms-admin-ui/drupal-migrate_tools.md) |
| drupal/migrate_source_csv | Utilisé | ~23 (3 PHP, 4 info.yml, 17 migrations) | vactory_migrate*, vactory_locator, vactory_user_migrate, modules contenu | Aucune | Élevé | P1 | [fiche](../drupal/forms-admin-ui/drupal-migrate_source_csv.md) |
| drupal/migrate_file | Utilisé | 5 | vactory_migrate, vactory_dynamic_import, vactory_migrate_plugin | Aucune | Élevé | P1 | [fiche](../drupal/forms-admin-ui/drupal-migrate_file.md) |
| drupal/taxonomy_import | Non utilisé | 0 | — (seulement `vactory_event/README.md:5-6` : « activer puis désactiver ») | — | — | — | 0 occurrence dans info.yml, config, PHP, twig |
| drupal/csv_serialization | Indirect | 1 | vactory_appointment (config) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-csv_serialization.md) |
| drupal/views_data_export | Utilisé | 4 | vactory_appointment, vactory_migrate_ui | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-views_data_export.md) |
| drupal/views_bulk_operations | Utilisé | 4 | vactory_locator | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-views_bulk_operations.md) |
| drupal/views_bulk_edit | Non utilisé | 0 | — | — | — | — | 0 occurrence de `views_bulk_edit` (info.yml, config, PHP) |
| drupal/views_autocomplete_filters | Utilisé | 7 | vactory_google_places, vactory_locator, vactory_smart_search, vactory_faq_prive | Aucune | Moyen (patch JS once) | P2 | [fiche](../drupal/forms-admin-ui/drupal-views_autocomplete_filters.md) |
| drupal/views_infinite_scroll | Indirect | 4 | vactory_mur, vactory_social_network | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-views_infinite_scroll.md) |
| drupal/views_url_alias | Indirect | 2 | profil (vue `content`) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-views_url_alias.md) |
| drupal/viewsreference | Utilisé | 6 | vactory_paragraphs, profil | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-viewsreference.md) |
| drupal/draggableviews | Non utilisé | 0 | — | — | — | — | 0 occurrence de `draggableviews` hors `translations/*.po` |
| drupal/better_exposed_filters | Utilisé | ~31 (25 vues, 5 info.yml, 1 .module) | vactory_views + 25 modules à vues | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-better_exposed_filters.md) |
| drupal/admin_toolbar | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-admin_toolbar.md) |
| drupal/coffee | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-coffee.md) |
| drupal/gin | Non utilisé | 0 | — | — | — | — | 0 occurrence de `gin` (thème non listé dans `themes:` du profil, admin = claro via `config/install/system.theme.yml:1`) |
| drupal/seven | Utilisé (installeur) | 2 | profil (`distribution.install.theme`), vactory_appointment (code mort) | Partielle (`drush si` CI) | Moyen | P3 | [fiche](../drupal/forms-admin-ui/drupal-seven.md) |
| drupal/classy | Indirect | 16 | 15 modules (`attach_library('classy/node')`) | Aucune | Moyen | P3 | [fiche](../drupal/forms-admin-ui/drupal-classy.md) |
| drupal/bartik | Non utilisé | 0 | — | — | — | — | 0 occurrence ; seul `vendor/drupal/amptheme/bartik_amp` en fait sa base theme, AMP commenté dans le profil |
| drupal/quickedit | Non utilisé | 0 | — | — | — | — | Commenté `vactory_starter_kit.info.yml:25` ; seule trace : clé d'annotation `quickedit` dans `vactory_field/src/Plugin/Field/FieldFormatter/VactoryExcerptFormatter.php:20` (inerte) |
| drupal/theme_switcher | Utilisé | 7 | vactory_decoupled_theme_switcher, vactory_multivers | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-theme_switcher.md) |
| drupal/admin_feedback | Utilisé | 6 | vactory_content_feedback, vactory_points | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-admin_feedback.md) |
| drupal/styleguide | Indirect | 1 | vactory_devtools | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-styleguide.md) |
| drupal/devel | Indirect | 3 | vactory_devtools, vactory_core (drush), profil (config mailsystem) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-devel.md) |
| drupal/upgrade_status | Non utilisé | 0 | — | — | — | — | 0 occurrence de `upgrade_status` (info.yml, config, PHP) |
| drupal/backup_migrate | Indirect | 5 | profil, vactory_decoupled (4 resource configs JSON:API désactivées) | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-backup_migrate.md) |
| drupal/config_update | Indirect | 0 (transitif via features) | — | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-config_update.md) |
| drupal/features | Indirect | ~28 (23 `*.features.yml`, 2 info.yml) | profil, vactory_decoupled, vactory_social_auth | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-features.md) |
| drupal/ultimate_cron | Indirect | 8 | 6 modules (jobs), vactory_decoupled, profil | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-ultimate_cron.md) |
| drupal/stage_file_proxy | Indirect | 2 | vactory_devtools, vactory_core (drush) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-stage_file_proxy.md) |
| drupal/log_stdout | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-log_stdout.md) |
| drupal/yaml_editor | Utilisé | 4 | vactory_dynamic_field, vactory_migrate_ui | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-yaml_editor.md) |
| drupal/components | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-components.md) |
| drupal/twig_tweak | Utilisé | ~155 (145 templates `image_style`, 7 `drupal_view`/`drupal_config`/`file_url`, 3 PHP) | vactory_core (service `vactory`), vactory_dynamic_field, vactory_frequent_searches, ~10 modules à templates | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-twig_tweak.md) |
| drupal/twig_field_value | Indirect | 2 | profil, vactory_core (info.yml) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-twig_field_value.md) |
| drupal/twig_vardumper | Indirect | 2 | vactory_devtools, vactory_core (drush) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-twig_vardumper.md) |
| drupal/colorapi | Utilisé | ~20 | vactory_field, vactory_paragraphs, vactory_banner, vactory_annual_report, vactory_paragraphs_bg_wrapper, vactory_content_package | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-colorapi.md) |
| drupal/chosen | Non utilisé | 0 | — | — | — | — | 0 occurrence de `chosen` comme module ; seule trace : `jquery.chosen` exclu dans `vactory_webform_custom/config/optional/webform.settings.yml:327` |
| drupal/improved_multi_select | Indirect | 7 | profil, vactory_notifications, vactory_cross_content, vactory_google_places, vactory_entity_canonical_access, vactory_points | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-improved_multi_select.md) |
| drupal/jquery_ui_checkboxradio | Non utilisé | 0 | — | — | — | — | 0 occurrence ; requis seulement par `webform_jqueryui_buttons` (non activé) |
| drupal/multiple_fields_remove_button | Indirect | 1 | vactory_quiz | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-multiple_fields_remove_button.md) |
| drupal/textfield_counter | Utilisé | 6 | vactory_core, vactory_dynamic_field, vactory_welcome, vactory_content_package | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-textfield_counter.md) |
| drupal/allowed_formats | Indirect | 7 | vactory_business, vactory_project_progress (+ help_center, seo) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-allowed_formats.md) |
| drupal/linkit | Utilisé | 8 | vactory_dynamic_field, profil (filtres/éditeurs), vactory_decoupled | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-linkit.md) |
| drupal/link_target | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-link_target.md) |
| drupal/link_attributes | Non utilisé | 0 | — | — | — | — | Module non installé ; seule trace défensive `vactory_core.module:308-311` (unset du widget s'il existe) ; les clés `link_attributes: {}` des displays sont un réglage de social_media_links |
| drupal/editor_advanced_link | Indirect | 3 | profil (éditeurs) | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-editor_advanced_link.md) |
| drupal/menu_link_attributes | Utilisé | 3 | profil, vactory_core | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-menu_link_attributes.md) |
| drupal/menu_item_extras | Utilisé | 6 | vactory_decoupled, vactory_toolbox, vactory_header | Partielle (BreadcrumbTest, indirect) | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-menu_item_extras.md) |
| drupal/simplify_menu | Utilisé | 16 (11 templates, 2 PHP, 3 info.yml) | vactory_core, vactory_header, vactory_espace_prive, vactory_toolbox, vactory_footer | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-simplify_menu.md) |
| drupal/menu_manipulator | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-menu_manipulator.md) |
| drupal/smart_trim | Indirect | ~20 (19 displays) | 8 modules contenu | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-smart_trim.md) |
| drupal/responsive_preview | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-responsive_preview.md) |
| drupal/responsive_favicons | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-responsive_favicons.md) |
| drupal/minifyhtml | Indirect | 1 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-minifyhtml.md) |
| drupal/advagg | Indirect | 5 | profil (install + 4 configs) | Aucune | Moyen | P3 | [fiche](../drupal/forms-admin-ui/drupal-advagg.md) |
| drupal/eu_cookie_compliance | Indirect | 2 | profil | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-eu_cookie_compliance.md) |
| drupal/addtoany | Utilisé | ~150 (149 displays, 2 hooks, 2 templates) | vactory_core + ~35 modules contenu | Aucune | Faible | P3 | [fiche](../drupal/forms-admin-ui/drupal-addtoany.md) |
| drupal/social_media_links | Utilisé | ~16 | vactory_decoupled, vactory_governance, vactory_business, profil | Aucune | Moyen | P2 | [fiche](../drupal/forms-admin-ui/drupal-social_media_links.md) |

**Totaux** : 68 paquets — 27 Utilisés, 31 Indirects, 10 Non utilisés.

**Constats transverses**
- Le `vendor/` local du profil n'embarque pas `cweagons/composer-patches` : aucun des 6 patches du lot (webform, features, simplify_menu, advagg, colorapi, views_autocomplete_filters) n'est appliqué localement ; l'application dépend du projet racine (à vérifier). Le hook `post_handler_submission` (vactory_webform_storage) et le JS `once()` de views_autocomplete_filters en dépendent.
- Dépendances dev (`devel`, `styleguide`, `stage_file_proxy`, `twig_vardumper`, `upgrade_status`) en `require` au lieu de `require-dev`.
- 4 contraintes `-dev` non figées : `webform_import_tab`, `coffee`, `responsive_favicons`, `backup_migrate` (lock `dev-5.1.x`).
