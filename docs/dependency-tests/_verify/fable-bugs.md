# Vérification indépendante — Groupe 1 (B01 à B16)

Méthode : lecture seule du code du profil (branche `3.x`) et des libs dans `vendor/`. Pour chaque lib citée, la version installée (`vendor/composer/installed.json`) a été comparée à la version lockée (`composer.lock`) : **elles sont identiques pour toutes les libs utilisées ici** (xmlsitemap 1.5.0, oauth_login_oauth2 2.6.0, jsonapi_extras 3.24.0, openai 1.0.0-beta4, search_api_ai 1.0.0-alpha2, webform 6.2.2, views_autocomplete_filters 1.7.0, simplify_menu 3.2.0, flag 5.0.3, firebase/php-jwt 6.10.0, mailchimp/marketing 3.0.80, guzzle 7.10.0, core 10.5.7, workbench_moderation 1.7.0, shield 1.7.0). Le `vendor/` est sans patches. Patches gist téléchargés et lus : oauth_login_oauth2, webform, jsonapi_extra(s), simplify_menu, search_api_ai. Le patch `views_autocomplete_filters` est hébergé sur drupal.org : non téléchargé (hors périmètre réseau autorisé).

Rien n'a été exécuté : les conséquences runtime annoncées sont déduites de la lecture du code.

