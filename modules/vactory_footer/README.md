Vactory Footer 8.x-1.0-dev
---------------

### ---  À propos de ce module  ---

LE module vous propose plusieurs variantes pour le `Footer` en block, faut juste placer l'un des block proposé dans la region que vous vouliez.

### ---  REQUIS  ---
* vactory_core : il utilise sa fonction twig `vactory_render`.
* block : 
* block_class : 
* content_translation : 
* default_content : 
* language : 
* mailchimp_signup : 
* simplify_menu : 
* social_media_links : 
* system : 
* vactory_mailchimp : 


### ---  INSTALLATION  ---
Installez comme d'habitude.

l'installé via l'url suivant `/admin/modules` et placer le block que vous vouliez via l'url suivant `admin/structure/block/library/vactory`.

For help regarding installation, visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8

**Pour importer tous les blocs personnalisés requis par le module, vous devez exécuter les deux commandes suivantes:**

    `drush php`
    `\Drupal::service('default_content.importer')->importContent('vactory_footer', TRUE);`


### ---  VARIANT REQUIREMENT  ---
#### VARIANT 1
**Dépendances De la variante:**
* Social Media Links

**Pour l'activation de la variante 1 du module, il est nécessaire de :**
* Activer Social Media Links Block
* Ajouter le bloc de 'Social Media link'dans une région (Région Bottom) et désactivez-le.

#### VARIANT 2
**Dépendances De la variante:**
* Vactory Mailchimp

**Pour l'activation de la variante 2 du module, il est nécessaire de :**
* Activer le module Vactory Mailchimp.
    
#### VARIANT 3
**Dépendances De la variante:**
* Vactory Mailchimp
* Social Media Links

**Pour l'activation de la variante 3 du module, il est nécessaire de :**
* Activer le module Vactory Mailchimp.
* Activer Social Media Links Block
* Ajouter le bloc de 'Social Media link'dans une région (Région Bottom) et désactivez-le.



### ---  CRÉATION VARIANT  ---
**Nous pouvons exporter les nouveaux blocs personnalisés créés pour une variante par la commande suivante:**

    `drush dce block_content ID_BLock --file=modules/vactory/vactory_header/content/block_content/ID_Block.json`

### ---  CONTACT  ---

Current Maintainers:
*Void - https://www.void.fr
