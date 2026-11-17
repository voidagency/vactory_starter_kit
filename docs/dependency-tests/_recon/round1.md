# Recon mi-aveugle — tour 1

Date : 2026-11-17, branche `3.x` (HEAD `808840c7`). Lecture seule. Rien exécuté (le PHP local est cassé : `libtidy.58.dylib`). Versions : `composer.lock` (core 10.5.7, `vendor/drupal/core` identique au lock). Scripts d'analyse (YAML via ruby) dans le scratchpad de session, non versionnés.

Angles couverts que l'audit n'avait pas pris : décorateurs de services **core**, classes de dépendances **transitives** absentes du lock, mécanismes d'assets (`*.libraries.yml`, `/libraries/`, CDN), dépendances de config (`config/install` contre `.info.yml`, script exhaustif), commandes Drush, chaîne CI réelle (`vactory-test-ci`), exigences plateforme cachées (PHP, core), services externes (API Google/Meta).

## Synthèse

| # | Titre | Catégorie | Gravité | Statut |
|---|---|---|---|---|
| F01 | Décorateur `http_client_factory` : TLS désactivé par défaut + options des appelants ignorées | Intégration core / sécurité | **Critique** | Vérifié en lecture |
| F02 | `/image-sitemap.xml` : classe `Laminas\Diactoros` absente du lock → erreur fatale | Transitive disparue | **Majeur** | Vérifié en lecture |
| F03 | Le `composer.lock` du profil n'est utilisé ni par la CI ni par les projets | CI / composer | **Majeur** (méthode) | Vérifié en lecture |
| F04 | `vactory_form_autosave` impossible à installer (dépendance de config `vactory_form_auotosave`) | Dépendance de config | Majeur | Vérifié en lecture |
| F05 | Exigences plateforme cachées : PHP ≥ 8.3 (devel), core ≥ 10.5 (editor_advanced_link), core ≥ 10.3 (code) | Contraintes | Majeur | Vérifié en lecture |
| F06 | 7 librairies front `/libraries/*` non déclarées ; repli `cdn:` de select2 inopérant | Assets front | Majeur | Vérifié en lecture / runtime à vérifier |
| F07 | Dépendances de librairies inexistantes (`core/jquery.ui.accordion`, `core/drupalSettingss`) ignorées en silence | Assets core | Moyen | Vérifié en lecture / effet à vérifier |
| F08 | Services externes : CDN rawgit mort, clé Google Maps en clair, Maps sans clé, API Places « legacy », Graph API v14.0 | Services externes | Moyen | Mixte |
| F09 | Thème `vactory` exigé par le profil mais fourni par aucun paquet | Dépendance hors composer | Moyen | Vérifié en lecture |
| F10 | `vactory_devtools` et `drush vse` installent des modules absents de composer | Dépendance non déclarée | Moyen | Vérifié en lecture |
| F11 | Commandes Drush : drush non déclaré, 3 générations d'enregistrement dont une morte | Outillage | Moyen | Vérifié / À vérifier (Drush 13) |
| F12 | Complément à README §7 / B15 : ~80 dépendances de config non déclarées dans ~45 modules | Dépendance de config | Moyen | Vérifié en lecture |
| F13 | 22 modules livrent de la config sans schéma → les tests Kernel/Functional proposés échoueront avant de tester | Angle mort de test | Moyen | Vérifié en lecture |
| F14 | Bibliothèques transitives utilisées directement sans déclaration (oauth2-server, lcobucci, defuse, shaper) | Transitive | Moyen | Vérifié / À vérifier (v9) |
| F15 | Extensions PHP non déclarées ; `apcu_delete()` non gardé | Plateforme | Mineur | À vérifier |
| F16 | Config composer du dépôt : `disable-tls`, `secure-http: false`, dépôt CKEditor 4 mort | Supply chain | Mineur | Vérifié en lecture |
| F17 | Complément à la fiche domain : branche gardée par une fonction qui n'existe que dans un fork | Fork implicite | Mineur | Vérifié en lecture |

---

## F01 — Décorateur `http_client_factory` : TLS désactivé par défaut et options des appelants ignorées

- **Catégorie** : intégration core (Guzzle / `ClientFactory`) / sécurité
- **Paquets** : drupal/core (`http_client_factory`, `http_client`), guzzlehttp/guzzle 7.10.0, drupal/openai 1.0.0-beta4 (`openai_embeddings`), drupal/xmlsitemap
- **Fichiers** :
  - `modules/vactory_core/vactory_core.services.yml:62-70` : `decorates: http_client_factory`
  - `modules/vactory_core/src/VactoryClientFactory.php:48-50`
  - `modules/vactory_core/src/Form/VactoryCoreConfig.php:47` : case par défaut affichée **cochée** (`?? TRUE`)
  - aucune config par défaut : `modules/vactory_core/config/install/` n'existe pas ; `vactory_core.global_config` n'est livrée nulle part
  - core : `vendor/drupal/core/core.services.yml:607-609` (`http_client` est construit par `http_client_factory:fromOptions`)
