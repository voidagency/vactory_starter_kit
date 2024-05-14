<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Publication.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_publication",
 *   label = @Translation("Publication"),
 *   description = @Translation("The publication module manages your publications content."),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Publication extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_publication']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The publication module manages your publications content."),
    ];

    return $form;
  }

}
