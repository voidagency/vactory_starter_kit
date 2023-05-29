# Vactory icon
Provides icon picker form element.

### Installation
`drush en vactory_icon -y`

### Configuration
The module provides a new plugin system VactoryIconProvidersManager
which can be used to add new icon providers settings forms and logics.

By default the module provides two icon providers:
* Icomoon: The plugin implementation is under 
`vactory_icon/src/Plugin/VactoryIconProvider/IcomoonIconProvider.php`
* XML source: This plugin is generally use the frontend svg icons 
(http://localhost:3000/icons.svg), the plugin implementation is under
`vactory_icon/src/Plugin/VactoryIconProvider/XmlSourceIconProvider.php`

You could add new icon provider plugin same way as those two plugins above

### Maintainer

Brahim KHOUY <b.khouy@void.fr>
