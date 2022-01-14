
# Vactory Node Views Count  
Ce module permet d'implémenter la fonctionnalité "Node Views Count".
## Table of Contents  

 * [Installation](#installation)   
 * [Configuration](#configuration) 
 * [Endpoint](#endpoint) 
 * [Maintainers](#maintainers)      

## Installation    

 drush en vactory_node_view_count -y  

## Configuration  
Une fois le module est activé , il faudra accéder à la partie : Configuration 
du type de contenu choisi afin d'activé cette fonctionnalité.

## Nouveau Field !!
---
A noter qu'une fois le module est activé le field est ajouté dynamiquement au type de contenu .

## Endpoint
Le module expose une endpoint qui sert à incrémenter le nombre de vue d'un
noeud depuis une application trièce:
Pour faire envoyer une requête post à l'endpoint suivante:

`/node-views-count/update-count/[NID]`

Avec NID et l'id du noeud concerné.

## Maintainers    

* BOUHOUCH Khalid : <k.bouhouch@void.fr>
