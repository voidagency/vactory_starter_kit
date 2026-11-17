# Recon mi-aveugle — tour 2

Lecture seule, 2026-11-17, branche `3.x`. Raisonnement sur les versions **lockées** (`drupal/core` 10.5.7, `twig/twig` 3.22.1, `webform` 6.2.2, `paragraphs` 1.17.0, `views_autocomplete_filters` 1.7.0, `simple_oauth` 5.2.5). Dépréciations vérifiées dans `vendor/drupal/core` (10.5.7).

Angles choisis : API core supprimées ou devenues strictes depuis D10 (scan des `@deprecated` du cœur contre notre code), hooks qui ne sont plus appelés, plugins référencés par la config, endpoints REST qui étendent des classes du cœur, permissions contrib accordées à l'installation, dépendances JS envers les bibliothèques du cœur, code contrib utilisé sans déclaration.

## Synthèse

| ID | Titre | Catégorie | Gravité | Statut |
|---|---|---|---|---|
| G01 | `entityQuery` sans `accessCheck()` sur des entités de contenu → `QueryException` | Bug actuel (core) | **Majeur** | Vérifié en lecture |
| G02 | Import d'utilisateurs : plugin `hash_password` typé `PhpassHashedPassword` → `TypeError` | Bug actuel (core) | **Majeur** | Vérifié en lecture |
| G03 | Upload REST webform : 3 méthodes du cœur supprimées en D11, validateurs webform ignorés, `sprintf` fatal | API interne core + webform | **Majeur** | Vérifié en lecture / exploit à vérifier |
| G04 | `access any webform configuration` donné aux anonymes ; seul jsonapi_extras masque les handlers | Sécurité / couplage contrib | **Majeur** | Vérifié en lecture / runtime à vérifier |
| G05 | views_autocomplete_filters ≥ 1.4 : les ID `views_autocomplete_filters_*` n'existent plus, l'alter Google Places ne s'applique plus | API contrib changée | Moyen | Vérifié en lecture / effet à vérifier |
| G06 | Hooks paragraphs/widget qui ne sont plus appelés (`#states` perdus) | Hook mort | Moyen | Vérifié en lecture |
| G07 | APIs du cœur supprimées en D11 encore utilisées (hors G03) | Dépréciation | Moyen | Vérifié en lecture |
| G08 | Normalizer webform : lit `#upload_validators['file_validate_extensions']` (format webform 6.2) | Couplage interne webform | Moyen | Vérifié (6.2) / À vérifier (6.3) |
| G09 | `vactory_decoupled` modifie l'entité `comment` sans déclarer le module → fatale si `comment` est désinstallé | Dépendance core non déclarée | Moyen | Vérifié en lecture |
| G10 | Complément à F07 : bibliothèques core inexistantes ou retirées en D11, ~50 bibliothèques sans dépendances core déclarées | Assets core | Moyen | Vérifié en lecture / runtime à vérifier |
| G11 | Complément à F13 : la config livrée référence des ID de plugins sans fournisseur | Config ↔ plugins | Mineur | Vérifié en lecture |
| G12 | Complément à ci.md : `cweagans/composer-patches` absent du lock et de `allow-plugins` | Composer / patches | Mineur | Vérifié en lecture / v2 à vérifier |
| G13 | Complément à F14 : `sebastian/diff` et `google/recaptcha` utilisés directement, sans être déclarés | Transitive non déclarée | Mineur | Vérifié en lecture |

---

## G01 — `entityQuery` sans `accessCheck()` → `QueryException`

