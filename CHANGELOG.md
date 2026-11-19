# Changelog

Toutes les évolutions notables du profil `vactory_starter_kit` (branche `3.x`).
Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).

**Règle :** chaque PR ajoute sa ligne dans `[Non publié]`, dans la bonne rubrique, avec son numéro. Si la mise à jour demande une action sur les projets, elle l'ajoute aussi dans « À faire sur les projets ». À la release, `[Non publié]` devient le numéro de version et sert de note de release.

## [Non publié]

### À faire sur les projets
- **`drush updb` puis `drush cr`** sont obligatoires (inclus dans `drush deploy`).
  - Sans vidage du cache, les pages avec un menu plantent tant que l'ancien conteneur reste en cache (#914).
  - Deux updates s'exécutent : `vactory_decoupled_webform` 10102 (#897) et `vactory_dynamic_field` 9500 (#910).
- **Sites en config sync :** après `drush updb`, faire `drush cex` et commiter.
  - Sinon, le `drush cim` suivant annule les updates : il remet une permission retirée aux anonymes (#897).
  - Il tente aussi de désinstaller `paragraphs_edit`, ce qui fait échouer `drush deploy` (#910).
- **Client HTTP Vactory :** la vérification TLS est active par défaut (#896). Un site qui appelle un service en certificat auto-signé doit la désactiver dans `/admin/config/vactory-core-config`, ou passer `verify` à l'appel.
- **Shield :** la config des sites existants n'est pas modifiée. Vérifier que chaque site qui utilise Shield a ses propres identifiants. Sinon, le rapport d'état affiche une erreur (#911).
- **Upload webform découplé** (`/webform_rest/{webform}/upload/{element}`) : les extensions hors de la liste du webform sont refusées (422), les noms de fichier limités à 150 caractères, et `file.default_max_filesize` est appliqué (#906). Vérifier les webforms qui acceptaient un type de fichier non listé.
- **Mailchimp, SSO, checklist, Remote Post :** une panne réseau ne bloque plus la soumission ou la page, elle est journalisée (#903, #936). Rien à faire.

### Sécurité
- Vérification TLS active par défaut dans le client HTTP de `vactory_core` (#896).
- Permission `access any webform configuration` retirée aux anonymes (#897).
- Upload webform découplé : validateurs du webform appliqués (#906).
- Identifiants Shield : plus de valeurs par défaut livrées, erreur au rapport d'état si absents (#911).
- Mises à jour de dépendances : guzzle 7.16.3, twig 3.30.0, phpseclib 3.0.58, symfony/yaml 6.4.50, etc. (#893, #925, #926, #927, #931, #932). Dependabot limité aux mises à jour de sécurité (#930).

### Correctifs
- Connexion SSO : plus d'appel à une classe disparue ; sans `resource_owner` en session, les champs mappés ne sont plus remis à NULL (#908).
- `/oauth/login-token` compatible simple_oauth 5.2.6, au lieu d'une erreur 500 (#928).
- Log OAuth2 ; champs conseiller non éditables en back-office (#911).
- Erreurs réseau capturées dans 7 appels HTTP : purge nginx Next, Remote Post, listes Mailchimp, checks `vactory_checklist`, diff de contenu (#936). Handler Mailchimp : la soumission n'est plus bloquée (#903).
- Remote Post : une URL invalide en GET ne fait plus planter la soumission (#936).
- `/image-sitemap.xml` fonctionne sans laminas-diactoros (#899).
- Clé `access` du sitemap, et activation de `paragraphs_edit` (#910).
- Plugin de migration `hash_password` compatible Drupal 10.1+ (#900).
- `accessCheck` sur les requêtes newsletters et liked_content (#901).
- Génération PDF sans enregistrement préalable des réglages html2pdf (#902).
- Modules installables : `vactory_form_autosave` (#898), `vactory_toolbox` sans workbench_moderation (#904). Dépendances déclarées pour `decoupled_flag` et `decoupled_search_ai` (#905).
- MarkerClusterer figé sur un commit jsdelivr, plus de dépendance à rawgit (#907).
- Filtre Google Places réellement utilisé par Views (#913).
- simplify_menu : injection par méthode, plus de site en panne quand le module ajoute un argument (#914).
- Élément URL des Dynamic Fields compatible linkit 7.0.13+ (#912).
- `SettingsForm` de `vactory_webform_typesafe` soumis sans entrée utilisateur (#935).
- Slugs de termes propres pour toutes les traductions (#884).
- Visibilité des blocs selon le statut HTTP : 200, 404, 403 (#890).

### Dépendances
- `drupal/captcha` figé en 2.0.11 : le patch ne s'applique plus sur 2.0.12 (#922).
- Patches composer jamais appliqués retirés (#909).

### Tests et CI
- Tests d'intégration des dépendances à historique d'incidents (#71160) : linkit, views_autocomplete_filters, simplify_menu, amp, cloudinary, rate, jsonapi_search_api, file_entity, contrats profil / dépendances (#912 à #920, #934).
- Tests de caractérisation avant refactor (typesafe, routeur, Keycloak, espace privé), puis découpage des méthodes signalées par Sonar (#935, #938, #939).
- CI Drone :
  - un build par commit (#921) ;
  - jeton GitHub pour composer (#940) ;
  - exclusions Sonar appliquées (#929) ;
  - image CI sans plafond mémoire phpunit, phpcs limité aux fichiers modifiés par la PR, Sonar sur le Node.js de l'image.
- Services `console.output` et `vactory_cloudinary.move_file_batch` retirés : inutilisés, classe inexistante (#920).

## [3.2.3] - 2026-10-07
Voir https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3
