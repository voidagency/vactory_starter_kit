# Vactory WYSIWYG 301to200
Provides a drush command to replace all 301 redirect on wysiwyg fields
to the result of the final redirect.
Also provide a wysiwyg filter plugin which serve to replace links with 301
redirects with the final link.

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

### Filter
1) Go to `/admin/config/content/formats` then click configure for HTML simple text format
and under Enabled filters section check "Vactory 301 to 200" option.
Do the same for HTML complet text format.

2) Visit the page that contains wysiwyg content with 301 redirects links (the filter
will automatically replace in render those link with the final redirect url)
3) Finally go to `/admin/content/wysiwyg-redirects-log` to view the redirects
log so you could detect the 301 redirect links and replace it manually on the associated page.

### Permission
The module expose a permission "View wysiwyg 301 redirects log", roles with
that permission could access to the wysiwyg 301 redirects log listing.

### Demo video
https://www.loom.com/share/71c64f2c639c43e3ba932a2cba5511aa

### Maintainers
Brahim KHOUY <b.khouy@void.fr>