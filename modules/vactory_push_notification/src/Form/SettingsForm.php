<?php

namespace Drupal\vactory_push_notification\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\vactory_push_notification\KeysHelper;
use Drupal\vactory_push_notification\TTL;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Push Notification settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\vactory_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The Push TTL converter.
   *
   * @var \Drupal\vactory_push_notification\TTL
   */
  protected $ttl;

  /**
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\vactory_push_notification\KeysHelper $keys_helper
   *   The push keys helper service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\vactory_push_notification\TTL $ttl
   *   The push TTL converter.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
   *   The redirect destination.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    KeysHelper $keys_helper,
    EntityTypeBundleInfoInterface $bundle_info,
    TTL $ttl,
    RedirectDestinationInterface $redirectDestination,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    parent::__construct($config_factory);
    $this->keysHelper = $keys_helper;
    $this->bundleInfo = $bundle_info;
    $this->ttl = $ttl;
    $this->redirectDestination = $redirectDestination;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('vactory_push_notification.keys_helper'),
      $container->get('entity_type.bundle.info'),
      $container->get('vactory_push_notification.ttl'),
      $container->get('redirect.destination'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_push_notification_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_push_notification.settings'
    ];
  }

  /**
   * Returns a list of node bundles.
   *
   * @return array
   *  The list of node bundles.
   */
  protected function getNodeBundles() {
    return $this->bundleInfo->getBundleInfo('node');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_push_notification.settings');
    $is_keys_defined = $this->keysHelper->isKeysDefined();

    $form['auth'] = [
      '#type' => 'details',
      '#open' => !$is_keys_defined, // Open when no keys, close when keys exist.
      '#title' => $this->t('Auth parameters'),
    ];
    $form['auth']['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#default_value' => $this->keysHelper->getPublicKey(),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];
    $form['auth']['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $this->keysHelper->getPrivateKey(),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];
    $form['auth']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t($is_keys_defined ? 'Regenerate keys' : 'Generate keys'),
      '#limit_validation_errors' => [], // Skip required fields validation.
    ];
    $form['auth']['generate']['#submit'] = $is_keys_defined ?
      ['::regenerateKeys'] : ['::generateKeys'];

    $form['content'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Posting a new content of the enabled type will send a notification.'),
    ];
    $form['content']['bundles'] = $this->buildBundlesForm();

    $form['config'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Configuration'),
    ];
    $form['config']['push_ttl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Push TTL'),
      '#description' => $this->t('TTL is how long a push message is retained by the push service in case the user browser is not yet accessible. You may want to use a very long time for important notifications. Notifications with TTL equals 0 will be delivered only if the user is currently connected. Please use the following modificator: "h" for hours, "d" for days, default value is minutes.'),
      '#default_value' => $config->get('push_ttl') ?: '30m',
    ];
    $form['config']['queue_batch_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Queue batch size'),
      '#description' => $this->t('How many number of notifications to send during the queue process.'),
      '#default_value' => $config->get('queue_batch_size') ?: 100,
      '#required' => TRUE,
    ];
    $form['config']['body_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max body length'),
      '#description' => $this->t('Before sending a notification html tags will be deleted and the body field trimmed to the specified length.'),
      '#default_value' => $config->get('body_length') ?: 100,
    ];

    return $form;
  }

  /**
   * Builds bundles form section.
   *
   * @return array
   */
  protected function buildBundlesForm() {
    $form = [
      '#type' => 'table',
      '#tableselect' => TRUE,
      '#default_value' => [],
      '#header' => [
        [
          'data' => $this->t('Content type'),
          'class' => ['bundle'],
        ],
        [
          'data' => $this->t('Body field'),
          'class' => ['body_field'],
        ],
        [
          'data' => $this->t('Image field'),
          'class' => ['image_field'],
        ],
        [
          'data' => $this->t('Settings'),
          'class' => ['operations'],
        ],
      ],
      '#empty' => $this->t('No content types available.'),
    ];

    $config = $this->config('vactory_push_notification.settings');

    foreach ($this->getNodeBundles() as $id => $info) {

      // Get human readable bundle field labels.
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions('node', $id);
      $fields = $config->get("fields.$id");
      $body_field = $fields['body'] ?? '';
      if ($body_field && isset($bundle_fields[$body_field])) {
        $body_field = $bundle_fields[$body_field]->getLabel();
      }
      $image_field = $fields['icon'] ?? '';
      if ($image_field && isset($bundle_fields[$image_field])) {
        $image_field = $bundle_fields[$image_field]->getLabel();
      }

      $form[$id] = [
        'bundle' => [
          '#markup' => $info['label'],
        ],
        'body_field' => [
          '#markup' => $body_field,
        ],
        'image_field' => [
          '#markup' => $image_field,
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'configure' => [
              'title' => $this->t('Configure'),
              'url' => Url::fromRoute('vactory_push_notification.bundle_configure', [
                'bundle' => $id,
              ]),
              'query' => $this->redirectDestination->getAsArray(),
            ],
          ],
        ],
      ];

      $form['#default_value'][$id] = $config->get("bundles.$id");
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the 'batch size' parameter.
    $val = $form_state->getValue('queue_batch_size');
    if (!($val >= 1 && $val <= 1000)) {
      $form_state->setErrorByName('queue_batch_size', $this->t('Queue batch size must be in range 1..1000 inclusively.'));
    }

    // Validate the 'body length' parameter.
    $val = $form_state->getValue('body_length');
    if (!($val >= 10 && $val <= 1000)) {
      $form_state->setErrorByName('body_length', $this->t('Body length must be in range 10..100 inclusively.'));
    }

    // Validate the 'push ttl' parameter.
    $val = $form_state->getValue('push_ttl');
    if (!$this->ttl->validate($val)) {
      $form_state->setErrorByName('push_ttl', $this->t('Incorrent TTL value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_push_notification.settings');

    // Save the keys.
    $public_key = $form_state->getValue('public_key');
    $private_key = $form_state->getValue('private_key');
    if ($public_key != $this->keysHelper->getPublicKey() ||
          $private_key != $this->keysHelper->getPrivateKey()) {
      $this->keysHelper->setKeys($public_key, $private_key)->save();
    }

    $config
      ->set('queue_batch_size', $form_state->getValue('queue_batch_size'))
      ->set('body_length', $form_state->getValue('body_length'))
      ->set('push_ttl', $form_state->getValue('push_ttl'))
      ->set('bundles', $form_state->getValue('bundles'))
      ->save();

    $this->messenger()->addStatus($this->t('Push notification settings have been updated.'));
  }

  /**
   * Form submit callback for keys generation.
   */
  public function generateKeys(array &$form, FormStateInterface $form_state) {
    $this->keysHelper
      ->generateKeys()
      ->save();
    $this->messenger()->addStatus($this->t('Public and private keys have been generated.'));
  }

  /**
   * Form submit callback for confirm keys regeneration.
   */
  public function regenerateKeys(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('vactory_push_notification.regenerate_keys');
  }

}
