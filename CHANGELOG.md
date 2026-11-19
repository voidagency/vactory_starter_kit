# Changelog

Évolutions notables du profil `vactory_starter_kit` (branche `3.x`), au format [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Règles de rédaction et de release : [RELEASING.md](RELEASING.md).
`#n` est un numéro de PR GitHub ; un ticket est noté « Redmine #n ».

## [Non publié]

### Mise à jour des projets

```
composer require voidagency/vactory_starter_kit:<version> -W   # projet en 3.x-dev : composer update voidagency/vactory_starter_kit -W
drush updb -y
drush cr
# Ne pas lancer drush features:import sur une feature qui exporte les rôles anonyme / authentifié (voir 2.)
```

**1. Composer**
- **Tous les sites :** la plupart des projets figent une version exacte du profil : passer par `composer require voidagency/vactory_starter_kit:<version> -W`. Un projet en `3.x-dev` reçoit ces changements dès son prochain `composer update`, avant le tag. Le `composer.lock` du profil n'est pas utilisé par les projets : c'est cette commande qui apporte les correctifs de sécurité des dépendances.
- **Projets en 3.1.x :** lire aussi les notes des releases 3.2.x (par exemple, linkit est figé en 7.0.12 depuis 3.1.7).
- **`google_tag` :** la contrainte accepte 1.x et 2.x (#931, #942). Si le `composer.json` du projet n'exige pas google_tag, `-W` le fait passer en 2.x. Pour rester en 1.x : `composer require drupal/google_tag:^1.8` avant la mise à jour. Si le module est activé et passe en 2.x : `drush updb`, vérifier les conteneurs GTM, puis réexporter la feature qui porte leur config, le cas échéant.
- **Si le projet exige `drupal/captcha` 2.0.12 ou plus :** conflit composer, le profil fige 2.0.11 (#922). Rester en 2.0.11. Un projet verrouillé en 2.0.12 redescend en 2.0.11 : retirer un patch projet prévu pour 2.0.12.
- **Si le projet requiert `drupal/swiftmailer`, `drupal/social_auth_microsoft` ou `stevenmaguire/oauth2-microsoft` :** reprendre leurs patches dans le projet, le profil ne les déclare plus (#909).

**2. Avant le déploiement**
- **Si une feature du projet exporte les rôles `anonymous` ou `authenticated`** (`user.role.anonymous.yml`, `user.role.authenticated.yml`) : retirer la permission `access any webform configuration` de ces fichiers, ou réexporter la feature après `drush updb`. Sinon, un `drush features:import` (revert) la redonne (#897). Les features du profil ne l'exportent pas. Même chose pour un dossier de config du projet (`config/sync`, `config/staging`…) importé à la main par `drush cim`.
- **Sites en config sync (rare) :** en local, `drush updb`, puis `drush cex`, puis commiter (`core.extension.yml`, rôles `anonymous` et `authenticated`). Sinon, un `drush cim` (ou `drush deploy`) annule les updates : il remet la permission (#897) et tente de désinstaller `paragraphs_edit`, ce qui fait échouer le déploiement (#910).
- **Si du code projet appelle les services `console.output` ou `vactory_cloudinary.move_file_batch` :** ils sont retirés (#920), supprimer ces appels.
- **Si le projet étend `Drupal\vactory_core\MenuItems` ou `MenuRoleLinkTreeManipulator`** en appelant leur ancien constructeur : passer aux setters `setVactoryServices()` et `setAdminContext()` (#914).
- **Si le projet surcharge lui-même le contrôleur `Oauth2Token` de simple_oauth :** passer le logger en troisième argument du constructeur parent, obligatoire à partir de simple_oauth 5.2.6 (#928).

**3. Au déploiement**
- **Tous les sites :** `drush updb` puis `drush cr`, obligatoires (c'est ce que fait le déploiement capistrano : `updatedb -y` puis `cr`).
  - Deux updates du profil s'exécutent : `vactory_decoupled_webform` 10102 (#897) et `vactory_dynamic_field` 9501 (#910). Ce dernier active `paragraphs_edit`, avec `paragraphs` et `contextual`, sur les sites qui ne l'ont pas.
  - Sans `drush cr`, les pages avec un menu plantent tant que l'ancien conteneur reste en cache (#914).

**4. Vérifications après déploiement**
- **Tous les sites, client HTTP de Drupal (#896) :** la vérification TLS est active par défaut pour tous les appels faits par `\Drupal::httpClient()` ou le service `http_client` (profil, contrib, code projet), y compris quand le réglage n'a jamais été enregistré. Un appel vers un service au certificat invalide ou auto-signé échoue (`cURL error 60`). Pour la désactiver sur un environnement :
  - en back-office, `/admin/config/vactory-core-config` ;
  - ou dans `settings.php` : `$config['vactory_core.global_config']['http_client_ssl_verification'] = FALSE;` ;
  - ou en passant `verify` à l'appel, qui reste prioritaire sur le réglage.
- **Tous les sites, sitemap (#910) :** les liens déjà stockés ne changent qu'au prochain enregistrement de la page d'accueil ou à un rebuild du sitemap.
- **Si Shield est actif :** vérifier les identifiants de chaque site (#911, détail dans le ticket Dispatch).
- **Si un front projet lit la configuration des webforms** sans rôle de confiance : l'adapter, la permission `access any webform configuration` est retirée aux rôles anonyme et authentifié (#897). Le front par défaut ne l'utilise pas.
- **Si le site a des webforms découplés avec upload de fichier (#906) :** vérifier que chaque élément fichier déclare `#file_extensions` ; un fichier hors liste est refusé (422). Un élément sans `#file_extensions` applique la liste par défaut de webform.
- **Si le front s'appuie sur `term_2_slug` (#884) :** vérifier les URL et filtres qui utilisent un slug avec majuscules, accents, `_` ou `/`.
- **Si `vactory_google_places` est actif (#913) :** vérifier les vues où l'option « Vactory google places instance » est cochée, l'autocomplete Google Places y est de nouveau chargé.
- **Si `vactory_appointment` est actif (#911) :** les champs conseiller ne sont plus éditables en back-office. Ils le restent en front.
- **Si `vactory_decoupled_flag`, `vactory_decoupled_search_ai` ou `vactory_toolbox` est actif (#904, #905) :** Drupal n'active pas sur un site existant les dépendances nouvellement déclarées. Vérifier qu'elles sont actives (`drush pm:list`).
- **Sites existants, module optionnel `vactory_admin_hardening` (#886) :** il n'est installé d'office que sur les nouveaux sites. Il bloque en web, pour tous les comptes, l'interface de migration et l'import, l'export et la synchronisation de config (pages `config_update_ui` comprises). L'activer (`drush en vactory_admin_hardening`) quand l'équipe passe par Drush et Features. Le check Security Review signale son absence.

### Sécurité
- `vactory_core` : vérification TLS active par défaut dans le client HTTP de Drupal (#896).
- `vactory_decoupled_webform` : permission `access any webform configuration` retirée aux rôles anonyme et authentifié (#897).
- `vactory_decoupled_webform` : l'upload de fichiers applique les validateurs du webform, comme le formulaire Drupal : extensions, taille, longueur du nom (#906).
- `vactory_core` : Shield ne peut plus être activé sans identifiants, et le rapport d'état signale une erreur dans ce cas (#911).

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

### Interne
- Tests d'intégration des dépendances à historique d'incidents et contrats entre le profil et ses dépendances (Redmine #71160 ; #912 à #920, #934).
- Tests de caractérisation, puis découpage des méthodes signalées par Sonar, sans changement de comportement (#935, #938, #939).
- CI Drone : un build par commit, sans build pour les branches sans PR ni pour les tags (#921) ; jeton GitHub pour composer (#940) ; exclusions Sonar appliquées et règle `php:S1781` ignorée (#929).
- Dependabot limité aux mises à jour de sécurité (#930).
- `vactory_image_sitemap` : phpcs propre, sans changement de comportement (#937).

## [3.2.3] - 2026-10-07
Voir la [release 3.2.3](https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3).

[Non publié]: https://github.com/voidagency/vactory_starter_kit/compare/3.2.3...3.x
[3.2.3]: https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3
