# Historique des dépendances — lots `content-media-search-seo` et `forms-admin-ui`

Source : `composer.json` (`require` + `extra.patches`) sur `3.x`, commit par commit (non-merge ; hash = commit auteur), + `git log --grep` sur `--all` et Redmine (projet vactory-4-next, lecture seule). Audit du 2026-11-17, ticket #71097.

Règle : ≥ 1 downgrade, épinglage après incident, fork, patch correctif ou revert ⇒ **P1** (paquet non utilisé : signalé seulement). « Nb événements » exclut l'ajout initial (`1af659f7`, 2020-06-28).

| Paquet | Nb événements | Incidents (downgrade/pin/fork/patch/revert) | Tickets | Priorité avant → après |
|---|---|---|---|---|
| drupal/advagg | 7 | patch `1be86d53` `894e4058` | #40522 #40654 #44188 #44206 | P3 → **P1** |
| drupal/amp | 8 | patch `894e4058` | #40522 | P3 → **P1** |
| drupal/auto_entityqueue | 3 | patch `72e5d710` | #45762 | P3 → **P1** |
| drupal/block_list_override | 8 | patch `cbe92e58`; retrait `07b911d9`; fork `07b911d9` `f1c27559`; remplacement `07b911d9` `f1c27559` `7759f845` | #44188 #62859 | — → **P1** |
| drupal/coffee | 4 | patch `1be86d53` | #67434 | P3 → **P1** |
| drupal/colorapi | 9 | patch `1be86d53` `ae0e3a00` `39fc2b27` (montée couplée au fork `voidagency/jquery_colorpicker`) | #40654 #62381 | P2 → **P1** |
| drupal/csv_serialization | 6 | patch `ec1ae4e6`; pin `9cbd99aa` | #44188 | P3 → **P1** |
| drupal/file_entity | 7 | patch `ca604782` `0965d660`; régression patch `22449f5b` | #49035 #49460 #53096 #53706 | P2 → **P1** |
| drupal/lang_dropdown | 3 | patch `4733f4b0` | — | P3 → **P1** |
| drupal/link_target | 4 | patch `1be86d53` `ca604782` | #49035 #49460 | P3 → **P1** |
| drupal/linkit | 9 | pin `dc656dbf` `f337dbc2`; downgrade `776f9ae0` | #41230 #44188 #50957 #52782 #55421 #60305 #62859 #64112 #69237 #70121 | P2 → **P1** |
| drupal/merge_translations | 7 | patch `1cbde7df`; retrait `8a9ab9ce`; fork `8a9ab9ce` `f1c27559`; remplacement `8a9ab9ce` `f1c27559` `f337dbc2` | #44188 #62859 | P3 → **P1** |
| drupal/rate | 18 | patch `22b235d7` `1be86d53` `a176f28d` `6915227d` `26bcbc6f`; downgrade `a176f28d` `6915227d`; revert `a176f28d` `6915227d`; pin `c176cf9d` | #44188 #48597 | P2 → **P1** |
| drupal/search_api_ai | 2 | patch `b1629c16` | — | P2 → **P1** |
| drupal/simplify_menu | 13 | patch `22b235d7` `6f19c294` | #30682 #40654 #44188 #53344 | P2 → **P1** |
| drupal/theme_switcher | 3 | patch `7b6c83eb` | #44209 | P2 → **P1** |
| drupal/views_autocomplete_filters | 2 | patch `614b9330` | #44188 | P2 → **P1** |
| drupal/viewsreference | 4 | pin `1be86d53`; patch `1be86d53` | — | P2 → **P1** |
| drupal/yaml_editor | 8 | patch `b182d404`; retrait `ab4042cd`; fork `ab4042cd` `f1c27559`; remplacement `ab4042cd` `f1c27559` | #44188 #49503 | P3 → **P1** |
| drupal/entity_reference_revisions | 0 | — | — | P1 (inchangé) |
| drupal/flag | 4 | patch `894e4058` | #52244 | P1 (inchangé) |
| drupal/media_library_form_element | 6 | patch `1be86d53` `ac773590` | #37628 #53005 | P1 (inchangé) |
| drupal/metatag | 2 | — | — | P1 (inchangé) |
| drupal/migrate_file | 1 | — | #45165 | P1 (inchangé) |
| drupal/migrate_plus | 3 | — | #44188 | P1 (inchangé) |
| drupal/migrate_source_csv | 0 | — | — | P1 (inchangé) |
| drupal/migrate_tools | 5 | pin `1be86d53`; patch `ae0e3a00` | #45165 | P1 (inchangé) |
| drupal/paragraphs | 5 | patch `035dd9f3` | #42408 | P1 (inchangé) |
| drupal/redirect | 0 | — | — | P1 (inchangé) |
| drupal/search_api | 12 | patch `1be86d53` `07765dd6` `61a5887c` `826865f2` | #54411 #57495 | P1 (inchangé) |
| drupal/symfony_mailer | 2 | remplacement `f3a80371` | #34779 | P1 (inchangé) |
| drupal/webform | 4 | patch `1be86d53` | #40654 #44188 | P1 (inchangé) — patch fonctionnel → P1 inchangé |
| drupal/address | 1 | — | — | P2 (inchangé) |
| drupal/addtoany | 2 | — | #44188 | P3 (inchangé) |
| drupal/admin_feedback | 1 | — | #28746 | P2 (inchangé) |
| drupal/admin_toolbar | 2 | — | #42408 | P3 (inchangé) |
| drupal/allowed_formats | 1 | — | #43898 | P3 (inchangé) |
| drupal/amptheme | 8 | patch `ca1115c4`; retrait `75a58335`; fork `75a58335` `f1c27559`; remplacement `75a58335` `f1c27559` | #44188 #49503 | — (non utilisé) — **non utilisé** : fork + doublon drupal/voidagency |
| drupal/backup_migrate | 2 | — | — | P2 (inchangé) |
| drupal/bartik | 1 | — | #44188 | — (non utilisé) |
| drupal/better_exposed_filters | 3 | — | #44188 | P2 (inchangé) |
| drupal/block_class | 3 | pin `9cbd99aa` | #44188 | P3 (inchangé) — pin sans incident documenté |
| drupal/block_field | 1 | — | — | P2 (inchangé) |
| drupal/chosen | 2 | — | #44188 | — (non utilisé) |
| drupal/classy | 1 | — | #44188 | P3 (inchangé) |
| drupal/components | 2 | — | #44188 | P3 (inchangé) |
| drupal/config_update | 1 | — | #44188 | P3 (inchangé) |
| drupal/crop | 1 | — | — | P3 (inchangé) |
| drupal/devel | 4 | pin `1be86d53` | #33602 #44188 | P3 (inchangé) — pin initial, levé |
| drupal/draggableviews | 1 | — | — | — (non utilisé) |
| drupal/dropzonejs | 1 | — | — | P3 (inchangé) |
| drupal/editor_advanced_link | 2 | pin `63d2b3f6` | #44188 | P3 (inchangé) — pin sans incident documenté (#62859) |
| drupal/entity_embed | 0 | — | — | P3 (inchangé) |
| drupal/entityqueue | 2 | — | #53776 | P2 (inchangé) |
| drupal/eu_cookie_compliance | 1 | — | — | P3 (inchangé) |
| drupal/facets | 1 | — | #40239 | P3 (inchangé) |
| drupal/faqfield | 1 | — | — | P3 (inchangé) |
| drupal/features | 2 | patch `22b235d7` | — | P3 (inchangé) — patch fonctionnel « Export config translation » → inchangé |
| drupal/field_group | 2 | — | — | P3 (inchangé) |
| drupal/filefield_paths | 1 | — | #29579 | — (non utilisé) |
| drupal/focal_point | 4 | patch `1be86d53` | #44188 | P2 (inchangé) — patch fonctionnel (pas correctif) → inchangé |
| drupal/geofield | 1 | — | — | P2 (inchangé) |
| drupal/gin | 1 | — | #46835 | — (non utilisé) |
| drupal/google_news_sitemap | 1 | — | #61121 | — (non utilisé) |
| drupal/google_tag | 1 | — | — | — (non utilisé) |
| drupal/hreflang | 1 | — | — | P2 (inchangé) |
| drupal/image_effects | 1 | — | #50342 | — (inchangé) |
| drupal/imagemagick | 1 | — | #50342 | — (inchangé) |
| drupal/improved_multi_select | 1 | — | — | P3 (inchangé) — patch D11 sur branche non fusionnée `upgrade/d11` → inchangé |
| drupal/inline_entity_form | 0 | — | — | — (non utilisé) |
| drupal/jquery_ui_checkboxradio | 2 | — | #44188 | — (non utilisé) |
| drupal/link_attributes | 1 | pin `9cbd99aa` | — | — (non utilisé) — **non utilisé** : pin 2.1.1 |
| drupal/log_stdout | 1 | — | — | P3 (inchangé) |
| drupal/mailchimp | 2 | — | #50668 | P2 (inchangé) |
| drupal/mailsystem | 0 | — | — | P3 (inchangé) |
| drupal/media_responsive_thumbnail | 2 | pin `1be86d53` | #43521 | P3 (inchangé) — pin initial ; MAJ sécurité #43521 |
| drupal/menu_item_extras | 1 | — | — | P2 (inchangé) |
| drupal/menu_link_attributes | 1 | — | — | P3 (inchangé) |
| drupal/menu_manipulator | 1 | — | — | P3 (inchangé) |
| drupal/minifyhtml | 1 | — | #44188 | P3 (inchangé) |
| drupal/multiple_fields_remove_button | 2 | — | #44188 | P3 (inchangé) |
| drupal/onesignal | 2 | fork `0749699f` | — | P2 (inchangé) — fork d'issue sur branche non fusionnée `update/d11` → inchangé |
| drupal/openai | 2 | — | #49503 | P2 (inchangé) — pin alpha17 levé (#49503) |
| drupal/paragraphs_edit | 2 | — | #49503 | P2 (inchangé) |
| drupal/path_redirect_import | 2 | — | #44188 | — (non utilisé) |
| drupal/pathauto | 1 | — | — | P2 (inchangé) |
| drupal/quickedit | 1 | — | #44188 | — (non utilisé) |
| drupal/rdf | 1 | — | #44188 | P3 (inchangé) |
| drupal/responsive_favicons | 2 | — | #67434 | P3 (inchangé) |
| drupal/responsive_preview | 2 | — | #44188 | P3 (inchangé) |
| drupal/scheduler | 2 | — | — | P2 (inchangé) |
| drupal/search_api_autocomplete | 1 | — | — | P3 (inchangé) |
| drupal/search_api_exclude_entity | 2 | — | — | P2 (inchangé) |
| drupal/seven | 1 | — | #44188 | P3 (inchangé) |
| drupal/sitemap | 1 | — | — | — (inchangé) |
| drupal/smart_trim | 2 | — | #42408 | P3 (inchangé) |
| drupal/social_media_links | 1 | — | — | P2 (inchangé) |
| drupal/stage_file_proxy | 2 | — | #44188 #67434 | P3 (inchangé) |
| drupal/styleguide | 1 | — | — | P3 (inchangé) |
| drupal/taxonomy_import | 1 | — | — | — (non utilisé) |
| drupal/textfield_counter | 1 | — | #32290 | P3 (inchangé) |
| drupal/time_range | 1 | — | — | P3 (inchangé) |
| drupal/tinypng | 1 | — | — | — (non utilisé) |
| drupal/twig_field_value | 1 | — | — | P3 (inchangé) |
| drupal/twig_tweak | 2 | — | #44188 | P2 (inchangé) |
| drupal/twig_vardumper | 2 | — | #42408 | P3 (inchangé) |
| drupal/ultimate_cron | 2 | — | #49503 | P2 (inchangé) |
| drupal/upgrade_status | 2 | — | #44188 | — (non utilisé) |
| drupal/video_embed_field | 1 | — | — | — (non utilisé) |
| drupal/views_bulk_edit | 1 | — | — | — (non utilisé) |
| drupal/views_bulk_operations | 1 | — | — | P2 (inchangé) |
| drupal/views_data_export | 1 | — | — | P2 (inchangé) |
| drupal/views_infinite_scroll | 4 | downgrade `1fdfb044` | #44188 | P3 (inchangé) — downgrade intra-PR #43 non publié → inchangé |
| drupal/views_url_alias | 1 | — | #52383 | P3 (inchangé) |
| drupal/votingapi | 2 | — | — | P2 (inchangé) — pin initial, levé |
| drupal/webform_import_tab | 1 | — | #61189 | P3 (inchangé) |
| drupal/workbench_moderation | 1 | — | #30704 | P3 (inchangé) |
| drupal/xmlsitemap | 2 | pin `1be86d53` | — | P2 (inchangé) — pin initial, levé |
| drupal/yoast_seo | 1 | — | — | P3 (inchangé) |

