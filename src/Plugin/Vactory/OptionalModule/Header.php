<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Header.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_header",
 *   label = @Translation("Header"),
 *   description = @Translation("manages layout : header"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Header extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_header']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("manages layout : header"),
    ];

    return $form;
  }

}
