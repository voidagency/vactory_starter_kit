<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Mediatheque.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_mediatheque",
 *   label = @Translation("Mediatheque"),
 *   description = @Translation("The mediatheque module adds a content model and default content"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Mediatheque extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_mediatheque']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The mediatheque module adds a content model and default content"),
    ];

    return $form;
  }

}
