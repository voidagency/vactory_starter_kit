# Pile locale par instance : `scripts/stack/stack`

Une commande monte une pile complète et isolée, sous un nom : un arbre de travail du profil sur une branche, un site Drupal avec sa base et son Mailpit, en option le front Next et son Redis. Plusieurs personnes ou agents travaillent ainsi en même temps, chacun sur son instance, sans toucher au site local habituel.

Sous le capot : ddev (Docker). La commande le masque ; `ddev` reste utilisable directement sur le projet `vsk-<nom>`.

## Prérequis

- ddev (1.24 ou plus), Docker (OrbStack ou Docker Desktop), git, curl, python3, openssl.
- Un projet Drupal installé par composer qui utilise le profil (`vendor/` présent), avec sa config ddev (`.ddev/config.yaml`) : il sert de modèle, il n'est jamais modifié.
- Pour `--front` : le dépôt du front Next (yarn workspaces, application `apps/starter` par défaut). Ses `node_modules` locaux ne servent pas : la base de référence installe ceux de Linux.
- Mesures (Mac M1 Pro, OrbStack, VM Docker de 9 Go) : sans front, 150 à 350 Mo de Docker par instance ; le front Next monte à 2,3–2,7 Go pendant ses compilations. Disque réel : environ 1,2 Go par instance avec front (copies APFS ; `du` en affiche 1,6 Go). Compter deux ou trois instances avec front à la fois sur une VM Docker de 9 Go, une dizaine sans front.

## Première fois

```sh
alias stack="$PWD/scripts/stack/stack"   # depuis le dépôt du profil, ou ajouter scripts/stack au PATH
stack init        # demande les chemins du projet Drupal et du front ; écrit ~/.config/vactory/stack.env
stack doctor      # vérifie les prérequis
stack baseline    # base de référence (une fois, puis à chaque changement important de 3.x)
```

`~/.config/vactory/stack.env` est hors dépôt : il contient les emplacements locaux (`VACTORY_DRUPAL_SRC`, `VACTORY_FRONT_SRC`, `VACTORY_STACKS_DIR`, par défaut `~/dev/.vactory-stacks`). Une variable d'environnement du même nom l'emporte sur le fichier.

`stack baseline` installe le profil de `origin/3.x` dans un site jetable (`drush site-install vactory_starter_kit`, langues ar et fr ajoutées, comme la CI), en garde le volume de base de données (copié par chaque instance en 2 à 3 s) et un dump, puis installe une fois les `node_modules` Linux du front dans un volume Docker partagé. Durée mesurée : 4 min 10 s pour la base, 2 min 20 s pour le front. Options :

- `--from-dump <fichier.sql.gz>` : part d'un dump au lieu d'une installation (puis `drush updb`). Le dump doit venir d'un site propre, pas d'un site en cours de test.
- `--no-front` : base seulement. `--front-only` : refait seulement le front (après un `yarn.lock` modifié).

Le fichier `_baseline/manifest.json` dit d'où vient la base (date, mode, commit du profil, commit du front).

Durées mesurées de `stack up` : environ 1 min 30 jusqu'au site Drupal prêt, 1 min 50 avec le front (première page compilée) ; relancé sur une instance existante : 20 s à 1 min 10.

## Tous les jours

```sh
stack up a                         # branche stack/a créée depuis origin/3.x
stack up b --branch feat/123-x     # branche existante (locale ou sur origin), sinon créée depuis origin/3.x
stack up c --ref 1a2b3c4           # commit précis, arbre détaché
stack up d --profile ~/dev/mon-arbre   # arbre du profil existant (jamais supprimé par stack)
stack up a --front                 # avec le front Next et Redis
stack up a --no-oauth              # sans clés, clients OAuth ni comptes de test

stack ls                           # instances, état, branche, adresses
stack info a [--json]              # détail ; --json : le contenu de stack.json
stack url a [front|mail]
stack drush a status               # lignes non coupées (COLUMNS=1000 ; STACK_DRUSH_COLUMNS pour changer)
stack exec a ls web/sites
stack uli a [utilisateur]          # lien de connexion (stack_editor par défaut)
stack test a modules/vactory_decoupled/tests/src/Kernel/MenusEndpointTest.php
stack test a --testsuite unit
stack phpcs a modules/vactory_decoupled/src
stack snapshot a avant-essai && stack restore a avant-essai   # base et fichiers du site ; 5 à 25 s mesurés
stack snapshot a --list
stack snapshot a --rm avant-essai
stack logs a [web|db|front|redis]
stack stop a && stack start a
stack rm a [--keep-branch] [--force] [--yes]
stack rm --all --yes               # toutes les instances (dossiers internes _baseline, _build ignorés) ; une instance en échec n'arrête pas les autres, bilan et code de sortie non nul à la fin
```

