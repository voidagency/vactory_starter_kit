<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\vactory_dynamic_import\Service\DynamicImportHelpers;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Form handler for the dynamic import add and edit forms.
 */
class DynamicImportForm extends EntityForm {

  const MEDIA_FIELD_NAMES = [
    'audio' => 'field_media_audio_file',
    'image' => 'field_media_image',
    'file' => 'field_media_file',
    'remote_video' => 'field_media_oembed_video',
    'video' => 'field_media_video_file',
    'onboarding_video' => 'field_video_onboarding',
  ];

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
        $form['container']['concerned_fields'] = [
          '#type' => 'checkboxes',
          '#title' => t('Concerned fields'),
          '#options' => $entity->isNew() ?
          $this->dynamicImportHelper->getRelatedFields($this->submitted['target_entity'], $this->submitted['target_bundle'], TRUE)
          : $this->dynamicImportHelper->getRelatedFields($entity->get('target_entity'), $entity->get('target_bundle'), TRUE),
          '#default_value' => $entity->get('concerned_fields'),
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
          '#required' => TRUE,
        ];

      }

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if (!$this->entity->isNew()) {
      $form['actions']['generate'] = [
        '#type' => 'submit',
        '#value' => t('Generate CSV model'),
        '#submit' => ['::generateCsvModel'],
        '#weight' => 10,
      ];
      $form['actions']['execute'] = [
        '#type' => 'submit',
        '#value' => t('Execute this migration'),
        '#submit' => ['::executeDynamicImport'],
        '#weight' => 10,
      ];
      $form['actions']['export'] = [
        '#type' => 'submit',
        '#value' => t('Export existing content'),
        '#submit' => ['::dynamicExport'],
        '#weight' => 10,
      ];
    }
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
    }
    else {
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
    return (bool) $entity;
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

  /**
   * Submit function for generate button.
   */
  public function generateCsvModel(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->dynamicImportHelper->generateCsvModel(
        $values['target_entity'],
        $values['target_bundle'],
        $values['concerned_fields'],
        $values['is_translation']
      );
  }

  /**
   * Submit function for execute button.
   */
  public function executeDynamicImport(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $header = $this->dynamicImportHelper->generateCsvModel(
        $values['target_entity'],
        $values['target_bundle'],
        $values['concerned_fields'],
        $values['is_translation'],
        NULL,
        TRUE
      );

    $data = $this->dynamicImportHelper->generateMigrationConfig(
        $values['id'],
        $values['label'],
        $header,
        $values['target_entity'],
        $values['target_bundle'],
        $values['is_translation'],
        $values['translation_langcode']
      );

    $config_name = "migrate_plus.migration.{$values['id']}";
    $migration_config = \Drupal::configFactory()
      ->getEditable($config_name);
    $migration_config->setData($data);
    $migration_config->save();
    drupal_flush_all_caches();
    $form_state->setRedirect('vactory_dynamic_import.execute', ['id' => $config_name]);
    $form_state->setIgnoreDestination();

  }

  /**
   * Export content based on dynamic import config.
   */
  public function dynamicExport(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $moduleHandler = \Drupal::service('module_handler');
    $alias_manager = \Drupal::service('path_alias.manager');
    $media_decoupled_manager = NULL;
    if ($moduleHandler->moduleExists('vactory_decoupled')) {
      $media_decoupled_manager = \Drupal::service('vacory_decoupled.media_file_manager');
    }

    $file_url_generator = \Drupal::service('file_url_generator');
    $header = $this->dynamicImportHelper->generateCsvModel(
      $values['target_entity'],
      $values['target_bundle'],
      $values['concerned_fields'],
      $values['is_translation'],
      NULL,
      TRUE
    );
    $storage = $this->entityTypeManager->getStorage($values['target_entity']);
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    if ($values['target_entity'] !== $values['target_bundle']) {
      $entity_type_definition = $this->entityTypeManager->getDefinition($values['target_entity']);
      $bundle_field = $entity_type_definition->getKey('bundle');
      $query->condition($bundle_field, $values['target_bundle']);
    }
    $ids = $query->execute();
    $data = [];
    foreach ($ids as $id) {
      $entity_data = [];
      $entity = NULL;
      $default_entity = $storage->load($id);
      if ($values['is_translation'] && $default_entity->hasTranslation($values['translation_langcode'])) {
        $entity = $default_entity->getTranslation($values['translation_langcode']);
      }
      else {
        $entity = $default_entity;
      }
      foreach ($header as $header_item) {
        if ($header_item == 'id') {
          $entity_data['id'] = $entity->id();
        }
        elseif ($header_item == 'original') {
          $entity_data['original'] = $entity->id();
        }
        else {
          $config = $header_item ? explode('|', $header_item) : [];
          $plugin = $config[0];
          $field = $config[1];
          $info = $config[2];
          if (is_array($config) && count($config) == 3) {
            $split = explode(':', $field);
            if ($plugin == '-' && $info == '-') {
              if (count($split) == 1) {
                if ($field == 'path' && $values['target_entity'] == 'node') {
                  $alias = $alias_manager->getAliasByPath('/node/' . $entity->id());
                  $entity_data[$header_item] = $alias;
                }
                else {
                  $entity_data[$header_item] = $entity->get($field)->value;
                }
              }
              if (count($split) == 2) {
                $value = $entity->get(reset($split))->getValue();
                $entity_data[$header_item] = $value[0][end($split)];
              }
            }
            if ($plugin == 'term') {
              $term_id = $entity->get($field)->target_id;
              $entity_data[$header_item] = $term_id ? Term::load($term_id)->label() : '';
            }
            if ($plugin == 'date') {
              $value = $entity->get(reset($split))->getValue();
              $entity_data[$header_item] = $value[0][end($split)];
            }
            if ($plugin == 'media') {
              if ($info !== 'image_alt') {
                $media_id = $entity->get($field)->target_id;
                $media = isset($media_id) ? Media::load($media_id) : NULL;
                if (!$media instanceof MediaInterface) {
                  $entity_data[$header_item] = '';
                  if ($info == 'image') {
                    $key = "media|{$field}|image_alt";
                    $entity_data[$key] = '';
                  }
                  continue;
                }
                if ($info == 'remote_video') {
                  $url = $media->get(self::MEDIA_FIELD_NAMES[$info])->value;
                  $entity_data[$header_item] = $url . " ({$media_id})";
                }
                else {
                  $fid = $media->get(self::MEDIA_FIELD_NAMES[$info])->target_id;
                  $file = $fid ? File::load($fid) : NULL;
                  if (!$file instanceof FileInterface) {
                    $entity_data[$header_item] = '';
                    if ($info == 'image') {
                      $key = "media|{$field}|image_alt";
                      $entity_data[$key] = $media->thumbnail->alt;
                    }
                    continue;
                  }
                  $image_uri = $file->getFileUri();

                  $url = '';
                  if ($moduleHandler->moduleExists('vactory_decoupled') && !is_null($media_decoupled_manager)) {
                    $url = $media_decoupled_manager->getMediaAbsoluteUrl($image_uri);
                  }
                  else {
                    $url = $file_url_generator->generateAbsoluteString($image_uri);
                  }
                  $entity_data[$header_item] = $url . " ({$media_id})";
                  if ($info == 'image') {
                    $key = "media|{$field}|image_alt";
                    $entity_data[$key] = $media->thumbnail->alt;
                  }
                }
              }
            }
            if ($plugin == 'file') {
              $fid = $entity->get($field)->target_id;
              $file = $fid ? File::load($fid) : NULL;
              if (!$file instanceof FileInterface) {
                $entity_data[$header_item] = '';
                continue;
              }
              $image_uri = $file->getFileUri();
              if ($moduleHandler->moduleExists('vactory_decoupled') && !is_null($media_decoupled_manager)) {
                $url = $media_decoupled_manager->getMediaAbsoluteUrl($image_uri);
              }
              else {
                $url = $file_url_generator->generateAbsoluteString($image_uri);
              }
              $entity_data[$header_item] = $url;
            }
            if ($plugin == 'wysiwyg') {
              if (count($split) == 2) {
                $value = $entity->get(reset($split))->getValue();
                $entity_data[$header_item] = $value[0][end($split)];
              }
            }
          }
        }
      }
      $data[] = $entity_data;
    }

    $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter') ?? ',';
    $path = $this->dynamicImportHelper->generateCsv($header, $data, "{$values['target_entity']}--{$values['target_bundle']}--export", $delimiter);

    $response = new BinaryFileResponse(\Drupal::service('file_system')
      ->realPath($path), 200, [], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "{$values['target_entity']}--{$values['target_bundle']}--export" . '.csv');
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

}
