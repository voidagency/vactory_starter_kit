## Stage File Proxy

Stage File Proxy est une solution générale permettant d’obtenir des fichiers de production sur un serveur de développement à la demande. Vous gagnez du temps et de l'espace disque en envoyant des requêtes dans le répertoire de fichiers de votre environnement de développement à l'environnement de production et en créant une copie du fichier de production sur votre site de développement. Vous ne devriez pas avoir besoin d'activer ce module en production.


```code
composer require 'drupal/stage_file_proxy:^1.0.0-alpha3'
drush en -y stage_file_proxy
drush config-set stage_file_proxy.settings origin "http://void:void_vactory@vactory8.lapreprod.com"
```

```hint|neutral
Pour les accès HTACCESS, voir la page WIKI Redmine du projet concerner.
```

```hint|warning
Ce module ne doit pas être installer en production.
```
