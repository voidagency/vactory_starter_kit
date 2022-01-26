# Vactory HTML2PDF
Provides a service for generating pdf using [MPDF](https://mpdf.github.io) php
library.

### Installation
 `drush en vactory_html2pdf -y`
 
### Module Settings
The module configuration serve to add custom fonts to MPDF supported fonts list
you could achieve that on module settings form page:
 `/admin/config/system/vactory-html2pdf`
You could add fonts directory (one fonts directory by line).
When clicking save you will get your custom fonts on the detected fonts list.

### Generate PDF service example
From any other module you could use the module service to generate pdf from
html output using the following method of service `vactory_html2pdf.manager`:

    \Drupal::service('vactory_html2pdf')
       ->html2Pdf($htmlContent, $outputFilename, $mpdfOptions);

With:
  - $htmlContent: The final html output to print to PDF.
  - $outputFilename: The output file name, it's preferred to be a valid URI
syntax with scheme specification ex: public://foo/bar.pdf
  - $mpdfOptions (Optional): an array defining custom the MPDF library options.

### Maintainers
Brahim KHOUY <b.khouy@void.fr>
