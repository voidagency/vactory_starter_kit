<?php

namespace Drupal\vactory_decoupled_revalidator\Plugin\Revalidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\vactory_decoupled_revalidator\ConfigurableRevalidatorBase;
use Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface;
use Drupal\vactory_decoupled_revalidator\RevalidatorInterface;

/**
 * Plugin implementation of the revalidator.
 *
 * @Revalidator(
 *   id = "revalidator_nodes_bundles",
 *   label = @Translation("Content Types"),
 *   description = @Translation("Revalidator using node bundle")
 * )
 */
class RevalidatorNodeBundle extends ConfigurableRevalidatorBase implements RevalidatorInterface {

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return [
      'bundles' => [],
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
    $node_types = NodeType::loadMultiple();
    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['bundles'] = [
      '#type' => 'select',
      '#title' => t('Bundles'),
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['bundles'],
      '#options' => $options ?? [],
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
    $this->configuration['bundles'] = $form_state->getValue('bundles');
  }

  /**
   * Revalidates an entity.
   *
   * @return bool
   *   TRUE if the entity was revalidated. FALSE otherwise.
   */
  public function revalidate(EntityRevalidateEventInterface $event): bool {
    $revalidated = FALSE;


    if (empty($this->configuration['bundles'])) {
      return $revalidated;
    }

    try {
      clear_next_cache([
        'bundles' => array_values($this->configuration['bundles']),
        'invalidate' => 'bundles',
      ]);
      $revalidated = TRUE;
    } catch (\Exception $exception) {

    }

    return $revalidated;
  }
}
