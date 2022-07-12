# Vactory Whatsapp
Provides Whatsapp Business API integration.

### Installation
`drush en vactory_whatsapp -y`

### Configuration
Module configuration page: `/admin/config/system/vactory-whatsapp`
Please check the following demo video on how to configure the module:
https://www.loom.com/share/266c11914c334b7aabfe53c2bd30804b

### Vactory Whatsapp API Manager
The module expose `vactory_whatsapp.api.manager` service,
the service has two methods:
* sendTemplateMessage: Send whatsapp template message
Example:

      \Drupal::service('vactory_whatsapp.api.manager')
        ->sendTemplateMessage($to, $template_id, $template_params, $langcode);
* sendTextMessage: Send whatsapp simple text message
Example:

      \Drupal::service('vactory_whatsapp.api.manager')
        ->sendTextMessage($to, $message_text);

### Maintainers
Brahim KHOUY <b.khouy@void.fr>

