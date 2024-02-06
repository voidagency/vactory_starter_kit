<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * News.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_news",
 *   label = @Translation("News"),
 *   description = @Translation("The news module adds a content model and default content"),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class News extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_news']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The news module adds a content model and default content"),
    ];

    return $form;
  }

}
