# Vactory decoupled revalidator.

Clear your Next.js site redis cache when content on drupal is created, updated or deleted.

## Configure :

Visit 

    /admin/structure/revalidator-entity-type

To enable on-demand revalidation for an entity type, we need to configure a revalidator plugin.

Click Configure entity type or Edit an existing one

Select Path/Bundles as the revalidator Plugin

Configure the plugin

And save.

 
## Custom revalidator plugin :

In your custom module, add a new plugin file *MyCustomRevalidator* 

    src/Plugin/Revalidator/MyCustomRevalidator.php
    
Follow the example bellow :
    
    <?php
    
    namespace Drupal\your_custom_module\Plugin\Revalidator;
    
    use Drupal\Core\Form\FormStateInterface;
    use Drupal\vactory_decoupled_revalidator\ConfigurableRevalidatorBase;
    use Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface;
    use Drupal\vactory_decoupled_revalidator\RevalidatorInterface;
    
    /**
     * Plugin implementation of the revalidator.
     *
     * @Revalidator(
     *   id = "plugin_id",
     *   label = @Translation("plugin label"),
     *   description = @Translation("plugin description.")
     * )
     */
    class MyCustomRevalidator extends ConfigurableRevalidatorBase implements RevalidatorInterface {
      
      public function defaultConfiguration() {
        return [
          'test' => [],
        ];
      }
      
      /**
       * Configuration form.
       **/  
      public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form['test'] = [
          '#type' => 'textfield',
          '#title' => t('test'),
          '#default_value' => $this->configuration['test'],
        ];
    
        return $form;
      }
      
      /**
       * Save the config.
      **/  
      public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        $this->configuration['test'] = $form_state->getValue('test');
      }
      
      /**
       * Your logic to revalidate the front cache.
       **/ 
      public function revalidate(EntityRevalidateEventInterface $event): bool {
        
        // ...
        return TRUE;
      }
    }


## Maintainers
BOUHOUCH Khalid
<k.bouhouch@void.fr>