- **Preuve** :
  ```php
  public function fromOptions(array $config = []) {
    $http_client_ssl_verification = $this->configFactory->get('vactory_core.global_config')->get('http_client_ssl_verification');
    return $this->clientFactory->fromOptions(['verify' => (bool) $http_client_ssl_verification ?? TRUE]);
  }
  ```
  1. `(bool) NULL ?? TRUE` vaut `FALSE` : le cast passe avant `??`, qui ne s'applique donc jamais. Sur une installation neuve (vactory_core est dans le profil), **le client `http_client` de tout le site a `verify => false`** tant qu'un admin n'a pas enregistré le formulaire. Le formulaire affiche pourtant la case cochée.
  2. `$config` est jeté : tout appelant de `http_client_factory->fromOptions([...])` perd ses options. Exemple : `vendor/drupal/openai/modules/openai_embeddings/src/Plugin/openai_embeddings/vector_client/Pinecone.php:44-51` passe `base_uri` et l'en-tête `API-Key` → requêtes Pinecone sans hôte ni clé. Même chose pour `Milvus.php` et `xmlsitemap_custom`.
- **Impact** :
  - Sortants HTTPS sans vérification de certificat : JWKS Keycloak (`SimpleOauthAuthenticationProvider.php:287`, la clé qui valide les JWT !), userinfo Google/Facebook, TypeSafe, Google Places, WhatsApp… Un MITM peut injecter un JWKS et forger des jetons.
  - Toute dépendance qui configure son client via la factory est cassée sans erreur visible. Cause probable supplémentaire de `/api/search-ai` (B05).
  - Toute MAJ Guzzle/core qui change `ClientFactory::fromOptions()` est masquée par ce décorateur.
- **Statut** : Vérifié en lecture.
- **Test** :
  - [Kernel] `vactory_core/tests/src/Kernel/HttpClientFactoryTest.php` : installer `vactory_core` sans config → `\Drupal::httpClient()->getConfig('verify')` doit valoir `TRUE`.
  - Même test : `\Drupal::service('http_client_factory')->fromOptions(['base_uri' => 'https://x.test', 'headers' => ['X-A' => '1']])` → `getConfig('base_uri')` et l'en-tête sont conservés.

## F02 — `/image-sitemap.xml` : `Laminas\Diactoros\Response\XmlResponse` absente → erreur fatale

- **Catégorie** : dépendance transitive disparue
- **Paquets** : laminas/laminas-diactoros (**absent** du `composer.lock` : le core D10 ne la tire plus, seuls `laminas-servicemanager`, `-stdlib`, `-text` sont présents)
- **Fichiers** : `modules/vactory_image_sitemap/src/Controller/VactoryImageSitemapXml.php:6,22` ; route `vactory_image_sitemap.xml` (`/image-sitemap.xml`)
- **Preuve** : `use Laminas\Diactoros\Response\XmlResponse;` puis `return new XmlResponse($xml);`. Grep du lock : 0 occurrence de `laminas-diactoros`.
- **Impact** : dès que le fichier sitemap existe, `Error: Class "Laminas\Diactoros\Response\XmlResponse" not found` → 500 sur le sitemap d'images (SEO). Le code date de 2022 (`8e42334b`, D9, quand le core tirait encore diactoros). Personne n'a vu la casse au passage à D10.
- **Statut** : Vérifié en lecture.
- **Test** : [ExistingSite] écrire `public://image_sitemap/en_image_sitemap.xml`, GET `/image-sitemap.xml` → 200 + `Content-Type: application/xml`. Plus généralement : un test statique (PHPStan niveau 0 `class.notFound`) attrape toutes les classes absentes du lock.

## F03 — Le `composer.lock` du profil n'est utilisé ni par la CI ni par les projets

- **Catégorie** : CI / composer (angle mort méthodologique)
- **Paquets** : tous
- **Fichiers** :
  - `~/dev/vactory-test-ci/entrypoint.sh:150` : `composer install` du projet `vactory8`. Puis `:154-163` : `composer require weitzman/drupal-test-traits drupal/core-dev "voidagency/vactory_starter_kit:dev-${BRANCH}#${COMMIT_HASH}" --with-all-dependencies --update-with-dependencies`
  - `Dockerfile:9-12` (racine) : `git clone` → `composer install` → **puis** `git checkout $SOURCE_BRANCH_ID`
  - `.drone.yml:24` : `DRUSH_VERSION: 8.4.1`
  - `.github/dependabot.yml` : PR quotidiennes sur `composer` (lock du profil)
