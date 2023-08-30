# Vactory content package
Provides ability to export site pages in a zip file, and
import it in another instance, the zip file contains json files
that could be edited before proceed to import operation.

### Installation
`drush en vactory_content_package -y`

### Configuration
#### Export
Provides a submit button to export site pages into zip an then
download the zip.
Path: `/admin/config/system/vactory-content-package-export`

#### Import
Provides an input file to upload the exported zip file, an then
choice between delete existing node and import or just import
new nodes.
Path: `/admin/config/system/vactory-content-package`

#### DF json generator
Get json structure of selected DF template with fake content.
Path: `/admin/config/system/dynamic-field-json-generator`

#### Exclude nodes from content package import/export
On each node we have a checkbox field to exclude node from  
content package export/import process

### Demo video
https://www.loom.com/share/cd25f0360f7942ca89c7a91ef38f35d2

### Maintainers
* Khalid BOUHOUCH <k.bouhouch@void.fr>
* Brahim KHOUY <b.khouy@void.fr>
