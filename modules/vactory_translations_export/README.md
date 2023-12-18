# Vactory Translations Export
Provides backoffice console to import/export interface translations. 

### Installation
`drush en vactory_translations_export -y`

### Export
Go to `/admin/config/regional/vactory-translations-export` select
the desired context and the delimiter then click export.

### Import
Go to `/admin/config/regional/translate/import` select
the desired context and the delimiter then upload the csv and click import.

### Permission
To be able to access the translation CSV import/export form user should have
the permissions:
* Administer vactory_translations_export configuration
(`administer vactory_translations_export configuration`)

### Maintainers
Brahim KHOUY <b.khouy@void.fr>