- **Catégorie** : bug actuel, API core rendue stricte en D10
- **Paquet** : drupal/core 10.5.7
- **Preuve** :
  - `vendor/drupal/core/lib/Drupal/Core/Entity/Query/Sql/Query.php:157-158` : `if (is_null($this->accessCheck)) { throw new QueryException('Entity queries must explicitly set whether the query should be access checked…`
  - `modules/vactory_newsletter/modules/vactory_mailchimp_newsletter/src/Commands/SendNewslettersCommands.php:33-37` : `\Drupal::entityQuery('node')` → `condition()` ×3 → `execute()`, sans `accessCheck`.
  - `modules/vactory_social_network/vactory_social_network.module:179-183` : `Drupal::entityQuery('flagging')` → `count()->execute()` dans `vactory_social_network_preprocess_flag__liked_content` (flag fournit la suggestion `flag__<id>`, `vendor/drupal/flag/flag.module:273`).
  - Scan de toutes les requêtes `entityQuery(` / `->getQuery()` du code custom : ce sont les 2 seuls appels sur une entité de contenu sans `accessCheck` dans les 15 lignes (le 3ᵉ, `DynamicImportForm.php:221`, vise une entité de config et ne lève pas d'exception).
- **Impact** : la commande Drush d'envoi des newsletters Mailchimp plante à chaque exécution. Le rendu du lien flag « liked_content » (config livrée par `vactory_social_network` et `vactory_ntcc`) lève une exception, donc la page renvoie une 500.
- **Statut** : Vérifié en lecture
- **Test** : [Kernel] activer `vactory_social_network` + `flag`, créer un node, rendre le lien flag `liked_content` (`flag.link_builder`) → aucune exception, `likes_count` = 0. [Statique] PHPStan + `phpstan-drupal` (règle `EntityQueryHasAccessCheckRule`) sur `modules/` en CI.

## G02 — Plugin migrate `hash_password` : `TypeError` à l'instanciation

- **Catégorie** : bug actuel, service core remplacé en 10.1
- **Paquets** : drupal/core 10.5.7, drupal/migrate_plus / migrate_source_csv (exécution)
- **Preuve** :
  - `modules/vactory_user/modules/vactory_user_migrate/src/Plugin/migrate/process/HashPassword.php:4,36,50` : `use Drupal\Core\Password\PhpassHashedPassword;` … `PhpassHashedPassword $password_manager` … `$container->get('password')`
  - `vendor/drupal/core/core.services.yml:1165-1166` : `password: class: Drupal\Core\Password\PhpPassword` (sans lien d'héritage avec `PhpassHashedPassword`).
  - `vendor/drupal/core/lib/Drupal/Core/Password/PhpassHashedPassword.php:5` : `deprecated in drupal:10.1.0 and is removed from drupal:11.0.0`.
  - Utilisé par `config/install/migrate_plus.migration.vactory_user_migrate.yml:25` (`pass: plugin: hash_password`).
- **Impact** : le constructeur typé reçoit un `PhpPassword` → `TypeError`. L'import CSV d'utilisateurs de `vactory_user_migrate` est cassé depuis D10.1. Même avec le module `phpass` activé, le service est `Drupal\phpass\Password\PhpassHashedPassword`, qui n'hérite pas non plus de la classe du cœur. En D11, la classe n'existe plus du tout.
- **Statut** : Vérifié en lecture
- **Test** : [Kernel] `plugin.manager.migrate.process->createInstance('hash_password', [], $migration)` puis `transform('secret', …)` → la chaîne retournée passe `\Drupal::service('password')->check('secret', $hash)`. Correctif attendu : typer `PasswordInterface`.

## G03 — `WebformFileUploadResource` : méthodes du cœur supprimées en D11, validateurs webform ignorés

- **Catégorie** : API core `@deprecated` (retrait D11), couplage webform, sécurité
- **Paquets** : drupal/core (`file`, `rest`), drupal/webform 6.2.2
- **Preuve** :
  - `modules/vactory_decoupled/modules/vactory_decoupled_webform/src/Plugin/rest/resource/WebformFileUploadResource.php:34` `extends FileUploadResource` ; appels `:70` `validateAndParseContentDispositionHeader()`, `:92` `prepareFilename()`, `:102` `streamUploadData()`.
  - `vendor/drupal/core/modules/file/src/Plugin/rest/resource/FileUploadResource.php:264-269, 302-308, 366-372` : les trois méthodes sont `@deprecated in drupal:10.3.0 and is removed from drupal:11.0.0`.
  - Ressource activée par la config livrée (`config/install/rest.resource.vactory_decoupled_webform_rest_file_upload.yml`, POST, cookie + oauth2).
  - Validateurs reconstruits à la main (`:200-220`) : l'extension n'est contrôlée que si `#file_extensions` est renseigné sur l'élément (`:216-218`). Or webform calcule ses extensions par défaut dans `#upload_validators` (`vendor/drupal/webform/src/Plugin/WebformElement/WebformManagedFileBase.php:244`), et le resource appelle `prepare()` (`:80`) sans réutiliser ce résultat.
  - `:117` : `sprintf('File "%s" is already locked for writing')` sans argument → `ArgumentCountError` en PHP 8 (au lieu de la 503 prévue).
  - Effet de bord : `:105-106` appelle `getDestinationFilename()` sans utiliser le résultat.
- **Impact** :
  - D11 : erreur fatale « undefined method » sur tout upload de fichier webform headless.
  - Aujourd'hui : un élément fichier sans extensions explicites accepte tout type de fichier non exécutable (les extensions dangereuses sont seulement renommées par le cœur), au lieu des extensions par défaut de webform.
  - Deux uploads concurrents du même nom renvoient une 500.
- **Statut** : retraits et `sprintf` vérifiés en lecture. L'acceptation d'extensions arbitraires est **à vérifier** au runtime.
- **Test** : [ExistingSite] POST `/webform_rest/{webform}/upload/{element}` avec `Content-Disposition: file; filename="x.svg"` sur un élément `managed_file` sans `#file_extensions` → 422 attendu. Le même POST avec un `.pdf` autorisé → 201. Le test attrape aussi le retrait D11, puisqu'il exécute les trois méthodes.

## G04 — Permission webform « restrict access » accordée aux anonymes à l'installation

- **Catégorie** : sécurité, couplage entre deux contribs (webform × jsonapi_extras)
- **Paquets** : drupal/webform 6.2.2, drupal/jsonapi_extras
- **Preuve** :
  - `modules/vactory_decoupled/modules/vactory_decoupled_webform/vactory_decoupled_webform.install:18-19` : `user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access any webform configuration']);` (idem `AUTHENTICATED_ID`). Le module est activé par le profil (`vactory_starter_kit.info.yml:181`).
  - `vendor/drupal/webform/webform.permissions.yml:65-69` : `restrict access: true`, « Give to trusted roles only… A webform's configuration includes all settings and handlers which can contain sensitive information. »
  - `vendor/drupal/webform/src/WebformEntityAccessControlHandler.php:109` : avec cette permission, `view` est autorisé pour toute requête non-HTML et pour `/jsonapi/*`.
  - Seul garde-fou : `config/install/jsonapi_extras.jsonapi_resource_config.webform--webform.yml`, qui désactive `handlers`, `settings`, `access`, `third_party_settings`, `css`, `javascript`, mais expose `elements`, `uid`, `status`, `open`/`close`, `template`, `archive`.
- **Impact** : la confidentialité des handlers (emails destinataires, endpoints et en-têtes du remote post, réglages Mailchimp/TypeSafe) ne tient qu'à cette ressource jsonapi_extras. Les handlers et réglages fuient vers les anonymes dès que ce garde-fou saute : une MAJ de jsonapi_extras qui change la sémantique de `disabled`, une config de ressource réinitialisée ou absente sur un projet, ou le passage de `jsonapi_extras.settings` en `default_disabled`/revert. Toute autre exposition non-HTML de l'entité `webform` (REST, GraphQL, futur module) hérite de l'accès. La collection expose aussi la liste et les éléments de tous les webforms, y compris fermés ou internes.
- **Statut** : permission et contrôle d'accès vérifiés en lecture. Champs réellement servis : **à vérifier** au runtime.
- **Test** : [ExistingSite] en anonyme, GET `/{jsonapi_base}/webform/webform` et `/…/webform/webform/{uuid}` → assert qu'aucune ressource ne contient les clés `handlers`, `settings`, `access`. [Kernel] `Role::load('anonymous')->hasPermission('access any webform configuration')` documenté et assumé, ou remplacé par une permission/route dédiée au headless.

## G05 — views_autocomplete_filters ≥ 1.4 : ID de plugins disparus, alter Google Places inopérant

- **Catégorie** : changement d'API contrib non suivi (montée `^1.3` → 1.7.0)
- **Paquet** : drupal/views_autocomplete_filters 1.7.0
- **Preuve** :
  - 1.7.0 ne déclare plus aucun plugin propre : les classes `src/Plugin/views/filter/*.php` n'ont ni annotation ni attribut, et `views_autocomplete_filters.views.inc:15-31` remplace seulement la **classe** des plugins core `string`, `combine`, `search_api_text`, `search_api_fulltext` (`hook_views_plugins_filter_alter`). Aucun `hook_views_data_alter`.
  - `modules/vactory_google_places/vactory_google_places.views.inc:13-14` : `if ($field_data['filter']['id'] == 'views_autocomplete_filters_string') { …['filter']['id'] = 'vactory_google_places'; }`. Cette valeur n'apparaît plus dans aucune donnée Views (0 occurrence dans `vendor/`).
  - La config indique `plugin_id: vactory_google_places` (`vactory_locator/config/install/views.view.vactory_locator.yml:5219`), mais Views instancie d'après l'ID de `views_data`, pas d'après le `plugin_id` de la config (`vendor/drupal/core/modules/views/src/Plugin/ViewsHandlerManager.php:107-110`).
  - La config livrée utilise des ID inexistants : `views_autocomplete_filters_string` (`views.view.vactory_locator.yml:1770`), `views_autocomplete_filters_search_api_fulltext` (`vactory_smart_search/config/install/views.view.vactory_smart_search.yml:539`), `views_autocomplete_filters_combine` (`vactory_faq_prive/config/install/views.view.vactory_faq_prive.yml:395`).
- **Impact** : le filtre Google Places du locator n'est jamais branché (le filtre reste un `string` autocomplete standard sur la colonne geofield). La recherche par lieu est silencieusement inopérante. Les 3 vues ont des `plugin_id` sans schéma (`views.filter.views_autocomplete_filters_*`), et un test avec schéma strict échoue à l'installation (voir F13).
- **Statut** : Vérifié en lecture. Le comportement des versions < 1.4 (qui posaient l'ID par `views_data_alter`) est déduit du code `vactory_google_places`, **à vérifier**.
- **Test** : [Kernel] modules `views`, `geofield`, `views_autocomplete_filters`, `vactory_google_places`, `vactory_locator` ; charger la vue `vactory_locator`, `$view->initHandlers()` → le filtre `field_vactory_locator_geofield_value` est une instance de `VactoryGooglePlacesString`. [Kernel] `ConfigSchemaChecker` sur les 3 vues.

## G06 — Hooks paragraphs/widget qui ne sont plus appelés

- **Catégorie** : hook mort (API core renommée en D10, hook inexistant en contrib)
- **Paquets** : drupal/core (`WidgetBase`), drupal/paragraphs 1.17.0
- **Preuve** :
  - `modules/vactory_paragraphs/vactory_paragraphs.module:172` `vactory_paragraphs_field_widget_entity_reference_paragraphs_form_alter()` : forme `hook_field_widget_WIDGET_TYPE_form_alter`, supprimée en D10. Le cœur n'invoque plus que `field_widget_single_element_<type>_form` et `field_widget_complete_<type>_form` (`vendor/drupal/core/lib/Drupal/Core/Field/WidgetBase.php:156,470`). Le widget `entity_reference_paragraphs` est toujours utilisé par 4 form displays (ex. `vactory_page/config/install/core.entity_form_display.node.vactory_page.default.yml:52`).
  - `modules/vactory_paragraphs_bg_wrapper/vactory_paragraphs_bg_wrapper.module:57` `…_form_paragraphs_subform_alter()` : aucun `alter('form_paragraphs_subform')` dans paragraphs 1.17 ni ailleurs (0 occurrence dans `vendor/drupal`).
- **Impact** : les `#states` qui masquent « titre d'ancre » (`field_titre_ancre`) et l'image de fond selon le type opener/closer/solid ne sont plus posés. L'UI d'édition des paragraphes est dégradée, sans erreur.
- **Statut** : Vérifié en lecture
- **Test** : [Kernel] `\Drupal::moduleHandler()->hasImplementations()` ne suffit pas. Il faut construire le form display `node.vactory_page.default`, `entity.form_builder->getForm($node)` avec un paragraphe `vactory_paragraph_*`, puis assert `#states` présent sur `field_titre_ancre`. [Statique] script CI qui liste les fonctions `*_form_alter` / `*_alter` du code custom dont le nom de hook n'est invoqué nulle part dans `vendor/` + `modules/`.

## G07 — APIs du cœur supprimées en D11 encore utilisées (hors G03)

- **Catégorie** : dépréciation (retrait D11)
- **Paquet** : drupal/core
- **Preuve** (chaque API vérifiée `@deprecated … removed from drupal:11.0.0` dans `vendor/drupal/core`) :
  - `system_retrieve_file()` (déprécié 10.2) : `vactory_content_package/src/Services/ContentPackageManager.php:779`, `vactory_page/modules/vactory_page_import/src/Services/PageImportService.php:570`. Import de packages de contenu et import xlsx de pages (médias par URL).
  - Clés de validateurs `file_validate_*` (déprécié 10.2, `vendor/drupal/core/modules/file/src/Validation/FileValidator.php:48,64` : « will throw an exception in drupal:11.0.0 ») : **42 occurrences dans 22 fichiers** (ex. `vactory_locator/src/Plugin/Block/VactoryLocatorBlock.php:82-151`, `vactory_dynamic_field/src/Plugin/Field/FieldWidget/FormWidgetTrait.php:64-85`, `vactory_migrate/modules/vactory_migrate_ui/src/Form/Import.php:108`).
  - `user_role_permissions()` (déprécié 10.1, `user.module:238`) : `vactory_timeout/vactory_timeout.module:54`, appelé dans `hook_page_attachments`, donc à chaque page.
  - `drupal_rewrite_settings()` (déprécié 10.1) : `vactory_core/vactory_core.drush.inc:72` (fichier déjà mort côté Drush, voir F11).
  - `PhpassHashedPassword` : voir G02. Bibliothèque `core/js-cookie` : voir G10.
- **Impact** : avec D11, chaque formulaire d'upload (`managed_file`) qui utilise ces clés lève une exception dès qu'un fichier est envoyé. Les imports par URL et `vactory_timeout` (sur toutes les pages) cassent. En 10.5, rien n'est visible : les avertissements sont muets (`@trigger_error`).
- **Statut** : Vérifié en lecture
- **Test** : [Statique] `drupal-check` / PHPStan `phpstan-deprecation-rules` avec `drupal_root` = core lockée, en CI sur chaque PR Dependabot (échec si une dépréciation retirée dans la version majeure suivante apparaît). [Kernel] `\Drupal::service('file.validator')->validate($file, ['FileExtension' => ['extensions' => 'png']])` dans un test par formulaire d'upload P1, avec `SYMFONY_DEPRECATIONS_HELPER` qui échoue sur les dépréciations.

## G08 — Normalizer webform : format interne des validateurs (webform 6.2)

- **Catégorie** : couplage à une structure interne contrib
- **Paquet** : drupal/webform 6.2.2 (contrainte `^6.2@beta`, qui accepte une 6.3 beta)
- **Preuve** : `modules/vactory_decoupled/modules/vactory_decoupled_webform/src/Webform.php:576-577` lit `$element['#upload_validators']['file_validate_extensions'][0]`, format posé par webform 6.2 (`vendor/drupal/webform/src/Plugin/WebformElement/WebformManagedFileBase.php:244`). Dans le même module, le resource d'upload utilise déjà le format constraint (`WebformFileUploadResource.php:217` : `'FileExtension' => ['extensions' => …]`). Le cœur lit les deux formats (`vendor/drupal/core/modules/file/src/Element/ManagedFile.php:366-372`).
- **Impact** : dès que webform pose `FileExtension` (passage aux constraints exigé par D11, attendu en 6.3), `validation.extensions` et `extensionsClean` disparaissent du schéma JSON. Le front Next.js n'affiche plus les extensions acceptées et ne filtre plus côté client. Pas d'erreur.
- **Statut** : Vérifié en lecture pour 6.2. Format de 6.3 **à vérifier**.
- **Test** : [ExistingSite/Kernel] étendre `WebformNormalizerTest` : webform avec un `managed_file` `#file_extensions: 'pdf docx'` → `properties.validation.extensions === '.pdf,.docx'`. Il échoue dès que la clé change.

## G09 — `vactory_decoupled` modifie l'entité `comment` sans dépendre du module

- **Catégorie** : dépendance core non déclarée (code, pas config)
- **Paquet** : drupal/core (`comment`), drupal/recaptcha (contrainte `jsonapi_recaptcha`)
- **Preuve** : `modules/vactory_decoupled/vactory_decoupled.module:408-409` : `$entity_types['comment']->addConstraint('jsonapi_recaptcha');` sans `isset()`. `vactory_decoupled.info.yml` ne déclare ni `comment` ni `recaptcha`, alors que le validateur importe `Drupal\recaptcha\…\Drupal8Post` (`src/Plugin/Validation/Constraint/RecaptchaValidator.php:9`). `comment` n'est installé que via le profil (`vactory_starter_kit.info.yml:19`).
- **Impact** : un projet qui désinstalle `comment` (fréquent en headless) obtient « Call to a member function addConstraint() on null » à chaque reconstruction du cache des types d'entité, et le site devient inutilisable. La contrainte s'applique aussi à toute validation de node sur une requête non-GET hors route admin (ex. formulaires front qui valident un node).
- **Statut** : Vérifié en lecture
- **Test** : [Kernel] installer `vactory_decoupled` et ses dépendances déclarées **sans** `comment` → `entity_type.manager->clearCachedDefinitions(); getDefinitions()` sans erreur. Ce test générique, « chaque module s'installe avec ses seules dépendances déclarées », attrape aussi B15/F12.

## G10 — Complément à F07 : bibliothèques core inexistantes, retirées en D11, ou non déclarées

- **Catégorie** : assets core
- **Paquet** : drupal/core (`core.libraries.yml`)
- **Preuve** :
  - `core/jquery.form` n'existe plus (seul `internal.jquery.form` subsiste, `vendor/drupal/core/core.libraries.yml:859`, « Do not depend on it outside core »). Il est attaché par `vactory_search/modules/vactory_frequent_searches/src/Form/FrequentSearchesAdminForm.php:153` et `vactory_simulation_credit/src/Form/VactorySimulationCreditAdminForm.php:128`. `AssetResolver` ignore la bibliothèque sans rien signaler.
  - `core/js-cookie` : `deprecated … removed in Drupal 11.0.0. There is no replacement` (`core.libraries.yml:1019-1028`). Il est requis par `vactory_splashscreen.libraries.yml:17`, `vactory_dynamic_field.libraries.yml:65` (`pop_in`), `vactory_push_notifications.libraries.yml:18`, `vactory_user_guide.libraries.yml:8`. Le JS appelle `Cookies.get/set` (`vactory_splashscreen/assets/js/splash-screen-v1.js:23,44`).
  - Scan des `*.libraries.yml` × contenu JS : 45 bibliothèques utilisent `Drupal.*` sans `core/drupal`, 22 utilisent `jQuery` sans `core/jquery`, 2 utilisent `Drupal.ajax` sans `core/drupal.ajax` (`vactory_espace_prive/…/managed_file_upload_complete_event_command.js`, `vactory_report_content/js/report-content.js`). Exemples : `vactory_appointment.libraries.yml` (`submit_appointment.calendar`, `adviser.calendar`).
- **Impact** : en D10, jQuery n'est plus chargé d'office. Ces scripts ne marchent que si une autre bibliothèque de la page tire les dépendances. Une MAJ du thème, d'un contrib ou du cœur qui en retire une sur la page provoque une `ReferenceError` côté navigateur, invisible côté PHP. En D11, popin/splashscreen/notifications push perdent la gestion des cookies (le JS se protège par `typeof Cookies === 'undefined'`, donc la fonctionnalité est perdue en silence).
- **Statut** : Vérifié en lecture. Effet navigateur **à vérifier**.
- **Test** : [Kernel] pour chaque bibliothèque des modules du profil : `library.discovery->getLibraryByName()` non vide et chaque dépendance `core/*` résolue (attrape `jquery.form`, `drupalSettingss`, `jquery.ui.accordion`, `js-cookie` en D11). [Statique] le script de scan JS↔dépendances (regex `once(`, `Drupal.`, `jQuery`, `Cookies.`) en CI. [ExistingSiteJavascript] page avec splashscreen → pas d'erreur console.

## G11 — Complément à F13 : la config livrée référence des plugins sans fournisseur

- **Catégorie** : config ↔ plugins
- **Paquets** : drupal/core (views, field), views_autocomplete_filters (G05)
- **Preuve** :
  - `plugin_id: vactory_date` : `vactory_blog/config/install/views.view.vactory_blog.yml:1072,1191,1381,1522`. Aucun plugin `vactory_date` dans le dépôt, son historique (`git log -S`) ni `vendor/` : il vient d'un code disparu.
  - Widget `vcc_entity_reference_autocomplete` : `vactory_business/config/install/core.entity_form_display.node.vactory_business.default.yml:329`. `vactory_cross_content` ne fournit que `field_cross_content_widget_autocomplete` (`src/Plugin/Field/FieldWidget/CrossContentAutocomplete.php:16`).
  - Les 3 `views_autocomplete_filters_*` de G05.
- **Impact** : au runtime, Views retombe sur l'ID de `views_data` et le form display sur le widget par défaut, avec des journaux d'erreur sans casse visible. En revanche, les types de schéma `views.filter.<plugin_id>` n'existent pas, et tout test Kernel/Functional en schéma strict qui installe `vactory_blog`, `vactory_business`, `vactory_locator`, `vactory_smart_search` ou `vactory_faq_prive` échoue avant de tester quoi que ce soit.
- **Statut** : Vérifié en lecture (scan de tous les `core.entity_*_display`, `views.view`, `block.block` de `config/{install,optional}` contre les plugins de `vendor/` + `modules/`). Les autres ID sont résolus : enhancers jsonapi_extras, processeurs/backends search_api, facets.
- **Test** : [Kernel] par module : `installConfig()` avec `$strictConfigSchema = TRUE`, puis, pour les displays, `getComponent()` → `getRenderer()` non nul et du type attendu.

## G12 — Complément à ci.md : `cweagans/composer-patches` absent du lock et non autorisé

- **Catégorie** : composer / patches
- **Paquet** : cweagans/composer-patches
- **Preuve** : `composer.lock` : 0 occurrence de `cweagans`. `composer.json` (`config.allow-plugins`) n'autorise que `php-http/discovery`. `extra.enable-patching: true` est une option de la v1 du plugin.
- **Impact** : un `composer install` lancé **dans le profil** (hook `post-merge`, CI qui partirait de ce dépôt, `vendor/` local) n'applique aucun des ~20 patches. Côté projet, si le projet racine passe à `cweagans/composer-patches` 2.x, `enable-patching` est ignoré et les patches des dépendances ne sont plus appliqués par défaut. **À vérifier** dans la doc v2 et le template projet. Les fonctionnalités qui n'existent que par patch (B12 hook webform, cache facettes, claims simple_oauth, JWKS public) disparaissent alors sans erreur composer.
- **Statut** : absence vérifiée en lecture. Comportement v2 à vérifier.
- **Test** : [CI] étape après `composer install` du projet : `grep -q post_handler_submission vendor/…/webform/src/WebformSubmissionStorage.php` (un marqueur par patch). [Kernel] un test par patch fonctionnel (voir B12).

## G13 — Complément à F14 : `sebastian/diff` et `google/recaptcha` utilisés directement

- **Catégorie** : transitive non déclarée
- **Paquets** : sebastian/diff 4.0.6 (via `drupal/core ^4`, `vendor/drupal/core/composer.json:49`), google/recaptcha 1.3.0 (via `drupal/recaptcha ^1.2`)
- **Preuve** : `vactory_diff/modules/vactory_diff_config_client/src/Service/ConfigComparisonService.php:12-13,739-743` (`new UnifiedDiffOutputBuilder(…)`, `new Differ($builder)`). `vactory_decoupled/src/EventSubscriber/JsonApiReCaptchaSubscriber.php:16` et `src/Plugin/Validation/Constraint/RecaptchaValidator.php:13` (`ReCaptcha\ReCaptcha`).
- **Impact** : versions dictées par le cœur et par recaptcha. Un changement de contrainte côté cœur (D11) ou côté recaptcha (passage à un autre client) casse la comparaison de config et la vérification reCAPTCHA JSON:API. Les appels actuels restent compatibles avec la signature `Differ(DiffOutputBuilderInterface)` des versions suivantes (à vérifier).
- **Statut** : Vérifié en lecture
- **Test** : [Statique] `composer-require-checker` (déjà proposé en F14) doit lister ces deux paquets. [Unit] `ConfigComparisonService` : diff de deux tableaux → sortie unifiée attendue.

---

## Zones couvertes sans finding nouveau

- **Twig** : toutes les fonctions et filtres utilisés dans les 1 023 fichiers `.twig` du code custom sont fournis par Twig 3.22, le cœur, twig_tweak, simplify_menu ou `vactory_core` (scan contre les `TwigFilter`/`TwigFunction` des extensions installées). Pas de `{% for … if %}`, pas de balise `{% filter %}` ni `{% spaceless %}` ; `apply spaceless` est déjà connu.
- **Services** : tous les `\Drupal::service()` / `$container->get()` du code custom pointent vers un service existant, sauf `openai_embeddings.pinecone_client` (B05), `key.repository` (gardé par `hasService`) et `vactory_academy.flag` (service jamais déclaré, mais le contrôleur `ApiFlagging` n'est routé nulle part : code mort, hors dépendances).
- **Événements contrib** souscrits (`FlagEvents`, `ResourceTypeBuildEvents`, `UserEvents`) : présents dans les versions lockées.
- **Enhancers jsonapi_extras** référencés par les 85 configs de ressources : tous fournis.
- **search_api / facets / autocomplete** (processeurs, backends, widgets) en config : tous fournis.
- **`hook_update_N`** : aucun appel à une API contrib susceptible de casser. Seuls des champs de base, `module_installer->install()` et des entités paragraph/block_content sont utilisés. Hors périmètre : `vactory_webform_typesafe_update_10002` charge des `webform_submission` avec des ID de `vactory_webform_analysis` (`.install:106-109`).
- **Modules retirés du cœur en D11** (forum, statistics, tracker, tour, book, action) : non utilisés.

## Non couvert

- Thème `vactory` (hors dépôt) : templates, JS, bibliothèques.
- Exécution réelle : tout est en lecture. Les effets « à vérifier » (G03 extensions, G04 champs servis, G05, G10 navigateur) demandent un site installé.
- Dépréciations core retirées en **D12** (`renderPlain` ×36, `FileSystemInterface::EXISTS_*` ×12, `user_load_by_name`) : non détaillées.
- `hook_module_implements_alter` (`vactory_decoupled.module:1038-1044`) face aux hooks OOP (D11.1+) : ordre des hooks non garanti si simple_oauth migre ses hooks en attributs. Non évalué.
- `modules/mae_group_poc` (non suivi) : `group`/`gnode`, `symfony/process`. Non revu.
