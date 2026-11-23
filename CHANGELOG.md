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
- **Message d'erreur du CAPTCHA (#949) :** le patch du profil qui imposait le message par défaut traduit est retiré. Un message personnalisé dans les réglages de CAPTCHA, ignoré jusque-là, s'affiche de nouveau, tel quel dans toutes les langues tant qu'il n'est pas traduit (traduction de configuration). `drush updb` l'indique dans sa sortie (étape 2).
- **`block_class` 3.1 (#948) :** le formulaire d'un bloc refuse une classe qui n'est pas un identifiant CSS (caractères `:`, `/`, `[`, `.`, `@`… ou début par un chiffre), par exemple une variante Tailwind `md:hidden`. Les classes déjà enregistrées ne sont pas modifiées et restent envoyées telles quelles au front découplé, mais un tel bloc ne peut plus être enregistré en back-office sans corriger le champ. Dans le HTML rendu par Drupal, ces caractères étaient déjà retirés.
- **Sitemap (#910) :** les liens déjà stockés ne changent qu'au prochain enregistrement de la page d'accueil ou à un rebuild du sitemap.
- **Menus rendus par `simplify_menu()` (en-têtes, pieds de page, menu de l'espace privé, #957) :** un lien dont la cible n'est pas accessible à l'utilisateur courant n'est plus affiché, comme dans les menus du core. Un lien laissé visible à dessein vers une page réservée (par exemple une page de l'espace privé montrée aux visiteurs anonymes pour les amener à se connecter) disparaît pour ceux qui n'y ont pas accès : le faire pointer vers une page accessible (page de connexion, page de présentation).

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
- **`drupal/captcha` (#949) :** la contrainte passe de 2.0.11 à `^2.0.11` : 2.0.12 sur un core 10.3 ou plus, 2.0.11 en dessous. Le profil ne déclare plus le patch « Fix captcha error message translation ». **Si le projet porte sa propre copie de ce patch** (`extra.patches` ou fichier de patches) : la retirer, elle ne s'applique pas sur 2.0.12 et composer échoue.
- **Si une feature du projet exporte `captcha.settings` :** y mettre `wrong_captcha_response_message: ''` après l'update. Sinon, un `drush fr` de cette feature remet le message anglais, qui n'est alors plus traduit (#949). Même chose pour un dossier de config importé par `drush cim`.
- **Si le projet requiert `drupal/swiftmailer`, `drupal/social_auth_microsoft` ou `stevenmaguire/oauth2-microsoft` :** reprendre leurs patches dans le projet, le profil ne les déclare plus (#909).
- **Si du code projet appelle `console.output` ou `vactory_cloudinary.move_file_batch` :** ces services sont retirés (#920), supprimer les appels.
- **Si le projet étend `Drupal\vactory_core\MenuItems` ou `MenuRoleLinkTreeManipulator`** avec l'ancien constructeur : passer aux setters `setVactoryServices()` et `setAdminContext()` (#914).
- **Si le projet surcharge le contrôleur `Oauth2Token` de simple_oauth :** passer le logger en troisième argument du constructeur parent (simple_oauth 5.2.6, #928).
- **Si une feature du projet exporte les rôles `anonymous` ou `authenticated`** (`user.role.anonymous.yml`, `user.role.authenticated.yml`) : retirer la permission `access any webform configuration` de ces fichiers. Sinon, un `drush fr` de cette feature la redonne (#897). Même chose pour un dossier de config importé à la main par `drush cim` (`config/sync`, `config/staging`…).
- **Versions relâchées (#944) :** consumers, csv_serialization, editor_advanced_link, jquery_colorpicker, link_attributes et block_list_override montent à la dernière version compatible avec le core du projet. Vérifier le diff du `composer.lock` avant de commiter.
- **`linkit` (#947) :** la contrainte passe de 7.0.12 à `^7.0.16` ; le projet monte en 7.0.18 ou plus. Un projet qui fige linkit en dessous de 7.0.16 (6.x, 7.0.12) aura un conflit composer : lever ce blocage. Un projet qui a son propre contournement de la régression linkit 7.0.13 (élément `uri` ajouté à `url_extended`, patch local) le retire : le profil la gère depuis #912.
- **`drupal/block_class` (#948) :** la contrainte passe de 3.0.0 à `^3.0` : 3.1.0 sur un core 10.3 ou plus, 3.0.0 en dessous. Pour rester en 3.0.0 (classes de bloc avec `:` ou `/` saisies en back-office) : `composer require drupal/block_class:3.0.0` avant la mise à jour. Ne pas passer en 4.x (branche expérimentale).

**2. `drush updb -y`** (à la main, après le déploiement)
- Deux updates du profil : `vactory_decoupled_webform` 10102, qui retire la permission `access any webform configuration` aux rôles anonyme et authentifié (#897) ; `vactory_dynamic_field` 9501, qui active `paragraphs_edit`, avec `paragraphs` et `contextual`, sur les sites qui ne l'ont pas (#910).
- `vactory_core` 10001 (#949) : vide le réglage `captcha.settings:wrong_captcha_response_message` quand il contient le message par défaut (en anglais ou dans une langue du site). CAPTCHA affiche alors son message par défaut traduit par la traduction d'interface, comme avec le patch. Un message personnalisé est conservé et signalé dans la sortie de la commande.
- Si google_tag passe en 2.x et que le module est activé : ses propres updates s'exécutent aussi.
- **Sites en config sync (rare) :** ensuite, `drush cex` et commiter (`core.extension.yml`, rôles `anonymous` et `authenticated`). Sinon, le prochain `drush cim` annule ces updates.
- Si `drupal/consumers` passe de moins de 1.23 à 1.23 ou plus : ses updates 8110 à 8112 s'exécutent (champ `status`) (#944).

**3. `drush fr -y <module>`** (features à réimporter)
- **Aucune pour cette version.** Les configs modifiées dans le profil (`shield.settings`, `captcha.settings`, `vactory_toolbox`, `vactory_form_autosave`) ne servent qu'à l'installation : les sites existants gardent les leurs.
- **Ne pas réimporter les autres features du profil :** un `drush fr` remplace la config active par celle du module et efface les valeurs propres au site (clés API, identifiants, réglages).
- **Si google_tag est passé en 2.x :** réexporter (et non réimporter) la feature du projet qui porte la config GTM, après vérification des conteneurs.

**4. Back-office**
- **Si un service appelé en HTTPS a un certificat invalide ou auto-signé (#896) :** désactiver la vérification TLS pour cet environnement dans `/admin/config/vactory-core-config`, ou dans `settings.php` : `$config['vactory_core.global_config']['http_client_ssl_verification'] = FALSE;`. Un appel qui passe `verify` reste prioritaire.
- **Si Shield est actif :** vérifier les identifiants du site dans la config de Shield (#911, détail dans le ticket Dispatch).
- **Message d'erreur du CAPTCHA (#949) :** dans `/admin/config/people/captcha`, laisser « Wrong CAPTCHA response error message » vide pour garder le message par défaut traduit. Si `drush updb` a signalé un message personnalisé : le vider, ou le traduire.
- **Si google_tag est passé en 2.x :** vérifier les conteneurs GTM dans la config de Google Tag.
- **Si `vactory_google_places` est actif :** revoir les vues où l'option « Vactory google places instance » est cochée ; l'autocomplete Google Places y est de nouveau chargé (#913).
- **Module optionnel `vactory_admin_hardening` (#886) :** sur un site existant, l'activer (`drush en vactory_admin_hardening`) quand l'équipe passe par Drush et Features. Il bloque en web, pour tous les comptes, l'interface de migration et l'import, l'export et la synchronisation de config (pages `config_update_ui` comprises). Le check Security Review signale son absence.
- **Si le projet attribue un rôle aux comptes créés par l'inscription JSON:API :** le régler dans `/admin/config/system/decoupled-settings`, onglet *Registration*. Les rôles envoyés par le front ne sont plus pris en compte (#885).
- **Si le projet attribue un rôle aux comptes créés par le webform d'inscription de l'espace privé** (`vactory_espace_prive_register`, utilisé par le front) : ce même réglage s'y applique (#950). Sans rôle coché, rien ne change : le compte est créé sans rôle. Un rôle ajouté par du code projet reste en place ; éviter de le régler aux deux endroits.
- **Si `vactory_rate` est actif :** le formulaire des types de contenu ouverts au vote demande la permission `administer site configuration` (#961). La donner aux rôles non administrateurs qui utilisent ce formulaire.

**5. `drush cr`**
- Obligatoire : sans lui, les pages avec un menu plantent tant que l'ancien conteneur reste en cache (#914).
- block_class 3.1 déclare un nouveau service pour ses hooks : sans `drush cr`, le rendu des blocs plante (#948).

**6. Vérifications**
- `drush updb` n'a plus rien en attente, et `drush status` est correct.
- Chaque appel HTTPS du projet (API métier, SSO, CRM, envoi de formulaire distant…) répond, sans `cURL error 60` dans les logs.
- **Si `vactory_decoupled_flag`, `vactory_decoupled_search_ai` ou `vactory_toolbox` est actif :** leurs dépendances nouvellement déclarées sont actives (`drush pm:list`), car Drupal ne les active pas sur un site existant (#904, #905).
- Une page avec menu s'affiche ; `/sitemap.xml` et `/image-sitemap.xml` répondent.
- **Si block_class est passé en 3.1 :** un bloc qui a une classe CSS l'affiche toujours (HTML Drupal, et propriété `classes` du bloc côté front) ; le formulaire d'un bloc avec classes s'enregistre (#948).
- Un envoi de webform avec fichier aboutit, côté front.
- Une mauvaise réponse à un CAPTCHA affiche le message d'erreur traduit, en français et en arabe (#949).
- Connexion au back-office, et connexion front (OAuth, SSO s'il est utilisé) (#908, #928).
- En back-office, l'autocomplete des liens d'un Dynamic Field propose des contenus et enregistre le lien choisi (linkit, #947).
- **Si `vactory_quiz` est actif :** `views_autocomplete_filters` est actif (`drush pm:list`) ; sinon l'activer (`drush en views_autocomplete_filters`), car Drupal n'active pas une dépendance nouvellement déclarée sur un site existant. Sans lui, la vue « Mes quiz passés » fonctionne, mais son filtre Titre n'a pas d'autocomplete (#956).
- **Si des rôles sont cochés dans l'onglet *Registration* :** un compte créé par le formulaire d'inscription du front les reçoit (#950).
- Menus du thème (en-tête, pied de page) en anonyme puis connecté : les liens attendus sont présents ; un lien vers une page non publiée ou réservée n'apparaît que pour les utilisateurs qui y ont accès (#957). Une page servie depuis un cache garde l'ancien menu jusqu'à son renouvellement.

### Sécurité
- `vactory_core` : vérification TLS active par défaut dans le client HTTP de Drupal (#896).
- `vactory_decoupled_webform` : permission `access any webform configuration` retirée aux rôles anonyme et authentifié (#897).
- `vactory_decoupled_webform` : l'upload de fichiers applique les validateurs du webform, comme le formulaire Drupal : extensions, taille, longueur du nom (#906).
- `vactory_core` : Shield ne peut plus être activé sans identifiants, et le rapport d'état signale une erreur dans ce cas (#911).
- `vactory_decoupled` : inscription JSON:API : les rôles du compte sont décidés côté serveur (#885).
- `vactory_core` : les menus rendus par `simplify_menu()` n'affichent plus les liens dont la cible n'est pas accessible à l'utilisateur courant, à tous les niveaux, comme le fait simplify_menu 3.3 (#957, Redmine #71363).
- `vactory_rate` : le formulaire de réglages demande la permission `administer site configuration` (#961).

### Changements de comportement
- `vactory_core` : les options passées au client HTTP de Drupal par l'appelant (timeout…) sont maintenant transmises (#896).
- `vactory_decoupled` : à chaque enregistrement d'un terme, le slug `term_2_slug` de chaque traduction est normalisé (minuscules ASCII, mots joints par `-`), y compris un slug saisi à la main (#884).
- `vactory_decoupled_webform` : upload limité à 150 caractères pour le nom de fichier (au lieu de 240), et `file.default_max_filesize` appliqué (#906).
- `vactory_google_places` : l'option « Vactory google places instance » des filtres de vues, ignorée jusque-là, est réellement appliquée (#913).
- `vactory_appointment` : champs conseiller masqués en back-office dès qu'un thème d'administration distinct du thème par défaut est configuré (seul `seven` était pris en compte) (#911).
- `vactory_webform_mailchimp_handler` : une panne Mailchimp ne bloque plus la soumission, et le timeout passe de 120 s à 10 s (#903).
- `vactory_core` : dans les réglages de CAPTCHA, le message d'erreur peut rester vide (message par défaut traduit) et n'est plus prérempli ; un message saisi est de nouveau pris en compte (#949).

### Ajouts
- `vactory_admin_hardening` : nouveau module optionnel qui bloque en web l'interface de migration et l'interface de config du core, même pour uid 1 ; installé d'office sur les nouveaux sites uniquement (#886).
- `vactory_security_review` : check « Migrate administration surface », qui signale l'absence de `vactory_admin_hardening` et les rôles non administrateurs ayant `import configuration`, `synchronize configuration` ou `administer migrations` (#886).
- `vactory_decoupled` : condition de visibilité des blocs selon le statut de la réponse (200, 403, 404), évaluée sur le statut renvoyé par le routeur découplé (#890).
- `vactory_decoupled` : rôles attribués à l'inscription JSON:API, réglables dans le back-office (onglet *Registration* des réglages découplés, permission « administer permissions » ; jamais administrateur, anonyme ni authentifié) (#885).
- `vactory_decoupled_espace_prive` : l'inscription par le webform de l'espace privé attribue les rôles de ce même réglage, avec le même filtre (jamais administrateur, anonyme ni authentifié) ; les rôles envoyés dans la soumission restent ignorés (#950, Redmine #71369).

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
- `vactory_dynamic_field` : le compteur de caractères (textfield_counter) fonctionne de nouveau dans la fenêtre Dynamic Fields, y compris sur les champs d'un groupe ; plus de `TypeError` dans la console (#954, Redmine #71223).
- `vactory_quiz` : dépendances `views_autocomplete_filters` (autocomplete du filtre Titre de la vue) et `vactory_views` déclarées ; la vue livrée ne référence plus `vactory_google_places`, qu'elle n'utilisait pas (#956, Redmine #71362).

### Retraits
- `vactory_cloudinary` : services `console.output` et `vactory_cloudinary.move_file_batch`, inutilisés (la classe du second n'existe pas) (#920).
- `vactory_decoupled` : hook mort `vactory_decoupled_jsonapi_default_enhancers_info()`, jamais appelé (#909).
- Patch composer « Fix captcha error message translation » de `drupal/captcha` : CAPTCHA traduit lui-même son message par défaut quand le réglage est vide (#949).

### Dépendances
- `drupal/google_tag` : `^1.4 || ^2.1` (#931, #942).
- `drupal/captcha` : `^2.0.11`, sans patch (#949, Redmine #71222). Il avait été figé en 2.0.11 parce que le patch du profil ne s'appliquait plus sur 2.0.12 (#922). La 2.0.12 exige un core 10.3 ou plus.
- Patches composer retirés : un jamais appliqué (clé `drupal/jsonapi_extra` erronée) et trois pour des paquets sortis du profil, `drupal/swiftmailer`, `drupal/social_auth_microsoft`, `stevenmaguire/oauth2-microsoft` (#909).
- `composer.lock` du profil mis à jour, sans effet sur les projets, qui résolvent leurs propres versions (#893, #924 à #927, #931, #932).
- Contraintes relâchées, plus de version figée sans raison (#944, Redmine #71222) : `drupal/consumers` ^1.24, `drupal/csv_serialization` ^4.0, `drupal/editor_advanced_link` ^2.2.8 (lève l'exigence core ^10.5), `voidagency/jquery_colorpicker` ^5.0 \|\| ^6.0 (6.0 compatible Drupal 11), `drupal/link_attributes` ^2.1, `drupal/block_list_override` ^1.0.3 ; `league/csv` retiré (dépendance de csv_serialization).
- `drupal/linkit` : 7.0.12 → `^7.0.16` (#947). La régression de linkit 7.0.13 sur les champs `url_extended` est gérée dans le profil depuis #912 (Redmine #71222).
- `drupal/block_class` : `^3.0` au lieu de 3.0.0, figé sans raison (#948, Redmine #71222). La 3.1.0 exige un core 10.3 ou plus, pose les classes par `hook_block_view_alter` et valide le champ « CSS class(es) » ; aucun update. Pas de 4.x.

### Interne
- Tests d'intégration des dépendances à historique d'incidents et contrats entre le profil et ses dépendances (Redmine #71160 ; #912 à #920, #934).
- Tests de caractérisation, puis découpage des méthodes signalées par Sonar, sans changement de comportement (#935, #938, #939).
- CI Drone : un build par commit, sans build pour les branches sans PR ni pour les tags (#921) ; jeton GitHub pour composer (#940) ; exclusions Sonar appliquées et règle `php:S1781` ignorée (#929).
- Dependabot limité aux mises à jour de sécurité (#930).
- `vactory_image_sitemap` : phpcs propre, sans changement de comportement (#937).
- `vactory_image_sitemap` : variable inutilisée retirée (Sonar S1481, #945).
- CI Drone : l'analyse Sonar tourne dans son propre pipeline, sur une image arm64 native ; le scanner se figeait par moments sous émulation (Redmine #71371).
- `vactory_decoupled` : test du contrat avec block_class (classes dans le HTML du bloc et dans la sortie découplée) (#948).
- `vactory_decoupled` : test de l'endpoint de menus lu par le front (liens inaccessibles ou désactivés absents pour un anonyme) (#957).
- Décisions d'architecture consignées dans `docs/adr/` : fichiers temporaires, version figée du profil, Features, clé `url` des liens, block_class 3.x (#960). Les ADR couvrent le profil, pas la CI (#PRNUM).

## [3.2.3] - 2026-10-07
Voir la [release 3.2.3](https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3).

[Non publié]: https://github.com/voidagency/vactory_starter_kit/compare/3.2.3...3.x
[3.2.3]: https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3
