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

### Whatsapp Webhook plugin
The module a new plugin type Whatsapp Webhook Callback plugin.
By default the module provides an example of whatsapp webhook callback plugin.

To add a custom whatsapp webhook callback:

1- Add a class in `moduleName/src/Plugin/WhatsappWebhookCallback` folder

2- The class chould use `\Drupal\vactory_whatsapp\Annotation\WhatsappWebhookCallback` annotation,
the annotation is used to configure the plugin:
 * **id**: the plugin ID
 * **label**: the plugin label
 * **fields**: list of concerned whatsapp business fields.

3- The class should extends `\Drupal\vactory_whatsapp\WhatsappWebhookManagerBase` class.

4- The class should implement the abstract methode `public function callback(array $change){};`,
this method should contains the logic to be executed when an event is triggered by whatsapp business.

5- Finally we'll end up with a class like:
 
       <?php
         
       namespace Drupal\vactory_whatsapp\Plugin\WhatsappWebhookCallback;

       use Drupal\vactory_whatsapp\WhatsappApiManager;
       use Drupal\vactory_whatsapp\WhatsappWebhookManagerBase;

       /**
        * @WhatsappWebhookCallback(
        * id="vactory_whatsapp_callback_void",
        * fields={
        *     "messages",
        * },
        * label=@Translation("Whtassap Webhook VOID")
        * )
        */
        class WhatsappCallbackVoid extends WhatsappWebhookManagerBase {

          /**
           * {@inheritDoc}
           */
          public function callback(array $change) {
            if (isset($change['value']['messages'])) {
              $message = $change['value']['messages'][0]['text']['body'] ?? NULL;
              $phone_number = $change['value']['messages'][0]['from'] ?? NULL;
              /** @var WhatsappApiManager $whatsapp_manager */
              $whatsapp_manager = \Drupal::service('vactory_whatsapp.api.manager');
              switch ($message) {
                case strtolower(trim($message)) === "activer":
                  $whatsapp_manager->sendTemplateMessage($phone_number, 'menu_list', [], 'fr');
                  break;
                case strtolower(trim($message, '*')) === "quitter":
                  $whatsapp_manager->sendTextMessage($phone_number, 'Good Bye!');
                  break;
              }
            }
          }

        }

6- Run `drush cr` so Drupal could recognize your plugin.

7- Go to `/admin/config/system/vactory-whatsapp-webhook` and enable your webhook plugin.

### Maintainers
Brahim KHOUY <b.khouy@void.fr>

