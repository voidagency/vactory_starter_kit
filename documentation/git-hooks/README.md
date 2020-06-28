# Mise en palce de hook pré-commit Drupal
## À propos des crochets Git (Git hooks)
Git dispose d’un moyen de lancer des scripts personnalisés quand certaines actions importantes ont lieu.
Il y a deux groupes de crochets : ceux côté client et ceux côté serveur.
- Les crochets côté client concernent les opérations de client telles que la validation et la fusion.
- Les crochets côté serveur concernent les opérations de serveur Git telles que la réception de commits.

## Table des matières

* [Pourquoi pré-commit](#pourquoi-pre-commit)
* [Requis](#requis)
* [Installation](#installation)
* [Configuration pré-commit](#configuration-hook-pre-commit)

## Pourquoi pre-commit?

L'idée de mettre en place le hook pré-commit est de vérifier si tous les fichiers prêts à être validés (commité) sont syntaxiquement et sémantiquement conformes à [la norme de codage Drupal](https://www.drupal.org/docs/develop/standards/coding-standards) avant d’établir un commit sur le projet..

 ## Requis
 * Coder
 * PHP CodeSniffer
 * Jshint
 * Scss-lint
 * Script pre-commit

## Installation
### Installation global de coder et PHP CodeSniffer :

 On opte pour une installation globale de **Coder** et **PHP CodeSniffer** pour éviter le problème d'installer coder individuellement pour chaque projet Drupal (**Ce qui produit une perte de temps**).

 * **Installation de coder:**



  Tapez la commande drush suivante qui permet de télécharger coder et l'installer au niveau du répertoir ~/.drush pour qu'il soit accessible globalement :

      drush pm-download coder --destination=$HOME/.drush --select

 Vous aurez un affichage comme suite:

    Choose one of the available releases for coder:
     [0]  :  Cancel                                              
     [1]  :  7.x-2.6      -  2016-Jul-12  -  Supported, Security
     [2]  :  7.x-2.x-dev  -  2016-Jul-12  -  Development         
     [3]  :  7.x-1.3      -  2016-Jul-12  -  Supported, Security
     [4]  :  7.x-1.x-dev  -  2016-Jul-12  -  Development

 **1-** Choisissez la dernière version (Dans ce cas on choisit **7.x-2.6**):  Tapez 1 sur votre clavier puis Entrer.

 **2-** Vider le cache de drush via la commande suivante:

      drush cache-clear drush

 **3-** Taper la commande suivante :

      drush

   Vous devez avoir un affichage montrant l'ensemble des options à utiliser avec la commande `drush` y compris la nouvelle option à découvrir `drupalcs`, voilà un extrait de cet affichage :

         ...
         make-generate         Generate a makefile from the current Drupal site.                                                         
         (generate-makefile)                                                                                                             
         make-update           Process a makefile and outputs an equivalent makefile with projects version resolved to latest available.
        Other commands: (coder,coder_review,drupalcs)
         coder-format          Re-format and rewrite code according Drupal coding standards.                    
         coder-review (coder,  Run code reviews                                                                 
         sniffer)                                                                                               
         install-php-code-sni  Install PHP Code_Sniffer                                                         
         ffer                                                                                                   
         drupalcs (dcs)        Executes PHP_CodeSniffer with Drupal Coding Standards on a particular directory.

 * **Installation de PHP CodeSniffer:**

  **1-** Exécuter la commande composer install sur le répertoire **~/.drush/coder**:

        cd ~/.drush/coder
        composer install

 Vous remarquez qu'un nouveau dossier **~/.drush/coder/vendor** est créé, c'est le dossier magique qui contient le script PHP CodeSniffer dont on a besoin (**Voir l'étape suivante**).

  **2-** Éditer le fichier profile **~/.bash_profile** pour créer un alias du script phpcs comme suite:

        nano ~/.bash_profile

 Puis ajouter la ligne suivante:

        alias phpcs="php $HOME/.drush/coder/vendor/squizlabs/php_codesniffer/scripts/phpcs"

 Vous venez d'associer l'alias phpcs à la commande d'exécution du script PHP CodeSniffer.
 Sauvegarder la nouvelle modification du fichier profile ~/.bash_profile et quitter le.

  **3-** Racharger à nouveau le fichier profile pour que prendre en considération la nouvelle modification :
 Tapez la commande :

        source ~/.bash_profile

  **4-** Tester maintenant si alias a été bien créé avec la commande :

        phpcs --version

 Vous devez avoir un résultat comme suite:

        PHP_CodeSniffer version 2.9.1 (stable) by Squiz (http://www.squiz.net)


 **NB:** Vous avez la main maintenant à exécuter la commande `drush drupalcs` sur un fichier PHP afin de vérifier
 s'il respect les normes de codage de Drupal ou non.

    drush drupalcs fichier_ou_module_à_vérifier

### Installation globale "jshint":

**jshint** est un outil qui permet la validation et la détection des erreurs de syntaxe des fichiers JS.

À l'aide du gestionnaire de packages `npm` installez l'outil `jshint` avec la commande suivante :

    cd $HOME
    npm i jshint
    ln -sv $HOME/node_modules/jshint/bin/jshint /usr/local/bin/jshint

Maintenant on a installer l'outil jshint globalement, vous pouvez tester si l'outil s'est installé
correctement en tapant la commande :

    jshint --version

Vous devez avoir comme résultat la version de votre `jshint` installée:

    jshint v2.9.6


### Installation globale de "scss-lint":

**scss-lint** est un outil qui permet la vérification de la syntaxe du code SCSS.

Installer l'outil `scss-lint` avec la commande:

    cd $HOME
    gem install scss_lint
Tester si l'outil s'est installé correctement avec la commande `scss-lint --version`.
 Vous devez avoir comme résultat la version de `scss-lint` installée:

    scss-lint 0.57.0

 ## Configuration hook pre-commit

 Pour cette phase tout le travail sera effectuer au niveau du dossier .git situé sur la racine du projet Drupal.

 Ouvrez une terminal, et soyez sûr que vous êtes bien sur **la racine du projet Drupal** sur lequel on souhaite mettre en place le hook pre-commit.

    cd Racine_du_projet
    cp docs_factory/git-hooks/pre-commit .git/hooks
    chmod +x .git/hooks/pre-commit

   **NB** :
   * Si vous utilisez un alias personnalisé pour la commande **drush** (drush7, drush8, d7...), veuillez
   modifier la partie **Alias drush** de fichier pre-commit (Remplacer la valeur de l'alias par défaut **drush**
   par votre alias).

   * Si vous souhaitez ignorer les vérifications, utilisez l'option `--no-verify` :


      git commit -m "Le commentaire de la commit." --no-verify
