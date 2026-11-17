# Dépendances Composer → usage → tests

Audit du 2026-11-17, branche `3.x` (HEAD `808840c7`). Ticket Redmine : #71097.

**Problème** : chaque montée de version (manuelle ou PR Dependabot quotidienne) casse des choses découvertes tard, parce que presque rien ne teste **notre usage** des dépendances.

**Méthode** : 4 recon en lecture seule (grep du code, `.info.yml`, `services.yml`, config, Twig ; `composer.lock` ; `vendor/` local). Rien n'a été exécuté. Méthode et gabarit des fiches : [_BRIEF.md](_BRIEF.md).

## Contenu

| Fichier | Rôle |
|---|---|
| [_matrix.md](_matrix.md) | Les 175 paquets : statut, usages, couverture, risque, priorité, lien vers la fiche |
| [libs/](libs/) | Librairies PHP + paquets `voidagency/*` (12 fiches) |
| [drupal/api-auth/](drupal/api-auth/) | JSON:API, OAuth, auth, sécurité (31 fiches) |
| [drupal/content-media-search-seo/](drupal/content-media-search-seo/) | Paragraphs, médias, search_api, SEO (43 fiches) |
| [drupal/forms-admin-ui/](drupal/forms-admin-ui/) | Webform, mail, migrate, views, admin, Twig/UI (58 fiches) |
| [unused.md](unused.md) | Paquets déclarés sans usage dans le profil |
| [bugs.md](bugs.md) | Bugs trouvés pendant l'audit (18 tickets Redmine) et constats à vérifier |
| [_verify/](_verify/) | Affirmations soumises à vérification + rapports Fable |
| [_recon/](_recon/) | Recon mi-aveugle, tours 1 et 2 |
| [NEXT.md](NEXT.md) | Passes suivantes |
| [ci.md](ci.md) | CI Drone, patches, état du `vendor/` local |
| `_partials/` | Matrices brutes par lot |

## Chiffres

