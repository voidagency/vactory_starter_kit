# Matrice partielle — lot `content-media-search-seo`

Fiches : `docs/dependency-tests/drupal/content-media-search-seo/`. Nb usages = références PHP / config vérifiées (ordre de grandeur).

| Paquet | Statut | Nb usages | Modules | Couverture | Risque | Priorité | Fiche |
|---|---|---|---|---|---|---|---|
| drupal/paragraphs | Utilisé | ~25 PHP + 35 config | vactory_core, vactory_paragraphs, vactory_decoupled, vactory_dynamic_field, vactory_page_import, vactory_content_package, vactory_paragraph_clipboard, 9× .install | Partielle | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-paragraphs.md) |
| drupal/entity_reference_revisions | Utilisé | ~20 PHP (`target_revision_id`) + 29 config | vactory_field, vactory_decoupled, vactory_content_collector, types de contenu | Partielle | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-entity_reference_revisions.md) |
| drupal/paragraphs_edit | Utilisé | 4 | vactory_dynamic_field | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-paragraphs_edit.md) |
| drupal/block_field | Utilisé | 2 PHP + 15 config | vactory_core, vactory_field, vactory_paragraphs, vactory_banner | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-block_field.md) |
| drupal/block_class | Indirect | 5 (info.yml) | profil, vactory_header/banner/footer/search_overlay | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-block_class.md) |
| drupal/block_list_override | Indirect | 1 (profil) | profil | Aucune | Faible | — | [fiche](../drupal/content-media-search-seo/drupal-block_list_override.md) |
| drupal/entityqueue | Utilisé | 3 PHP + 55 config | vactory_decoupled, vactory_dynamic_field, vactory_core, 10 modules de contenu | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-entityqueue.md) |
| drupal/auto_entityqueue | Indirect | 21 config | 8 modules de contenu | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-auto_entityqueue.md) |
| drupal/inline_entity_form | Non utilisé | 0 | — | — | — | — | 0 occurrence de `inline_entity_form` (code, config, info.yml) |
| drupal/field_group | Indirect | 12 config | vactory_paragraphs, vactory_locator, +5 | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-field_group.md) |
| drupal/address | Utilisé | 3 PHP + 12 config | vactory_decoupled, vactory_google_places, vactory_locator, vactory_governance, vactory_business | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-address.md) |
| drupal/faqfield | Indirect | 4 config | vactory_faq | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-faqfield.md) |
| drupal/time_range | Indirect | 1 config | vactory_welcome | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-time_range.md) |
| drupal/geofield | Utilisé | 2 PHP + 7 config | vactory_locator(_decoupled), vactory_content_localizer | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-geofield.md) |
| drupal/scheduler | Utilisé | 1 PHP + ~45 config | vactory_announcements, ~25 modules de contenu | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-scheduler.md) |
| drupal/workbench_moderation | Indirect | 1 config | vactory_toolbox | Aucune | Moyen | P3 | [fiche](../drupal/content-media-search-seo/drupal-workbench_moderation.md) |
| drupal/flag | Utilisé | ~12 PHP + 1 config | vactory_decoupled_flag, vactory_academy, vactory_points, vactory_ntcc, vactory_social_network | Aucune | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-flag.md) |
| drupal/rate | Utilisé | ~7 | vactory_rate, vactory_academy | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-rate.md) |
| drupal/votingapi | Utilisé | 2 fichiers PHP | vactory_rate | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-votingapi.md) |
| drupal/merge_translations | Indirect | 1 (profil) | profil | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-merge_translations.md) |
| drupal/lang_dropdown | Utilisé | 1 PHP + profil | vactory_core | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-lang_dropdown.md) |
| drupal/file_entity | Utilisé | 4 PHP + 12 config | vactory_cloudinary, vactory_private_files_decoupled, profil | Aucune | Élevé | P2 | [fiche](../drupal/content-media-search-seo/drupal-file_entity.md) |
| drupal/focal_point | Indirect | 18 config | profil (image styles), vactory_media_image_focal_point, +4 | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-focal_point.md) |
| drupal/crop | Indirect | 2 config + profil | vactory_decoupled, vactory_dynamic_field_video_help | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-crop.md) |
| drupal/image_effects | Indirect | 1 (info.yml) | vactory_decoupled_image_styles | Aucune | Faible | — | [fiche](../drupal/content-media-search-seo/drupal-image_effects.md) |
| drupal/imagemagick | Indirect | 1 (info.yml) | vactory_decoupled_image_styles | Aucune | Faible | — | [fiche](../drupal/content-media-search-seo/drupal-imagemagick.md) |
| drupal/media_responsive_thumbnail | Indirect | 24 config | 13 modules de contenu | Aucune | Moyen | P3 | [fiche](../drupal/content-media-search-seo/drupal-media_responsive_thumbnail.md) |
| drupal/media_library_form_element | Utilisé | ~15 PHP | vactory_dynamic_field, vactory_locator, vactory_video_ask, vactory_satisfaction, vactory_sondage | Partielle | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-media_library_form_element.md) |
| drupal/dropzonejs | Utilisé | 1 PHP | vactory_core | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-dropzonejs.md) |
| drupal/tinypng | Non utilisé | 0 | — | — | — | — | 0 occurrence de `tinypng` (code, config, info.yml) |
| drupal/filefield_paths | Non utilisé | 0 | — | — | — | — | 0 occurrence de `filefield_paths` |
| drupal/entity_embed | Indirect | 1 config | vactory_dynamic_field_unitegallery | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-entity_embed.md) |
| drupal/video_embed_field | Non utilisé | 0 | — | — | — | — | 0 occurrence de `video_embed`/`VideoEmbed` hors 2 README (vactory_testimonials, vactory_academy) |
| drupal/search_api | Utilisé | ~10 PHP + 9 config | vactory_decoupled_search, vactory_search, vactory_frequent_searches, vactory_jsonapi_extras, vactory_faq, vactory_forums | Partielle | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-search_api.md) |
| drupal/search_api_autocomplete | Utilisé | 1 PHP + 2 config | vactory_search_autosuggestions, vactory_faq | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-search_api_autocomplete.md) |
| drupal/search_api_exclude_entity | Indirect | 6 config | vactory_field, vactory_page, vactory_search, vactory_faq | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-search_api_exclude_entity.md) |
| drupal/search_api_ai | Utilisé | 1 fichier PHP | vactory_decoupled_search_ai | Aucune | Élevé | P2 | [fiche](../drupal/content-media-search-seo/drupal-search_api_ai.md) |
| drupal/facets | Indirect | 3 config + 1 import mort | vactory_decoupled_facets, vactory_locator_decoupled | Aucune | Faible | P3 | [fiche](../drupal/content-media-search-seo/drupal-facets.md) |
| drupal/metatag | Utilisé | ~15 PHP + 42 config | vactory_decoupled, vactory_decoupled_search, vactory_locator_decoupled, vactory_extended_seo, vactory_core, vactory_field, profil | Partielle | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-metatag.md) |
| drupal/yoast_seo | Indirect | 54 config | vactory_field + ~18 modules de contenu | Aucune | Moyen | P3 | [fiche](../drupal/content-media-search-seo/drupal-yoast_seo.md) |
| drupal/pathauto | Utilisé | 2 PHP + 20 config | vactory_multivers, vactory_help_center, modules de contenu, profil | Partielle | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-pathauto.md) |
| drupal/redirect | Utilisé | 1 PHP + config | vactory_decoupled_router, vactory_core, profil | Aucune | Élevé | P1 | [fiche](../drupal/content-media-search-seo/drupal-redirect.md) |
| drupal/path_redirect_import | Non utilisé | 0 | — | — | — | — | 0 occurrence de `path_redirect_import` |
| drupal/xmlsitemap | Utilisé | 3 PHP + config | vactory_core, profil, vactory_dynamic_field_dummy | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-xmlsitemap.md) |
| drupal/sitemap | Indirect | 1 (profil) | profil | Aucune | Faible | — | [fiche](../drupal/content-media-search-seo/drupal-sitemap.md) |
| drupal/google_news_sitemap | Non utilisé | 0 | — | — | — | — | 0 occurrence de `googlenews_sitemap`/`google_news_sitemap`/`googlenews`; version `dev-1.0.x` instable |
| drupal/hreflang | Utilisé | 3 | vactory_hreflang, profil | Aucune | Moyen | P2 | [fiche](../drupal/content-media-search-seo/drupal-hreflang.md) |
| drupal/google_tag | Non utilisé | 0 | — | — | — | — | 0 occurrence de `google_tag`/`googletag` |
| drupal/amp | Utilisé | 4 | vactory_amp (non installé par le profil), vactory_dynamic_field | Aucune | Moyen | P3 | [fiche](../drupal/content-media-search-seo/drupal-amp.md) |
| drupal/amptheme | Non utilisé | 0 | — | — | — | — | Seule trace : commentaire `vactory_starter_kit.info.yml:196` (`# - amptheme > handled in fontend`) |
| drupal/rdf | Indirect | 2 (profil + config) | profil | Aucune | Moyen | P3 | [fiche](../drupal/content-media-search-seo/drupal-rdf.md) |

## Constats transverses
- Tests existants sous `tests/Functional/` (hors `tests/src/`), certains avec namespace non PSR-4 (`MetaTagsTest`, `JsonApiCollectionTest`, `InternalBlocksTest`) — à vérifier qu'ils sont découverts par la CI.
- Deux patches composer pointent vers des **gists personnels** (search_api, search_api_ai).
- Dépendances non déclarées : `vactory_decoupled_flag` → flag ; `vactory_decoupled_search_ai` → search_api_pinecone ; `vactory_toolbox` config → workbench_moderation ; plusieurs modules de contenu → scheduler.
- Bugs constatés : `vactory_core.module:428` (`$link['acccess']`), `vactory_dynamic_field.install:27` (condition inversée).
