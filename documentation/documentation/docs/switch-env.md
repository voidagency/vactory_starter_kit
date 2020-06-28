Drupal 8 ne contient pas d'équivalant au module Tadaa!

La communauté utilise le module config split pour switcher entre les environments. Pour notre cas, ont ne synchronise pas la configuration mais on passe par Features.

Une commande Drush à été ajouter à la Factory Core qui permet de switcher entre les environments.

- La commande Drush suivante permet de switcher vers l'environment de production:

```code
drush vactory-switch-environment production

# Alias
# drush vse production
```

- La commande Drush suivante permet de switcher vers l'environment de dévelopment:

```code
drush vactory-switch-environment development

# Alias
# drush vse development
```

Voici la liste de modules que le commande install/désinstall selon l'environment:

- devel
- features_ui
- modules_weight
- realistic_dummy_content
- security_review
- views_ui
- twig_vardumper

La commande modifie la valeur de la variable global `$vactory_environment` qui se trouve dans le fichier settings.php

On se basant sur la valeur de cette variable (production ou development), Drupal charge une configuration spécifique à l'environnement choisie.
Par exemple, activer ou désactiver l'aggrégation Advagg.
