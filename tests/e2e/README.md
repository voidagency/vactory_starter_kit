# Parcours navigateur (Playwright)

Parcours de bout en bout d'un contributeur, dans un vrai navigateur, sur un site
du profil. Rejoués à chaque release du profil et sur les PR qui touchent
l'administration (thème gin, paragraphs, Dynamic Field, médiathèque, linkit).
Suivi : Redmine #71385 (parcours), #71564 (stratégie de tests).

Outillage de développement : exclu de l'archive installée par Composer
(`.gitattributes`).

## Lancer

Contre une instance `stack` (`docs/dev-stack.md`) :

```sh
scripts/stack/stack up e2e            # si l'instance n'existe pas
tests/e2e/run.sh e2e                  # toutes les specs
tests/e2e/run.sh e2e --headed         # options passées à « playwright test »
```

`run.sh` :

1. lit l'adresse du site (`stack url`) ;
2. prépare le site : active `vactory_dynamic_field_default` (le template utilisé
   par le parcours), `E2E_SKIP_PREPARE=1` pour ne pas le faire ;
3. installe les dépendances (`npm ci`) et Chromium si besoin ;
4. demande un lien de connexion à usage unique (`stack uli`, compte
   `stack_editor`) et le passe à Playwright par variable d'environnement : il
   n'est ni affiché ni écrit. La connexion se fait avant les tests, sans trace
   ni vidéo (`global-setup.ts`) ;
5. donne au test une commande drush de l'instance (`E2E_DRUSH`) pour les
   vérifications en base.

Résultats : `test-results/` (captures nommées des étapes, vidéo, trace),
`playwright-report/` (`npx playwright show-report`). `E2E_OUTPUT_DIR` et
`E2E_REPORT_DIR` changent ces dossiers. Les traces contiennent le cookie de
session de l'instance : ne pas les publier ailleurs.

Hors `stack`, sur un autre site local : `E2E_BASE_URL`, `E2E_LOGIN_URL`
(`drush uli`) et, optionnel, `E2E_DRUSH` (ex. `ddev drush`), puis
`npx playwright test`.

## Parcours

`specs/page-dynamic-field.spec.ts` : créer puis modifier une page (`vactory_page`)
avec un paragraphe « Template » (`vactory_component`) rempli par la popin Dynamic
Field, template « DWidget Links » (`vactory_default:16` : image, texte
alternatif, description, lien).

1. Contenu > Add content > Page ; titre, description de page, titre du paragraphe.
2. Popin : « Choose template », onglet Content, « DWidget Links », Next.
3. Image : « Add media », envoi de `fixtures/e2e-image.png` dans la
   médiathèque, texte alternatif, Save, insertion.
4. Description, titre du lien, lien choisi dans l'autocomplete linkit
   (« Homepage » → `/node/<nid>`), « Save template ».
5. Enregistrement de la page publiée : message « has been created ».
6. Vérifications : base (nœud publié, données du Dynamic Field), page Drupal
   rendue (texte, image, lien vers la page choisie), JSON:API lu comme le front
   (`/api/router` puis la ressource du nœud avec ses paragraphes, en anonyme :
   texte, URL de l'image servie, lien résolu vers le nœud choisi).
7. Modification : rouvrir la popin (« Edit template »), valeurs reprises,
   nouveau texte, enregistrement : message « has been updated », mêmes
   vérifications, l'ancien texte a disparu.

Erreurs JavaScript : toute erreur non attrapée d'une page fait échouer le test,
sauf celles de `KNOWN_JS_ERRORS` (spec), chacune liée à son ticket Redmine,
limitée aux pages où elle est connue et signalée dans la sortie et les
annotations du rapport. Les erreurs de console sont jointes au rapport.

## Ce que le parcours ne couvre pas

- Le rendu par le front Next (`vactory_next`) : il vérifie la sortie JSON:API
  que le front lit, pas la page du front.
- Les autres langues (fr, ar), la traduction, les autres templates et types de
  champs du Dynamic Field (vidéo, fichier, vues, champs multiples à plusieurs
  éléments), le glisser-déposer des éléments.
- Les autres thèmes d'administration que celui de l'instance (gin ou claro), et
  les autres navigateurs que Chromium.
