<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Glossary.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_glossary",
 *   label = @Translation("Glossary"),
 *   description = @Translation("The Glossary module adds a content model and default content"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class Glossary extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_glossary']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The glossary module adds a content model and default content"),
    ];

    return $form;
  }

}
