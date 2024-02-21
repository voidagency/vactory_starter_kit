<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Contact.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_contact",
 *   label = @Translation("Contact"),
 *   description = @Translation("Creates page with contact form"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Contact extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_contact']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("Creates page with contact form"),
    ];

    return $form;
  }

}
