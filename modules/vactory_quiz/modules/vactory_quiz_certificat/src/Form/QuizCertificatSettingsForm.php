<?php

namespace Drupal\vactory_quiz_certificat\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Quiz Setting Form Class.
 */
class QuizCertificatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_quiz_certificat.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_quiz_certificat_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_quiz_certificat.settings');
    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Certificate Generate method'),
      '#options' => [
        'browser_print' => $this->t('Using browser print button'),
        'html2pdf' => $this->t('Using Vactory HTML2PDF (mpdf php library)'),
      ],
      '#default_value' => $config->get('method'),
    ];
    $form['html2pdf_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#states' => [
        'visible' => [
          'input[name="method"]' => ['value' => 'html2pdf'],
        ],
      ],
    ];
    $form['html2pdf_container']['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Document orientation'),
      '#options' => [
        'default' => $this->t('Default'),
        'landscape' => $this->t('Landscape (Paysage)'),
      ],
      '#default_value' => $config->get('orientation'),
    ];
    $form['html2pdf_container']['enable_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email notification when certificat is generated'),
      '#default_value' => $config->get('enable_email'),
    ];
    $form['html2pdf_container']['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('email_subject'),
      '#states' => [
        'visible' => [
          'input[name="enable_email"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Supports tokens'),
    ];
    $form['html2pdf_container']['email_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#default_value' => !empty($config->get('email_body')) ? $config->get('email_body')['value'] : '',
      '#format' => !empty($config->get('email_body')) ? $config->get('email_body')['format'] : 'basic_html',
      '#states' => [
        'visible' => [
          'input[name="enable_email"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Supports tokens'),
    ];

    $form['certificat_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Certificat body'),
      '#default_value' => !empty($config->get('certificat_body')) ? $config->get('certificat_body')['value'] : '',
      '#format' => !empty($config->get('certificat_body')) ? $config->get('certificat_body')['format'] : 'basic_html',
    ];
    $form['token_tree'] = $this->getTokenTree();
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $enable_email = $form_state->getValue('enable_email');
    if ($enable_email) {
      $email_subject = $form_state->getValue('email_subject');
      $email_body = $form_state->getValue('email_body')['value'];
      if (empty($email_body)) {
        $form_state->setErrorByName('email_body', $this->t('Email body field is required'));
      }
      if (empty($email_subject)) {
        $form_state->setErrorByName('email_subject', $this->t('Email subject field is required'));
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('vactory_quiz_certificat.settings');
    $config->set('certificat_body', $form_state->getValue('certificat_body'))
      ->set('orientation', $form_state->getValue('orientation'))
      ->set('method', $form_state->getValue('method'))
      ->set('enable_email', $form_state->getValue('enable_email'))
      ->set('email_subject', $form_state->getValue('email_subject'))
      ->set('email_body', $form_state->getValue('email_body'))
      ->save();
    Cache::invalidateTags(['vactory_quiz:settings']);
    parent::submitForm($form, $form_state);
  }

  /**
   * Function providing the site token tree link.
   */
  public function getTokenTree() {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];
    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($token_tree),
    ];
  }

}
