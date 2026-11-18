# Bugs trouvés pendant l'audit

Chaque bug a été vérifié par une revue indépendante (lecture du code, preuves `fichier:ligne`), puis corrigé dans une PR avec un test. État au 2026-11-19 dans la colonne « Correctif ».

## Tickets créés (projet vactory-4-next)

| Ticket | ID | Bug | Gravité | Correctif |
|---|---|---|---|---|
| #71100 | B03 | `vactory_sso.module:38` : classe `oauth_login_oauth2Controller` inexistante → erreur fatale sur `/mo_login` | Bloquant (module optionnel) | PR #908 (ouverte) |
| #71102 | B05 | `/api/search-ai` : service `openai_embeddings.pinecone_client` absent d'openai beta4 ; `search_api_pinecone` alpha2 en dépend aussi | Bloquant (module optionnel) | en attente de décision (supprimer ou migrer search AI) |
| #71135 | B06 | `vactory_html2pdf` : `array_merge` sur clé de config absente → `TypeError`, PDF (certificats quiz) en échec | Majeur | PR #902 ✅ mergée |
| #71136 | B07 | Handler Mailchimp : `ConnectException` non attrapée → soumission webform en erreur | Majeur | PR #903 (ouverte) |
| #71137 | B10 | `vactory_toolbox` : dépendance `workbench_moderation` dans `config/install` → activation impossible | Majeur | PR #904 ✅ mergée |
| #71138 | B15 | `vactory_decoupled_flag` ne déclare pas `flag` ; `vactory_decoupled_search_ai` ne déclare pas `search_api_pinecone` | Majeur / Mineur | PR #905 ✅ mergée |
| #71101 | B04 | Patch jsonapi_extras sous la clé `drupal/jsonapi_extra` → jamais appliqué, hook mort | Mineur (impact surestimé au départ : seuls les cache tags par bloc sont perdus) | PR #909 ✅ mergée |
| #71099 | B01, B02 | Clé `acccess` (sans effet, `status = 0` suffit) ; condition inversée `update_9500` (seuls les sites mis à jour sont concernés) | Cosmétique / Mineur | PR #910 ✅ mergée |
| #71142 | F01 | **Sécurité (High)** — décorateur `http_client_factory` de `vactory_core` : `verify => false` par défaut sur tout le client HTTP du site (dont JWKS Keycloak), options des appelants jetées | Majeur / sécurité | PR #896 ✅ mergée |
| #71143 | F02 | `/image-sitemap.xml` : `Laminas\Diactoros\XmlResponse`, paquet plus installé depuis D10 → 500 | Majeur | PR #899 ✅ mergée |
| #71144 | F04 | `vactory_form_autosave` : dépendance de config `vactory_form_auotosave` → installation impossible | Majeur | PR #898 ✅ mergée |
| #71145 | F08 | Locator / cartes / WhatsApp : CDN rawgit mort, clé Google en clair, Maps sans clé, Places legacy, Graph API v14 | Moyen | point 1 (rawgit) : PR #907 ✅ mergée ; points 2-5 en attente de décision |
| #71149 | G04 | **Sécurité (High)** — `vactory_decoupled_webform` accorde `access any webform configuration` aux anonymes/authentifiés : config webform (handlers) lisible hors HTML | Majeur / sécurité | PR #897 ✅ mergée |
| #71146 | G01 | `entityQuery` sans `accessCheck` : commande d'envoi newsletters Mailchimp et lien flag `liked_content` en erreur | Majeur | PR #901 ✅ mergée |
| #71147 | G02 | `vactory_user_migrate` : plugin `hash_password` typé `PhpassHashedPassword` → `TypeError` depuis D10.1, import utilisateurs cassé | Majeur | PR #900 ✅ mergée |
| #71148 | G03 | `WebformFileUploadResource` : `sprintf` sans argument (500), 3 API core supprimées en D11, extensions non contrôlées par défaut | Majeur | PR #906 ✅ mergée (migration D11 : #71158) |
| #71156 | H01 | **CI (High)** — résolution composer du profil en échec (core 10.4.7 épinglé par le projet racine vs editor_advanced_link 2.3.4) ignorée : tests sur le lock du projet racine, build vert. Constat sur le log du build #705 | Majeur | ✅ corrigé : droneci (branche develop, arrêt si composer échoue, `SONAR_TIMEOUT`, DSN utf8mb4) + #921 |
| #71139 | B08, B11, B14 | Log OAuth2 **succès** inactif (l'échec fonctionne) ; branche morte `seven` dans appointment ; identifiants Shield par défaut | Mineur | PR #911 (ouverte) |

