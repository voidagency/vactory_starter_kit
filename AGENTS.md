# Règles pour contribuer au profil

Ce fichier s'adresse à toute personne ou tout agent qui modifie le profil `vactory_starter_kit`. Il complète [RELEASING.md](RELEASING.md) (changelog, numéro de version, release) et le [modèle de PR](.github/pull_request_template.md), sans les répéter.

## Le contexte qui change tout
- **Le dépôt est public.** Rien de sensible dans le code, les commits, les PR ni le changelog : pas de secret, pas de nom de client, pas d'infrastructure, pas de détail d'exploitation pour un correctif de sécurité. Le détail va dans le ticket Redmine.
- **Une cinquantaine de projets clients consomment le profil**, chacun en version figée, avec leur config par Features, leurs thèmes, leurs fronts Next et leur code. Un changement du profil leur arrive sans qu'ils l'aient lu. Le contrat est la **surface publique** : ce qu'un projet peut appeler, surcharger ou lire (voir « API publique » dans RELEASING.md, et ci-dessous).

## Surface publique
Routes et chemins, contrôles d'accès, fournisseurs d'authentification, permissions, services (identifiants), hooks et événements, plugins (identifiants), commandes Drush, config installée et son schéma, update hooks, templates Twig et leurs variables, réponses lues par un front (JSON:API, routeur découplé, menus, endpoints de `vactory_decoupled`), contraintes composer et patches.

Changer le **comportement** d'un de ces points (même signature, résultat différent) est un changement de comportement, même si aucune signature ne bouge.

## Chaque PR
1. **Un ticket Redmine**, cité dans le titre : `type: #ticket - sujet`.
2. **Des tests, vus en échec sans le correctif.** Donner la sortie dans la PR. Choisir le niveau le plus bas qui prouve la chose : Unit, Kernel, puis ExistingSite.
3. **Tester le chemin réel.** Passer par ce qui tourne en production (le `hook_cron`, la route, la commande Drush) plutôt que de rejouer la logique dans le test. Exemple : le test de la fermeture des sondages rejouait la file lui-même, alors que le cron qui devait la traiter échouait (Redmine #71773).
4. **Comportement existant** :
   - inchangé : le dire ;
   - changé : d'abord un **test de caractérisation**, qui fige le comportement actuel, puis la PR qui le change. Le changement d'attendu du test est visible à la relecture ;
   - changé pour un point public : **déprécier avant de retirer**. L'ancien comportement reste et signale son usage (avertissement de dépréciation, journal), la version de retrait est annoncée. Lignes dans « Changements de comportement » ou « Dépréciations » du changelog, et dans « Mise à jour des projets » si un projet doit agir.
5. **Impact sur le parc**, si un comportement public change : chercher les usages dans les dépôts des projets (branche de prod : code custom, templates surchargés, fronts) et dire dans la PR qui est touché.
6. **Modules sensibles** (authentification, accès, sécurité) : tests de mutation quand l'outil sera en place (Redmine #71766), sinon dire pourquoi les tests suffisent.
7. **Hors périmètre** : un défaut vu en passant est corrigé, ou suivi dans un ticket cité. Jamais « à suivre » sans ticket.

## Garde-fous (tests de `vactory_core`)
Ils échouent en CI ; ne pas les contourner, corriger la cause :
- `NoSecretsInProfileTest` : pas de secret dans le profil ;
- `ComposerPatchesTest` : les patches du profil s'appliquent ;
- `ShippedConfigSchemaTest` (PR #1019) : la config livrée suit son schéma ; les clés sans schéma connues sont listées dans `modules/vactory_core/tests/fixtures/`, chacune avec son ticket ;
- `DrushCommandDeclarationTest` (PR #1024) : commandes Drush déclarées dans `src/Drush/Commands`, pas par le tag `drush.command` d'un `*.services.yml`.

Un nouveau défaut qui peut revenir sur tout le profil (une classe de bug, pas un cas) mérite son garde-fou ici.

## Lancer les tests en local
- Pile isolée par branche : [docs/dev-stack.md](docs/dev-stack.md) (`stack up`, `stack test <instance> <chemin>`, `stack phpcs`).
- Style : coder (Drupal, DrupalPractice), vérifié par le hook `pre-commit` et en CI. Porte qualité Sonar : complexité cognitive d'une fonction ≤ 15.

## Avant de revenir sur un choix
Lire l'ADR correspondant dans [docs/adr/](docs/adr/README.md). Une décision difficile à défaire, surprenante et issue d'un vrai arbitrage s'écrit en ADR dans la PR qui l'applique.
