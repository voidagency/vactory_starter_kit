Vactory Header 8.x-1.0-dev
---------------

### ---  À propos de ce module  ---
Le module vous propose plusieurs variantes pour le `Header` en block, faut juste placer l'un des block proposé dans la region que vous vouliez,
 le module rajoute une class au body en fonction de la variante exemple `vh-header-1`.


### ---  REQUIS  ---
* vactory_core : il utilise une sa fonction twig `vactory_render`.
* block : 
* block_class : 
* block_field : 
* content_translation : 
* default_content : 
* field : 
* language : 
* link : 
* menu_item_extras : Il permet d'injecter des blocs personnalisés
* menu_link_content : 
* simplify_menu : 
* system : 


### ---  INSTALLATION  ---
Installez comme d'habitude.

l'installé via l'url suivant `/admin/modules` et placer le block que vous vouliez via l'url suivant `admin/structure/block/library/vactory`.

For help regarding installation, visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8


**Pour importer tous les blocs personnalisés requis par le module, vous devez exécuter les deux commandes suivantes:**

    `drush php`
    `\Drupal::service('default_content.importer')->importContent('vactory_header', TRUE);`


### ---  VARIANT REQUIREMENT  ---
#### VARIANT 3
**Dépendencies:** 
* vactory search overlay : 

**Pour l'activation de la variante 3 du module, il est nécessaire de :**
    - Activer le modules search overlay.
    - Ajouter le bloc de module vactory search overlay dans une région (Région Bottom).
    - Ajouter la variante 3 du module dans la region Header.
    
**La configuration de l'affichage**
* L'affichage de menu est configurable au niveau du Back-Office on specifiant Les class bootstrap ('col-lg-x') dans le champ associé aux noms class.
* On a la possibilité d'injecter les block personnalisée via le champs 'injected_block'.


### ---  CRÉATION VARIANT  ---

**Nous pouvons exporter les nouveaux blocs personnalisés créés pour une variante par la commande suivante:**

    `drush dce block_content ID_BLock --file=modules/vactory/vactory_header/content/block_content/ID_Block.json`

### ---  CONTACT  ---

Current Maintainers:
*Void - https://www.void.fr