| | Paquets |
|---|---|
| Utilisés dans le code | 80 |
| Indirects (config / dépendance seulement) | 64 |
| Non utilisés | 31 |
| **P1** | **47** (27 code critique non testé + 20 promus par l'historique : downgrade, épinglage après incident, fork, patch correctif) |
| P2 | 40 (dont 8 paquets indirects à incidents, ramenés de P1) |
| P3 | 54 |
| Avec incidents historiques | 67 (colonne « Incidents historiques » de `_matrix.md`, section « Historique » des fiches) |

Tests existants : 15 fichiers (13 ExistingSite DTT dans `vactory_decoupled/**`, 2 Kernel). Ils couvrent partiellement 11 des 27 P1, aucun complètement.

## Les 27 P1, par chantier de test

| Chantier | Paquets | Pourquoi |
|---|---|---|
| **Auth headless** | simple_oauth, firebase/php-jwt, social_auth, oauth_login_oauth2, stevenmaguire/oauth2-keycloak, email_registration, user_registrationpassword, password_policy | On remplace le fournisseur d'auth de simple_oauth, on étend `/oauth/token`, on ajoute 2 grants, on vérifie des JWT Keycloak, on appelle des fonctions privées de user_registrationpassword. Rien de testé. |
| **JSON:API** | jsonapi_extras, jsonapi_cross_bundles | 13 classes étendues, 85 configs de ressources, accès par rôle injecté dans les routes. |
| **Contenu / paragraphs** | paragraphs, entity_reference_revisions, media_library_form_element, metatag, redirect, search_api, flag | Classe `paragraph` remplacée, `target_revision_id` réécrit à la main, API internes de metatag, `Index::query()` direct, `findMatchingRedirect()` avant notre routeur. |
| **Formulaires / anti-spam** | webform, captcha, recaptcha, mailchimp/marketing, symfony_mailer | 6 handlers webform, SQL direct sur `captcha_sessions`, classe interne `Drupal8Post`, `hook_mailer_build`. |
| **Import / export** | migrate_plus, migrate_tools, migrate_source_csv, migrate_file, phpoffice/phpspreadsheet | Plugins source/process custom, extension de `FileImport`, import/export xlsx des pages. |

## Ce que dit l'historique (passe A)

- Les régressions déjà vécues se concentrent sur les **montées groupées non testées** : `f337dbc2` (#62859) a monté d'un coup linkit 7.0.16, consumers 1.24.0, ctools ^4.1.1 ; linkit a dû être rétrogradé (#69237).
- **linkit** : 7.0.13 a cassé l'autocomplete des Dynamic Fields (#64112, projets RMA) → épinglé 7.0.12 ; remonté 7.0.16 puis rétrogradé sans cause documentée.
- **jquery_colorpicker** : 3 forks (b-khouy → voidagency → fork d'issue D11), 5 patches, suppression tentée (#62381) puis maintenu.
- **rate** : montée 1.x → 3.x annulée deux fois avant épinglage (#48597). **file_entity** : un patch correctif a lui-même causé un bug urgent (#53706).
- **jsonapi_search_api** : patch cache facettes ajouté/retiré 5 fois, puis version épinglée.
- 28 paquets promus en P1 par l'historique. Les 8 seulement indirects (shortcode, yaml_editor ×2, domain, nocurrent_pass, ctools, block_list_override, merge_translations) sont ramenés en P2 (arbitrage du 2026-11-17).

## Vérification indépendante (passe B)

24 affirmations soumises à Fable sans nos conclusions ([_verify/](_verify/)) : aucune infirmée totalement, plusieurs nuancées (impacts de #71101 et #71099 surestimés, jsonapi_user_resources finalement utilisé, doute `SIMPLETEST_DB` levé). Bugs et tickets : [bugs.md](bugs.md).

## Recon mi-aveugle (passe C, 2 tours)

Deux chercheurs indépendants, chacun avec la liste de ce qui était déjà connu : 17 findings au tour 1 ([_recon/round1.md](_recon/round1.md)), 13 au tour 2 ([_recon/round2.md](_recon/round2.md)). 8 bugs confirmés → tickets (dont 2 sécurité en High : #71142 TLS désactivé par défaut, #71149 config webform lisible par les anonymes).

Deux constats changent la stratégie de test :

1. **Le `composer.lock` du profil n'est pas utilisé** (F03). Les projets installent le profil par `composer require`, donc avec les dernières versions permises par `composer.json` : une PR Dependabot qui ne touche que le lock ne change rien, et `3.x` peut casser sans commit (scénario linkit 7.0.13). La CI, elle, installe le profil dans un projet racine `vactory8` qui épingle `drupal/core 10.4.7` et `linkit 7.0.12` : la résolution échoue (editor_advanced_link 2.3.4 exige core ^10.5), l'échec est ignoré, et **les tests tournent sur le lock du projet racine**, pas sur les contraintes du profil (build #705, #71156). → Faire échouer le build sur erreur composer, puis tester les **contraintes** (job planifié « latest permis » et job `--prefer-lowest`).
2. **Patches** (G12) : le profil ne requiert pas `cweagans/composer-patches`. Ils ne s'appliquent que si le projet racine l'installe avec `patches-file` ou `enable-patching`. C'est le cas du projet racine de la CI (« Gathering patches for dependencies », build #705) ; à vérifier projet par projet. B12/B13 et #71101 en dépendent.

Autres constats utiles pour les tests : 22 modules livrent de la config sans schéma (F13) → les tests Kernel stricts échoueront à l'install, à traiter d'abord ; ~80 dépendances de config non déclarées (F12) ; API core supprimées en D11 encore utilisées (G03, G07) → ajouter `phpstan-drupal` / `drupal-check` en CI.

## Constats transverses

1. **Contraintes trop basses pour l'API utilisée** : `phpoffice/phpspreadsheet ^1.20` (il faut ≥ 1.23), `firebase/php-jwt ^6.0` (il faut ≥ 6.2). Une résolution sur une version basse casse sans que composer ne bronche.
2. **Contraintes qui bloquent Drupal 11** : `symfony/finder ~4|~5|~6`, `commerceguys/addressing ~1.0`.
3. **Patches** : un patch jamais appliqué (clé `drupal/jsonapi_extra`, #71101) ; plusieurs patches hébergés sur des gists personnels ; les patches ne sont pas appliqués dans le `vendor/` local du profil (voir [ci.md](ci.md)).
4. **Code mort ou cassé par des MAJ passées** : SSO (#71100), `/api/search-ai` (#71102). La preuve que des régressions sont déjà en prod sans être vues.
5. **Surcharges fragiles** : services contrib surchargés avec des `addArgument()` positionnels (`simplify_menu.menu_items`), méthodes `protected` ré-exposées (social_auth `UserManager`), classes internes instanciées (`Drupal8Post`). Ce sont les premières choses qui cassent à une MAJ mineure.
6. **Twig** : filtre `image_style` dans 145 templates, `vactory_render()` (107 appels) délègue à `TwigTweakExtension`, `attach_library('classy/node')` dans 16 templates. Une casse Twig est silencieuse : un test qui compile tous les templates de widgets est rentable.
7. **Dépendances non déclarées** dans les `.info.yml` (`vactory_decoupled_flag` → flag, `vactory_decoupled_search_ai` → search_api_pinecone, scheduler dans plusieurs modules contenu).
8. **Sécurité** : identifiants Basic Auth en clair dans `config/install/shield.settings.yml`.

## Suite proposée

1. **Socle** : vérifier que Drone tourne sur chaque PR Dependabot et bloque la fusion ([ci.md](ci.md)).
2. **Tests P1** par chantier (tableau ci-dessus), un ticket par chantier, sous-tâches de #71097. Les fiches donnent pour chaque paquet le scénario, le type (Unit / Kernel / ExistingSite) et le chemin du test.
3. **Hygiène composer** : relever les contraintes (point 1), corriger la clé de patch, rapatrier les patches des gists dans `patches/`, traiter [unused.md](unused.md).
4. Un test « smoke » ExistingSite par module activé par le profil (route principale → 200, pas d'erreur PHP en log) attrape à lui seul une bonne partie des P2/P3.
