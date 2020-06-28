
# Press kit

Ce module offre la possibilité de créer un contenu de type press kit, où chaque press kit est caractérisée par un titre, une image, une date ,une description et une thématique.
Les press kits peuvent être listées sous forme de card, card-inline ou masonry.


## Table of Contents
 * [Requirements](#Requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [extend](#extend)
 * [Api](#api)
 * [Troubleshooting &FAQ](#Troubleshooting&FAQ)
 * [Maintainers](#Maintainers)

## Requirements

Les dépendances :
  - better_exposed_filters
  - block
  - block_class
  - content_translation
  - datetime
  - entityqueue
  - fences
  - field
  - image
  - language
  - menu_ui
  - node
  - path
  - pathauto
  - responsive_image
  - system
  - taxonomy
  - text
  - user
  - vactory_generator (génération du contenu de type press kit)
  - vactory_core (réutilisation des champs et des modes de vues déjà définis)
  - views
  - wysiwyg_template

## Installation
- Activation du module via la commande drush suivante :

    drush en vactory_press_kit

## Configuration
Aucun

## Extends
Aucun

##  API
Aucun

## theming

*  templates
On distingue entre trois modes de vues :
	* card.html.twig : Template correspondante au mode de vue card (carte).
	* card-inline.html.twig : Template correspondante au mode de vue card-inline (alignement des cartes).
	* masonry.html.twig : Template correspondante au mode de vue masonry.
*  hook theme
	* press_kit_theme : Permet d'appliquer les templates aux noeuds et aux vues correspondantes.
	 * vactory_press_kit_preprocess_node : Un preprocess pour remplacer le titre de l'image de chaque press kit par celui du cours correspondant.

## Troubleshooting & FAQ
Aucun

## Maintainers

Bouharras Rida
<r.bouharras@void.fr>
