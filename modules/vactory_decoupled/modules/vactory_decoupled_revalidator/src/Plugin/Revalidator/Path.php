<?php

namespace Drupal\vactory_decoupled_revalidator\Plugin\Revalidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_decoupled_revalidator\ConfigurableRevalidatorBase;
use Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface;
use Drupal\vactory_decoupled_revalidator\RevalidatorInterface;

/**
 * Plugin implementation of the revalidator.
 *
 * @Revalidator(
 *   id = "path",
 *   label = @Translation("Path"),
 *   description = @Translation("Revalidator using paths.")
 * )
 */
class Path extends ConfigurableRevalidatorBase implements RevalidatorInterface {

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return [
      'additional_paths' => [],
    ];
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['additional_paths'] = [
      '#type' => 'textarea',
      '#title' => t('Paths to revalidate'),
      '#default_value' => $this->configuration['additional_paths'],
      '#description' => t('Paths to revalidate on entity add/update/delete. Enter one path per line. Example %example.', [
        '%example' => '/homepage',
      ]),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['additional_paths'] = $form_state->getValue('additional_paths');
  }

  /**
   * Revalidates an entity.
   *
   * @return bool
   *   TRUE if the entity was revalidated. FALSE otherwise.
   */
  public function revalidate(EntityRevalidateEventInterface $event): bool {
    $revalidated = FALSE;

    $paths = [];
    if (!empty($this->configuration['additional_paths'])) {
      $paths = array_filter(array_map('trim', explode("\n", $this->configuration['additional_paths'])));
    }
    if (!count($paths)) {
      return $revalidated;
    }

    try {
      clear_next_cache([
        'slugs' => $paths,
        'invalidate' => 'slugs',
      ]);
      $revalidated = TRUE;
    } catch (\Exception $exception) {

    }

    return $revalidated;
  }
}
