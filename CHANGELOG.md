# Changelog

Évolutions notables du profil `vactory_starter_kit` (branche `3.x`), au format [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Règles de rédaction et de release : [RELEASING.md](RELEASING.md).
`#n` est un numéro de PR GitHub ; un ticket est noté « Redmine #n ».

## [Non publié]

### Mise à jour des projets

**Risques et points d'attention**
- **⚠️ Risque principal, vérification TLS (#896) :** elle est active par défaut pour **tous** les appels HTTP du site (`\Drupal::httpClient()`, service `http_client` : profil, contrib, code projet), y compris quand le réglage n'a jamais été enregistré. Un service au certificat invalide ou auto-signé échoue (`cURL error 60`). **Tester chaque appel HTTPS du projet avant la mise en production** (étapes 4 et 6).
- **Front qui lit la configuration des webforms** sans rôle de confiance : l'adapter. La permission `access any webform configuration` n'est plus donnée aux rôles anonyme et authentifié (#897) ; le front par défaut ne l'utilise pas.
- **Webforms découplés avec upload de fichier (#906) :** un fichier hors de la liste d'extensions de l'élément est refusé (422). Un élément sans `#file_extensions` applique la liste par défaut de webform.
- **`term_2_slug` (#884) :** le slug de chaque traduction est normalisé à l'enregistrement du terme (minuscules ASCII, mots joints par `-`). Les URL et filtres front qui s'appuient sur un slug avec majuscules, accents, `_` ou `/` changent.
- **`vactory_appointment` (#911) :** les champs conseiller ne sont plus éditables en back-office ; ils le restent en front.
- **Sitemap (#910) :** les liens déjà stockés ne changent qu'au prochain enregistrement de la page d'accueil ou à un rebuild du sitemap.
- **`vactory_rate` (#952) :** le vote et son annulation ne répondent plus qu'aux requêtes POST. Un front ou un script du projet qui les appelle en GET reçoit une erreur 405 : l'adapter. Le front par défaut envoie déjà un POST. Un appel fait avec un cookie de session Drupal doit aussi envoyer l'en-tête `X-CSRF-Token` ; un appel par jeton OAuth n'en a pas besoin.

**Procédure, dans cet ordre**

```
# 1. Code et composer (en local, puis commit)
composer require voidagency/vactory_starter_kit:<version> -W
# 2. Déployer (capistrano ou Docker), puis à la main :
drush updb -y
# 3. Features à réimporter : aucune pour cette version
# 4. Back-office : voir l'étape 4
# 5.
drush cr
# 6. Vérifications : voir ci-dessous
```

**1. Code et composer** (en local, avant le déploiement)
- **Tous les sites :** figer la version du profil : `composer require voidagency/vactory_starter_kit:<version> -W`. Le `composer.lock` du profil n'est pas utilisé par les projets : c'est cette commande qui apporte les correctifs de sécurité des dépendances.
- **Si le projet suit `3.x-dev` (ou une autre branche de dev) :** passer sur la version figée `voidagency/vactory_starter_kit:<version>`. Une branche de dev livre des changements non publiés, sans changelog ni tests de release.
- **Si le projet est en 3.1.x :** appliquer aussi les notes des releases 3.2.x (changements de dépendances compris).
- **`google_tag` (#931, #942) :** la contrainte accepte 1.x et 2.x. Si le `composer.json` du projet n'exige pas google_tag, `-W` le fait passer en 2.x. Pour rester en 1.x : `composer require drupal/google_tag:^1.8` avant la mise à jour.
- **Si le projet exige `drupal/captcha` 2.0.12 ou plus :** conflit composer, le profil fige 2.0.11 (#922). Un projet verrouillé en 2.0.12 redescend en 2.0.11 : retirer un patch projet prévu pour 2.0.12.
- **Si le projet requiert `drupal/swiftmailer`, `drupal/social_auth_microsoft` ou `stevenmaguire/oauth2-microsoft` :** reprendre leurs patches dans le projet, le profil ne les déclare plus (#909).
- **Si du code projet appelle `console.output` ou `vactory_cloudinary.move_file_batch` :** ces services sont retirés (#920), supprimer les appels.
- **Si le projet étend `Drupal\vactory_core\MenuItems` ou `MenuRoleLinkTreeManipulator`** avec l'ancien constructeur : passer aux setters `setVactoryServices()` et `setAdminContext()` (#914).
- **Si le projet surcharge le contrôleur `Oauth2Token` de simple_oauth :** passer le logger en troisième argument du constructeur parent (simple_oauth 5.2.6, #928).
- **Si une feature du projet exporte les rôles `anonymous` ou `authenticated`** (`user.role.anonymous.yml`, `user.role.authenticated.yml`) : retirer la permission `access any webform configuration` de ces fichiers. Sinon, un `drush fr` de cette feature la redonne (#897). Même chose pour un dossier de config importé à la main par `drush cim` (`config/sync`, `config/staging`…).
- **Versions relâchées (#944) :** consumers, csv_serialization, editor_advanced_link, jquery_colorpicker, link_attributes et block_list_override montent à la dernière version compatible avec le core du projet. Vérifier le diff du `composer.lock` avant de commiter.
- **`linkit` (#947) :** la contrainte passe de 7.0.12 à `^7.0.16` ; le projet monte en 7.0.18 ou plus. Un projet qui fige linkit en dessous de 7.0.16 (6.x, 7.0.12) aura un conflit composer : lever ce blocage. Un projet qui a son propre contournement de la régression linkit 7.0.13 (élément `uri` ajouté à `url_extended`, patch local) le retire : le profil la gère depuis #912.

**2. `drush updb -y`** (à la main, après le déploiement)
- Deux updates du profil : `vactory_decoupled_webform` 10102, qui retire la permission `access any webform configuration` aux rôles anonyme et authentifié (#897) ; `vactory_dynamic_field` 9501, qui active `paragraphs_edit`, avec `paragraphs` et `contextual`, sur les sites qui ne l'ont pas (#910).
- Si google_tag passe en 2.x et que le module est activé : ses propres updates s'exécutent aussi.
- **Sites en config sync (rare) :** ensuite, `drush cex` et commiter (`core.extension.yml`, rôles `anonymous` et `authenticated`). Sinon, le prochain `drush cim` annule ces updates.
- Si `drupal/consumers` passe de moins de 1.23 à 1.23 ou plus : ses updates 8110 à 8112 s'exécutent (champ `status`) (#944).

**3. `drush fr -y <module>`** (features à réimporter)
- **Aucune pour cette version.** Les configs modifiées dans le profil (`shield.settings`, `vactory_toolbox`, `vactory_form_autosave`) ne servent qu'à l'installation : les sites existants gardent les leurs.
- **Ne pas réimporter les autres features du profil :** un `drush fr` remplace la config active par celle du module et efface les valeurs propres au site (clés API, identifiants, réglages).
- **Si google_tag est passé en 2.x :** réexporter (et non réimporter) la feature du projet qui porte la config GTM, après vérification des conteneurs.

**4. Back-office**
- **Si un service appelé en HTTPS a un certificat invalide ou auto-signé (#896) :** désactiver la vérification TLS pour cet environnement dans `/admin/config/vactory-core-config`, ou dans `settings.php` : `$config['vactory_core.global_config']['http_client_ssl_verification'] = FALSE;`. Un appel qui passe `verify` reste prioritaire.
- **Si Shield est actif :** vérifier les identifiants du site dans la config de Shield (#911, détail dans le ticket Dispatch).
- **Si google_tag est passé en 2.x :** vérifier les conteneurs GTM dans la config de Google Tag.
- **Si `vactory_google_places` est actif :** revoir les vues où l'option « Vactory google places instance » est cochée ; l'autocomplete Google Places y est de nouveau chargé (#913).
- **Module optionnel `vactory_admin_hardening` (#886) :** sur un site existant, l'activer (`drush en vactory_admin_hardening`) quand l'équipe passe par Drush et Features. Il bloque en web, pour tous les comptes, l'interface de migration et l'import, l'export et la synchronisation de config (pages `config_update_ui` comprises). Le check Security Review signale son absence.
- **Si le projet attribue un rôle aux comptes créés par l'inscription JSON:API :** le régler dans `/admin/config/system/decoupled-settings`, onglet *Registration*. Les rôles envoyés par le front ne sont plus pris en compte (#885).
- **Si `vactory_rate` est actif :** le formulaire des types de contenu ouverts au vote demande la permission `administer site configuration` (#952). La donner aux rôles non administrateurs qui utilisent ce formulaire.

**5. `drush cr`**
- Obligatoire : sans lui, les pages avec un menu plantent tant que l'ancien conteneur reste en cache (#914).
- Il applique aussi les nouvelles règles d'accès des routes de `vactory_rate` (#952).

**6. Vérifications**
- `drush updb` n'a plus rien en attente, et `drush status` est correct.
- Chaque appel HTTPS du projet (API métier, SSO, CRM, envoi de formulaire distant…) répond, sans `cURL error 60` dans les logs.
- **Si `vactory_decoupled_flag`, `vactory_decoupled_search_ai` ou `vactory_toolbox` est actif :** leurs dépendances nouvellement déclarées sont actives (`drush pm:list`), car Drupal ne les active pas sur un site existant (#904, #905).
- Une page avec menu s'affiche ; `/sitemap.xml` et `/image-sitemap.xml` répondent.
- Un envoi de webform avec fichier aboutit, côté front.
- Connexion au back-office, et connexion front (OAuth, SSO s'il est utilisé) (#908, #928).
- En back-office, l'autocomplete des liens d'un Dynamic Field propose des contenus et enregistre le lien choisi (linkit, #947).
- **Si `vactory_rate` est actif :** un vote depuis le front aboutit (#952).

### Sécurité
- `vactory_core` : vérification TLS active par défaut dans le client HTTP de Drupal (#896).
- `vactory_decoupled_webform` : permission `access any webform configuration` retirée aux rôles anonyme et authentifié (#897).
- `vactory_decoupled_webform` : l'upload de fichiers applique les validateurs du webform, comme le formulaire Drupal : extensions, taille, longueur du nom (#906).
- `vactory_core` : Shield ne peut plus être activé sans identifiants, et le rapport d'état signale une erreur dans ce cas (#911).
- `vactory_decoupled` : inscription JSON:API : les rôles du compte sont décidés côté serveur (#885).
- `vactory_rate` : le formulaire de réglages demande la permission `administer site configuration` ; le vote et son annulation n'acceptent que POST, avec l'en-tête `X-CSRF-Token` pour une session par cookie (#952).

### Changements de comportement
- `vactory_core` : les options passées au client HTTP de Drupal par l'appelant (timeout…) sont maintenant transmises (#896).
- `vactory_decoupled` : à chaque enregistrement d'un terme, le slug `term_2_slug` de chaque traduction est normalisé (minuscules ASCII, mots joints par `-`), y compris un slug saisi à la main (#884).
- `vactory_decoupled_webform` : upload limité à 150 caractères pour le nom de fichier (au lieu de 240), et `file.default_max_filesize` appliqué (#906).
- `vactory_google_places` : l'option « Vactory google places instance » des filtres de vues, ignorée jusque-là, est réellement appliquée (#913).
- `vactory_appointment` : champs conseiller masqués en back-office dès qu'un thème d'administration distinct du thème par défaut est configuré (seul `seven` était pris en compte) (#911).
- `vactory_webform_mailchimp_handler` : une panne Mailchimp ne bloque plus la soumission, et le timeout passe de 120 s à 10 s (#903).

### Ajouts
- `vactory_admin_hardening` : nouveau module optionnel qui bloque en web l'interface de migration et l'interface de config du core, même pour uid 1 ; installé d'office sur les nouveaux sites uniquement (#886).
- `vactory_security_review` : check « Migrate administration surface », qui signale l'absence de `vactory_admin_hardening` et les rôles non administrateurs ayant `import configuration`, `synchronize configuration` ou `administer migrations` (#886).
- `vactory_decoupled` : condition de visibilité des blocs selon le statut de la réponse (200, 403, 404), évaluée sur le statut renvoyé par le routeur découplé (#890).
- `vactory_decoupled` : rôles attribués à l'inscription JSON:API, réglables dans le back-office (onglet *Registration* des réglages découplés, permission « administer permissions » ; jamais administrateur, anonyme ni authentifié) (#885).

### Correctifs
- `vactory_sso` : la connexion SSO n'appelle plus une classe disparue ; dépendance `oauth_login_oauth2` déclarée ; sans `resource_owner` en session, les champs mappés ne sont plus remis à NULL (#908).
- `vactory_decoupled` : `/oauth/login-token` compatible simple_oauth 5.2.6, au lieu d'une erreur 500 (#928).
- `vactory_decoupled` : connexion OAuth2 réussie de nouveau journalisée quand l'option de `vactory_security_review` est cochée (#911).
- `vactory_decoupled`, `vactory_webform_remote_post`, `vactory_mailchimp_newsletter`, `vactory_checklist`, `vactory_diff_content` : erreurs réseau (DNS, timeout, connexion refusée) capturées et journalisées dans 7 appels HTTP. Pour la purge nginx Next, l'erreur ne remonte plus et le log n'annonce plus « cache cleared » sur un échec ; la purge elle-même est suivie dans Redmine #71229 (#936).
- `vactory_webform_remote_post` : une URL invalide en GET ne fait plus planter la soumission (#936).
- `vactory_decoupled_webform` : upload en 503 au lieu de 500 sur un fichier verrouillé, 400 si le champ n'est pas un élément fichier, liste d'extensions de nouveau envoyée au front (#906).
- `vactory_webform_typesafe` : plus de TypeError quand le formulaire de réglages est soumis sans saisie utilisateur (#935).
- `vactory_image_sitemap` : `/image-sitemap.xml` fonctionne sans laminas-diactoros (#899).
- `vactory_core` : faute de frappe corrigée sur la clé `access` du lien de sitemap de la page d'accueil (#910).
- `vactory_dynamic_field` : `paragraphs_edit` activé sur les sites où il manquait (#910).
- `vactory_user_migrate` : plugin de migration `hash_password` compatible Drupal 10.1+ (#900).
- `vactory_mailchimp_newsletter`, `vactory_social_network` : `accessCheck` sur les requêtes newsletters et liked_content (#901).
- `vactory_html2pdf` : génération PDF sans enregistrement préalable des réglages (#902).
- `vactory_form_autosave` : module installable (#898).
- `vactory_toolbox` : module installable sans workbench_moderation (#904).
- `vactory_decoupled_flag`, `vactory_decoupled_search_ai` : dépendances déclarées (#905).
- `vactory_locator` : MarkerClusterer figé sur un commit jsdelivr, plus de dépendance à rawgit (#907).
- `vactory_core` : services de menu (simplify_menu, manipulateur de menu) injectés par méthode. Le site ne tombe plus si simplify_menu ou le core ajoute un argument à un constructeur (#914).
- `vactory_dynamic_field` : élément URL compatible linkit 7.0.13+ (#912).
- `vactory_amp` : plus d'erreur fatale au rendu d'un nœud exclu d'AMP sans liens d'en-tête (#919).
- `vactory_cloudinary` : `getExternalUrl()` renvoie une chaîne, propriété dynamique déclarée (PHP 8.2), dépendances déclarées (#920).

### Retraits
- `vactory_cloudinary` : services `console.output` et `vactory_cloudinary.move_file_batch`, inutilisés (la classe du second n'existe pas) (#920).
- `vactory_decoupled` : hook mort `vactory_decoupled_jsonapi_default_enhancers_info()`, jamais appelé (#909).

### Dépendances
- `drupal/google_tag` : `^1.4 || ^2.1` (#931, #942).
- `drupal/captcha` figé en 2.0.11 : le patch du profil ne s'applique plus sur 2.0.12 (#922).
- Patches composer retirés : un jamais appliqué (clé `drupal/jsonapi_extra` erronée) et trois pour des paquets sortis du profil, `drupal/swiftmailer`, `drupal/social_auth_microsoft`, `stevenmaguire/oauth2-microsoft` (#909).
- `composer.lock` du profil mis à jour, sans effet sur les projets, qui résolvent leurs propres versions (#893, #924 à #927, #931, #932).
- Contraintes relâchées, plus de version figée sans raison (#944, Redmine #71222) : `drupal/consumers` ^1.24, `drupal/csv_serialization` ^4.0, `drupal/editor_advanced_link` ^2.2.8 (lève l'exigence core ^10.5), `voidagency/jquery_colorpicker` ^5.0 \|\| ^6.0 (6.0 compatible Drupal 11), `drupal/link_attributes` ^2.1, `drupal/block_list_override` ^1.0.3 ; `league/csv` retiré (dépendance de csv_serialization).
- `drupal/linkit` : 7.0.12 → `^7.0.16` (#947). La régression de linkit 7.0.13 sur les champs `url_extended` est gérée dans le profil depuis #912 (Redmine #71222).

### Interne
- Tests d'intégration des dépendances à historique d'incidents et contrats entre le profil et ses dépendances (Redmine #71160 ; #912 à #920, #934).
- Tests de caractérisation, puis découpage des méthodes signalées par Sonar, sans changement de comportement (#935, #938, #939).
- CI Drone : un build par commit, sans build pour les branches sans PR ni pour les tags (#921) ; jeton GitHub pour composer (#940) ; exclusions Sonar appliquées et règle `php:S1781` ignorée (#929).
- Dependabot limité aux mises à jour de sécurité (#930).
- `vactory_image_sitemap` : phpcs propre, sans changement de comportement (#937).
- `vactory_image_sitemap` : variable inutilisée retirée (Sonar S1481, #945).
- CI Drone : l'analyse Sonar tourne dans son propre pipeline, sur une image arm64 native ; le scanner se figeait par moments sous émulation (Redmine #71371).

## [3.2.3] - 2026-10-07
Voir la [release 3.2.3](https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3).

[Non publié]: https://github.com/voidagency/vactory_starter_kit/compare/3.2.3...3.x
[3.2.3]: https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3
