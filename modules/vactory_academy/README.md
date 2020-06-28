
## Vactory Academy

Ce module offre la fonctionnalité de gestion des cours en ligne. Les cours sont affichés sous forme d'une liste. Chaque cours permet d’exposer son contenu sur une page détail. Le module 'Academy' offre également la possibilité de filtrer les résultats d'affichage par date ou par thématique.

## Table of Contents
 * [Requirements](#Requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [extend](#extend)
 * [Api](#api)
 * [Troubleshooting &FAQ](#Troubleshooting&FAQ)
 * [Maintainers](#Maintainers)

### Requirements

Module :
- Facebook comments (Ajouter des commentaires via un compte Facebook)
- Avoir un compte developer.facebook.com

Les dépendances :
- better_exposed_filters
- content_translation
- datetime
- entityqueue
- facebook_comments
- fences
- field
- image
- language
- menu_ui
- node
- path
- pathauto
- responsive_image
- taxonomy
- text
- user
- vactory_features_dependencies
- vactory_field
- vactory_fields_base (réutilisation des champs déjà définis)
- vactory_generator (génération du contenu de type Academy)
- vactory_user
- vactory_views (utilisation du mode de vue card-inline)
- video_embed_field
- views
- wysiwyg_template

### Installation

- Après avoir installé le module Facebook Comment, ne pas oublier d'ajouter le Facebook App ID dans : Administration > Configuration > Content authoring > Facebook comment settings. le Facebook APP ID peut être obtenu depuis developers.facebook.com.
- Activation du module via la commande drush suivante : drush en vactory_academy

### Configuration

Aucun

### Extends

Aucun

### API

Aucun

### theming

- templates
    - card.html.twig : Permet de définir la façon de visualiser chaque cours dans la liste des cours disponibles.
    - full.html.twig : Permet de définir la façon de visualiser la page de détail des cours.

- hook theme
    - vactory_academy_theme : Permet d'appliquer les templates aux noeuds et aux vues correspondantes.
    - vactory_academy_preprocess_node : Un preprocess pour remplacer le titre de l'image par celui du cours correspondant.

### Troubleshooting & FAQ

Aucun

### Maintainers

Bouharras Rida
<r.bouharras@void.fr>
