# Changelog

Évolutions notables du profil `vactory_starter_kit` (branche `3.x`), au format [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Règles de rédaction et de release : [RELEASING.md](RELEASING.md).
`#n` est un numéro de PR GitHub ; un ticket est noté « Redmine #n ».

## [Non publié]

### Mise à jour des projets

**Risques et points d'attention**
- **⚠️ Risque principal, vérification TLS (#896) :** elle est active par défaut pour **tous** les appels HTTP du site (`\Drupal::httpClient()`, service `http_client` : profil, contrib, code projet), y compris quand le réglage n'a jamais été enregistré. Un service au certificat invalide ou auto-signé échoue (`cURL error 60`). **Tester chaque appel HTTPS du projet avant la mise en production** (étapes 4 et 6).
- **Sites protégés par Shield (#988) :** la mise à jour fait passer Shield en 1.9 ou plus (1.11). Avec flood_control 2.x, Shield répond alors 500 à toute requête qui envoie des identifiants : plus personne n'entre. Le profil exige maintenant flood_control `^3.0.1`, qui corrige ce cas ; un projet qui fige flood_control en 2.x doit lever ce blocage (étape 1).
- **Front qui lit la configuration des webforms** sans rôle de confiance : l'adapter. La permission `access any webform configuration` n'est plus donnée aux rôles anonyme et authentifié (#897) ; le front par défaut ne l'utilise pas.
- **Webforms découplés avec upload de fichier (#906) :** un fichier hors de la liste d'extensions de l'élément est refusé (422). Un élément sans `#file_extensions` applique la liste par défaut de webform.
- **`term_2_slug` (#884) :** le slug de chaque traduction est normalisé à l'enregistrement du terme (minuscules ASCII, mots joints par `-`). Les URL et filtres front qui s'appuient sur un slug avec majuscules, accents, `_` ou `/` changent.
- **`vactory_appointment` (#911) :** les champs conseiller ne sont plus éditables en back-office ; ils le restent en front.
- **Message d'erreur du CAPTCHA (#949) :** le patch du profil qui imposait le message par défaut traduit est retiré. Un message personnalisé dans les réglages de CAPTCHA, ignoré jusque-là, s'affiche de nouveau, tel quel dans toutes les langues tant qu'il n'est pas traduit (traduction de configuration). `drush updb` l'indique dans sa sortie (étape 2).
- **`block_class` 3.1 (#948) :** le formulaire d'un bloc refuse une classe qui n'est pas un identifiant CSS (caractères `:`, `/`, `[`, `.`, `@`… ou début par un chiffre), par exemple une variante Tailwind `md:hidden`. Les classes déjà enregistrées ne sont pas modifiées et restent envoyées telles quelles au front découplé, mais un tel bloc ne peut plus être enregistré en back-office sans corriger le champ. Dans le HTML rendu par Drupal, ces caractères étaient déjà retirés.
- **Sitemap (#910) :** les liens déjà stockés ne changent qu'au prochain enregistrement de la page d'accueil ou à un rebuild du sitemap.
- **Menus rendus par `simplify_menu()` (en-têtes, pieds de page, menu de l'espace privé, #957) :** un lien dont la cible n'est pas accessible à l'utilisateur courant n'est plus affiché, comme dans les menus du core. Un lien laissé visible à dessein vers une page réservée (par exemple une page de l'espace privé montrée aux visiteurs anonymes pour les amener à se connecter) disparaît pour ceux qui n'y ont pas accès : le faire pointer vers une page accessible (page de connexion, page de présentation).
- **Sites avec `vactory_quiz_certificat`, méthode html2pdf (#953) :** `$settings['file_private_path']` est requis. Sans lui, les certificats ne sont pas générés et le rapport d'état affiche une erreur. Un certificat se télécharge connecté sur le site Drupal, par son titulaire ou par un compte qui a la permission `administer site configuration` : le lien envoyé par e-mail demande de se connecter d'abord.
- **Certificats déjà générés :** les migrer vers le dossier privé, procédure dans le ticket Dispatch.
- **Front de projet qui a modifié l'appel de l'API des menus (#966) :** les menus reçus contiennent seulement les liens auxquels l'utilisateur a accès. Le front par défaut n'est pas concerné. Vérifier les menus du front après la mise à jour (étape 6) ; détail dans Redmine #71439.
- **Boutons AddToAny (#959) :** le script AddToAny n'est plus chargé que là où le bloc de partage, le bloc « follow », le champ AddToAny d'un affichage d'entité ou le champ de vue AddToAny est affiché. Un template projet qui écrit lui-même le HTML des boutons (`a2a_kit`), sans ce bloc ni ce champ, doit attacher la librairie `addtoany/addtoany.front` (`{{ attach_library('addtoany/addtoany.front') }}`), sinon ses boutons ne s'affichent plus. Un module projet qui porte déjà son propre contournement (retrait du script AddToAny des pages sans boutons) peut le retirer après la mise à jour.
- **Routeur découplé, pages qui ne sont pas des nœuds (#891) : un projet dont le front affiche par le routeur des entités autres que des nœuds (termes de taxonomie, entités personnalisées) doit ajouter ces types à la liste des types de page, avant ou juste après la mise à jour. Sinon ces pages répondent 404.** Avant : livrer avec le code `$settings['vactory_decoupled_router_page_entity_types'] = ['node', 'taxonomy_term'];` dans `settings.php` (aucune 404 pendant le déploiement). Après : cocher les types en back-office (étape 4). Sans réglage, seuls les nœuds sont servis, dès le déploiement du code. Le front par défaut n'affiche que des nœuds : rien à faire.
- **Routeur découplé, variantes d'un alias (#891) :** un chemin qui ne diffère de l'alias d'une page que par la casse, les accents ou un slash final n'affiche plus la page : il est redirigé de façon permanente vers l'alias. Un lien interne, un sitemap ou une campagne qui utilise une telle variante passe par une redirection : corriger ces liens. Un front de projet qui a modifié la lecture des redirections du routeur vérifie qu'il suit bien celle-ci (étape 6).
- **Routeur découplé, autres chemins d'une page et 404 (#972) :** le routeur ne résout plus que l'adresse d'une page (son alias, ou `/node/<id>`). Les autres chemins d'un contenu (`/node/<id>/edit`, `/delete`, `/revisions`, `/latest`, `/translations`, une révision, et `/node/<id>` écrit avec des majuscules, un zéro initial ou un slash final) répondent 404. Un `/node/<id>` inconnu ou non publié répond 404 sans redirection vers la page d'erreur. **Un front de projet qui s'appuyait sur cette redirection vers la page d'erreur, ou qui résout `/node/<id>/<autre chose>` par le routeur, doit être vérifié** (étape 6). Le front par défaut affiche sa page 404 avec le statut 404 : rien à faire.
- **Aperçu du front dans le back-office (#972) :** sur la page d'une révision d'un contenu (`/node/<id>/revisions/<révision>/view`), le cadre d'aperçu du front affichait la version publiée du contenu, et non la révision ; il affiche maintenant la page 404 du front. L'aperçu d'un brouillon par « Enregistrer et voir la page » ne change pas.
- **Sites avec `vactory_sondage` (#983) :** un sondage ouvert est maintenant fermé à sa date de fermeture, au passage suivant du cron de `vactory_reminder` (tâche Ultimate Cron `vactory_reminder_cron`, toutes les 5 minutes par défaut) ; jusqu'ici aucun sondage n'était fermé automatiquement. Un sondage ouvert dont la date de fermeture est déjà passée reste ouvert : `drush updb` en donne le nombre (étape 2), le fermer en back-office si besoin.

**Procédure, dans cet ordre**

Testée sur trois projets du parc (3.1.2, `3.x-dev` d'avant 3.1.3, 3.2.3), Redmine #71368. Sur les trois, la commande composer seule ne suffisait pas : faire les étapes 0 et 1 en entier.

```
# 0. Avant : sauvegarde de la base ; Composer 2.8 (voir l'étape 0)
# 1. Code et composer (en local, puis commit)
composer remove --no-update <dépendances du profil recopiées dans le projet>   # voir 1.a
composer require drush/drush:^13.7 voidagency/vactory_starter_kit:<version> -W
composer install                                                                # voir 1.e
# 2. Déployer (capistrano ou Docker), site en maintenance, puis à la main :
drush updb -y
# 3. Features à réimporter : aucune pour cette version
# 4. Back-office : voir l'étape 4
# 5.
drush cr
# 6. Vérifications : voir ci-dessous
```

**0. Avant de commencer**
- Sauvegarder la base de l'environnement à mettre à jour, et la tester d'abord sur une copie (préproduction ou local).
- **Composer 2.8.** Les versions 2.9 et plus refusent par défaut les paquets visés par un avis de sécurité, y compris ceux que le projet fige lui-même : l'étape 1 échoue. Correction des contraintes concernées suivie dans le profil (Redmine #71556).
- Noter les erreurs du rapport d'état (`/admin/reports/status`) et les mises à jour déjà en attente (`drush updatedb-status`) : après la mise à jour, on compare.

**1. Code et composer** (en local, avant le déploiement)
- **Tous les sites :** figer la version du profil : `composer require voidagency/vactory_starter_kit:<version> -W`. Le `composer.lock` du profil n'est pas utilisé par les projets : c'est cette commande qui apporte les correctifs de sécurité des dépendances.
- **Si le projet suit `3.x-dev` (ou une autre branche de dev) :** passer sur la version figée `voidagency/vactory_starter_kit:<version>`. Une branche de dev livre des changements non publiés, sans changelog ni tests de release.
- **a. Dépendances du profil recopiées dans le projet.** Beaucoup de projets redéclarent dans leur propre `composer.json` des paquets que le profil exige déjà, avec les contraintes de leur époque (un projet en 3.1.2 en avait 112). Une contrainte plus ancienne que celle du profil bloque l'étape 1. **Les retirer du projet** (`composer remove --no-update <paquets>`) et laisser le profil les piloter ; garder seulement ce que le projet utilise en plus du profil. Lister les paquets en commun : comparer les `require` du projet à ceux du `composer.json` du profil à la version visée. Cas rencontrés : `drupal/captcha` figé sous 2.0.11 (2.0.10), `drupal/flag` 4.x (profil `^5.0` depuis 3.1.5), `drupal/ctools` `^3` (profil `^4.1.1`), `drupal/metatag` `^1` (`^2`), `drupal/field_group` `^3` (`^4`), `drupal/search_api_exclude_entity` `^1` (`^3`), `drupal/colorapi` `^1` (`^2`), `voidagency/jquery_colorpicker` `^4`, `drupal/flood_control` `^2` (`^3.0.1`), versions figées (`drupal/search_api` 1.31.0…).
- **b. drush 13.7 ou plus.** Le profil exige `drupal/devel ^5.5` (depuis 3.1.8), qui refuse drush avant 13.7. Un projet en drush 12 : `composer require drush/drush:^13.7` dans la même commande que le profil.
- **c. Projets en 3.1.x ou sur un `3.x-dev` d'avant 3.2 :** les montées majeures arrivent en une fois (ctools 4, flag 5, metatag 2, field_group 4) ; `drush updb` convertit les données (metatag : plusieurs milliers d'enregistrements sur un site moyen). Prévoir le temps et la sauvegarde.
- **d. Paquets propres au projet devenus incompatibles** (exemple rencontré : `facebook/graph-sdk ~5.0`, qui exige PHP 7 et n'était plus utilisé) : vérifier leur usage dans le code, les retirer ou les remplacer.
- **e. `composer install` après le `require`.** Des forks `voidagency/*` sont remplacés par le paquet `drupal/*` du même nom (`merge_translations`, `block_list_override`, `swiftmailer`) : composer installe le nouveau puis supprime le dossier en retirant l'ancien. `drush updb` échoue alors (« The module merge_translations does not exist »). `composer install` réinstalle ce qui manque ; vérifier que chaque module du `composer.lock` a son dossier.
- **f. Si le projet exige `voidagency/vactory_dashboard` :** le monter en `^1.4.4` dans la même commande que le profil (`composer require voidagency/vactory_dashboard:^1.4.4 …`). Sa mise à jour `vactory_dashboard_update_9971` retire de l'index de recherche du dashboard le champ « Term slug » inutilisable sur un site sans `vactory_decoupled`, qui fait échouer `drush updb` avec search_api 1.43 (Redmine #71557). Testé de 1.2.4 à 1.4.4.
- **Versions corrigées (#993) :** `drupal/stage_file_proxy` passe en `^3.1.7`, `drupal/gin` en `^4.1.4` (avec `drupal/gin_toolbar` 2.x), `drupal/responsive_favicons` en `^2.0.2 || ^3.0 || ^4.0` (4.0 en pratique), `drupal/tinypng` en `^2.0.2`. Un projet qui fige l'une de ces dépendances sur une version plus ancienne (gin 3.x, favicons `1.x-dev`, tinypng 1.x, stage_file_proxy 1.x ou 2.x) la retire (voir 1.a). Aucune update de base ni action en back-office ; un projet dont le thème d'administration est gin voit le back-office en gin 4.
- **`google_tag` (#931, #942) :** la contrainte accepte 1.x et 2.x. Si le `composer.json` du projet n'exige pas google_tag, `-W` le fait passer en 2.x. Pour rester en 1.x : `composer require drupal/google_tag:^1.8` avant la mise à jour.
- **`drupal/captcha` (#949) :** la contrainte du profil devient `^2.0.11` (un projet qui fige captcha en dessous, par exemple 2.0.10, bloque l'étape 1 : retirer ce figeage, voir 1.a) : 2.0.12 sur un core 10.3 ou plus, 2.0.11 en dessous. Le profil ne déclare plus le patch « Fix captcha error message translation ». **Si le projet porte sa propre copie de ce patch** (`extra.patches` ou fichier de patches) : la retirer, elle ne s'applique pas sur 2.0.12 et composer échoue.
- **Si une feature du projet exporte `captcha.settings` :** y mettre `wrong_captcha_response_message: ''` après l'update. Sinon, un `drush fr` de cette feature remet le message anglais, qui n'est alors plus traduit (#949). Même chose pour un dossier de config importé par `drush cim`.
- **Si le projet requiert `drupal/swiftmailer`, `drupal/social_auth_microsoft` ou `stevenmaguire/oauth2-microsoft` :** reprendre leurs patches dans le projet, le profil ne les déclare plus (#909).
- **Si du code projet appelle `console.output` ou `vactory_cloudinary.move_file_batch` :** ces services sont retirés (#920), supprimer les appels.
- **Si le projet étend `Drupal\vactory_core\MenuItems` ou `MenuRoleLinkTreeManipulator`** avec l'ancien constructeur : passer aux setters `setVactoryServices()` et `setAdminContext()` (#914).
- **Si le projet surcharge le contrôleur `Oauth2Token` de simple_oauth :** passer le logger en troisième argument du constructeur parent (simple_oauth 5.2.6, #928).
- **Si une feature du projet exporte les rôles `anonymous` ou `authenticated`** (`user.role.anonymous.yml`, `user.role.authenticated.yml`) : retirer la permission `access any webform configuration` de ces fichiers. Sinon, un `drush fr` de cette feature la redonne (#897). Même chose pour un dossier de config importé à la main par `drush cim` (`config/sync`, `config/staging`…).
- **Versions relâchées (#944) :** consumers, csv_serialization, editor_advanced_link, jquery_colorpicker, link_attributes et block_list_override montent à la dernière version compatible avec le core du projet. Vérifier le diff du `composer.lock` avant de commiter.
- **`linkit` (#947) :** la contrainte passe de 7.0.12 à `^7.0.16` ; le projet monte en 7.0.18 ou plus. Un projet qui fige linkit en dessous de 7.0.16 (6.x, 7.0.12) aura un conflit composer : lever ce blocage. Un projet qui a son propre contournement de la régression linkit 7.0.13 (élément `uri` ajouté à `url_extended`, patch local) le retire : le profil la gère depuis #912.
- **`drupal/block_class` (#948) :** la contrainte passe de 3.0.0 à `^3.0` : 3.1.0 sur un core 10.3 ou plus, 3.0.0 en dessous. Pour rester en 3.0.0 (classes de bloc avec `:` ou `/` saisies en back-office) : `composer require drupal/block_class:3.0.0` avant la mise à jour. Ne pas passer en 4.x (branche expérimentale).
- **`drupal/flood_control` (#988) :** la contrainte passe de `^2.3` à `^3.0.1`. Si le `composer.json` du projet exige flood_control 2.x, lever cette contrainte, sinon conflit composer. Aucune update de base à lancer ; la liste des IP autorisées est conservée.
- **Si du code projet appelle `vactory_html2pdf.manager::html2Pdf()` (#953) :** le PDF est écrit à l'URI demandée et l'`uri` renvoyée est un URI de flux (`public://dossier/fichier.pdf`). Adapter le code qui utilise cette `uri` ou le chemin du fichier. Un schéma indisponible sur le site lève `VactoryHtml2PdfException`.

**2. `drush updb -y`** (à la main, après le déploiement)
- **Site en maintenance entre le déploiement du code et la fin de `drush updb`.** Avec le nouveau code et l'ancien conteneur en cache (flood_control 3, Shield 1.11), les pages et la plupart des commandes drush répondent en erreur ; `drush updb` passe et le corrige.
- **Sites sans `vactory_decoupled` qui gardent `vactory_dashboard` en version antérieure à 1.4.4** (voir 1.f) : `drush updb` peut échouer sur `entityqueue_post_update_relationship_limit_queue_list` (« Could not retrieve data definition for field 'Term slug' on index 'Dashboard Search' »). Retirer le champ de l'index : `drush php:eval "\Drupal\search_api\Entity\Index::load('vactory_dashboard_search')->removeField('slug')->save();"`, puis relancer `drush updb` (Redmine #71557).
- Deux updates du profil : `vactory_decoupled_webform` 10102, qui retire la permission `access any webform configuration` aux rôles anonyme et authentifié (#897) ; `vactory_dynamic_field` 9501, qui active `paragraphs_edit`, avec `paragraphs` et `contextual`, sur les sites qui ne l'ont pas (#910).
- `vactory_core` 10001 (#949) : vide le réglage `captcha.settings:wrong_captcha_response_message` quand il contient le message par défaut (en anglais ou dans une langue du site). CAPTCHA affiche alors son message par défaut traduit par la traduction d'interface, comme avec le patch. Un message personnalisé est conservé et signalé dans la sortie de la commande.
- Si google_tag passe en 2.x et que le module est activé : ses propres updates s'exécutent aussi.
- **Sites en config sync (rare) :** ensuite, `drush cex` et commiter (`core.extension.yml`, rôles `anonymous` et `authenticated`). Sinon, le prochain `drush cim` annule ces updates.
- Si `drupal/consumers` passe de moins de 1.23 à 1.23 ou plus : ses updates 8110 à 8112 s'exécutent (champ `status`) (#944).
- `vactory_quiz_history` 10001 (#963) : ajoute un index sur la colonne `uid` de la table `vactory_quiz_history`. Rien n'est fait si l'index existe déjà ; les lignes de la table ne sont pas modifiées.
- `vactory_decoupled_router` 10001 (#891) : crée la config `vactory_decoupled_router.settings` avec la liste des types d'entité servis comme pages par le routeur (`node`). Rien n'est fait si la liste existe déjà. **Sites en config sync (rare) :** ensuite, `drush cex` et commiter ce fichier.
- `vactory_sondage` 10001 (#983) : supprime de la file de `vactory_reminder` les fermetures de sondage mises en file sans identifiant de sondage (elles échouaient à chaque passage), puis remet en file la fermeture de chaque sondage ouvert dont la date de fermeture est à venir. La sortie donne le nombre d'éléments supprimés, de sondages remis en file et de sondages ouverts dont la date est passée, laissés ouverts. Rien à faire si le module n'est pas actif.

**3. `drush fr -y <module>`** (features à réimporter)
- **Aucune pour cette version.** Les configs modifiées dans le profil (`shield.settings`, `captcha.settings`, `vactory_toolbox`, `vactory_form_autosave`) ne servent qu'à l'installation : les sites existants gardent les leurs.
- **Ne pas réimporter les autres features du profil :** un `drush fr` remplace la config active par celle du module et efface les valeurs propres au site (clés API, identifiants, réglages).
- **Si google_tag est passé en 2.x :** réexporter (et non réimporter) la feature du projet qui porte la config GTM, après vérification des conteneurs.
- **Si `vactory_quiz_certificat` est actif (#963) :** rien à réimporter. Le corps de mail par défaut est corrigé pour les nouvelles installations ; le corps de mail enregistré sur un site existant n'est pas modifié, et son jeton `[quiz:certificat_url]` est maintenant remplacé par le lien du certificat.

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
- **Si `vactory_quiz_certificat` est actif avec la méthode html2pdf (#953) :** définir `$settings['file_private_path']` dans `settings.php` (dossier accessible en écriture par le serveur web), puis vérifier que le rapport d'état (`/admin/reports/status`) n'affiche pas l'erreur « Quiz certificates ». Tant que le réglage manque, aucun certificat n'est généré : à chaque cron, l'erreur est journalisée et la demande reste dans la file, reprise aux crons suivants. Pour le support : un compte qui a la permission `administer site configuration` télécharge tous les certificats ; les autres comptes, seulement les leurs.
- **Si le front affiche par le routeur des termes de taxonomie ou des entités personnalisées (#891) :** cocher ces types dans `/admin/config/system/vactory_router/page-entity-types` (onglet « Page entity types » de la config du routeur, permission `administer site configuration`). Si `$settings['vactory_decoupled_router_page_entity_types']` est défini dans `settings.php`, il remplace ce réglage, et le formulaire le signale.

**5. `drush cr`**
- Obligatoire : sans lui, les pages avec un menu plantent tant que l'ancien conteneur reste en cache (#914).
- block_class 3.1 déclare un nouveau service pour ses hooks : sans `drush cr`, le rendu des blocs plante (#948).
- Boutons AddToAny (#959) : sans `drush cr`, les boutons de partage ne s'affichent plus, tant que le nouveau hook qui leur attache le script n'est pas pris en compte.
- Routeur découplé (#891) : sans `drush cr`, la page du réglage des types de page est introuvable. Si le front garde en cache les réponses du routeur, les nouvelles réponses (404, redirections) apparaissent au renouvellement de ce cache.
- Routeur découplé (#972) : le service des règles du routeur reçoit un nouvel argument. Le code fonctionne sans `drush cr`, mais le lancer aligne le conteneur ; si le front garde en cache les réponses du routeur, les nouvelles réponses (404 sans redirection) apparaissent au renouvellement de ce cache.
- Si `vactory_quiz_certificat` est actif (#975) : un nouveau service et un nouveau hook décident de l'accès aux certificats. Sans `drush cr`, ils ne sont pas pris en compte et le titulaire peut ne pas obtenir son certificat.

**6. Vérifications**
- `drush updb` n'a plus rien en attente, et `drush status` est correct.
- Chaque appel HTTPS du projet (API métier, SSO, CRM, envoi de formulaire distant…) répond, sans `cURL error 60` dans les logs.
- **Si Shield est actif :** une requête avec les identifiants Shield répond normalement, et avec un mauvais mot de passe 401, jamais 500 (#988).
- **Responsive favicons 4.0 :** le rapport d'état signale une erreur si les fichiers d'icônes déclarés sont absents du disque (l'ancienne version ne vérifiait pas) ; sur une copie sans les fichiers du site, ce n'est pas une régression (#993).
- **Si `vactory_decoupled_flag`, `vactory_decoupled_search_ai` ou `vactory_toolbox` est actif :** leurs dépendances nouvellement déclarées sont actives (`drush pm:list`), car Drupal ne les active pas sur un site existant (#904, #905).
- Une page avec menu s'affiche ; `/sitemap.xml` répond, et `/image-sitemap.xml` si `vactory_image_sitemap` est actif (sur une copie sans les fichiers du site, les régénérer avant de vérifier : `drush xmlsitemap:rebuild` puis `drush xmlsitemap:regenerate` pour `/sitemap.xml` ; `drush cron` pour `/image-sitemap.xml`, produit par `vactory_image_sitemap`).
- **Si block_class est passé en 3.1 :** un bloc qui a une classe CSS l'affiche toujours (HTML Drupal, et propriété `classes` du bloc côté front) ; le formulaire d'un bloc avec classes s'enregistre (#948).
- Un envoi de webform avec fichier aboutit, côté front.
- Une mauvaise réponse à un CAPTCHA affiche le message d'erreur traduit, en français et en arabe (#949).
- Connexion au back-office, et connexion front (OAuth, SSO s'il est utilisé) (#908, #928).
- En back-office, l'autocomplete des liens d'un Dynamic Field propose des contenus et enregistre le lien choisi (linkit, #947).
- **Si `vactory_quiz` est actif :** `views_autocomplete_filters` est actif (`drush pm:list`) ; sinon l'activer (`drush en views_autocomplete_filters`), car Drupal n'active pas une dépendance nouvellement déclarée sur un site existant. Sans lui, la vue « Mes quiz passés » fonctionne, mais son filtre Titre n'a pas d'autocomplete (#956).
- **Si des rôles sont cochés dans l'onglet *Registration* :** un compte créé par le formulaire d'inscription du front les reçoit (#950).
- Menus du thème (en-tête, pied de page) en anonyme puis connecté : les liens attendus sont présents ; un lien vers une page non publiée ou réservée n'apparaît que pour les utilisateurs qui y ont accès (#957). Une page servie depuis un cache garde l'ancien menu jusqu'à son renouvellement.
- **Si `vactory_quiz_certificat` est actif (méthode html2pdf) :** le rapport d'état n'affiche pas l'erreur « Quiz certificates ». Réussir un quiz avec certificat, lancer le cron, puis télécharger le certificat avec le compte qui l'a obtenu ; avec un autre compte, le même lien répond « page introuvable » ; hors connexion, il ne donne pas le fichier (#953).
- **Si `vactory_quiz_certificat` envoie le mail de notification (#963) :** le mail reçu après le cron contient le lien du certificat, et non le texte `[quiz:certificat_url]`. Avec un certificat qui ne peut pas être généré (par exemple sans `file_private_path`), le cron se termine normalement et l'erreur est dans les logs, canal `vactory_quiz_certificat`.
- **Si `vactory_quiz_certificat` envoie le mail de notification (#963) :** ouvrir le lien du mail hors connexion : la page de connexion de Drupal s'affiche ; après connexion avec le compte qui a obtenu le certificat, le PDF se télécharge. Avec un autre compte, le lien répond « page introuvable ». Sur un site découplé, depuis un domaine `api.…` (où `vactory_decoupled` renvoie les pages de Drupal vers le front), la page de connexion n'est pas servie : pas de redirection, la réponse est celle d'un fichier introuvable.
- **Si `vactory_quiz_certificat` est actif (#975) :** le titulaire télécharge son certificat sans permission sur les fichiers privés. Un compte qui a des permissions sur les fichiers (`view private files`, `bypass file access`, par exemple le rôle webmaster) sans `administer site configuration` reçoit « page introuvable » pour le certificat d'un autre compte. Les autres fichiers privés répondent comme avant la mise à jour.
- **Si le front du projet a modifié l'appel de l'API des menus :** les menus du front, en anonyme puis connecté, contiennent les liens attendus ; un lien vers une page non publiée ou réservée n'y figure que pour les utilisateurs qui y ont accès (#966).
- **Si le site affiche des boutons AddToAny (#959) :** sur une page sans boutons, le code source ne contient plus le script `static.addtoany.com` ; sur une page avec boutons (partage ou « follow »), il le contient, les boutons s'affichent et un partage s'ouvre au clic.
- **Sites découplés (#891) :** une page du front s'affiche à son adresse habituelle ; la même adresse écrite avec des majuscules redirige vers l'adresse de l'alias (redirection permanente) ; `/taxonomy/term/<id>` répond 404, ou affiche le terme si le type a été ajouté à la liste des types de page.
- **Sites découplés (#972) :** sur le front, une adresse inconnue et `/node/<id inconnu>` répondent 404 (statut 404, sans redirection vers la page d'erreur) ; l'adresse de modification d'une page (`/node/<id>/edit`) répond 404 ; `/node/<id>` d'une page publiée redirige toujours vers son alias ; les pages système (connexion, recherche, page d'erreur) s'affichent.
- **Si `vactory_sondage` est actif (#983) :** créer un sondage avec une date de fermeture dans quelques minutes ; après cette date et un passage du cron de `vactory_reminder` (ou `drush rrq`), son statut est « Fermé ». `drush rrq` n'affiche plus d'élément `sondage_closer` en « Failed ».

**Après la mise à jour : à prévoir**
- **Menus (#957) :** les liens vers un contenu non publié ou réservé disparaissent pour les visiteurs qui n'y ont pas accès. Sur un projet testé, trois liens du menu principal, affichés aux anonymes avant, ont disparu : **prévenir le client** et corriger ces liens.
- **swiftmailer :** le profil envoie les mails par `symfony_mailer`. Un projet qui exige encore `drupal/swiftmailer` et l'utilise comme expéditeur (`drush cget mailsystem.settings defaults`) a un module abandonné : prévoir la migration vers symfony_mailer, avec un envoi réel vérifié (formulaire, mot de passe oublié).
- **Shield 1.11** stocke les identifiants encodés dans sa configuration : un script qui lisait `credentials.shield.user` / `pass` en clair ne fonctionne plus.

### Sécurité
- Dépendances dont aucune version autorisée par le profil n'était corrigée : `drupal/stage_file_proxy` `^3.1.7`, `drupal/gin` `^4.1.4`, `drupal/responsive_favicons` `^2.0.2 || ^3.0 || ^4.0`, `drupal/tinypng` `^2.0.2` (#993, Redmine #71556).
- `vactory_core` : vérification TLS active par défaut dans le client HTTP de Drupal (#896).
- `vactory_decoupled_webform` : permission `access any webform configuration` retirée aux rôles anonyme et authentifié (#897).
- `vactory_decoupled_webform` : l'upload de fichiers applique les validateurs du webform, comme le formulaire Drupal : extensions, taille, longueur du nom (#906).
- `vactory_core` : Shield ne peut plus être activé sans identifiants, et le rapport d'état signale une erreur dans ce cas (#911).
- `vactory_decoupled` : inscription JSON:API : les rôles du compte sont décidés côté serveur (#885).
- `vactory_core` : les menus rendus par `simplify_menu()` n'affichent plus les liens dont la cible n'est pas accessible à l'utilisateur courant, à tous les niveaux, comme le fait simplify_menu 3.3 (#957, Redmine #71363).
- `vactory_rate` : le formulaire de réglages demande la permission `administer site configuration` (#961).
- `vactory_quiz_certificat` : les certificats générés par html2pdf sont enregistrés dans le dossier privé du site, sous un nom de fichier non devinable ; téléchargement réservé au titulaire connecté et aux comptes qui ont la permission `administer site configuration`, les autres demandes reçoivent la réponse « page introuvable » (#953, Redmine #71157).
- `vactory_decoupled` : l'API des menus applique toujours le contrôle d'accès aux liens (#966, Redmine #71439).

### Changements de comportement
- `vactory_core` : les options passées au client HTTP de Drupal par l'appelant (timeout…) sont maintenant transmises (#896).
- `vactory_decoupled` : à chaque enregistrement d'un terme, le slug `term_2_slug` de chaque traduction est normalisé (minuscules ASCII, mots joints par `-`), y compris un slug saisi à la main (#884).
- `vactory_decoupled_webform` : upload limité à 150 caractères pour le nom de fichier (au lieu de 240), et `file.default_max_filesize` appliqué (#906).
- `vactory_google_places` : l'option « Vactory google places instance » des filtres de vues, ignorée jusque-là, est réellement appliquée (#913).
- `vactory_appointment` : champs conseiller masqués en back-office dès qu'un thème d'administration distinct du thème par défaut est configuré (seul `seven` était pris en compte) (#911).
- `vactory_webform_mailchimp_handler` : une panne Mailchimp ne bloque plus la soumission, et le timeout passe de 120 s à 10 s (#903).
- `vactory_core` : dans les réglages de CAPTCHA, le message d'erreur peut rester vide (message par défaut traduit) et n'est plus prérempli ; un message saisi est de nouveau pris en compte (#949).
- `vactory_quiz_certificat` : un certificat généré par html2pdf se télécharge connecté sur le site Drupal, et la méthode html2pdf exige `$settings['file_private_path']` ; le rapport d'état signale une erreur s'il manque (#953).
- `vactory_quiz_certificat` : hors connexion, le lien d'un certificat (celui du mail de notification) mène à la page de connexion de Drupal, puis au certificat une fois connecté, au lieu d'une page d'erreur. Rien ne change pour un utilisateur connecté, ni pour les autres fichiers privés (#963, Redmine #71440).
- `vactory_core` : le script externe AddToAny n'est chargé que sur les pages qui affichent des boutons de partage ou « follow » (bloc, champ ou champ de vue AddToAny), et plus sur toutes les pages (#959, Redmine #66851).
- `vactory_decoupled_router` : le routeur répond « introuvable » (page d'erreur, statut 404) pour une entité dont le type n'est pas dans la liste des types de page, nœuds seuls par défaut : termes de taxonomie, utilisateurs, entités d'administration (#891, Redmine #69443).
- `vactory_decoupled_router` : un chemin qui diffère de l'alias d'un nœud par la casse, les accents ou un slash final est redirigé de façon permanente vers l'alias, dans la langue de la requête ; le statut de cette redirection est la chaîne `"301"`, comme pour les redirections du module Redirect (#891, Redmine #69443).
- `vactory_decoupled_router` : seule l'adresse canonique d'une page est résolue (alias, ou chemin interne exact comme `/node/<id>`), pour tous les types de page. Les autres chemins de la même entité (modification, suppression, révisions, dernière version, traductions, ressource JSON:API) et les autres écritures du chemin interne (majuscules, zéro initial, slash final) répondent « introuvable » : page d'erreur, statut 404, sans redirection. Les chemins enregistrés dans les routes système ne sont pas concernés (#972, Redmine #71456).
- `vactory_decoupled_router` : un `/node/<id>` inconnu ou non publié, ou un chemin inconnu qui se termine par `/node/<chiffres>`, répond « introuvable » sans redirection vers l'alias de la page d'erreur ; il en va de même pour un `/node/<id>` refusé à un utilisateur connecté (statut 403). Le front affiche alors sa page d'erreur avec le bon statut, au lieu d'une page en statut 200 (#972, Redmine #71456).
- `vactory_decoupled_router` : une valeur de `path` qui commence par deux slashs (`//segment`) répond « introuvable » au lieu de la page d'accueil (#972, Redmine #71456).

### Ajouts
- `vactory_admin_hardening` : nouveau module optionnel qui bloque en web l'interface de migration et l'interface de config du core, même pour uid 1 ; installé d'office sur les nouveaux sites uniquement (#886).
- `vactory_security_review` : check « Migrate administration surface », qui signale l'absence de `vactory_admin_hardening` et les rôles non administrateurs ayant `import configuration`, `synchronize configuration` ou `administer migrations` (#886).
- `vactory_decoupled` : condition de visibilité des blocs selon le statut de la réponse (200, 403, 404), évaluée sur le statut renvoyé par le routeur découplé (#890).
- `vactory_decoupled` : rôles attribués à l'inscription JSON:API, réglables dans le back-office (onglet *Registration* des réglages découplés, permission « administer permissions » ; jamais administrateur, anonyme ni authentifié) (#885).
- `vactory_decoupled_espace_prive` : l'inscription par le webform de l'espace privé attribue les rôles de ce même réglage, avec le même filtre (jamais administrateur, anonyme ni authentifié) ; les rôles envoyés dans la soumission restent ignorés (#950, Redmine #71369).
- `vactory_decoupled_router` : liste des types d'entité servis comme pages par le routeur, réglable en back-office (`/admin/config/system/vactory_router/page-entity-types`), dans `settings.php` (`$settings['vactory_decoupled_router_page_entity_types']`) ou par `hook_vactory_decoupled_router_page_entity_types_alter()` (#891, Redmine #69443).

### Correctifs
- Shield et flood_control : un site protégé par Shield 1.9 ou plus répondait 500 à toute requête avec identifiants ; flood_control 3.0.1 ne dépend plus d'une fonction de son `.module`, pas encore chargé quand le middleware de Shield s'exécute (#988, Redmine #71534).
- `vactory_sondage` : un sondage est fermé à sa date de fermeture. La fermeture était mise en file sans l'identifiant du sondage et échouait à chaque passage de la file de `vactory_reminder` ; elle a lieu à l'heure quel que soit le fuseau du site (la file de `vactory_reminder` lit la date dans le fuseau du site : en avance ou en retard du décalage avec UTC). Une fermeture en file pour un sondage supprimé, ou dont la date a été vidée ou changée, est abandonnée au lieu d'échouer à chaque passage. Sans `vactory_reminder` (dépendance déclarée, absente seulement si le module a été installé sans ses dépendances), un sondage s'enregistre au lieu d'une erreur, et l'absence de fermeture est journalisée (#983, Redmine #71443).
- `vactory_quiz_certificat` : sur un site où File Entity est actif, le certificat se télécharge par son titulaire et par les comptes qui ont la permission `administer site configuration`, et reste refusé aux autres comptes quelles que soient leurs permissions sur les fichiers (#975, Redmine #71471).
- `vactory_dynamic_field_generator` : description de la commande `drush templates-generator` corrigée (elle reprenait celle d'un autre module) ; le README précise que la commande n'existe que si le module est activé et qu'aucun `drush.services.yml` n'est nécessaire (#968, Redmine #66249).
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
- `vactory_quiz` : dépendances `taxonomy`, `vactory_core` et `vactory_field` déclarées (champ Tags, vocabulaire `tags`) ; l'affichage « card » et la ressource JSON:API du type Quiz passent dans `config/optional` : ils ne bloquent plus l'installation du module sans le mode d'affichage `card` du profil ou sans `jsonapi_extras` et `vactory_decoupled`. Rien à faire sur les sites existants, aucune feature à réimporter (#965, Redmine #71388).
- `vactory_locator` : MarkerClusterer figé sur un commit jsdelivr, plus de dépendance à rawgit (#907).
- `vactory_core` : services de menu (simplify_menu, manipulateur de menu) injectés par méthode. Le site ne tombe plus si simplify_menu ou le core ajoute un argument à un constructeur (#914).
- `vactory_core` : le compteur de caractères (textfield_counter) des champs texte des formulaires d'entité fonctionne sur les champs multi-valeurs, sans `TypeError` dans la console ; chaque champ garde sa propre longueur maximale, au lieu de celle du dernier champ du formulaire (#964, Redmine #71387).
- `vactory_dynamic_field` : élément URL compatible linkit 7.0.13+ (#912).
- `vactory_amp` : plus d'erreur fatale au rendu d'un nœud exclu d'AMP sans liens d'en-tête (#919).
- `vactory_cloudinary` : `getExternalUrl()` renvoie une chaîne, propriété dynamique déclarée (PHP 8.2), dépendances déclarées (#920).
- `vactory_dynamic_field` : le compteur de caractères (textfield_counter) fonctionne de nouveau dans la fenêtre Dynamic Fields, y compris sur les champs d'un groupe ; plus de `TypeError` dans la console (#954, Redmine #71223).
- `vactory_quiz` : dépendances `views_autocomplete_filters` (autocomplete du filtre Titre de la vue) et `vactory_views` déclarées ; la vue livrée ne référence plus `vactory_google_places`, qu'elle n'utilisait pas (#956, Redmine #71362).
- `vactory_html2pdf` : le PDF est écrit à l'URI demandée et l'`uri` renvoyée est un URI de flux valide ; dossier créé par Drupal (permissions correctes), dossier temporaire de Drupal au lieu de `/tmp`, exception `VactoryHtml2PdfException` de nouveau chargeable (#953, Redmine #71157).
- `vactory_quiz_certificat` : le mail de notification contient de nouveau le lien du certificat. Le corps de mail par défaut utilisait le jeton `[quiz:certificat_url]`, que le module ne remplaçait pas : il est remplacé comme `[quiz:certificate_url]`, et le corps par défaut utilise le jeton déclaré (#963, Redmine #71440).
- `vactory_quiz_certificat` : la génération des certificats par le cron ne dépend plus de `vactory_reminder`, qui n'était pas déclaré. Sans ce module, une demande en échec interrompait le cron ; elle reste maintenant dans la file, est reprise à un cron suivant, et l'erreur est journalisée dans le canal `vactory_quiz_certificat` (#963).
- `vactory_dynamic_field`, `vactory_field` : dépendances déclarées, les modules s'installent avec leurs seules dépendances. `vactory_dynamic_field` déclare `block_content` (type de bloc `vactory_block_component`) ; les réglages de traduction de ce type de bloc passent dans `config/optional` : installés avec `content_translation`, que le module n'impose plus. `vactory_field` déclare `comment`, `datetime_range`, `menu_link_content`, `node`, `options` et `taxonomy` (stockages de champs livrés, vocabulaire `tags`) ; sur un site du profil où `media` est déjà actif, il reste refusé tant que le profil livre une copie de ses configs média (Redmine #71508). Rien à faire sur les sites existants : le profil installe tous ces modules, Drupal n'active pas une dépendance nouvellement déclarée et la config déjà en base ne change pas ; aucune feature à réimporter (#984, Redmine #71511).

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
- `drupal/flood_control` : `^2.3` → `^3.0.1` (#988, Redmine #71534).
- `drupal/stage_file_proxy` `^2.0` → `^3.1.7` ; `drupal/gin` `^3.0@RC` → `^4.1.4` ; `drupal/responsive_favicons` `1.x-dev@dev` → `^2.0.2 || ^3.0 || ^4.0` ; `drupal/tinypng` `^1.0@RC` → `^2.0.2` (#993, Redmine #71556).

### Interne
- Commande `scripts/stack/stack` : pile locale complète et isolée par instance nommée (arbre du profil, site Drupal et sa base, Mailpit, front Next et Redis en option), pour travailler à plusieurs ou avec des agents en parallèle ; documentation `docs/dev-stack.md` (Redmine #71475, #976).
- `RELEASING.md` : la liste « Une PR est terminée quand » demande de tester ce qu'un point d'accès accepte encore (accès, valeurs, entité inexistante) et de rejouer une mise à jour de données sur des données d'avant la PR (#979, Redmine #71160).
- `RELEASING.md` : une version candidate est publiée en pre-release GitHub (notes : section `[Non publié]` du commit taggé), ne se modifie pas après publication, et un problème trouvé donne une nouvelle `rcN` (#989, Redmine #71368).
- Tests d'intégration des dépendances à historique d'incidents et contrats entre le profil et ses dépendances (Redmine #71160 ; #912 à #920, #934).
- Tests de caractérisation, puis découpage des méthodes signalées par Sonar, sans changement de comportement (#935, #938, #939).
- CI Drone : un build par commit, sans build pour les branches sans PR ni pour les tags (#921) ; jeton GitHub pour composer (#940) ; exclusions Sonar appliquées et règle `php:S1781` ignorée (#929).
- CI Drone : suites `unit` et `kernel` lancées pendant l'installation du site, sur une base à part ; build plus court d'environ 7 minutes, mêmes tests (#970).
- CI Drone : le pipeline `sonar` analyse le commit de la PR sans le fusionner dans `3.x` ; une PR en conflit n'empêche plus l'analyse (#973).
- CI Drone : le pipeline `sonar` tourne sur un runner qui lui est réservé et n'attend plus derrière les builds `default` (#977).
- CI Drone : étape `setup` sur l'image `vactory-ci` en arm64 natif (plus de Rosetta sur la machine CI) ; build plus court de 25 à 35 %, mêmes tests (#982).
- Sonar : les outils de développement `scripts/stack` et `scripts/module-audit` sont exclus de l'analyse (#985).
- Sonar : `sonar.projectVersion` suit la version publiée ; le « nouveau code » de la porte qualité repart de chaque release au lieu du 2025-10-02 (Redmine #71220, #987).
- Dependabot limité aux mises à jour de sécurité (#930).
- Hook git `pre-commit` : phpcs ne vérifie plus les fichiers `.md` et `.txt`, comme la CI (#971).
- `vactory_image_sitemap` : phpcs propre, sans changement de comportement (#937).
- `vactory_image_sitemap` : variable inutilisée retirée (Sonar S1481, #945).
- CI Drone : l'analyse Sonar tourne dans son propre pipeline, sur une image arm64 native ; le scanner se figeait par moments sous émulation (Redmine #71371).
- `vactory_decoupled` : test du contrat avec block_class (classes dans le HTML du bloc et dans la sortie découplée) (#948).
- `vactory_decoupled` : test de l'endpoint de menus lu par le front (liens inaccessibles ou désactivés absents pour un anonyme) (#957).
- Décisions d'architecture consignées dans `docs/adr/` : fichiers temporaires, version figée du profil, Features, clé `url` des liens, block_class 3.x (#960). Les ADR couvrent le profil, pas la CI (#962).
- `vactory_quiz_history` : index sur la colonne `uid`, utilisée par le contrôle d'accès au téléchargement des certificats ; `vactory_html2pdf` : test du cas « chemin sans schéma » de `html2Pdf()` (#963).
- `vactory_decoupled_router` : tests de caractérisation de l'endpoint de routage lu par le front (casse et variantes d'un alias, langues, redirections, types d'entité autres que les nœuds, paramètre `path`, en-têtes de cache), sans changement de comportement (Redmine #69443, #969).
- Outil d'audit des modules `scripts/module-audit/module-audit` : installation seule, désinstallation, restes et réinstallation de chaque module, dépendances non déclarées ou inutiles prouvées par une sonde Kernel, rapport `RESULT.md` ; à lancer avant chaque release ; documentation `docs/module-audit.md` ; nécessite la commande `stack` (#976) (Redmine #71479, #980).

## [3.2.3] - 2026-10-07
Voir la [release 3.2.3](https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3).

[Non publié]: https://github.com/voidagency/vactory_starter_kit/compare/3.2.3...3.x
[3.2.3]: https://github.com/voidagency/vactory_starter_kit/releases/tag/3.2.3
