<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * FAQ.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_faq",
 *   label = @Translation("FAQ"),
 *   description = @Translation("The FAQ module adds a content model and default content"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class FAQ extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_faq']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The FAQ module adds a content model and default content"),
    ];

    return $form;
  }

}
