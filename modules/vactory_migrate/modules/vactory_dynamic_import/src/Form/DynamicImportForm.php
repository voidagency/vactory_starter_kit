<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\vactory_dynamic_import\Service\DynamicImportHelpers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the dynamic import add and edit forms.
 */
class DynamicImportForm extends EntityForm {

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Dynamic import helper.
   *
   * @var \Drupal\vactory_dynamic_import\Service\DynamicImportHelpers
   */
  protected $dynamicImportHelper;

  /**
   * Submitted values.
   *
   * @var array
   */
  protected $submitted = [];

  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, DynamicImportHelpers $dynamicImportHelper) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->dynamicImportHelper = $dynamicImportHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('vactory_dynamic_import.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the Dynamic import."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, fn($entity_type) => $entity_type instanceof ContentEntityType);
    $entity_types = array_map(fn($entity_type) => $entity_type->getLabel(), $entity_types);
    $form['target_entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Targeted entity type'),
      '#options' => $entity_types,
      '#empty_option' => '- Select -',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bundlesCallback',
        'wrapper' => 'bundles-container',
      ],
      '#description' => $this->t('Select the destination content type'),
      '#default_value' => $entity->get('target_entity'),
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundles-container'],
    ];

    if ((isset($this->submitted['target_entity']) && !empty($this->submitted['target_entity'])) || !$entity->isNew()) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->submitted['target_entity'] ?? $entity->get('target_entity'));
      $bundles = array_map(fn($bundle) => $bundle['label'], $bundles);
      $form['container']['target_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Targeted bundle'),
        '#options' => $bundles,
        '#empty_option' => '- Select -',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::bundlesCallback',
          'wrapper' => 'bundles-container',
        ],
        '#description' => $this->t('Select the targeted bundle'),
        '#default_value' => $entity->get('target_bundle'),
      ];
      if ((isset($this->submitted['target_bundle']) && !empty($this->submitted['target_bundle'])) || !$entity->isNew()) {
        $form['container']['concered_fields'] = [
          '#type' => 'checkboxes',
          '#title' => t('Concerned fields'),
          '#options' => $entity->isNew() ?
            $this->dynamicImportHelper->getRelatedFields($this->submitted['target_entity'], $this->submitted['target_bundle'], TRUE)
            : $this->dynamicImportHelper->getRelatedFields($entity->get('target_entity'), $entity->get('target_bundle'), TRUE),
          '#default_value' => $entity->get('concered_fields'),
        ];

        $form['container']['is_translation'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('This is a translation'),
          '#description' => $this->t("For translations of existing content, please check this checkbox."),
          '#default_value' => $entity->get('is_translation'),
        ];

        $form['container']['translation_langcode'] = [
          '#type' => 'language_select',
          '#title' => $this->t('language'),
          '#default_value' => $entity->get('translation_langcode'),
        ];

      }

    }

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $example = $this->entity;
    $status = $example->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label Example created.', [
        '%label' => $example->label(),
      ]));
    } else {
      $this->messenger()->addMessage($this->t('The %label Example updated.', [
        '%label' => $example->label(),
      ]));
    }

    $form_state->setRedirect('entity.dynamic_import.collection');
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('dynamic_import')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool)$entity;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getTriggeringElement()['#name'];
    $this->submitted[$name] = $form_state->getValue($name);
    parent::validateForm($form, $form_state);
  }

  /**
   * Ajax Callback.
   */
  public function bundlesCallback($form, FormStateInterface $form_state) {
    return $form['container'];
  }

}