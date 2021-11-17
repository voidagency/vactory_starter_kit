<?php

namespace Drupal\vactory_datalayer_handler\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Download a webform handler.
 *
 * @WebformHandler(
 *   id = "vactory_datalayer_handler",
 *   label = @Translation("DataLayer"),
 *   category = @Translation("GTM"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DataLayerWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
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

    $form['fields'] = [
      '#type' => 'webform_multiple',
      '#title' => $this->t('Fields'),
      '#empty_items' => 0,
      '#no_items_message' => $this->t('No fields entered. Please add fields below.'),
      '#add' => FALSE,
      '#element' => [
        'key' => [
          '#type' => 'textfield',
          '#title' => $this->t('Key'),
          '#description' => $this->t('The key to be sent in the json.'),
          '#required' => TRUE,
        ],
        'value' => [
          '#type' => 'webform_select_other',
          '#title' => $this->t('Field'),
          '#description' => $this->t('Select the field to add for the selected key. You select can "Other..." to enter a customized field value.'),
          '#options' => $options,
          '#other__type' => 'textfield',
        ],
        'raw' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Raw Value'),
          '#description' => $this->t('If selected the option value from the form build will be sent.'),
        ],
      ],
      '#default_value' => $this->configuration['fields'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $sid = $webform_submission->id();
    $layerDataAttributes = [];
    $layerDataAttributes['sid'] = str_replace('=', '', base64_encode($sid));
    // Display message.
    if ($this->configuration['fields']) {
      $fields = $this->configuration['fields'] ?? [];
      $webform_data = $webform_submission->toArray(TRUE);
      $data = $webform_data['data'] ?? [];

      foreach ($fields as $field) {
        $key = $field['key'];
        $value = $field['value'];
        $is_raw = (bool) $field['raw'];
        $layerDataAttributes[$key] = $value;

        if (isset($data[$value])) {
          if ($is_raw) {
            $layerDataAttributes[$key] = $data[$value];
          }
          else {
            $layerDataAttributes[$key] = $this->replaceTokens('[webform_submission:values:' . $value . ':htmldecode]', $webform_submission);
          }
        }

      }
    }
    if (!empty($layerDataAttributes)) {
      $build = [
        '#children' => '<script>dataLayer = [' . json_encode($layerDataAttributes) . ']; document.querySelector(".messages > script").parentNode.style.display = \'none\';</script>',
      ];

      $this->messenger()->addMessage(\Drupal::service('renderer')->renderPlain($build), 'success');
    }
  }

}
