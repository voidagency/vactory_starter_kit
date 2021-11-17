<?php

namespace Drupal\vactory_quiz\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Quiz Setting Form Class.
 */
class QuizSettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_quiz.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_quiz_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_quiz.settings');
    $form['parallel_correction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer la correction en parallèle'),
      '#description' => $this->t("Si cochée alors la correction de chaque question sera invoquée au moment où l'utilisateur répond à cette question là."),
      '#default_value' => $config->get('parallel_correction'),
    ];
    $form['validate_answer_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Validate question button text'),
      '#default_value' => $config->get('validate_answer_title'),
      '#states' => [
        'visible' => [
          'input[name="parallel_correction"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['show_quiz_correction'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Afficher la correction à la fin du quiz'),
      '#description' => $this->t("Si cochée alors la correction sera afficher à la fin du quiz."),
      '#default_value' => $config->get('show_quiz_correction'),
    ];
    $form['allow_new_attempts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoriser de nouvelles tentatives'),
      '#description' => $this->t("Si cochée alors à la fin de quiz l'utilisateur peut refaire ce quiz là."),
      '#default_value' => $config->get('allow_new_attempts'),
    ];
    $form['allow_new_attempts_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New attempt button text'),
      '#default_value' => $config->get('allow_new_attempts_title'),
      '#states' => [
        'visible' => [
          'input[name="allow_new_attempts"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['next_button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next button text'),
      '#default_value' => $config->get('next_button_title'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('vactory_quiz.settings');
    $config->set('parallel_correction', $form_state->getValue('parallel_correction'))
      ->set('show_quiz_correction', $form_state->getValue('show_quiz_correction'))
      ->set('allow_new_attempts', $form_state->getValue('allow_new_attempts'))
      ->set('validate_answer_title', $form_state->getValue('validate_answer_title'))
      ->set('allow_new_attempts_title', $form_state->getValue('allow_new_attempts_title'))
      ->set('next_button_title', $form_state->getValue('next_button_title'))
      ->save();
    Cache::invalidateTags(['vactory_quiz:settings']);
    parent::submitForm($form, $form_state);
  }

}
