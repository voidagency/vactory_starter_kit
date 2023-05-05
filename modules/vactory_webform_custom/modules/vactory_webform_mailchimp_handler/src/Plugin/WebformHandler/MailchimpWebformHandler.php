<?php

namespace Drupal\vactory_webform_mailchimp_handler\Plugin\WebformHandler;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\RequestException;
use MailchimpMarketing;

/**
 * MailChimp Webform handler.
 *
 * @WebformHandler(
 *   id = "vactory_mailchimp_webform_handler",
 *   label = @Translation("Vactory Mailchimp Webform Handler"),
 *   category = @Translation("Transaction"),
 *   description = @Translation("Sends the submission data to Mailchimp."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class MailchimpWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'api_key' => '',
      'server_prefix' => '',
      'list_id' => '',
      'email' => '',
      'name' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $this->applyFormStateToConfiguration($form_state);

    // Define Ajax-callback.
    $ajax = [
      'callback' => [get_class($this), 'ajaxCallback'],
      'wrapper' => 'mailchimp-webform-handler--api-settings',
    ];

    // API settings.
    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mailchimp API settings'),
      '#attributes' => ['id' => 'mailchimp-webform-handler--api-settings'],
    ];

    $form['api_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailchimp API-key'),
      '#description' => $this->t('You can find your API-key in your Mailchimp Account Settings'),
      '#default_value' => $this->configuration['api_key'],
    ];

    $form['api_settings']['server_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailchimp server prefix'),
      '#description' => $this->t('To find this parameter, log into your Mailchimp account and look at the URL in your browser. You’ll see something like https://us19.admin.mailchimp.com/ — the us19 part is the server prefix. Note that your specific value may be different.'),
      '#default_value' => $this->configuration['server_prefix'],
    ];

    $form['api_settings']['update_lists'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Mailchimp lists'),
      '#ajax' => $ajax,
      '#submit' => [[get_class($this), 'updateConfigSubmit']],
    ];

    $form['api_settings']['list_id'] = [
      '#type' => 'select',
      '#title' => $this->t('List'),
      '#empty_option' => $this->t('- Select an List -'),
      '#default_value' => $this->configuration['list_id'],
      '#options' => $this->getLists(),
      '#ajax' => $ajax,
      '#description' => $this->t('Select the list you want to send this submission to.'),
    ];

    // Mailchimp fields if lists found.
    if (!empty($this->getLists())) {

      if (isset($this->configuration['list_id'])) {
        // Get webform elements as base for mapping.
        $elements = $this->webform->getElementsDecodedAndFlattened();
        foreach($elements as $key => $element) {
          if(!isset($element['#title']) || in_array($element['#type'], ['webform_actions'])) {
            unset($elements[$key]);
          }
        }
        $options = [];
        foreach($elements as $key => $element) {
          $options[$key] = $element['#title'];
        }

        // Email is the only required in Mailchimp.
        $form['api_settings']['fields']['email'] = [
          '#type' => 'select',
          '#title' => $this->t('Email'),
          '#options' => $options,
          '#required' => TRUE,
          '#default_value' => $this->configuration['email'],
        ];

        // Get the merge fields for the extra field selections.
        $merge_fields = $this->getMergeFields($this->configuration['list_id']);

        foreach ($merge_fields as $mailchimp_key => $mailchimp_name) {
          $form['api_settings']['fields'][$mailchimp_key] = [
            '#type' => 'select',
            '#title' => $mailchimp_name,
            '#options' => ['' => ''] + $options,
            '#default_value' => $this->configuration[$mailchimp_key],
          ];
        }

        if (!empty($this->configuration['list_id'])) {
          // Get interests if exists.
          $interests = $this->getInterests();
          foreach ($interests as $interest) {
            $cid = $interest['category_id'];
            $form['api_settings']['interests'][$cid] = [
              '#type' => 'fieldset',
              '#title' => $interest['category'],
            ];
            foreach ($interest['interests'] as $interest_group) {
              $iid = $interest_group['id'];
              $form['api_settings']['interests'][$cid][$iid] = [
                '#type' => 'select',
                '#title' => $interest_group['name'],
                '#options' => ['' => ''] + $options,
                '#default_value' => $this->configuration[$iid],
              ];
            }
          }
        }
      }
    }

    return $this->setSettingsParents($form);
  }

  /**
   * Submit callback for the refresh button.
   */
  public function updateConfigSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Get interests.
   */
  public function getInterests() {
    $client = new MailchimpMarketing\ApiClient();
    $client->setConfig([
      'apiKey' => $this->configuration['api_key'],
      'server' => $this->configuration['server_prefix'],
    ]);
    $results = [];
    try {
      $interests_categories = $client->lists->getListInterestCategories($this->configuration['list_id']);
      if (!empty($interests_categories->categories)) {
        foreach ($interests_categories->categories as $index => $category)
        $interests = $client->lists->listInterestCategoryInterests($this->configuration['list_id'], $category->id);
        $interests = $interests->interests;
        if (!empty($interests)) {
          $results[$index]['category'] = $category->title;
          $results[$index]['category_id'] = $category->id;
          foreach ($interests as $interest) {
            $results[$index]['interests'][] = [
              'id' => $interest->id,
              'name' => $interest->name,
            ];
          }
        }
      }
    }
    catch (RequestException $request_exception) {
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['api_key'] = $form_state->getValue('api_key');
    $this->configuration['server_prefix'] = $form_state->getValue('server_prefix');
    $this->configuration['list_id'] = $form_state->getValue('list_id');
    $this->configuration['email'] = $form_state->getValue('email');

    // Save configuration for extra MERGE fields.
    $merge_fields = $this->getMergeFields($this->configuration['list_id']);

    foreach ($merge_fields as $mailchimp_key => $mailchimp_name) {
      $this->configuration[$mailchimp_key] = $form_state->getValues()['api_settings']['fields'][$mailchimp_key];
    }

    if (!empty($this->configuration['list_id'])) {
      $interests = $this->getInterests();
      foreach ($interests as $interest) {
        $cid = $interest['category_id'];
        foreach ($interest['interests'] as $interest_group) {
          $iid = $interest_group['id'];
          $this->configuration[$iid] = $form_state->getValues()['api_settings']['interests'][$cid][$iid];;
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state,  WebformSubmissionInterface $webform_submission) {
    // Get the submission data.
    $values = $webform_submission->getData();

    // Post to Mailchimp.
    $client = new MailchimpMarketing\ApiClient();
    $client->setConfig([
      'apiKey' => $this->configuration['api_key'],
      'server' => $this->configuration['server_prefix'],
    ]);

    $data = [
      'email_address' => $values[$this->configuration['email']],
      'status' => 'subscribed',
    ];

    // Add the extra fields.
    $merge_fields = $this->getMergeFields($this->configuration['list_id']);
    foreach ($merge_fields as $mailchimp_key => $mailchimp_name) {
      if (isset($this->configuration[$mailchimp_key])
          && isset($values[$this->configuration[$mailchimp_key]])
          && !is_null($values[$this->configuration[$mailchimp_key]])) {
        $data['merge_fields'][$mailchimp_key] = $values[$this->configuration[$mailchimp_key]];
      }
    }

    $interests = $this->getInterests();
    foreach ($interests as $interest) {
      foreach ($interest['interests'] as $interest_group) {
        $iid = $interest_group['id'];
        if (isset($this->configuration[$iid])
          && isset($values[$this->configuration[$iid]])
          && !is_null($values[$this->configuration[$iid]])
        ) {
          $data['interests'][$iid] = boolval($values[$this->configuration[$iid]]);
        }
      }
    }

    try {
      $response = $client->lists->addListMember($this->configuration['list_id'], $data);
    }
    catch (RequestException $request_exception) {
      \Drupal::logger('vactory_webform_mailchimp')->error($request_exception->getMessage());
    }

  }

  /**
   * Get Mailchimp lists.
   */
  protected function getLists() {
    $lists = [];

    if (isset($this->configuration['api_key']) && isset($this->configuration['server_prefix'])) {
      $client = new MailchimpMarketing\ApiClient();
      $client->setConfig([
        'apiKey' => $this->configuration['api_key'],
        'server' => $this->configuration['server_prefix']
      ]);

      try {
        // By default it fetchs 10 lists, now we set the count to 900.
        $response = $client->lists->getAllLists(NULL, NULL, 900);
      }
      catch (RequestException $request_exception) {
      }

      if (isset($response->total_items) && $response->total_items > 0) {
        foreach ($response->lists as $list) {
          $lists[$list->id] = $list->name;
        }
      }
    }

    return $lists;
  }

  /**
   * Get the merge fields for a specific list.
   *
   * @param $list_id
   */
  protected function getMergeFields($list_id) {
    $merge_fields = [];

    $client = new MailchimpMarketing\ApiClient();
    $client->setConfig([
      'apiKey' => $this->configuration['api_key'],
      'server' => $this->configuration['server_prefix']
    ]);

    try {
      $response = $client->lists->getListMergeFields($list_id);
    }
    catch (RequestException $request_exception) {
    }

    foreach ($response->merge_fields as $merge_field) {
      $merge_fields[$merge_field->tag] = $merge_field->name;
    }

    return $merge_fields;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing entity reference details element.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return NestedArray::getValue($form, ['settings', 'api_settings']);
  }

}