Un nom fait de 1 à 20 caractères parmi `a-z`, `0-9` et `-`, sans `-` au début ni à la fin. Noms réservés aux ressources partagées, refusés : `baseline`, `front`, `yarn-cache`, `base`, `build`, `locks`. `stack up` sur une instance existante la redémarre et remet à niveau sa config (idempotent) ; il ne change pas sa branche.

Le code du profil est celui de l'arbre `<instance>/profile`, monté dans le site : une modification y est visible tout de suite (`stack drush a cr` si besoin). On y commit et on y pousse comme dans tout arbre git. Le hook `pre-commit` du dépôt cherche le `vendor/` du profil, absent d'un arbre d'instance : y committer avec `--no-verify` et vérifier le style par `stack phpcs`.

`stack test` lance PHPUnit dans l'instance. Un chemin est relatif au profil (ou absolu dans l'arbre du profil, ou en `profiles/contrib/vactory_starter_kit/…`) ; `stack test` et `stack phpcs` le normalisent (`.`, `..`, `//`) et refusent tout chemin qui sort du profil, lien symbolique compris. Les tests Functional (`tests/Functional` et `tests/src/Functional`, les deux dispositions existent dans le profil) et la suite `existing-site-functional` sont refusés : ils installent et désinstallent des modules sur le site et y laissent du contenu. La vérification se fait sur le chemin normalisé, sans tenir compte de la casse. Un dossier qui en contient est refusé aussi : viser `tests/src/Unit`, `Kernel` ou `ExistingSite`. Les options de PHPUnit passent telles quelles (`--filter`, `--log-junit <fichier>`…) ; un rapport écrit sous `/var/www/html/` se retrouve dans `<instance>/drupal/`.

`stack rm` supprime le projet ddev (conteneurs, base), le front, l'arbre du profil et le dossier de l'instance. La branche créée par `stack up` est supprimée si elle n'a pas de nouveau commit ; sinon elle est gardée et la commande le dit. `rm` refuse un arbre avec des modifications non commitées (`--force` pour les perdre). Il ne touche jamais un chemin donné par `--profile`.

## Ce que monte `stack up`

