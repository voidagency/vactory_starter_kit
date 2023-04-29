<?php

namespace Drupal\vactory_event_relance\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Download a webform handler.
 *
 * @WebformHandler(
 *   id = "event_relance_reminder",
 *   label = @Translation("Event relance reminder"),
 *   description = @Translation("Event relance reminder"),
 *   category = @Translation("vactory_event"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ReminderWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [
        'date_field_name' => 'field_vactory_date_interval',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $elements = $this->getWebform()->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $element_key => $element) {
      $options[$element_key] = (isset($element['#title']) ? $element['#title'] : '');
    }
    $form['handler'] = [
      '#type' => 'details',
      '#title' => $this->t('Event reminder settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['handler']['from_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Courriel de l'expéditeur"),
      '#default_value' => $this->configuration['fields']['from_email'],
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
    ];
    $form['handler']['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Nom de l'expéditeur"),
      '#default_value' => $this->configuration['fields']['from_name'],
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
    ];
    $form['handler']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $this->configuration['fields']['subject'],
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
    ];
    $form['handler']['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Default Mail Body'),
      '#format' => 'full_html',
      '#default_value' => $this->configuration['fields']['message']['value'] ?? '',
    ];
    $form['handler']['date_field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event date field machine name'),
      '#default_value' => $this->configuration['fields']['date_field_name'] ?? 'field_vactory_date_interval',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
    $this->configuration['fields']['subject'] = $form_state->getValue('handler')['subject'];
    $this->configuration['fields']['from_email'] = $form_state->getValue('handler')['from_email'];
    $this->configuration['fields']['from_name'] = $form_state->getValue('handler')['from_name'];
    $this->configuration['fields']['message'] = $form_state->getValue('handler')['message'];
    $this->configuration['fields']['date_field_name'] = $form_state->getValue('handler')['date_field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if ($this->configuration['fields']) {
      $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $fields = $this->configuration['fields'] ?? [];
      $webform_data = $webform_submission->toArray(TRUE);
      $event_nid = $webform_data['data']['node_id'];
      $event_langcode = $webform_data['data']['language'] ?? '';
      if (!empty($event_nid)) {
        $node_storage = $this->entityTypeManager->getStorage('node');
        $node = $node_storage->load($event_nid);
        if (!empty($event_langcode) && $event_langcode !== $default_langcode) {
          $node = \Drupal::service('entity.repository')
            ->getTranslationFromContext($node, $event_langcode);
        }
        $event_registration = $node->get('field_vactory_date_interval')->value;
        if ($event_registration) {
          $date_event = strtotime($event_registration);
          $sujbect = $this->replaceTokens($this->replaceTokens($fields['subject'], $webform_submission));
          $message = $this->replaceTokens($this->replaceTokens($fields['message']['value'], $webform_submission));
          $extra_data = [
            'date' => $date_event,
            'interval' => [
              'entity_type' => 'node',
              'entity_id' => $event_nid,
              'reminder_field_name' => 'field_reminder',
            ],
            'subject' => $sujbect,
            'email' => $webform_data['data']['adresse_mail'],
            'message' => MailFormatHelper::htmlToText($message),
            'from' => $fields['from_name'] . ' <' . $fields['from_email'] . '>',
          ];
          $reminder_manager = \Drupal::service('vactory_reminder.queue.manager');
          $reminder_manager->reminderQueuePush(NULL, 'mail', $extra_data);
        }
      }
    }
  }

}
