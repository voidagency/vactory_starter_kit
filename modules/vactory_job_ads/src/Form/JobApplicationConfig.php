<?php

namespace Drupal\vactory_job_ads\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class JobApplicationConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      "vactory_job_ads.settings",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "job_application_config";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_job_ads.admin_settings');
    $candidate_mode = $config->get('vactory_job_ads_candidate_mode');

    $form['vactory_job_ads_candidate_mode'] = [
      '#title'       => t('Mode de candidature'),
      '#description' => t('Choisissez le mode de candidature depuis la liste.'),
      '#type'        => 'select',
      '#options'     => [
        VACTORY_JOB_ADS_CANDIDATE_VIA_EMAIL      => 'Candidature via email',
        VACTORY_JOB_ADS_CANDIDATE_VIA_FORM       => 'Candidature via formulaire de candidature',
        VACTORY_JOB_ADS_CANDIDATE_VIA_USER_SPACE => 'Candidature via espace utilisateur',
      ],
      '#default_value' => $candidate_mode,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


    $this->configFactory->getEditable('vactory_job_ads.admin_settings')
      ->set('vactory_job_ads_candidate_mode', $form_state->getValue('vactory_job_ads_candidate_mode'))
      ->save();


    parent::submitForm($form, $form_state);
  }

}
