## Préparation

L'installation de la Vactory nécessite d'abord un serveur Web et un serveur de base de données connu sous le nom de "Stack AMP".

**AMP** stack

- Apache (2.x)
- MySQL (> 5.5.3)
- PHP (7.2)

En plus de la Stack **AMP**, d'autres outils sont nécessaires pour le développoment des modules et themes.

```hint
Voir la séction [Pré-requis](/pre-requis/drupal-console) pour les outils de développoment.
```

## Obtenez le code

Pour contribuer au développement, vous devez télécharger la Vactory en utilisant Git.
Le dépôt Git officiel de la vactory est:

```download
title: Vactory8.git
subtitle: VOID
url: https://bitbucket.org/adminvoid/vactory8
```

Le téléchargement du code est aussi simple que:

```code
git clone git@bitbucket.org:adminvoid/vactory8.git
```

Depuis votre ligne de commande.


## Installer les dépendances avec Composer

Pour obtenir une base de code fonctionnelle, vous devez exécuter `composer install` à partir du répértoire racine du projet.
Cela va installer Symfony et d'autres paquets requis par Drupal dans le répertoire vendor /.

## Pré-requis (Mac OS x Sierra)

LA Vactory fournis un script shell qui permet d'installer rapidement les outils de développoment sous `Mac OS x Sierra`.

```code
cd mon-projet
chmod +x install_dev.sh
./install_dev.sh
```

```hint
En cas d'échec d'installation, il faut installer les dépdenances manuellement, à savoir:
- composer
- coder
- phpcs (codesniffer)
- nodejs
- jshint
- scss_lint

Sans oublier de configurer pre-commit
```

## Installer les dépendances du Theme avec NPM

```code
cd themes/vactory
npm install
```

## Database et settings

Créer une base de données

Déplacer le fichier `docs_factory/resources/settings/settings.local.php` dans `sites/default/settings.local.php`
et configuréer les paramètres de connexion à la base de donnés.

## Lancer l'installation

Visitez votre site dans un navigateur Web. Vous devriez être redirigé vers la page d'installation de `/core/install.php`.

La procédure est simple à l'excéption du choix du profile où il faut choisir `La Factory Starter Kit`.

## Finnalisation de l'installation

Finnaliser l'installation de la Factory en exécutant les commandes suivantes:

```code
drush entup -y
```

```code
drush cron
```

```code
drush cr
```
