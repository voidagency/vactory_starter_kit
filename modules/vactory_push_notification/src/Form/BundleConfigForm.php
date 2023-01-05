<?php

namespace Drupal\vactory_push_notification\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure which bundle fields to use in notifications.
 */
class BundleConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * BundleConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $bundle_info
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_push_notification_bundle_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_push_notification.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bundle = NULL) {
    $form = parent::buildForm($form, $form_state);

    $bundles = $this->getBundles();
    if (!isset($bundles[$bundle])) {
      $this->messenger()->addError($this->t('A node bundle %bundle is not found.', [
        '%bundle' => $bundle,
      ]));
      return;
    }
    $config = $this->config('vactory_push_notification.settings');

    $options = [];
    foreach ($this->getBundleFields($bundle) as $id => $field) {
      if ($field instanceof \Drupal\field\FieldConfigInterface) {
        $options[$id] = $field->label();
      }
    }

    $form['body'] = [
      '#type' => 'select',
      '#title' => $this->t('Body field'),
      '#description' => $this->t('This field will be used as a notification body.'),
      '#options' => $options,
      '#default_value' => $config->get("fields.$bundle.body"),
      '#empty_value' => '',
    ];

    $form['icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon field'),
      '#description' => $this->t('This field will be used as a notification icon.'),
      '#options' => $options,
      '#default_value' => $config->get("fields.$bundle.icon"),
      '#empty_value' => '',
    ];

    $form['bundle'] = [
      '#type' => 'value',
      '#value' => $bundle,
    ];

    $destination = $this->getRequest()->get('destination');

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#href' => $destination ?? Url::fromRoute('vactory_push_notification.settings')->toString(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundle = $form_state->getValue('bundle');
    $this->config('vactory_push_notification.settings')
      ->set("fields.$bundle.body", $form_state->getValue('body'))
      ->set("fields.$bundle.icon", $form_state->getValue('icon'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * Returns a list of the bundle fields.
   *
   * @param string $bundle
   *   The node bundle name.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions for the bundle, keyed by field name.
   */
  protected function getBundleFields($bundle) {
    return $this->entityFieldManager->getFieldDefinitions('node', $bundle);
  }

  /**
   * Returns a list of node bundles.
   *
   * @return array
   *   The list of node bundles.
   */
  protected function getBundles() {
    return $this->bundleInfo->getBundleInfo('node');
  }

}
