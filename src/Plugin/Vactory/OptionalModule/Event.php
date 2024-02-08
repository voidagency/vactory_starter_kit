<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Events.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_event",
 *   label = @Translation("Event"),
 *   description = @Translation("The event module manages your events content."),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Event extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_event']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The event module manages your events content."),
    ];

    return $form;
  }

}
