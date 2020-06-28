
# Vactory partner


Ce module permet d'ajouter un contenu de type 'partner', et donne la possibilité de visualiser les partenaires sous forme de liste ou slider.



## Table of Contents
* [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Extends](#extends)
 * [Api](#api)
* [Troubleshooting &FAQ](#troubleshooting-faq)
* [Maintainers](#maintainers)

## Requirements
les dépendances :
  - better_exposed_filters
  - content_translation
  - entityqueue
  - fences
  - field
  - image
  - language
  - link
  - menu_ui
  - node
  - path
  - responsive_image
  - text
  - user
  - vactory_features_dependencies
  - vactory_fields_base ( utilisation des champs déjà définis )
  - vactory_views ( utilisation de views-slider )
  - views

## Installation
Activation du module via la commande drush suivante :

    drush en vactory_partner

## Configuration
Aucun


## Extends

Aucun

##  API

Aucun

## theming
*  templates :
	* block-list.html.twig : permet de modifier le theme du block contenant la liste des partenaires.
	
	* partner.html.twig : permet de visualiser le contenu (l'image et le lien correspondant) de chaque partenaire.
*  hook theme :
	* vactory_partner_theme : permet d'appliquer les templates aux noeuds et aux vues correspondantes.

## Troubleshooting & FAQ
Aucun

## Maintainers
Bouharras Rida
<r.bouharras@void.fr>
