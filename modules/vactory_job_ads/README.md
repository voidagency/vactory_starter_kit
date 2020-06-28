# Vactory Job Ads

Vactory Job Ads est un module Drupal pour gérer les annonces d’emploi et candidature sur un site web. Il offre à la fois une interface front pour les utilisateurs du site afin de visualiser ou postuler pour un job ainsi qu'une interface backoffice permettant aux administrateurs/contributeurs de gérer les différentes fonctionnalités du module. 

## Table of Contents
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Extend](#extend)
 * [Api](#api)
 * [Troubleshooting & FAQ](#troubleshooting-faq)
 * [Maintainers](#maintainers)

## Requirements

  - better_exposed_filters
  - content_translation
  - entityqueue
  - fences
  - field
  - language
  - menu_ui
  - node
  - path
  - pathauto
  - taxonomy
  - text
  - user
  - vactory_fields_base
  - views
  - webform
  - wysiwyg_template
## Installation

    drush en vactory_job_ads

## Configuration

- Il faut activer 'file_private_path' dans le fichier setting.php, pour ce faire , décommenter cette ligne du fichier setting.php : $settings['file_private_path'] et lui donner comme valeur le chemin vers un répértoire accessible par le serveur (lecture/écriture) 


## Extends


#### Aucun

##  API
#### Aucun

## Theming


Le module se base sur 3 templates :
 - job-ads-content.html.twig : template qui agit sur les annonces du module
 - job-ads-list.html.twig : template de la page contenant les annonces
 - full.html.twig : template de la page contenant les détails de chaque offre d'emploi 
 - L'affichage du formulaire est celui de Webform par défaut
 ---
 Extras :
 - hook theme : vactory_job_ads_theme
 - hook preprocess node : Pour gérer le type de candidature
 - hook form alter : pour extraire le titre de l'annonce depuis l'annonce et le mettre sur le formulaire  
 - sass et variable sass : à ajouter
 - classes css : listing-vactory-job-ads
 - JobApplicationConfig : Formulaire de configuration, afin de choisir le type de candidature.

## Troubleshooting & FAQ
#### Aucun

## Maintainers

* Hasbi Hamza : <h.hasbi@void.fr> 