1. Arbre de travail du profil : `<instance>/profile`.
2. Copie du projet Drupal : `<instance>/drupal`, en clones APFS (copie à l'écriture : quasi pas de place disque) ; `rsync` ailleurs. Sans `.git`, sans `.ddev`, sans fichiers ni fichiers privés. Un `settings.stack.php` ajouté en fin de `settings.php` règle la base, les hôtes et le front de l'instance.
3. Projet ddev `vsk-<nom>` (même PHP, base et serveur web que le projet source) : `https://vsk-<nom>.ddev.site`, sans port fixe sur la machine.
4. Base : copie du volume de référence (2 à 3 s), puis `drush updb` et `drush cr`.
5. Mails envoyés au Mailpit de l'instance (`https://vsk-<nom>.ddev.site:8026`) : transport SMTP `localhost:1025` dans symfony_mailer.
6. Sauf `--no-oauth` : clés de signature OAuth propres à l'instance ; deux clients, celui du front **sans rôle** (avec simple_oauth, les rôles d'un client sont donnés à tous les jetons qu'il émet) et un client administrateur ; comptes `stack_user` (authentifié) et `stack_editor` (administrateur) ; la route de connexion retirée des routes protégées par captcha de l'instance, pour se connecter en local.
7. Avec `--front` : le front Next dans un conteneur du projet (Node 22, `node_modules` Linux partagés en lecture seule), sur `http://front.vsk-<nom>.ddev.site:<port>` (port libre à partir de 4100), et un Redis à lui. Son `.env.local` reprend les clés du `.env` et du `.env.local` du front source et remplace celles de l'instance : adresses de Drupal et du front, Redis, préfixe Redis `vsk-<nom>:`, client OAuth sans rôle, secrets NextAuth et cache, `NODE_TLS_REJECT_UNAUTHORIZED=0`, Sentry vidé. Drupal appelle ce front quand son cache est vidé (`BASE_FRONTEND_URL`).
8. `<instance>/stack.json` : nom, dates, branche et commit, chemins, adresses, port du front, Mailpit, projet ddev, chemin des secrets. Lisible par un outil.

## Fichiers

```
~/.config/vactory/stack.env        emplacements locaux (hors dépôt)
~/dev/.vactory-stacks/
  _baseline/                       base de référence : snapshot/, db.sql.gz, manifest.json, front/
  <nom>/
    stack.json                     description de l'instance
    secrets.env                    secrets (600) : clients OAuth, mots de passe des comptes, secrets du front
    keys/                          clés de signature OAuth
    profile/                       arbre de travail du profil
    drupal/                        copie du site (dont .ddev/)
    front/                         copie du front (avec --front)
    logs/up.log                    journal de création
```

Volumes Docker : `vsk-baseline-db` et `vsk-front-node-modules` (référence), `vsk-<nom>-mariadb` (base de l'instance), `vsk-<nom>-snap-<étiquette>` (snapshots ; les fichiers du site, `files` et `private`, sont copiés dans `<instance>/snapshots/<étiquette>/`) ; `stack rm` supprime ceux de l'instance : ses volumes portent l'étiquette `com.vactory.stack.instance=<nom>` (les volumes partagés portent `com.vactory.stack=baseline` ou `front`, jamais supprimés par `rm`). Un snapshot pris avant cette version n'a pas de copie des fichiers : `restore` le dit et ne remet que la base.

Les secrets ne sont jamais affichés. Pour un mot de passe : l'ouvrir dans `secrets.env`, ou préférer `stack uli`.

## Travailler à plusieurs en parallèle

- **Une instance par personne, par agent et par branche.** Nom court et parlant : ticket ou initiales (`71475`, `hb-menus`, `agent-2`).
- **Jamais l'instance d'un autre**, ni le site local habituel pour des tests qui changent la base.
- Chaque instance a son site, sa base, son Mailpit, ses comptes, son front et son Redis : rien n'est partagé à part le routeur ddev et la base de référence (lue seulement).
- Deux `stack up` de noms différents peuvent tourner en même temps (verrous sur git, les ports et la base de référence). Les `ddev start` passent un par un (verrou, voir Dépannage) : mesuré le 24/11 avec cinq `up` lancés presque ensemble, `ddev start` en 60 s pour le premier, 95 s pour le suivant, 220 s pour le cinquième ; `up` complet en 2 min 40 à 3 min 10 pour deux instances lancées à 2 s d'écart.
- Chaque nom nouveau coupe tous les sites ddev de 2 à 4 s (recréation du routeur) : cinq coupures mesurées pour cinq noms nouveaux, aucune pour les commandes suivantes.
- `stack <commande> --help` affiche l'aide.

### Agent automatisé

1. `stack up <nom> --branch <sa-branche> [--front]` ; lire `~/dev/.vactory-stacks/<nom>/stack.json` pour les adresses et les chemins.
2. Travailler et committer dans `profile` de l'instance (l'arbre git de sa branche).
3. Tests et vérifications de bout en bout dans l'instance : `stack test`, `stack phpcs`, `stack drush`, l'adresse Drupal et celle du front. Pas de suite Functional.
4. Aucun secret dans les sorties, journaux, commentaires de PR ou de ticket : ni `secrets.env`, ni `.env*`, ni clé, ni cookie. Se connecter avec `stack uli`, ou lire le mot de passe dans un script sans l'afficher.
5. À la fin : pousser sa branche, puis `stack rm <nom> --yes`.

Consigne à donner à un agent :

> Monte ta propre instance : `scripts/stack/stack up <nom> --branch <branche> [--front]` (nom : 1 à 20 caractères a-z, 0-9, -, ni `baseline` ni `front`). Lis `~/dev/.vactory-stacks/<nom>/stack.json` pour les adresses et les chemins. Travaille et committe dans le dossier `profile` de ton instance. Lance tes tests avec `stack test <nom> <chemin>` (pas de tests `tests/Functional`), ton phpcs avec `stack phpcs <nom> <chemins>`, tes vérifications avec `stack drush <nom> …` et les adresses de ton instance. N'utilise jamais une autre instance ni le site local habituel. N'affiche jamais le contenu de `secrets.env`, des `.env*`, d'une clé ou d'un cookie. À la fin, pousse ta branche puis `stack rm <nom> --yes`.

## Dépannage

- **`stack up` échoue :** la fin du journal s'affiche (`<instance>/logs/up.log`) et l'instance à moitié créée est supprimée. Une création tuée (`kill -9`, machine éteinte) laisse une instance « incomplète » : `stack rm <nom> --yes`.
- **Front sans réponse :** `stack logs <nom> front`. La première page compile plusieurs dizaines de secondes.
- **`yarn.lock` du front changé :** `stack baseline --front-only` (instances avec front arrêtées).
- **Base trop ancienne** (3.x a beaucoup avancé) : `stack baseline`. Les instances existantes gardent leur base.
- **Suites lourdes en parallèle :** mesuré le 24/11 (VM Docker de 9 Go, partagée avec d'autres instances) : une suite ExistingSite sur un site où presque tous les modules du profil sont activés fait monter le conteneur web à 2,4–3,5 Go (PHPUnit sans limite de mémoire, sorties de dépréciation par milliers). Deux à la fois : une suite tuée (exit 137), puis panique du noyau de la VM OrbStack (récupération mémoire) qui a arrêté tous les conteneurs de la machine et laissé les données d'OrbStack non montables. Une seule suite lourde à la fois, et surveiller `docker stats`. `stack up` et `stack test` lisent la mémoire disponible de la VM Docker (`MemAvailable`, dans un conteneur jetable de l'image Redis) et refusent de démarrer sous 3 Go (`VACTORY_STACK_MIN_FREE_MB` pour changer le seuil, `VACTORY_STACK_SKIP_MEMORY_CHECK=1` pour passer outre).
- **Mémoire de Docker pleine :** `stack stop` sur les instances inutilisées, ou plus de mémoire pour la VM Docker.
- **Routeur ddev :** le premier démarrage d'une instance sous un nom nouveau recrée le routeur ddev partagé : tous les sites ddev de la machine sont coupés environ 4 s (mesuré). Les `ddev start` des instances passent un par un (verrou) : deux démarrages simultanés recréent le routeur en même temps et l'un échoue. Réutiliser les mêmes noms limite ces coupures.
- **`stack drush`, `exec` ou `test` dans une boucle `while read` :** ces commandes passent l'entrée standard au conteneur (`ddev exec`) et vident le reste de la boucle ; ajouter `< /dev/null` (constaté avec une file d'essais qui s'arrêtait après le premier).
- **Projet ddev à la main :** `cd ~/dev/.vactory-stacks/<nom>/drupal && ddev …`.

## Limites

- La base de référence est une installation du profil (ou un dump fourni), pas les contenus d'un projet. Les fichiers (`sites/default/files`) partent de ceux de la base de référence.
- Le front tourne en mode dev (`next dev`) : la première visite de chaque page compile.
- Le projet Drupal source fixe le code de Drupal et des modules contrib (son `vendor/`) ; seul le profil change d'une instance à l'autre. Une branche qui change `composer.json` demande un projet source à jour.
- Pas de Solr, de Memcache ni de Keycloak dans l'instance.
