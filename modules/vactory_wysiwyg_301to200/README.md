# Vactory WYSIWYG 301to200
Provides a drush command to replace all 301 redirect on wysiwyg fields
to the result of the final redirect.

### Installation
`drush en vactory_wysiwyg_301to200`

### Drush command
`drush wysiwyg301to200 --site-uri=http://vactory.lapreprod.com`
Replace in wysiwyg fields all 301 links to final 200 associated links.

Examples:
* `drush wysiwyg301to200 --entity-type=node --bundle=vactory_news --site-uri=http://vactory.lapreprod.com`

Replace in vactory_news content type wysiwyg fields all 301 links with 200 links.                

* `drush wysiwyg301to200 --entity-type=node --site-uri=http://vactory.lapreprod.com` 

Replace in all content types wysiwyg fields all 301 links with 200 links.                        

* `drush wysiwyg301to200 --site-uri=http://vactory.lapreprod.com` 

When --entity-type is not specified, the default value is node entity type. So the given command  replace in all content types wysiwyg fields all 301 links.

*Options*:

`--site-uri[=SITE-URI]`: The Site URI to be used for hrefs with relative path, the site uri should contains the schema + domain name, Example: https://vactory.lapreprod.com.
`--entity-type[=ENTITY-TYPE]`: The concerned entity type. (default to node)                                                                                                          
`--bundle[=BUNDLE]`: The concerned bundle of given entity type.

### Demo video
https://www.loom.com/share/71c64f2c639c43e3ba932a2cba5511aa

### Maintainers
Brahim KHOUY <b.khouy@void.fr>