# Paquets sans usage dans le profil

31 paquets de `require` n'ont **aucune** trace dans le code ni la config du profil (preuve « 0 occurrence » dans [matrix.md](matrix.md)).

> Le profil est une distribution : un projet client peut activer l'un de ces modules via sa propre config (ex. `gin`, `google_tag`). **Avant de retirer un paquet, vérifier les projets** (config/sync des sites, référentiel Dispatch). Le retirer du profil = le déplacer dans le `composer.json` du projet qui en a besoin.

## Transitifs (inutile de les déclarer)

Présents uniquement parce qu'un autre paquet les tire. Les retirer de `require` ne change rien à l'install, mais supprime une contrainte qu'on ne maîtrise pas.

| Paquet | Tiré par |
|---|---|
| webflo/drupal-finder | outillage composer/drush |
| masterminds/html5 | drupal/core |
| lullabot/amp | drupal/amp |

## Aucun usage

| Domaine | Paquets |
|---|---|
| Libs | google/apiclient, commerceguys/intl |
| voidagency | voidagency/easy_install, voidagency/mobile_native_share, voidagency/amptheme |
| Auth / sécurité | drupal/simple_oauth_revoke, drupal/login_security, drupal/advban, drupal/entity, drupal/health_check |
| Contenu / médias | drupal/inline_entity_form, drupal/tinypng, drupal/filefield_paths, drupal/video_embed_field |
| SEO | drupal/path_redirect_import, drupal/google_news_sitemap, drupal/google_tag, drupal/amptheme |
| Views / admin | drupal/taxonomy_import, drupal/views_bulk_edit, drupal/draggableviews, drupal/upgrade_status |
| Thèmes legacy | drupal/gin, drupal/bartik, drupal/quickedit |
| UI | drupal/chosen, drupal/jquery_ui_checkboxradio, drupal/link_attributes |

## Doublons

| Paquets | Problème |
|---|---|
| `drupal/yaml_editor` + `voidagency/yaml_editor` | même nom machine de module : collision possible à l'install (à vérifier) |
| `drupal/amptheme` + `voidagency/amptheme` | même nom machine de thème, aucun des deux utilisé |

## Indirects à questionner

`drupal/decoupled_router` : déclaré en dépendance mais `/api/router` est réimplémenté par notre code. Candidat au retrait, à confirmer côté front Next.js.

`drupal/jsonapi_user_resources` : **utilisé** (seul fournisseur de `/api/user/register` et des routes mot de passe, cité dans `DecoupledSettingsForm.php:81`) — vérification Fable C06. À garder.

`drupal/seven` sert encore de thème d'installation (`vactory_starter_kit.info.yml:9`) ; `drupal/classy` est appelé dans 16 templates (`attach_library('classy/node')`). Les garder tant que ces usages existent.
