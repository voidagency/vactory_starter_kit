# Vactory Cloudinary
Enhance cloudinary contrib module

### Installation
`drush en vactory_cloudinary -y`

### Drush command
The cmodule expose a drush command to migrate file to/from cloudinary

`drush mtc`

Execute `drush mtc --help` to see all available options.

### Filter
The module expose a filter to replace old files uri on wysiwyg
just go to `/admin/config/content/formats` and click configure for HTML simple
text format, then under Enabled filter section check "Vactory cloudinary files
replace" filter, and under filter settings you could choose the replace policy.
And the same for HTML Complet text format.

### Maintainer
Brahim KHOUY <b.khouy@void.fr>
