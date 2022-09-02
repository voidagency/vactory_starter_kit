<?php

namespace Drupal\vactory_whatsapp_example\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_whatsapp\Exceptions\WhatsappApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vactory Whatsapp Test form
 */
class VactoryWhatsappTestForm extends FormBase {

  /**
   * Whatsapp API manager service.
   *
   * @var \WhatsappApiManagerInterface
   */
  protected $whatsappApiManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->whatsappApiManager = $container->get('vactory_whatsapp.api.manager');
    $instance->configFactory = $container->get('config.factory');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_whatsapp_test_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination phone number'),
      '#required' => TRUE,
    ];
    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => [
        'template' => $this->t('Template message'),
        'text' => $this->t('Text message'),
      ],
      '#empty_option' => '- Select -',
      '#required' => TRUE,
    ];
    $form['template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template name'),
      '#description' => 'Default to the selected template name within module config form',
      '#states' => [
        'visible' => [
          '[name="method"]' => ['value' => 'template'],
        ],
      ],
    ];
    $form['template_params'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template params (if exist)'),
      '#description' => 'Enter template params in json format see components section on: https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates#text-based',
      '#states' => [
        'visible' => [
          '[name="method"]' => ['value' => 'template'],
        ],
      ],
    ];
    $form['message_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message text'),
      '#description' => 'Enter any text here this will be sent as simple whatsapp message',
      '#states' => [
        'visible' => [
          '[name="method"]' => ['value' => 'text'],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $to = $form_state->getValue('to');
    $method = $form_state->getValue('method');
    $template_id = $form_state->getValue('template_id');
    $template_params = $form_state->getValue('template_params');
    $message_text = $form_state->getValue('message_text');
    $state = \Drupal::state();
    if ($method === 'template') {
      $template_params = Json::decode($template_params);
      $template_params = $template_params ? $template_params : [];
      $template_id = empty($template_id) ? $state->get('vactory_whatsapp_template_id') : $template_id;
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      try {
        // Send template message Case.
        $this->whatsappApiManager->sendTemplateMessage($to, $template_id, $template_params, $langcode);

      }
      catch (WhatsappApiException $e) {
        $form_state->setError($form, $e->getMessage());
      }
    }
    else {
      if (empty($message_text)) {
        $form_state->setErrorByName('message_text', $this->t('Message text should not be empty'));
        return;
      }
      try {
        // Send text message Case.
        $this->whatsappApiManager->sendTextMessage($to, $message_text);
      }
      catch (WhatsappApiException $e) {
        $form_state->setError($form, $e->getMessage());
      }
    }
  }

/**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->addStatus($this->t('Whatsapp message has been sent successfully'));
  }

}
