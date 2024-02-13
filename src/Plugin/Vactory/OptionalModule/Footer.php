<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Footer.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_footer",
 *   label = @Translation("Footer"),
 *   description = @Translation("manages layout : footer"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Footer extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_footer']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("manages layout : footer"),
    ];

    return $form;
  }

}
