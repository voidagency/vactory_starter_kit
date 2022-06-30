# Vactory Image Sitemap
Generate XML sitemap for site nodes images according to google image sitemap
required structure see:
https://developers.google.com/search/docs/advanced/sitemaps/image-sitemaps#example-sitemap.

### Installation
`drush en vactory_image_sitemap -y`

### Settings
Setting form: `/admin/config/system/vactory-image-sitemap`
You could choose which content type should be excluded from generated XML image
sitemap file.

### Generate image sitemap
* **From BackOffice**:
Under setting form `/admin/config/system/vactory-image-sitemap` you could
click generate button to generate the image sitemap xml file.
* **From Cron**:
All you need is running cron (`drush cron`),
A cron job is exposed to generate image sitemap xml file.

Generated image sitemap is accessible via: `/image-sitemap.xml`

Note:
Private images are by default excluded from image sitemap.

### Maintainers:
Brahim KHOUY <b.khouy@void.fr>