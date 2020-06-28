
# Vactory cross content      
Ce module permet d'implémenter la fonctionnalité "contenu lié" ou "cross content".
Sur chaque nœud, vous pouvez remonter des teasers d'articles de différents types de contenu suivant le format d'affichage qui vous plait. 
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

- improved_multi_select  
- vactory_fields_base
- vactory_generator
- vactory_view_modes
- vactory_views
- views    

## Recommended modules    

 - Improved multi-select  
 **Configuration :**   
node/*/edit  			
*/node/&ast;/edit   
*/node/&ast;/edit&ast;   

## Installation    

 drush en vactory_cross_content  

## Configuration  
Une fois le module est activé , il faudra accéder à la partie gestion de block (admin/structure/block) et choisir la position du block cross content et le configurer. La configuration du module se fait de 3 manières à savoir :
- Configuration Block : Un formulaire permettant de choisir les types de contenu où VCC sera activé + nombre d'élement à afficher + Format d'affichage + Nombre d'élément + Libellé lien voir plus + Titre block
- Configuration du type de contenu choisi : Un formulaire permettant de choisir la taxonomie à utiliser (field + termes) +  nombre d'élement à afficher + Nombre d'élément + Libellé lien voir plus
- Configuration d'un noeud : Dans ce cas un field sera ajouté à notre type de contenu ce field aura pour valeur les différents noeud à afficher dans le block (ceci dit que la configuration du noeud n'est qu'une configuration direct du vcc ).

###### Ordre de priorité :
Config noeud &rarr; Config block &rarr; Config type de contenu.
A noter que : 
X &rarr; Y veut dire que X est plus prioritaire que Y , ce qui veut dire que la configuration de X surcharge celle de Y .

##### Nouveau Field !!
---
A noter qu'une fois le module est activé le field est ajouté dynamiquement au type de contenu .

## Extends      

#### Aucun    

## API      
- Le module met à votre disposition un hook à exploiter comme bon vous semble :
```php
function hook_vactory_cross_content_alter_view($view, $content_type,$block)
```
- Une commande drush est aussi disponible pour la création du block ainsi que l'ajout du field contenu lié :
```bash
drush vcc [optional content_type]
```
l'argument content_type est optionnel puisque la commande vous fournit une liste de type de contenu disponible (si l'appel se fait sans argument biensûr) , par la suite elle vous fournira une liste des régions où vous pourriez mettre le block cross content , avant de vous confirmer la création de votre block le field contenu lié est directemnt créé et ajouté à votre type de contenu pour vous faciliter la configuration par la suite .
## Theming      

#### Aucun    

## Troubleshooting & FAQ  

#### Aucun    

## Maintainers    

* Hasbi Hamza : <h.hasbi@void.fr>