## Trouvés pendant les correctifs et les tests (après l'audit)

| Ticket | Bug | État |
|---|---|---|
| #71157 | `vactory_html2pdf` écrit dans `public/` au lieu de `public://`, `mkdir` en 755 | à traiter |
| #71158 | Migration D11 de l'upload de fichiers webform (suite de #71148) | à traiter |
| #71161 | `vactory_rate` : formulaire d'admin ouvert aux anonymes, vote en GET sans CSRF | à traiter |
| #71162 | **High** — `/api/*/private-files` en 500 (`TypeError` dans `create()`) ; une correction naïve exposerait tous les fichiers privés | décision attendue |
| #71163 | `vactory_business` : widget `vcc` inexistant dans le formulaire | décision attendue |
| #71167 | Patch captcha (gist tronqué) KO sur captcha 2.0.12 : `composer update` en échec | captcha fixé à 2.0.11 (PR #922 ✅), patch à refaire |
| #71168 | simple_oauth 5.2.6 : `/oauth/login-token` en 500 (`ArgumentCountError`) | PR #928 ✅ mergée |
| #71220 | Quality gate Sonar en échec sur 3.x (exclusions inopérantes, règle S1781) | config corrigée (PR #929 ✅) ; 18 problèmes restants dans `vactory_webform_typesafe` |
| #71222 | 11 versions figées dans `composer.json` (dont block_class, release de sécurité 4.0.1) | à traiter |
| #71223 | Dynamic Fields : compteur de caractères inactif (`textfield_counter`, `key` encodé en objet JSON) | à traiter |
| #71224 | Dynamic Fields : images insérées dans CKEditor laissées en fichier temporaire (aucun `file_usage`) | à traiter |

## Dépendent de l'application des patches (pas de ticket tant que non vérifié)

| ID | Constat |
|---|---|
| B12 | Hook `post_handler_submission` de `vactory_webform_storage` n'existe que via le patch webform. Sans patch : option « ne pas stocker ce champ » silencieusement inopérante. |
| B13 | JS `views_autocomplete_filters` 1.7.0 utilise `.once()` (retiré en D10), chargé par 3 vues livrées. Patch déclaré antérieur à la 1.7 : application à vérifier. → Testé dans la PR #913 (1.7 patchée et 2.0.4 OK). |

→ La CI applique désormais les patches du profil et s'arrête si l'un échoue (constaté avec captcha, #71167). Côté projets, vérifier que le projet racine autorise les patches des dépendances ([ci.md](ci.md)).

## Fragilités (pas de bug avec les versions lockées)

| ID | Constat |
|---|---|
| B09 | Rafraîchissement JWKS Keycloak déclenché sur le texte `"kid"` d'une exception php-jwt. |
| B16 | `simplify_menu.menu_items` (et `menu.default_tree_manipulators` du cœur) surchargés avec `addArgument()` positionnels. → Corrigé par injection par setters (PR #914). |

## Hors profil — sécurité

- Jeton Bitbucket en clair, suivi par git, dans `~/dev/vactory-test-ci/Makefile:7`, recopié en `ENV` dans l'image `vactory/vactory-ci`. À révoquer (signalé à Hamza, pas de ticket).