## Branches non fusionnées (`--all`)
- `origin/upgrade/d11` (tip `fa887595`, 2026-10-28) et `origin/update/d11` (`1ec90e6b`, 2026-01-13) : montées D11 massives (address ^2, better_exposed_filters ^7, xmlsitemap ^2, auto_entityqueue ^3, faqfield ^8, menu_item_extras ^3.1…), patch D11 `improved_multi_select`, `onesignal` via fork d'issue `dev-3433682-automated-drupal-11` (`0749699f`), retrait du patch `views_autocomplete_filters`.
- `origin/feat/62381` (`b9a0efd6`, 2026-01-07) : colorapi ^2.1 (fusionné ensuite via `39fc2b27`/PR #862).
- Dependabot non fusionnés : `search_api_exclude_entity` 2.0.0 (`d4c68ab5`), `views_autocomplete_filters` 2.0.2 (`49555558`).
- ~100 branches 2.x/2023 non fusionnées : bruit (paragraphs ^1.16, gin, block_field ^1.0@RC), non détaillé.

## Constats
- `composer.json` déclare **en double** `drupal/yaml_editor ^1.2` + `voidagency/yaml_editor ^2.0` et `drupal/amptheme ^3.1` + `voidagency/amptheme ^3.0` (lock : les deux installés) depuis `10bbf6f7` (#49503). Chemin d'installation commun probable : à vérifier.
- `vendor/drupal/linkit` local = 7.0.8 alors que le lock = 7.0.12 : `vendor/` local périmé.
- Patches encore appliqués sur ces lots : simplify_menu, features, advagg, colorapi, webform, views_autocomplete_filters, search_api_ai, search_api. 4 viennent de gists personnels (`ismail-void` : simplify_menu, colorapi, search_api ; `khalidbouhouch77` : search_api_ai).
- `ae0e3a00` (2021-11-11) a commité un `composer.json` JSON invalide, corrigé par `620c057c`.
