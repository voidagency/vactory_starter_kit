# Vactory Quiz Certificat
Enhance Vactory Quiz module with possibilty to generate certificat
after passing test.

### Installation
`drush en vactory_quiz_certificat`

### Module configuration page
You could configure your certificat under:
`/admin/config/vactory_quiz/certificat`

### Generated certificates (HTML2PDF method)
Certificates are written in `private://quiz_certificat/<year>-<month>/`:
`$settings['file_private_path']` must be set in `settings.php`, otherwise no
certificate is generated and the status report shows an error.

A certificate is downloaded by its owner, logged in on the Drupal site, or by
a user with the `administer site configuration` permission. Any other request
for a file of this folder gets a "page not found" answer.

### Module Loom Demo
Please check the module demo video on:
https://www.loom.com/share/2a132fa25ee9497f974e88cb2a9ab41d

### Maintainers
Brahim KHOUY <b.khouy@void.fr>
