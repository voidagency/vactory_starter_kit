<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * News.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_academy",
 *   label = @Translation("Academy"),
 *   description = @Translation("The academy module adds a content model and default content"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Academy extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_academy']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The cademy module adds a content model and default content"),
    ];

    return $form;
  }

}
