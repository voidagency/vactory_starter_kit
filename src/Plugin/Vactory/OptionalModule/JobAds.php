<?php

namespace Drupal\vactory_starter_kit\Plugin\Vactory\OptionalModule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Job Ads.
 *
 * @VactoryOptionalModule(
 *   id = "vactory_job_ads",
 *   label = @Translation("Job Ads"),
 *   description = @Translation("The Job ads module manage your job ads content."),
 *   type = "module",
 *   weight = 10,
 *   standardlyEnabled = false,
 * )
 */
class JobAds extends AbstractOptionalModule {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['vactory_job_ads']['project_info'] = [
      '#type' => 'item',
      '#description' => $this->t("The Job ads module manage your job ads content."),
    ];

    return $form;
  }

}
