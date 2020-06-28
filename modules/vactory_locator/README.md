
# Vactory partner


Ce module permet d'ajouter un block sous forme de `MAP` qui contient des elements créer à travers le back-office,
 avec la possiblité de configuré le block par des filtres.


## Table of Contents
* [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Extends](#extends)
 * [Api](#api)
 * [Todo](#todo)
* [Troubleshooting &FAQ](#troubleshooting-faq)
* [Maintainers](#maintainers)

## Requirements
les dépendances :

  - address
  - content_moderation
  - content_translation
  - rest
  - serialization
  - telephone
  - vactory_features_dependencies
  - vactory_google_map_field



## Installation
Activation du module via la commande drush suivante :

    drush en -y vactory_locator

## Configuration

Utilisation du module pour un utilisateur Webmaster :
   > - Ajouter les élements de votre map via l'url `admin/structure/locator_entity`.
   > - Ajouter et modifier les catégorie des élements via l'url `admin/structure/taxonomy/manage/locator_category/overview`
   > - Configuré la map via la page de configuration du block  `admin/structure/block/manage/`ID_DE_BLOCK
   
Utilisation du module pour un utilisateur Développeur :
   > - Module élements :
    >> - Le module vous donne une `Entity` au nom de `Locator`.
    >> - Le module a un block qui vous permet d'afficher les élements de l'entité sous forme d'une map, 
    le nom du block est `Vactory Locator Block` créer en code.
    >> - Le module dispose d'une vue qui génere une liste `JSON` contien toute les element avec la possibilité de filtré avec un filtre contextuel configuré depuis la configuration du block.
   
   > - Développement
    >> - Entity & `Fields`
        >>> - Qu'on ajoute un field a l'entite faut pas oublier de configuré le `Mode de Vue` pour qu'il soit visible, aussi faut penser à l'ajouter au `Vue` [**NOTE**: la vue et overridé vie le `vactory_locator_views_post_render`, donc faut ajouter votre field dans le code, template et le readme aussi].
        >>> - Pour utilisé les fields au niveau de `TPL` lire la documentation dans la tpl (pin.html.twig).
    >> - Vue
        >>> - Dans notre vue on a ajouter field par field pour qu'on puisse bien géré notre rendu puisque c'est une vue de dévrloppeur, on trouve aussi 2 filtres de critéres et un filtre contextuels qui filtre par Catégorie en multiple [**NOTE**: On a appliqué un patch de communauté pour fixé un bug de rendu `JSON`, source: 'https://www.drupal.org/files/issues/2018-04-28/2854543-88.patch'].
    >> - Block
        >>> - Notre block et développé en code, il a la possibilité de configuré les filtres contextuels, pour l'instant on trouve d'un filtre disponible (Category).
    >> - Templates
        >>> - `map-block.html.twig` : c'est la template du block de map.
        >>> - `pin.html.twig` : c'est la template du block remonté on cliquant sur un element du map, il contien la photo, description et d'autre information a propos d'élement.
        
**NOTE** : Pour tous les changement au niveau de ce module besoin d'un export de la featurese, faites trés attention au dépondance, et vous n'oublier pas de méttre à joure ce fichier (README.md) a chaque changement. 


## Extends

Aucun

##  API

Aucun

##  Todo

Ce module a bien sur besoin d'amélioration et d'optimisation.
   - Todo 
	 > -  Enrichir le block du map avec plus de filtre.

## theming
Dans ce module on trouve des templates générer par l'entité mais on trouve aussi des template custom.
*  templates :
	* map_block.html.twig : c'est la template du block ou on trouve notre map.
	
	* pin.html.twig : c'est la template qui contien notre balisage du pop-in qui remonte au niveau du map (le petit block qui affiche une image de l'elemet avec la discription et les informations supplémentaire).

## Troubleshooting & FAQ
Aucun

## Maintainers
Ezzerrouqi Oualid
<o.ezzerrouqi@void.fr>
