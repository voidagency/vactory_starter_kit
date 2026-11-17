# Bugs trouvés pendant l'audit

Vérification indépendante (Fable, lecture seule) : [_verify/fable-bugs.md](_verify/fable-bugs.md), [_verify/fable-constraints-ci.md](_verify/fable-constraints-ci.md). Affirmations soumises : [_verify/claims.md](_verify/claims.md).

## Tickets créés (projet vactory-4-next)

| Ticket | ID | Bug | Gravité (Fable) |
|---|---|---|---|
| #71100 | B03 | `vactory_sso.module:38` : classe `oauth_login_oauth2Controller` inexistante → erreur fatale sur `/mo_login` | Bloquant (module optionnel) |
| #71102 | B05 | `/api/search-ai` : service `openai_embeddings.pinecone_client` absent d'openai beta4 ; `search_api_pinecone` alpha2 en dépend aussi | Bloquant (module optionnel) |
| #71135 | B06 | `vactory_html2pdf` : `array_merge` sur clé de config absente → `TypeError`, PDF (certificats quiz) en échec | Majeur |
| #71136 | B07 | Handler Mailchimp : `ConnectException` non attrapée → soumission webform en erreur | Majeur |
| #71137 | B10 | `vactory_toolbox` : dépendance `workbench_moderation` dans `config/install` → activation impossible | Majeur |
| #71138 | B15 | `vactory_decoupled_flag` ne déclare pas `flag` ; `vactory_decoupled_search_ai` ne déclare pas `search_api_pinecone` | Majeur / Mineur |
| #71101 | B04 | Patch jsonapi_extras sous la clé `drupal/jsonapi_extra` → jamais appliqué, hook mort | Mineur (impact surestimé au départ : seuls les cache tags par bloc sont perdus) |
| #71099 | B01, B02 | Clé `acccess` (sans effet, `status = 0` suffit) ; condition inversée `update_9500` (seuls les sites mis à jour sont concernés) | Cosmétique / Mineur |
| #71142 | F01 | **Sécurité (High)** — décorateur `http_client_factory` de `vactory_core` : `verify => false` par défaut sur tout le client HTTP du site (dont JWKS Keycloak), options des appelants jetées | Majeur / sécurité |
| #71143 | F02 | `/image-sitemap.xml` : `Laminas\Diactoros\XmlResponse`, paquet plus installé depuis D10 → 500 | Majeur |
| #71144 | F04 | `vactory_form_autosave` : dépendance de config `vactory_form_auotosave` → installation impossible | Majeur |
| #71145 | F08 | Locator / cartes / WhatsApp : CDN rawgit mort, clé Google en clair, Maps sans clé, Places legacy, Graph API v14 | Moyen |
| #71149 | G04 | **Sécurité (High)** — `vactory_decoupled_webform` accorde `access any webform configuration` aux anonymes/authentifiés : config webform (handlers) lisible hors HTML | Majeur / sécurité |
| #71146 | G01 | `entityQuery` sans `accessCheck` : commande d'envoi newsletters Mailchimp et lien flag `liked_content` en erreur | Majeur |
| #71147 | G02 | `vactory_user_migrate` : plugin `hash_password` typé `PhpassHashedPassword` → `TypeError` depuis D10.1, import utilisateurs cassé | Majeur |
| #71148 | G03 | `WebformFileUploadResource` : `sprintf` sans argument (500), 3 API core supprimées en D11, extensions non contrôlées par défaut | Majeur |
| #71156 | H01 | **CI (High)** — résolution composer du profil en échec (core 10.4.7 épinglé par le projet racine vs editor_advanced_link 2.3.4) ignorée : tests sur le lock du projet racine, build vert. Constat sur le log du build #705 | Majeur |
| #71139 | B08, B11, B14 | Log OAuth2 **succès** inactif (l'échec fonctionne) ; branche morte `seven` dans appointment ; identifiants Shield par défaut | Mineur |

Constats non-bug de la recon : tour 1 (F03, F05-F07, F09-F17) [_recon/round1.md](_recon/round1.md) ; tour 2 (G05-G13) [_recon/round2.md](_recon/round2.md).

## Dépendent de l'application des patches (pas de ticket tant que non vérifié)

| ID | Constat |
|---|---|
| B12 | Hook `post_handler_submission` de `vactory_webform_storage` n'existe que via le patch webform. Sans patch : option « ne pas stocker ce champ » silencieusement inopérante. |
| B13 | JS `views_autocomplete_filters` 1.7.0 utilise `.once()` (retiré en D10), chargé par 3 vues livrées. Patch déclaré antérieur à la 1.7 : application à vérifier. |

→ Vérifier comment les projets appliquent les patches du profil ([ci.md](ci.md)).

## Fragilités (pas de bug avec les versions lockées)

| ID | Constat |
|---|---|
| B09 | Rafraîchissement JWKS Keycloak déclenché sur le texte `"kid"` d'une exception php-jwt. |
| B16 | `simplify_menu.menu_items` (et `menu.default_tree_manipulators` du cœur) surchargés avec `addArgument()` positionnels. |

## Hors profil — sécurité

- Jeton Bitbucket en clair, suivi par git, dans `~/dev/vactory-test-ci/Makefile:7`, recopié en `ENV` dans l'image `vactory/vactory-ci`. À révoquer (signalé à Hamza, pas de ticket).