| ID | Verdict | Gravité | Preuve (fichier:ligne) | Commentaire |
|---|---|---|---|---|
| B01 | CONFIRMÉ (impact quasi nul) | Cosmétique | `modules/vactory_core/vactory_core.module:428` ; `vendor/drupal/xmlsitemap/src/XmlSitemapLinkStorage.php:221` ; `vendor/drupal/xmlsitemap/src/XmlSitemapGenerator.php:295-296` | Faute de frappe réelle, mais la ligne 427 (`status = 0`) suffit déjà à exclure le lien du sitemap. |
| B02 | CONFIRMÉ | Mineur | `modules/vactory_dynamic_field/vactory_dynamic_field.install:27-28` ; `modules/vactory_dynamic_field/vactory_dynamic_field.info.yml:14` | Condition inversée. Les nouvelles installations sont couvertes par la dépendance `.info.yml` ; seuls les sites existants mis à jour restent sans `paragraphs_edit`. |
| B03 | CONFIRMÉ | Bloquant (pour `vactory_sso`, module optionnel) | `modules/vactory_sso/vactory_sso.module:8,38` ; `vendor/drupal/oauth_login_oauth2/src/Controller/miniorange_oauth_clientController.php:28,247` | La classe n'existe ni dans la lib, ni dans le patch, ni dans notre code. Erreur fatale sur `/mo_login`. |
| B04 | CONFIRMÉ (impact surestimé) | Mineur | `composer.json:137,250` ; `modules/vactory_decoupled/vactory_decoupled.module:886` ; `vendor/drupal/jsonapi_extras/src/ResourceType/ConfigurableResourceType.php:156` | Patch jamais appliqué, hook mort. Mais le champ calculé pose déjà l'essentiel des cache tags : la perte est partielle. |
| B05 | CONFIRMÉ | Bloquant (pour `vactory_decoupled_search_ai`, module optionnel) | `.../vactory_decoupled_search_ai/src/Controller/SearchAiController.php:17,60,79` ; `vendor/drupal/openai/modules/openai_embeddings/openai_embeddings.services.yml:1-4` | Service et classe absents. Aggravation : `search_api_pinecone` alpha2 dépend du même service absent. |
| B06 | CONFIRMÉ | Majeur | `modules/vactory_html2pdf/src/Services/VactoryHtml2PdfManager.php:122,130` ; `modules/vactory_html2pdf/config/install/vactory_html2pdf.settings.yml:1-10` ; `modules/vactory_quiz/modules/vactory_quiz_certificat/src/Plugin/QueueWorker/GenerateCertificatQueueProcessor.php:175,212,215` | Clé absente de la config d'install. Le `TypeError` n'est pas rattrapé par l'appelant. |
| B07 | CONFIRMÉ | Majeur | `.../vactory_webform_mailchimp_handler/src/Plugin/WebformHandler/MailchimpWebformHandler.php:9,190,270,294,324` ; `vendor/guzzlehttp/guzzle/src/Exception/ConnectException.php:13` | En Guzzle 7, `ConnectException` n'hérite pas de `RequestException`. L'exception sort en fait dès `getMergeFields()` (l.324), avant l'appel d'inscription. |
| B08 | CONFIRMÉ (mal décrit) | Mineur | `modules/vactory_decoupled/src/Controller/DecoupledOauth2Token.php:193,206` ; `modules/vactory_security_review/src/Form/SettingsForm.php:40` | Seul le log de **succès** (l.206) est mort. Le log d'**échec** (l.193) lit la bonne clé et fonctionne. |
| B09 | CONFIRMÉ | Mineur (fragilité, pas un bug actuel) | `modules/vactory_decoupled/src/SimpleOauthAuthenticationProvider.php:204` ; `vendor/firebase/php-jwt/src/JWT.php:470,479` | Fonctionne avec la 6.10.0 lockée. Casse silencieusement si le libellé amont change. |
| B10 | CONFIRMÉ (sous-estimé) | Majeur | `modules/vactory_toolbox/config/install/core.entity_form_display.block_content.vactory_toolbox.default.yml:13` ; `vendor/drupal/core/lib/Drupal/Core/Config/ConfigInstaller.php:525-527` | Dépendance parasite dans `config/install` : l'activation de `vactory_toolbox` doit échouer (`UnmetDependenciesException`) tant que `workbench_moderation` n'est pas activé. |
| B11 | CONFIRMÉ | Mineur | `modules/vactory_appointment/vactory_appointment.module:317-318` ; `config/install/system.theme.yml:1` ; `vactory_starter_kit.install:80` | Branche morte avec Claro. `seven` n'est que le thème de l'installeur (`vactory_starter_kit.info.yml:9`). |
| B12 | CONFIRMÉ | Majeur (si le patch n'est pas appliqué) | `.../vactory_webform_storage/vactory_webform_storage.module:48` ; `vendor/drupal/webform/src/WebformSubmissionStorage.php:1120-1122` ; `composer.json:239-241` | Hook inexistant dans webform 6.2.2 non patché. Sans patch, l'option « ne pas stocker ce champ » est silencieusement inopérante. |
| B13 | CONFIRMÉ (sans patch) / À EXÉCUTER (avec patch) | Majeur sans patch, nul avec | `vendor/drupal/views_autocomplete_filters/js/views-autocomplete-filters-dependent.js:228` ; `vendor/drupal/views_autocomplete_filters/views_autocomplete_filters.libraries.yml:9-16` ; `composer.json:258-260` | `.once()` présent en 1.7.0. Un patch est déclaré, mais antérieur à la 1.7 : son application reste à vérifier. |
| B14 | CONFIRMÉ | Mineur (sécurité) | `config/install/shield.settings.yml:5-6,1` | `vactory` / `void_vactory` en clair. Shield désactivé par défaut (`shield_enable: false`). |
| B15 | CONFIRMÉ (flag) / PARTIELLEMENT INFIRMÉ (pinecone) | Majeur (flag) / Mineur (pinecone) | `.../vactory_decoupled_flag/vactory_decoupled_flag.info.yml:6-8` ; `.../vactory_decoupled_flag/src/Controller/ApiFlagging.php:6,66` ; `.../vactory_decoupled_flag/config/install/flag.flag.default_flag.yml` ; `composer.json:158` ; `vendor/drupal/search_api_ai/modules/pinecone/search_api_pinecone.info.yml` | `flag` non déclaré : vrai. `search_api_pinecone` non déclaré dans le `.info.yml` : vrai. Mais il **est** fourni par composer : c'est un sous-module de `drupal/search_api_ai`. |
| B16 | CONFIRMÉ | Mineur (fragilité, pas un bug actuel) | `modules/vactory_core/src/VactoryCoreServiceProvider.php:21-26,28-30` ; `modules/vactory_core/src/MenuItems.php:65-72` ; `vendor/drupal/simplify_menu/simplify_menu.services.yml:4` | Correct avec simplify_menu 3.2.0 (un seul argument amont). Même motif sur `menu.default_tree_manipulators` du cœur. |

Décompte : 16 affirmations, 16 confirmées sur le fait central, dont 1 partiellement infirmée (B15, volet composer) et 1 dont la gravité dépend d'une exécution (B13). Aucune infirmée en totalité.

---

## B01 — clé `acccess`

- `modules/vactory_core/vactory_core.module:428` : `$link['acccess'] = FALSE;`. Faute de frappe avérée.
- `XmlSitemapLinkStorage::save()` n'écrit que des clés nommées (`XmlSitemapLinkStorage.php:219-230`) : la clé `acccess` est ignorée, sans erreur SQL.
- Échappatoire trouvée : la ligne 427 pose `status = 0`, et le générateur exige `x.access = 1` **et** `x.status = 1` (`XmlSitemapGenerator.php:295-296`). Le lien de la page d'accueil est donc déjà exclu.
- Impact concret : aucun sur le sitemap produit ; ligne morte à corriger ou supprimer.

## B02 — `update_9500` inversé

- `vactory_dynamic_field.install:27-28` : `if (moduleExists('paragraphs_edit')) { install(['paragraphs_edit']); }`. `moduleExists()` ne renvoie vrai que pour un module déjà installé : l'appel est toujours un no-op.
- Échappatoire trouvée : `vactory_dynamic_field.info.yml:14` déclare `paragraphs_edit` en dépendance, donc toute **nouvelle** installation l'active.
- Reste concerné : un site installé avant l'ajout de cette dépendance. Le contrôle de `update.php` vérifie la présence des fichiers du module requis, pas son activation.
- Impact concret : sur ces sites, les liens d'édition du tableau de bord « pending content » (`PendingContentDashboard.php:172`, route `paragraphs_edit`) mènent à une 404.
- C'est le seul `hook_update_N` du fichier.

## B03 — `oauth_login_oauth2Controller`

- `vactory_sso.module:8` importe `Drupal\oauth_login_oauth2\Controller\oauth_login_oauth2Controller`, instancié l.38.
- La lib 2.6.0 ne contient qu'une classe de contrôleur : `miniorange_oauth_clientController` (`miniorange_oauth_clientController.php:28`). `getnestedattribute()` y existe (l.247).
- Le patch composer déclaré (`composer.json:219-221`) n'ajoute qu'une ligne `$_SESSION['resource_owner'] = $resourceOwner;` : il ne crée ni ne renomme de classe.
- Aucune définition ni `class_alias` dans notre code (recherche sur `modules/`, `src/`, fichiers du profil).
- Atteignabilité : la route `oauth_login_oauth2.mo_login` existe (`oauth_login_oauth2.routing.yml:58`) et le contrôleur appelle `user_login_finalize()` (l.230), qui déclenche `hook_user_login`. La condition de la l.33 est donc vraie à chaque connexion SSO.
- Même corrigé sur le nom de classe, `new miniorange_oauth_clientController()` échouerait : le constructeur exige un `FormBuilder` (l.31).
- Limites : `vactory_sso` n'est pas activé par le profil et ne déclare aucune dépendance (`vactory_sso.info.yml`). L'état exact de la session après l'erreur n'a pas été observé.
- Impact concret : toute connexion SSO se termine en erreur fatale `Class not found` dès que `vactory_sso` est activé.

## B04 — patch sous `drupal/jsonapi_extra`

- `composer.json:250` : clé `drupal/jsonapi_extra`. Le paquet requis et locké est `drupal/jsonapi_extras` (`composer.json:137`, `composer.lock:5866`). composer-patches associe par nom de paquet : le patch n'est jamais appliqué.
- Le patch (lu) ajoute dans `getFieldEnhancer()` un `invokeAll('jsonapi_default_enhancers_info')`. Le code 3.24.0 non patché (`ConfigurableResourceType.php:156-158`) n'invoque pas ce hook. Aucun autre appelant dans notre code.
- `vactory_decoupled_jsonapi_default_enhancers_info()` (`vactory_decoupled.module:886`) est donc mort : l'enhancer `vactory_blocks` n'est jamais branché par défaut sur `internal_blocks`. Les configs livrées ont `enhancer.id: ''` pour ce champ (ex. `modules/vactory_news/config/install/jsonapi_extras.jsonapi_resource_config.node--vactory_news.yml:182-187`).
- Échappatoire trouvée, qui réduit la gravité : l'enhancer ne fait qu'ajouter de la cacheabilité (`VactoryBlocksFieldEnhancer.php:55-66`). Or le champ calculé pose déjà `config:block_list`, `block_list`, `block_content_list` et un contexte de cache (`InternalNodeEntityBlocksFieldItemList.php:85-93,114-115`).
- Ce qui est réellement perdu : les cache tags propres à chaque bloc et le contexte `url.query_args:q`.
- Le contexte du patch correspond au code 3.24.0 : corriger le nom du paquet devrait suffire.
- Impact concret : risque limité de réponses JSON:API périmées quand un bloc change sans invalider les tags de liste.

## B05 — `openai_embeddings.pinecone_client`

- `SearchAiController.php:79` : `$container->get('openai_embeddings.pinecone_client')` ; l.17 et l.60 : type `Drupal\openai_embeddings\Http\PineconeClient`.
- openai 1.0.0-beta4 : `openai_embeddings.services.yml` ne définit que `plugin.manager.vector_client`. Pas de dossier `src/Http/`. La chaîne `openai_embeddings.pinecone_client` n'apparaît que comme nom de **config** migrée (`openai_embeddings.install:94,105`). Le client Pinecone est désormais un plugin (`src/Plugin/openai_embeddings/vector_client/Pinecone.php:23`).
- Aucune définition de ce service ni de cette classe dans notre code.
- Aggravation non signalée : `search_api_pinecone` (search_api_ai 1.0.0-alpha2) utilise le même service absent (`SearchApiPineconeBackend.php:7,63`). Le backend lui-même est cassé avec openai beta4, et `drupal/search_api_ai` requiert `drupal/openai: *` sans borne.
- Autre défaut : l'index `ai_search` (l.104) n'est livré par aucune config ; s'il est absent, `$index->getServerInstance()` (l.105) échoue sur `null`.
- Limite : module optionnel, non activé par le profil.
- Impact concret : `/api/search-ai` renvoie une 500 (`ServiceNotFoundException`) à chaque appel.

## B06 — `array_merge` sur config NULL

- `VactoryHtml2PdfManager.php:122` lit `vactory_html2pdf_font_dirs` ; l.130 : `array_merge($fontDirs, $custom_fonts_directories)`.
- `config/install/vactory_html2pdf.settings.yml` ne contient que `font_directories` et `fonts_data`. La clé n'est écrite que par `SettingsForm.php:118`. Aucun `hook_install` ni update ne la pose.
- En PHP 8, `array_merge(array, null)` lève un `TypeError`.
- L'appelant ne compense pas : `GenerateCertificatQueueProcessor.php:212,215` n'attrape que `MpdfException` et `VactoryHtml2PdfException`. Un `TypeError` est un `\Error`.
- Après enregistrement du formulaire, la valeur est toujours un tableau (`SettingsForm.php:89`) : le bug disparaît.
- Impact concret : toute génération de PDF (dont les certificats de quiz) échoue sur un site où le formulaire de réglages n'a jamais été enregistré.

## B07 — `ConnectException` non attrapée

- Guzzle 7.10.0 : `ConnectException extends TransferException` (`ConnectException.php:13`), `RequestException extends TransferException` (`RequestException.php:14`). Ce sont deux branches sœurs.
- Le SDK relance tel quel (`ListsApi.php:9306-9309`) et n'attrape que `RequestException` : une `ConnectException` remonte.
- Les quatre `catch` du handler visent `RequestException` (`MailchimpWebformHandler.php:190,270,294,324`).
- Précision par rapport à l'affirmation : dans `submitForm()` (l.225), les premiers appels réseau sont `getMergeFields()` (l.243) puis `getInterests()` (l.252). L'exception sort donc dès la l.321, avant le `try` de la l.267.
- Les erreurs HTTP 4xx/5xx sont, elles, bien attrapées (`ClientException` et `ServerException` héritent de `RequestException`).
- Défaut voisin : si `getMergeFields()` attrape une erreur, `$response` est indéfini à la l.327 (avertissements PHP, pas fatal).
- Impact concret : une panne DNS, un timeout ou un refus de connexion vers Mailchimp fait échouer la soumission du webform pour l'utilisateur.

## B08 — clé `failed_auth_log`

- `DecoupledOauth2Token.php:206` lit `failed_auth_log` dans `logAuthSuccess()`.
- `DecoupledOauth2Token.php:193` lit `log_failed_auth` dans `logAuthFailure()` : c'est la bonne clé, celle qu'enregistre `vactory_security_review/src/Form/SettingsForm.php:40`.
- `failed_auth_log` n'est écrite nulle part (ni formulaire, ni YAML).
- Écart avec l'affirmation : ce n'est pas « le log » qui est inactif, c'est uniquement la trace de **connexion réussie** (« Session opened for … via Oauth2 »). La trace des échecs fonctionne.
- Impact concret : pas de journalisation des ouvertures de session OAuth2, même quand l'option est cochée.

## B09 — détection par `"kid"` dans le message

- `SimpleOauthAuthenticationProvider.php:202-204` : `catch (\UnexpectedValueException)` puis `strpos($exception->getMessage(), '"kid"')`.
- php-jwt 6.10.0 émet bien `"kid" empty, unable to lookup correct key` (`JWT.php:470`) et `"kid" invalid, unable to lookup correct key` (`JWT.php:479`). Le mécanisme fonctionne avec la version lockée.
- Les autres `UnexpectedValueException` (signature invalide, token expiré) ne contiennent pas `"kid"` et donnent une 401 directe : comportement voulu.
- Le rafraîchissement forcé est limité à un par minute (l.276-281).
- Impact concret : aucun aujourd'hui ; si php-jwt reformule ces messages, la rotation de clés Keycloak provoquera des 401 jusqu'à expiration du cache JWKS (1 h, l.296).

## B10 — dépendance `workbench_moderation`

- `core.entity_form_display.block_content.vactory_toolbox.default.yml:13` : `dependencies.module: workbench_moderation`. Aucun composant du formulaire ne l'utilise : dépendance parasite d'export.
- Le profil installe `content_moderation` (`vactory_starter_kit.info.yml:189`). `workbench_moderation` est requis par composer (`composer.json:132`) mais n'est dépendance d'aucun `.info.yml` du dépôt.
- Conséquence plus forte que l'affirmation : pour une config de `config/install`, `ConfigInstaller::checkConfigurationToInstall()` lève `UnmetDependenciesException` si un module requis n'est pas activé (`ConfigInstaller.php:525-527`).
- Limite : `vactory_toolbox` n'est pas activé par le profil ; l'échec d'installation est déduit du code du cœur, pas observé.
- Impact concret : `vactory_toolbox` ne peut pas être activé sur un site standard du profil sans activer d'abord `workbench_moderation`.

## B11 — thème `seven`

- `vactory_appointment.module:317-318` : la condition exige `$theme_name == 'seven'`.
- Thème d'administration du profil : `claro` (`config/install/system.theme.yml:1`, `vactory_starter_kit.install:80`). Liste des thèmes installés : `vactory`, `claro` (`vactory_starter_kit.info.yml:193-195`).
- `seven` n'apparaît que comme thème de l'installeur (`vactory_starter_kit.info.yml:9`) et dans composer. La branche n'est atteinte que si un projet repasse manuellement son thème d'administration à Seven.
- Impact concret : les champs `field_adviser_holiday` et `field_adviser_appointments` restent visibles dans le formulaire utilisateur du back-office.

## B12 — hook `post_handler_submission`

- `vactory_webform_storage.module:48` implémente `hook_post_handler_submission_alter`.
- webform 6.2.2 non patché : `WebformSubmissionStorage.php:1120-1122` se termine sur `invokeWebformHandlers('postSave', …)`, sans `alter`. Aucune autre invocation dans `vendor/` ni dans notre code.
- Le patch déclaré (`composer.json:239-241`, lu) ajoute exactement `\Drupal::moduleHandler()->alter('post_handler_submission', $entity);` à cet endroit. Le contexte correspond à la 6.2.2 (décalage de quelques lignes).
- Le patch utilise des chemins `a/modules/contrib/webform/…` : il ne s'applique qu'au niveau `-p4`. À confirmer sur une installation réelle.
- Impact concret : sans le patch, les champs marqués « ne pas stocker » sont conservés en base, fichiers compris, sans aucun message d'erreur.

## B13 — `.once()` dans views_autocomplete_filters

- `views-autocomplete-filters-dependent.js:228` : `$(context).find('input.form-autocomplete').once('autocomplete')`. La bibliothèque ne déclare que `core/jquery`, `core/drupal`, `core/drupal.form` (`views_autocomplete_filters.libraries.yml:9-16`). Drupal 10 ne fournit plus `jquery.once`.
- L'autre fichier JS du module n'utilise pas `.once()`.
- Atteignabilité : ce JS n'est attaché que si l'option `autocomplete_dependent` est active (`ViewsAutocompleteFiltersTrait.php:198-199`). Trois vues livrées l'activent : `views.view.vactory_smart_search.yml:523`, `views.view.vactory_faq_prive.yml:379`, `views.view.vactory_quiz.yml:242`.
- Compensation déclarée : `composer.json:258-260`, patch « D10: jquery once is removed » daté de mars 2023. La 1.7.0 date d'octobre 2023 et contient encore `.once()`. Le patch n'a pas pu être lu : son application sur la 1.7.0 est **à exécuter**.
- Impact concret (si le patch ne s'applique pas) : l'autocomplétion est cassée sur ces trois vues, avec une erreur JS `once is not a function`.

## B14 — identifiants Shield

- `config/install/shield.settings.yml:5-6` : `user: vactory`, `pass: void_vactory`, fournisseur `shield` (stockage en clair dans la config).
- `shield_enable: false` (l.1) : la protection est désactivée à l'installation.
- Impact concret : tout projet qui active Shield sans changer ces valeurs est protégé par un couple identifiant/mot de passe versionné dans le dépôt du starter kit.

## B15 — dépendances non déclarées

**Volet flag : confirmé.**
- `vactory_decoupled_flag.info.yml:6-8` ne déclare que `vactory_decoupled` et `vactory_decoupled_revalidator`. Aucune de ces deux chaînes n'amène `flag`.
- Usage : `ApiFlagging.php:6` (`Drupal\flag\FlagServiceInterface`), l.66 (`$container->get('flag')`).
- Le module livre en plus `config/install/flag.flag.default_flag.yml`, config dont le fournisseur est `flag`. L'installation sans `flag` doit donc échouer bruyamment (`UnmetDependenciesException`), plutôt que casser plus tard. Déduit du code du cœur, non exécuté.
- `drupal/flag` est bien dans composer (`composer.json:84`).

**Volet pinecone : partiellement infirmé.**
- Vrai : `vactory_decoupled_search_ai.info.yml` ne déclare que `vactory_decoupled_search` et `search_api_ai`, alors que le contrôleur utilise `Drupal\search_api_pinecone\…\SearchApiPineconeBackend` (`SearchAiController.php:14,106`).
- Faux : « ni dans composer.json ». `search_api_pinecone` est un sous-module du paquet `drupal/search_api_ai`, requis (`composer.json:158`) et locké en 1.0.0-alpha2 (`vendor/drupal/search_api_ai/modules/pinecone/search_api_pinecone.info.yml`). Le code est donc bien téléchargé.
- Sujet secondaire devant B05, qui bloque déjà ce module.

## B16 — `addArgument()` positionnels

- `VactoryCoreServiceProvider.php:21-26` : `setClass('Drupal\vactory_core\MenuItems')` puis quatre `addArgument()` ajoutés après les arguments amont.
- Amont 3.2.0 : `arguments: ['@menu.link_tree']` (`simplify_menu.services.yml:4`), constructeur à un paramètre (`vendor/drupal/simplify_menu/src/MenuItems.php:38`). Notre constructeur attend bien 1 + 4 arguments dans cet ordre (`modules/vactory_core/src/MenuItems.php:65-72`). C'est correct aujourd'hui.
- Même motif, non signalé, sur un service du cœur : `VactoryCoreServiceProvider.php:28-30` ajoute `router.admin_context` en 5e position de `menu.default_tree_manipulators`. Cela correspond aux quatre arguments de core 10.5.7 (`core.services.yml:836`, `MenuRoleLinkTreeManipulator.php:32-39`).
- `getDefinition()` lèverait une exception si `simplify_menu` était absent, mais `vactory_core.info.yml:11` le déclare.
- Impact concret : aucun aujourd'hui ; un argument ajouté en amont (simplify_menu ou cœur) décalerait les positions et provoquerait un `TypeError` à la construction du service, donc sur toutes les pages.
