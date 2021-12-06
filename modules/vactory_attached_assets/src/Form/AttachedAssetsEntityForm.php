<?php

namespace Drupal\vactory_attached_assets\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AttachedAssetsEntityForm.
 */
class AttachedAssetsEntityForm extends EntityForm {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The Attached Assets entity.
   *
   * @var \Drupal\vactory_attached_assets\Entity\AttachedAssetsEntityInterface
   */
  protected $entity;

  /**
   * Array of conditions Ids.
   *
   * @var string[]
   */
  protected $conditionsList = [
    'entity_bundle:node',
    'request_path',
    'user_role',
    'language',
  ];

  /**
   * Constructs a ContainerForm object.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The ConditionManager for building the insertion conditions.
   */
  public function __construct(ExecutableManagerInterface $condition_manager) {
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   *
   * This routine is the trick to DependencyInjection in Drupal. Without it the
   * __construct method complains of no arguments instead of three.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Get site default stream wrapper.
    $default_stream_wrapper = $this->configFactory
      ->get('system.file')
      ->get('default_scheme');

    $attached_assets_entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $attached_assets_entity->label(),
      '#description' => $this->t("Label for the Attached assets."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $attached_assets_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vactory_attached_assets\Entity\AttachedAssetsEntity::load',
      ],
      '#disabled' => !$attached_assets_entity->isNew(),
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('File to attach'),
      '#required' => TRUE,
      '#default_value' => $attached_assets_entity->getType(),
      '#options' => [
        'style' => 'Stylesheet (.css)',
        'script' => 'Script (.js)',
      ],
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#upload_location' => $default_stream_wrapper . '://attached_assets',
      '#upload_validators' => [
        'file_validate_extensions' => ['css js'],
      ],
      '#default_value' => $attached_assets_entity->getFileId(),
      '#required' => TRUE,
    ];

    $form['conditions_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Attachment Conditions'),
      '#parents' => ['conditions_tabs'],
    ];

    // Build the conditions plugin form.
    $conditions_form = [];
    foreach ($this->conditionsList as $condition_id) {
      $condition = $this->conditionManager->createInstance($condition_id);
      $default_config = $attached_assets_entity->getConditions()[$condition_id] ?? [];

      $condition->setConfiguration($default_config);
      $form_state->set(['conditions', $condition_id], $condition);
      $attached_assets_entity->setConditions($condition_id, $default_config);

      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'conditions_tabs';
      $conditions_form[$condition_id] = $condition_form + $condition->buildConfigurationForm([], $form_state);
      unset($conditions_form[$condition_id]['negate']);
    }

    $form['conditions_collection'] = $conditions_form;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $attached_assets_entity = $this->entity;

    try {
      $status = $attached_assets_entity->save();
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addMessage($this->t('Something went wrong while saving the %label Attached assets.', [
        '%label' => $attached_assets_entity->label(),
      ]));
      $form_state->setRebuild(TRUE);
      return;
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Attached assets.', [
          '%label' => $attached_assets_entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Attached assets.', [
          '%label' => $attached_assets_entity->label(),
        ]));
    }

    Cache::invalidateTags($attached_assets_entity->getCacheTagsToInvalidate());
    $form_state->setRedirect('entity.attached_assets_entity.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->set('file', $form_state->getValue('file')[0]);
    $attached_assets_entity = $this->entity;

    // Make file permanent.
    $file = File::load($form_state->getValue('file')[0]);
    $file->setPermanent();
    $file->save();

    $conditions = $attached_assets_entity->getConditions();
    foreach ($conditions as $condition_id => $condition_config) {
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form, $form_state);
      $this->entity->setConditions($condition_id, $condition->getConfiguration());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (isset($form_state->getValue('file')[0])) {
      $file_id = $form_state->getValue('file')[0];
      $file_type = $form_state->getValue('type');
      $file = File::load($file_id);

      if (($file->getMimeType() == 'text/css' && $file_type != 'style') || ($file->getMimeType() == 'text/javascript' && $file_type != 'script')) {
        $form_state->setErrorByName('type', $this->t("The uploaded file mime type doesn't match the specified type of file"));
        $form_state->setRebuild(TRUE);
      }
    }

  }

}
