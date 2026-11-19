# Changelog

Toutes les évolutions notables du profil `vactory_starter_kit` (branche `3.x`).
Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Dans ce fichier, `#n` est un numéro de PR GitHub ; un ticket est noté « Redmine #n ».

**Règle :** chaque PR ajoute sa ligne dans `[Non publié]`, dans la bonne rubrique. Si la mise à jour demande une action sur les projets, elle l'ajoute aussi dans « À faire sur les projets ». À la release, `[Non publié]` prend le numéro de version et sert de note de release. Le dépôt est public : pour un correctif de sécurité, on décrit le comportement corrigé, sans détail d'exploitation.

## [Non publié]

### À faire sur les projets

**1. Composer**
- **Google Tag :** la contrainte passe de `^1.4` à `^2.1` (#931). C'est une version majeure : au `composer update`, les projets qui utilisent `google_tag` passent en 2.x. Ensuite, lancer `drush updb`, vérifier les conteneurs GTM, puis `drush cex`. Un projet qui fixe `drupal/google_tag` en `^1` aura un conflit composer.
- **`drupal/captcha` figé en 2.0.11** (#922) : un projet qui exige 2.0.12 ou plus aura un conflit composer.
- **Patches retirés** pour `drupal/swiftmailer`, `drupal/social_auth_microsoft` et `stevenmaguire/oauth2-microsoft` (#909) : un projet qui requiert encore ces paquets doit reprendre les patches chez lui.
- **`composer.lock` du profil :** il n'est pas utilisé par les projets. Lancer `composer update` côté projet pour récupérer les correctifs de sécurité des dépendances (voir « Dépendances »).

**2. Avant le déploiement (sites en config sync)**
- En local : `drush updb`, puis `drush cex`, puis commiter (`core.extension.yml`, rôles `anonymous` et `authenticated`). Sinon, le `drush cim` de `drush deploy` annule les updates :
  - il remet la permission retirée aux rôles anonyme et authentifié (#897) ;
  - il tente de désinstaller `paragraphs_edit`, ce qui fait échouer le déploiement (#910).

**3. Au déploiement**
- `drush updb` puis `drush cr`, obligatoires (c'est ce que fait `drush deploy`).
  - Deux updates s'exécutent : `vactory_decoupled_webform` 10102 (#897) et `vactory_dynamic_field` 9501 (#910). Ce dernier active `paragraphs_edit`, avec `paragraphs` et `contextual`, sur les sites qui ne l'ont pas.
  - Sans `drush cr`, les pages avec un menu plantent tant que l'ancien conteneur reste en cache (#914).
  - Les services `console.output` et `vactory_cloudinary.move_file_batch` sont retirés (#920). Vérifier qu'aucun code projet ne les appelle.

**4. Vérifications par fonctionnalité**
- **Client HTTP Vactory (#896)**
  - La vérification TLS est active par défaut, y compris sur les sites où le réglage n'avait jamais été enregistré : elle y était désactivée jusqu'ici.
  - Un site qui appelle un service en certificat auto-signé doit la désactiver dans `/admin/config/vactory-core-config`, ou passer `verify` à l'appel.
  - Les options passées par l'appelant (timeout…) sont maintenant transmises au client.
- **Permission `access any webform configuration`** retirée aux rôles anonyme et authentifié (#897). Le front par défaut ne l'utilise pas. Un front projet qui lit la config des webforms sans rôle de confiance doit être adapté.
- **Shield (#911)**
  - La config des sites existants n'est pas modifiée. Vérifier à la main que chaque site où Shield est actif a des identifiants propres au projet : rien ne le signale.
  - Le rapport d'état n'affiche une erreur que si Shield est actif avec des identifiants vides ; le formulaire refuse alors l'activation.
- **Upload de fichiers des webforms découplés (#906)**
  - Mêmes contrôles que le formulaire Drupal : extensions hors de la liste du webform refusées (422), nom de fichier limité à 150 caractères (au lieu de 240), `file.default_max_filesize` appliqué.
  - Vérifier les webforms qui acceptaient un type de fichier non listé : renseigner `#file_extensions` sur l'élément.
- **Slugs de termes (`term_2_slug`, #884)**
  - À chaque enregistrement d'un terme, le slug de chaque traduction est normalisé (minuscules ASCII, mots joints par `-`), y compris un slug saisi à la main.
  - Vérifier les URL ou filtres front qui s'appuient sur un slug avec majuscules, accents, `_` ou `/`.
- **Filtre Google Places (#913)** : les vues où l'option « Vactory google places instance » était cochée retrouvent l'autocomplete Google Places (script Google chargé avec la clé `google_places_api_key`). L'option était ignorée jusque-là.
- **Rendez-vous (#911)** : les champs conseiller ne sont plus éditables en back-office, quel que soit le thème d'administration (seul `seven` était pris en compte). Ils le restent en front.
- **Menus (#914)** : un projet qui étend `Drupal\vactory_core\MenuItems` ou `MenuRoleLinkTreeManipulator` en appelant leur ancien constructeur doit passer aux setters.
- **Sitemap (#910)** : les liens déjà stockés ne changent qu'au prochain enregistrement de la page d'accueil ou à un rebuild du sitemap.

### Ajouts
- Blocs découplés : condition de visibilité selon le statut de la réponse (200, 403, 404), évaluée sur le statut renvoyé par le routeur découplé (#890).

### Sécurité
- Vérification TLS active par défaut dans le client HTTP de `vactory_core` (#896).
- Permission `access any webform configuration` retirée aux rôles anonyme et authentifié (#897).
- Upload de fichiers des webforms découplés : validateurs du webform appliqués (#906).
- Shield : la config d'installation ne contient plus d'identifiants. L'activation est refusée, et une erreur apparaît au rapport d'état, si Shield est actif sans identifiants (#911).

### Correctifs
- **Authentification**
  - Connexion SSO : plus d'appel à une classe disparue, dépendance `oauth_login_oauth2` déclarée. Sans `resource_owner` en session, les champs mappés ne sont plus remis à NULL (#908).
  - `/oauth/login-token` compatible simple_oauth 5.2.6, au lieu d'une erreur 500 (#928).
  - Connexion OAuth2 réussie de nouveau journalisée quand l'option de `vactory_security_review` est cochée. Champs conseiller de `vactory_appointment` masqués en back-office quel que soit le thème d'administration (#911).
- **Appels HTTP**
  - Erreurs réseau (DNS, timeout, connexion refusée) capturées et journalisées dans 7 appels HTTP : purge nginx Next (exception et message de log seulement, voir Redmine #71229), Remote Post, listes Mailchimp, 3 checks `vactory_checklist`, diff de contenu (#936).
  - Remote Post : une URL invalide en GET ne fait plus planter la soumission (#936).
  - Handler Mailchimp : la soumission n'est plus bloquée en cas de panne, et le timeout est ramené à 10 s (#903).
- **Webforms**
  - Upload : réponse 503 au lieu de 500 sur un fichier verrouillé, 400 si le champ n'est pas un élément fichier, liste d'extensions de nouveau envoyée au front (#906).
  - Formulaire de réglages de `vactory_webform_typesafe` : plus de TypeError quand il est soumis sans saisie utilisateur (#935).
- **Sitemaps**
  - `/image-sitemap.xml` fonctionne sans laminas-diactoros (#899).
  - Sitemap : faute de frappe corrigée sur la clé `access` de la page d'accueil ; `paragraphs_edit` activé sur les sites où il manquait (#910).
- **Modules et dépendances**
  - Plugin de migration `hash_password` compatible Drupal 10.1+ (#900).
  - `accessCheck` sur les requêtes newsletters et liked_content (#901).
  - Génération PDF sans enregistrement préalable des réglages html2pdf (#902).
  - Modules installables : `vactory_form_autosave` (#898), et `vactory_toolbox` sans workbench_moderation (#904).
  - Dépendances déclarées pour `decoupled_flag` et `decoupled_search_ai` (#905).
  - MarkerClusterer figé sur un commit jsdelivr, plus de dépendance à rawgit (#907).
  - Filtre Google Places réellement utilisé par Views (#913).
  - simplify_menu et manipulateur de menu : services injectés par méthode. Le site ne tombe plus si simplify_menu ou le core ajoute un argument à un constructeur (#914).
- **Contenu**
  - Élément URL des Dynamic Fields compatible linkit 7.0.13+ (#912).
  - `vactory_amp` : plus d'erreur fatale au rendu d'un nœud exclu d'AMP sans liens d'en-tête (#919).
  - `vactory_cloudinary` : `getExternalUrl()` renvoie une chaîne, propriété dynamique déclarée (PHP 8.2), dépendances déclarées (#920).
  - Slugs de termes propres pour toutes les traductions (#884).

### Retraits
- Services `console.output` et `vactory_cloudinary.move_file_batch`, inutilisés et dont la classe n'existe pas (#920).

### Dépendances
- `drupal/google_tag` : `^1.4` → `^2.1` (#931).
- `drupal/captcha` figé en 2.0.11 : le patch ne s'applique plus sur 2.0.12 (#922).
- Patches composer jamais appliqués retirés (#909).
- `composer.lock` du profil mis à jour : guzzle 7.16.3, twig 3.30.0, phpseclib 3.0.58, symfony 6.4.50, drupal/core 10.6.22, php_codesniffer 3.13.6 (#893, #924 à #927, #931, #932). Sans effet sur les projets, qui résolvent leurs propres versions.

### Tests et CI
- Tests d'intégration des dépendances à historique d'incidents (Redmine #71160) :
  - linkit, views_autocomplete_filters, simplify_menu, amp, cloudinary, rate, jsonapi_search_api, file_entity ;
  - contrats entre le profil et ses dépendances ;
  - PR #912 à #920, et #934.
- Tests de caractérisation avant refactor (typesafe, routeur, Keycloak, espace privé), puis découpage des méthodes signalées par Sonar (#935, #938, #939).
- CI Drone :
  - un build par commit ; les branches sans PR et les tags ne sont plus construits (#921) ;
  - jeton GitHub pour composer (#940) ;
  - exclusions Sonar appliquées et règle `php:S1781` ignorée (#929).
- Dependabot limité aux mises à jour de sécurité (#930).

## [3.2.3] - 2026-10-07
Voir https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3
