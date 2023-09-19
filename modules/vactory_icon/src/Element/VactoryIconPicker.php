<?php

namespace Drupal\vactory_icon\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an example element.
 *
 * @FormElement("vactory_icon_picker")
 */
class VactoryIconPicker extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processVactoryIconPicker'],
      ],
      '#theme' => 'select',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function processVactoryIconPicker(&$element, FormStateInterface $form_state, &$form) {
    $config = \Drupal::config('vactory_icon.settings');
    $provider_plugin = $config->get('provider_plugin');
    $element['#type'] = 'select';
    $element['#multiple'] = FALSE;
    $element['#attributes'] = [
      'class' => ['vactory--icon-picker'],
    ];
    $element['#options'] = [];
    $element['#options'][''] = '';
    $icon_provider_plugin_manager = \Drupal::service('plugin.manager.vactory_icon');
    $icon_provider = $icon_provider_plugin_manager->createInstance($provider_plugin);
    // Allow icon provider to alter the form element.
    $icon_provider->iconPickerFormElementAlter($element, $config);
    // Workaround for setting the default selected value.
    $element['#value'] = $element['#default_value'];
    $element['#attached']['library'][] = 'vactory_icon/vactory_icon.fonticonpicker';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element['#validated'] = TRUE;
    return str_replace('icon-', '', $input);
  }

}