- **Preuve** : un paquet installé comme dépendance n'utilise jamais son propre `composer.lock`. La CI résout le profil avec `-W`, donc chaque contrainte `^x` du `composer.json` du profil se résout à la **dernière version disponible au moment du build**. Ni le lock de `vactory8` ni celui du profil ne s'appliquent. `drupal/core-dev` et `drupal-test-traits` ne sont pas versionnés.
- **Impact** :
  - Une PR Dependabot qui ne touche que `composer.lock` produit **exactement le même build** que `3.x` : elle ne teste rien.
  - À l'inverse, un build de `3.x` peut casser du jour au lendemain sans aucun commit (nouvelle version d'une dépendance `^`). C'est le scénario linkit 7.0.13 (#64112).
  - Les projets reçoivent la dernière version permise par les contraintes, pas celle du lock. La consigne « raisonner sur la version lockée » ne décrit donc pas la prod des projets.
  - Le `Dockerfile` racine installe les dépendances de la branche par défaut avant le checkout : phpcs tourne avec le `vendor` de `3.x`, pas celui de la PR (usage de ce Dockerfile à confirmer).
  - `DRUSH_VERSION: 8.4.1` : Drush 8 ne pilote pas D10 (utilisé ? à vérifier dans l'image).
- **Statut** : Vérifié en lecture (comportement composer standard).
- **Test / garde-fou** :
  - [CI] imprimer `composer show -f json` après install et l'archiver par build, pour savoir ce qui a été testé.
  - [CI] job « lowest/highest » : `composer update --prefer-lowest` sur les contraintes du profil, qui attrape aussi les contraintes trop basses (cf. README §1).
  - Pour que le lock serve, la CI doit installer **depuis** le dépôt du profil (projet de test avec `path` repository + lock commité), ou épingler les versions critiques dans `composer.json`.

## F04 — `vactory_form_autosave` impossible à installer

- **Catégorie** : dépendance de config (mécanisme core `ConfigInstaller`)
- **Paquets** : drupal/core (`ConfigInstaller::getMissingDependencies`)
- **Fichiers** : `modules/vactory_form_autosave/config/install/vactory_form_autosave.settings.yml` (fin du fichier) ; core `vendor/drupal/core/lib/Drupal/Core/Config/ConfigInstaller.php:616-630` ; `ModuleInstaller.php:203`
- **Preuve** :
  ```yaml
  dependencies:
    enforced:
      module:
        - vactory_form_auotosave
  ```
  Le core fusionne `enforced` dans les dépendances, et `checkConfigurationToInstall()` lève `UnmetDependenciesException` pour un module inexistant.
- **Impact** : le module ne peut pas être activé (UI ou drush), depuis son ajout (`d92f7aa8`, 2022-02-03, #28231). Aucun signal.
- **Statut** : Vérifié en lecture.
- **Test** : [Kernel, générique] pour chaque module du profil : `\Drupal::service('config.installer')->checkConfigurationToInstall('module', $m)` ne lève pas d'exception (ou `ModuleInstaller::install([$m])` en ExistingSite sur une base jetable). Il attrape aussi B10 (`workbench_moderation`) et F12.

## F05 — Exigences plateforme cachées : PHP 8.3, core 10.5, core 10.3

- **Catégorie** : incohérence contrainte / lock / code
- **Paquets** : drupal/devel, drupal/editor_advanced_link, drupal/core (non contraint), php
- **Fichiers** :
  - `composer.json:10` `"php": ">=7.3"`. Pas de `drupal/core` dans `require`.
  - `composer.json:101` `"drupal/devel": "^5.5"` → lock `drupal/devel 5.5.0` exige **`php >=8.3`** (5.5.0 est le minimum autorisé par `^5.5`). Monté par `f337dbc2` (montée groupée #62859).
  - `composer.json:75` `"drupal/editor_advanced_link": "2.3.4"` (épinglage exact) → exige `drupal/core ^10.5 || ^11.2`.
  - `modules/vactory_decoupled/modules/vactory_decoupled_webform/src/Plugin/rest/resource/WebformFileUploadResource.php:8,106,148` : `FileExists` (enum core ≥ 10.3), alors que son `.info.yml:4` déclare `^9 || ^10`. Le profil déclare `core_version_requirement: ^9 || ^10` (`vactory_starter_kit.info.yml:4`).
  - Code PHP 8.1+ (`private readonly`) : `vactory_webform_typesafe/src/Service/AnalysisManager.php:26`, `vactory_dynamic_field_generator/.../DynamicFieldGeneratorCommands.php:30`.
- **Impact** :
  - Un projet en PHP 8.1/8.2 (supporté par D10) ne peut plus mettre à jour le profil : composer échoue sur devel ou garde silencieusement une ancienne version du profil.
  - Un projet en core 10.3/10.4 est bloqué par `editor_advanced_link 2.3.4`.
  - Sur core < 10.3, l'upload de fichier webform décuplé est en erreur fatale.
  - devel, outil de dev, fixe le plancher PHP de la prod.
- **Statut** : Vérifié en lecture (lock + code).
- **Test** :
  - [CI] matrice PHP 8.1 / 8.3 et core 10.3 / 10.5 sur `composer update --dry-run`.
  - [Statique] `composer check-platform-reqs` dans la CI.
  - [Lint] comparer `core_version_requirement` aux API utilisées (PHPStan + `phpstan-drupal` avec `drupal_root` de la version minimale).

## F06 — 7 librairies front `/libraries/*` non déclarées ; repli `cdn:` de select2 inopérant

- **Catégorie** : assets front (dépendances hors composer)
- **Paquets** : select2 4.0.5, SortableJS, FullCalendar (structure v4 `packages/*/main.min.js`), CodeMirror 5, intro.js, jQuery.print, ReadMore.js. Aucun n'est dans `composer.json`/lock (seules `drupal-library` lockées : `goalgorilla/rtseo.js`, `politsin/jquery-ui-touch-punch`).
- **Fichiers** :
  - `modules/vactory_dynamic_field/vactory_dynamic_field.libraries.yml:34-47` (select2), `:66-68` (sortable)
  - `modules/vactory_video_ask/vactory_video_ask.libraries.yml:7`
  - `modules/vactory_appointment/vactory_appointment.libraries.yml:15-21`
  - `modules/vactory_dynamic_block/vactory_dynamic_block.libraries.yml:15-23`
  - `modules/vactory_user_guide/vactory_user_guide.libraries.yml:3-19`
  - `modules/vactory_quiz/modules/vactory_quiz_certificat/vactory_quiz_certificat.libraries.yml:3`
  - `modules/vactory_social_network/vactory_social_network.libraries.yml:14`
  - `select2` est attaché par `vactory_dynamic_field/src/Form/ModalForm.php:929` (modale DF, cœur de l'édition)
- **Preuve** :
  ```yaml
  cdn:
    /libraries/jquery.select2/dist/: https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/
  ```
  Le core ignore la clé `cdn`. Seul `webform_library_info_alter()` la traite (`vendor/drupal/webform/includes/webform.libraries.inc:52,120-122`), et seulement si l'extension implémente `hook_webform_libraries_info` **et** si la librairie a une clé `directory`. Aucune des deux conditions n'est remplie : le repli CDN n'existe pas.
- **Impact** : chaque projet doit déposer à la main la bonne version dans `web/libraries`. Sans elle : 404 JS, et modale DF, drag & drop DF, calendrier de rendez-vous, éditeur de blocs dynamiques ou guide utilisateur cassés en silence. Une version différente (FullCalendar v5/v6, CodeMirror 6) casse à cause des chemins codés en dur.
- **Statut** : Vérifié en lecture (déclarations). Présence réelle dans les projets : à vérifier (`vactory8`, Dispatch).
- **Test** : [ExistingSite] pour chaque librairie déclarée du profil, résoudre les chemins locaux via `library.discovery` et vérifier `file_exists(DRUPAL_ROOT . $path)`. Un seul test couvre toutes les librairies `/libraries/*` et `./assets/*`.

## F07 — Dépendances de librairies inexistantes ignorées en silence

- **Catégorie** : assets core (bibliothèques retirées du core)
- **Paquets** : drupal/core (jQuery UI retiré en D9/D10), drupal/jquery_ui_accordion 2.0.0 (tiré **uniquement** par `drupal/faqfield`), drupal/field_group (seulement `suggest`)
- **Fichiers** :
  - `modules/vactory_dynamic_field/vactory_dynamic_field.libraries.yml:20` : `- core/jquery.ui.accordion` (n'existe plus dans `core.libraries.yml`)
  - `:72` et `:82` : `- core/drupalSettingss` (faute de frappe)
  - JS : `modules/vactory_dynamic_field/assets/js/modal.js:12` `.accordion({...})`
  - core : `LibraryDependencyResolver.php:64-74` (dépendance inconnue = `getLibraryByName()` renvoie FALSE, sans erreur)
- **Impact** :
  - L'accordéon de la modale DF ne marche que si une autre librairie de la page charge `jquery_ui_accordion/accordion` (faqfield). Si faqfield est retiré ou ne déclare plus l'accordéon : `$(...).accordion is not a function`.
  - `drag_and_drop` et `pending_content` ne déclarent pas réellement `drupalSettings` (impact faible tant que leurs JS ne l'utilisent pas).
- **Statut** : Vérifié en lecture (résolution silencieuse). Effet visible : à vérifier en navigateur.
- **Test** : [Kernel] pour chaque librairie du profil, chaque entrée `dependencies` doit être résoluble : `library.discovery->getLibraryByName()` ≠ FALSE. Attrape aussi les renommages de librairies contrib à la MAJ.

## F08 — Services externes : CDN mort, clés, API dépréciées

- **Catégorie** : services externes consommés (hors composer)
- **Paquets / services** : Google Maps JS + Places, MarkerClusterer (googlemaps/js-marker-clusterer, archivé), Meta Graph API (WhatsApp Cloud), cdn.rawgit.com, unpkg/jsdelivr
- **Fichiers / preuves** :
  - `modules/vactory_locator/vactory_locator.libraries.yml:16` : `https://cdn.rawgit.com/googlemaps/js-marker-clusterer/...` ; rawgit est arrêté depuis 2019. `new MarkerClusterer(...)` en `assets/js/locator.js:382` → ReferenceError si le script ne charge pas. Attaché par `VactoryLocatorBlock.php:324`, `VactoryLocatorBlockAgence.php:135`.
  - `modules/vactory_google_map_field/vactory_google_map_field.libraries.yml:36` : **clé API Google en clair** dans le dépôt (`key=AIzaSy…`), partagée par tous les projets (quota, révocation, facturation), plus le paramètre obsolète `sensor=false`.
  - `modules/vactory_dynamic_field/modules/vactory_dynamic_field_map/vactory_dynamic_field_map.libraries.yml:3` : `https://maps.google.com/maps/api/js` **sans clé** (Google l'exige depuis 2016).
  - `modules/vactory_locator/modules/vactory_locator_decoupled/src/StoreLocatoreManager.php:148` : endpoint `maps/api/place/autocomplete/json` = **Places API (Legacy)**, non activable pour les nouveaux projets Google Cloud depuis mars 2025.
  - `modules/vactory_whatsapp/src/WhatsappApiInterface.php:6` et `src/Form/SettingsForm.php:39` : `https://graph.facebook.com/v14.0`. Version de 2022, hors de la fenêtre de support Meta (~2 ans).
  - `modules/vactory_content_package/vactory_content_package.libraries.yml:7` : `clipboard@2` (version flottante sur jsdelivr). Ce fichier utilise aussi `{ external: true }` au lieu de `{ type: external }` (fonctionne par détection d'URL absolue, à vérifier).
- **Impact** : cartes, store locator et autocomplete d'adresse cassés sans MAJ de notre code. Un nouveau client ne peut pas activer l'API Places legacy. WhatsApp dépend de la politique de redirection de version de Meta.
- **Statut** : Vérifié en lecture (URL, clé, versions). Disponibilité réelle des services : à vérifier (réseau non sollicité).
- **Test** :
  - [Unit/statique] lister toutes les URL `external` des `*.libraries.yml` et refuser `rawgit`, une version flottante ou une clé en clair.
  - [Kernel] `StoreLocatoreManager` avec client HTTP mocké, réponse au format Places (New), pour forcer la migration.
  - [Smoke externe, hors CI bloquante] HEAD sur chaque URL CDN.

## F09 — Thème `vactory` exigé par le profil, fourni par aucun paquet

- **Catégorie** : dépendance hors composer
- **Fichiers** : `vactory_starter_kit.info.yml:193-195` (`themes: - vactory - claro`) ; `vactory_starter_kit.install:74` (`->set('default', 'vactory')`) ; `modules/vactory_header/vactory_header.install:34` (bloc placé dans `vactory`) ; tests `vactory_decoupled/tests/Functional/InternalBlocksTest.php:82,196`, `DecoupledCrossContentTest.php:140,306`
- **Preuve** : aucun `*.info.yml` nommé `vactory` dans le dépôt ni dans `vendor/`. Aucun paquet du lock ne le fournit. Il vient du dépôt projet (`vactory8`, cloné par la CI).
- **Impact** : le profil n'est pas installable seul (`drush si` échoue sans le thème du projet). La CI teste donc toujours le couple profil + `vactory8@main`. Une évolution du thème côté projet (renommage de régions, suppression) casse l'install et les tests du profil sans commit dans le profil.
- **Statut** : Vérifié en lecture.
- **Test** : [CI] `drush si vactory_starter_kit` sur un projet vierge (drupal/recommended-project + profil) : attrape le thème manquant et toute dépendance projet implicite.

## F10 — `vactory_devtools` et `drush vse` installent des modules absents de composer

- **Catégorie** : dépendance non déclarée
- **Paquets** : `webprofiler` (sorti de devel depuis devel 5 → `drupal/webprofiler`), `modules_weight`, `module_filter`, `realistic_dummy_content`. Aucun n'est dans le lock.
- **Fichiers** :
  - `modules/vactory_devtools/vactory_devtools.info.yml` (listes `install:`/`uninstall:`) + `vactory_devtools.install:13-28` : `module_installer->install($module_info_data['install'], TRUE)`
  - `modules/vactory_core/vactory_core.drush.inc:43-61` (`_vactory_core_switch_environment('development')`)
- **Impact** : activer `vactory_devtools` lève `MissingDependencyException` dans `hook_install` (état partiel : le module est installé, les outils non). La commande `vse development` échoue aussi (cf. F11 : elle n'est de toute façon plus chargée).
- **Statut** : Vérifié en lecture.
- **Test** : [ExistingSite / base jetable] `ModuleInstaller::install(['vactory_devtools'])` sans exception. Variante statique : toute liste de modules dans le code ou un `.info.yml` doit se résoudre dans `vendor/` ou `modules/`.

## F11 — Commandes Drush : drush non déclaré, 3 générations d'enregistrement

- **Catégorie** : outillage / dépendance non déclarée
- **Paquets** : drush/drush (**absent** de `require` et du lock ; version imposée par chaque projet)
- **Fichiers** :
  - Format Drush 8 (`hook_drush_command`, **non chargé par Drush ≥ 9**) : `modules/vactory_core/vactory_core.drush.inc:10-27`, `modules/vactory_dynamic_field/vactory_dynamic_field.drush.inc`
  - `drush.services.yml` (déprécié en Drush 12, retiré en Drush 13 : à vérifier), sans `create()` : `vactory_reminder`, `vactory_notifications`, `vactory_wysiwyg_301to200`, `vactory_migrate`, `vactory_dynamic_field_dummy`, `vactory_diff_config_client`, `vactory_diff_content`
  - tag `drush.command` dans le `*.services.yml` du module (hérité de Drush 9-11) : `vactory_cloudinary/vactory_cloudinary.services.yml:9-11`, `vactory_mailchimp_newsletter/vactory_mailchimp_newsletter.services.yml:6-8`
  - Seul `vactory_dynamic_field_generator/src/Drush/Commands/DynamicFieldGeneratorCommands.php` suit le format Drush 12+ (`create()`, `src/Drush/Commands`)
  - `modules/vactory_reminder/composer.json` : PSR-4 `Drupal\\drush9_example\\` → `src/` (copié-collé, faux)
  - `vactory_reminder/src/Commands/VactoryRunReminderQueueCommand.php:163,181` : `watchdog_exception()` (déprécié 10.1, retiré en 11)
- **Impact** : une montée de Drush côté projet fait disparaître en silence les commandes du profil, dont des tâches planifiées (`send_newsletters`, rappels, déplacement Cloudinary, diff de config). `vse` est déjà morte.
- **Statut** : Vérifié en lecture (formats). Drush 13 : à vérifier dans la version utilisée par `vactory8`.
- **Test** : [CI] `drush list --format=json` après install, puis vérifier la présence de chaque commande attendue (liste dans un fichier de référence).

## F12 — Complément à README §7 / B15 : ~80 dépendances de config non déclarées

- **Catégorie** : dépendance de config
- **Méthode** : pour chaque module, comparer `dependencies.module` (+ `enforced`) de `config/install/*.yml` à la **fermeture transitive** des dépendances `.info.yml` (vendor + modules). Script exhaustif.
- **Résultat** (hors `scheduler`/`workbench_moderation`/`flag`, déjà connus) :

  | Dépendance manquante | Modules concernés |
  |---|---|
  | `media_responsive_thumbnail` | academy, annual_report, blog, event, governance, mediatheque, news, partner, publication, publication_prive, testimonials |
  | `smart_trim` | blog, event, job_ads, news, publication, tender, testimonials |
  | `entityqueue` | blog, event, news, partner, press_kit, press_release, publication, testimonials |
  | `file_entity` | blog, press_kit, press_release, publication, dynamic_field_video_help |
  | `better_exposed_filters` | announcements, mailchimp_newsletter, report_content, revisions, social_network |
  | `social_media_links_field` | governance (8 configs), login_token |
  | `jsonapi_defaults` + `vactory_decoupled` | academy, publication, tender |
  | autres | `viewsreference` (vactory_paragraphs), `focal_point` (testimonials), `colorapi` (paragraphs_bg_wrapper), `textfield_counter` (welcome), `crop` (dynamic_field_video_help), `menu_item_extras` (toolbox), `paragraphs`/`entity_reference_revisions` (help_center, content_collector), `backup_migrate`/`captcha` (vactory_decoupled) |

  Au niveau du profil : `vactory_starter_kit.info.yml:82` installe `vendor_stream_wrapper`, que **seul** notre fork `voidagency/jquery_colorpicker 5.0.0` tire (`file_mdm`/`image_effects` le suggèrent seulement). Retirer ou remplacer colorpicker (tenté en #62381) casse l'install du profil.
- **Impact** : ça marche dans le profil complet (tout est activé), mais un projet qui active un module seul, ou qui retire un module contrib (ex. `smart_trim`, `file_entity`), obtient `UnmetDependenciesException`. Une MAJ contrib qui renomme un sous-module casse l'install.
- **Statut** : Vérifié en lecture (script).
- **Test** : le même test générique que F04, plus un test statique (script CI) qui recalcule ce tableau et échoue sur un nouvel écart.

## F13 — 22 modules livrent de la config sans schéma : les tests Kernel/Functional proposés échoueront

- **Catégorie** : angle mort de test
- **Paquets** : drupal/core (`$strictConfigSchema = TRUE` par défaut dans `KernelTestBase`/`BrowserTestBase`)
- **Fichiers** : config simple `config/install/<module>.*.yml` sans dossier `config/schema` dans : vactory_dynamic_field, vactory_html2pdf, vactory_reminder, vactory_image_sitemap, vactory_entity_canonical_access, vactory_academy_agency, vactory_translations_export, vactory_social_network, vactory_form_autosave, vactory_simulation_credit, vactory_job_ads, vactory_timeout, vactory_seo_noindex, vactory_migrate, vactory_login_token, vactory_appointment, vactory_sso, vactory_whatsapp, vactory_google_places, vactory_decoupled_image_styles, vactory_migrate_ui, vactory_diff_config_client
- **Impact** : les fiches proposent des tests Kernel (ex. html2pdf B06, DF). En Kernel, `installConfig()` de ces modules lève `SchemaIncompleteException` avant le moindre test. Ça explique en partie pourquoi les tests existants sont presque tous ExistingSite (DTT, sans contrôle de schéma). Les MAJ contrib qui changent le schéma de leurs `third_party_settings` ne sont donc jamais vérifiées.
- **Statut** : Vérifié en lecture (absence de schéma). Comportement core connu.
- **Test** : [Kernel] `ConfigSchemaCoverageTest` : installer chaque module du profil avec schéma strict. À écrire **avant** les autres Kernel (prérequis), ou documenter `$strictConfigSchema = FALSE` comme dette.

## F14 — Bibliothèques transitives utilisées directement sans déclaration

- **Catégorie** : transitive non déclarée
- **Paquets / fichiers** :
  - `league/oauth2-server` 8.5.5 (via simple_oauth `^8.3`) : grants qui étendent `PasswordGrant`, `vactory_oauth_apikey/src/OAuth2/Server/Grant/ApikeyGrant.php:21,115`, `vactory_faceid_auth/src/OAuth2/Server/Grant/FaceIdGrant.php:20,99` (`getIdentifier()` sans type de retour), repository `vactory_phone_auth_decoupled/src/Repositories/UserRepository.php:13,33`
  - `lcobucci/jwt` 4.3.0 : `vactory_decoupled/src/SimpleOauthAuthenticationProvider.php:8,260` (`new DataSet`)
  - `defuse/php-encryption` : `vactory_decoupled/src/Controller/OneTimeToken.php:25`
  - `e0ipso/shaper` (via jsonapi_extras) : `Shaper\Util\Context` dans 14 enhancers (`vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/*`, `vactory_sondage`, `vactory_decoupled_webform`)
- **Impact** : la version est dictée par le paquet parent. `simple_oauth 6.x` passe à `league/oauth2-server ^9`, qui ajoute des types stricts (`getIdentifier(): string`, à vérifier) : erreur fatale de compatibilité de signature sur nos 2 grants dès que la contrainte `^5.2` est levée. Complète la fiche simple_oauth (qui note les grants, pas la lib).
- **Statut** : usage vérifié en lecture ; signatures v9 à vérifier.
- **Test** : [Unit] instancier `ApikeyGrant`/`FaceIdGrant` via la réflexion (la classe se charge = signatures compatibles) et `respondToAccessTokenRequest` avec des repositories mockés. [Statique] `composer-require-checker` sur `modules/` liste toutes les transitives utilisées directement.

## F15 — Extensions PHP non déclarées ; `apcu_delete()` non gardé

- **Catégorie** : plateforme
- **Fichiers** :
  - `modules/vactory_redirect/src/Form/UploadUrlsCsv.php:85` : `apcu_delete('redirection_csv');` sans garde. Aucun `apcu_fetch` nulle part : reliquat.
  - `ZipArchive` : `vactory_content_package/src/Services/ContentPackageArchiverManager.php:153`
  - `Imagick` : `vactory_core/vactory_core.module:659` (gardé par `extension_loaded`, `:626`)
  - `openssl_*` : `vactory_webform_typesafe/src/Service/ApiKeyStore.php:54,68` (déclaré seulement dans le `composer.json` du sous-module, jamais fusionné)
- **Impact** : sans APCu (ou en CLI), l'envoi du formulaire d'import de redirections lève `Call to undefined function apcu_delete()`. Le `composer.json` racine ne déclare aucun `ext-*`.
- **Statut** : À vérifier (extensions de l'image `vactory/php-ubi9:8.3.14` et des hébergements projets).
- **Test** : [Kernel] soumettre `UploadUrlsCsv` ; [CI] `composer check-platform-reqs` après ajout des `ext-*` au `composer.json`.

## F16 — Config composer du dépôt : TLS désactivé, dépôt CKEditor 4 mort

- **Catégorie** : supply chain / hygiène composer
- **Fichiers** : `composer.json:302-303` (`"disable-tls": true`, `"secure-http": false`) ; `composer.json:278-290` (dépôt `package` `ckeditor/templates` 4.11.1, zip S3, `"reference": "master"`), jamais requis (absent du lock), et CKEditor 4 n'existe plus en D10.
- **Impact** : toute résolution lancée **dans** ce dépôt (Dependabot, `Dockerfile` racine, `post-merge` local) télécharge sans TLS, et un dépôt HTTP peut être intercepté. Ça ne touche pas les projets (la config racine du projet s'applique), mais c'est l'environnement qui produit le lock et les PR Dependabot.
- **Statut** : Vérifié en lecture.
- **Test** : [CI] `composer validate --strict` + garde-fou qui refuse `disable-tls`/`secure-http: false`.

## F17 — Complément à la fiche domain : branche gardée par une fonction de fork

- **Catégorie** : fork implicite / dépendance non déclarée
- **Fichiers** : `modules/vactory_extended_seo/src/VactoryExtendedSeoHelper.php:66-67,74-75`
- **Preuve** : `if (function_exists('_get_allowed_languages_with_alias'))` (commentaire : « should get the LBR version of domain module »). La fonction n'existe ni dans `drupal/domain 2.0.0-beta1` ni dans le profil. Les clés `<domain>_languages` de `domain.settings` non plus.
- **Impact** : avec le domain officiel, le hreflang multi-domaine est mort (hôte par défaut). Avec le fork mais sans module `domain`, `$domain_config` reste `NULL` → `array_filter(NULL)` → TypeError.
- **Statut** : Vérifié en lecture.
- **Test** : [Kernel] fiche domain existante + cas « fonction définie, domain absent ».

---

## Non couvert (pour le tour 2)

- Alters de formulaires contrib (`hook_form_<id>_alter` qui accèdent à des clés de structure contrib) : non inventoriés.
- `hook_update_N` / `post_update` qui appellent des API contrib (chemin de mise à jour des vieux sites).
- Twig : `spaceless` (4 occurrences, déprécié Twig 3.12), macros `_self` (40), compatibilité Twig 4 non évaluée.
- Templates/JS côté thème `vactory` (hors dépôt).
- Plugins `migrate` YAML (`migrations/*.yml`) contre les versions de migrate_plus/migrate_source_csv : non revérifiés.
- `modules/mae_group_poc` (non suivi par git) : dépend de `group`/`gnode`, absents de composer. À déclarer s'il est commité.
- Filtre `vactory_wysiwyg_301to200` : requêtes HTTP sortantes au rendu avec un `new GuzzleHttp\Client` hors factory (contourne F01, mais aussi `http_client_config`).
