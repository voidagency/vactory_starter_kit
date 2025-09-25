<?php

namespace Drupal\vactory_single_content_sync_extra\Plugin\SingleContentSyncFieldProcessor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\single_content_sync\ContentExporterInterface;
use Drupal\single_content_sync\ContentImporterInterface;
use Drupal\single_content_sync\SingleContentSyncFieldProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation generic field processor plugin.
 *
 * @SingleContentSyncFieldProcessor(
 *   id = "vactory_component",
 *   field_type="field_wysiwyg_dynamic"
 * )
 */
class WysiwygDynamicField extends SingleContentSyncFieldProcessorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The content exporter service.
   *
   * @var \Drupal\single_content_sync\ContentExporterInterface
   */
  protected ContentExporterInterface $exporter;

  /**
   * The content importer service.
   *
   * @var \Drupal\single_content_sync\ContentImporterInterface
   */
  protected ContentImporterInterface $importer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Constructs new WysiwygDynamicField plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContentExporterInterface $exporter,
    ContentImporterInterface $importer,
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->exporter = $exporter;
    $this->importer = $importer;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('single_content_sync.exporter'),
      $container->get('single_content_sync.importer'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function exportFieldValue(FieldItemListInterface $field): array {
    $widget_data = json_decode($field->getValue()[0]['widget_data'], TRUE);
    $processed_data = $this->processWidgetDataExport($widget_data);
    $field_value = $field->getValue();
    $field_value[0]['widget_data'] = json_encode($processed_data);
    return $field_value;
  }

  /**
   * {@inheritdoc}
   */
  public function importFieldValue(FieldableEntityInterface $entity, string $fieldName, array $value): void {
    $widget_data = json_decode($value[0]['widget_data'], TRUE);
    $processed_data = $this->processWidgetDataImport($widget_data);
    $value[0]['widget_data'] = json_encode($processed_data);
    $entity->set($fieldName, $value);
  }

  /**
   * Recursively process widget data to find and export media.
   *
   * @param array $data
   *   The widget data array to process.
   *
   * @return array
   *   The processed widget data with exported media references.
   */
  private function processWidgetDataImport(array $data): array {
    foreach ($data as &$item) {
      foreach ($item as $key => &$value) {
        // Handle group section.
        if (str_starts_with($key, 'group_')) {
          foreach ($value as &$sub_value) {
            if (is_array($sub_value) && $this->isMediaElement($sub_value)) {
              $this->processMediaImport($sub_value);
            }
            // Handle text format.
            if (is_array($sub_value) && isset($sub_value['embed_entities'])) {
              $this->processWysiwygImport($sub_value);
            }
          }
          continue;
        }

        if (is_array($value) && $this->isMediaElement($value)) {
          $this->processMediaImport($value);
        }
        // Handle text format.
        if (is_array($value) && isset($value['embed_entities'])) {
          $this->processWysiwygImport($value);
        }
      }
    }
    return $data;
  }

  /**
   * Recursively process widget data to find and export image media.
   *
   * @param array $data
   *   The widget data array to process.
   *
   * @return array
   *   The processed widget data with exported media references.
   */
  private function processWidgetDataExport(array $data): array {
    foreach ($data as &$item) {
      foreach ($item as $key => &$value) {
        // Handle group section.
        if (str_starts_with($key, 'group_')) {
          foreach ($value as &$sub_value) {
            // Handle Media field.
            if (is_array($sub_value) && $this->isMediaElement($sub_value)) {
              $sub_value = [
                'original' => $sub_value,
                'single_content_sync_media' => $this->processMediaExport($sub_value),
              ];
            }
            // Handle Text Format Field.
            if (is_array($sub_value) && isset($sub_value['format'], $sub_value['value'])) {
              $this->processWysiwygExport($sub_value);
            }
          }
          continue;
        }

        // Handle Media Fields.
        if (is_array($value) && $this->isMediaElement($value)) {
          $value = [
            'original' => $value,
            'single_content_sync_media' => $this->processMediaExport($value),
          ];
        }
        // Handle Text Format Field.
        if (is_array($value) && isset($value['format'], $value['value'])) {
          $this->processWysiwygExport($value);
        }
        // Handle case of Nested arrays (components, extra_field).
        if (is_array($value)) {
          // Recurse into nested arrays.
          $value = $this->processWidgetDataExport([$value])[0] ?? $value;

          // Check for WYSIWYG.
          if (isset($value['format'], $value['value'])) {
            $this->processWysiwygExport($value);
          }

          // Check for media element.
          if ($this->isMediaElement($value)) {
            $value = [
              'original' => $value,
              'single_content_sync_media' => $this->processMediaExport($value),
            ];
          }
        }
      }
    }
    return $data;
  }

  /**
   * Process an image media element by loading and exporting the media.
   */
  private function processMediaExport(array $media_element): array {
    $media_element = reset($media_element);
    $target_id = $media_element['selection'][0]['target_id'] ?? NULL;
    if (!isset($target_id)) {
      return [];
    }
    try {
      $ids_by_entity_type['media'] = [$target_id];
      foreach ($ids_by_entity_type as $entity_type => $ids) {
        $storage = $this->entityTypeManager->getStorage($entity_type);
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $child_entity) {
          if ($child_entity instanceof FieldableEntityInterface) {
            // Avoid exporting the same entity multiple times.
            if (!$this->exporter->isReferenceCached($child_entity)) {
              // Export content entity relation.
              $value[] = $this->exporter->doExportToArray($child_entity);
            }
            else {
              $value[] = [
                'uuid' => $child_entity->uuid(),
                'entity_type' => $child_entity->getEntityTypeId(),
                'base_fields' => $this->exporter->exportBaseValues($child_entity),
                'bundle' => $child_entity->bundle(),
              ];
            }
          }
          // Support basic export of config entity relation.
          elseif ($child_entity instanceof ConfigEntityInterface) {
            $value[] = [
              'type' => 'config',
              'dependency_name' => $child_entity->getConfigDependencyName(),
              'value' => $child_entity->id(),
            ];
          }
        }
      }

      $media_element['exported_media'] = $value;
    }
    catch (\Exception $e) {
      // Log the error but continue processing.
      \Drupal::logger('single_content_sync')
        ->error('Error processing image media @id: @error', [
          '@id' => $target_id,
          '@error' => $e->getMessage(),
        ]);
    }
    return $media_element;
  }

  /**
   * Process Media import.
   */
  private function processMediaImport(&$value) {
    $media_values = $value['single_content_sync_media']['exported_media'] ?? [];
    $values = [];
    foreach ($media_values as $childEntity) {
      // If the entity was fully exported we do the full import.
      if ($this->importer->isFullEntity($childEntity)) {
        $values[] = $this->importer->doImport($childEntity);
        continue;
      }

      $referencedEntity = $this
        ->entityRepository
        ->loadEntityByUuid($childEntity['entity_type'], $childEntity['uuid']);

      // Create a stub entity without custom field values.
      if (!$referencedEntity) {
        $referencedEntity = $this->importer->createStubEntity($childEntity);
      }

      $values[] = $referencedEntity;
    }

    if (!empty($values)) {
      $original = $value['original'];
      $first_key = array_key_first($original);
      $media = reset($values);
      if ($media instanceof MediaInterface) {
        $original[$first_key]['selection'][0]['target_id'] = $media->id();
        $value = $original;
      }
    }
  }

  /**
   * Process wysiwyg export.
   */
  public function processWysiwygExport(array &$value) {
    $text = $value['value'] ?? NULL;

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $embed_entities = [];

    foreach ($xpath->query('//drupal-media[@data-entity-type="media" and normalize-space(@data-entity-uuid)!=""]') as $node) {
      /** @var \DOMElement $node */
      $uuid = $node->getAttribute('data-entity-uuid');
      $media = $this->entityRepository->loadEntityByUuid('media', $uuid);
      assert($media === NULL || $media instanceof MediaInterface);

      if ($media) {
        $embed_entities[] = $this->exporter->doExportToArray($media);
      }
    }

    foreach ($xpath->query('//a[normalize-space(@href)!="" and normalize-space(@data-entity-type)!="" and normalize-space(@data-entity-uuid)!=""]') as $element) {
      /** @var \DOMElement $element */
      $entity_type_id = $element->getAttribute('data-entity-type');
      $uuid = $element->getAttribute('data-entity-uuid');
      $linked_entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);

      // Skip the process if the link is broken and entity could not be found.
      if (!$linked_entity instanceof FieldableEntityInterface) {
        continue;
      }

      if (!$this->exporter->isReferenceCached($linked_entity)) {
        $embed_entities[] = $this->exporter->doExportToArray($linked_entity);
      }
      else {
        $embed_entities[] = [
          'uuid' => $linked_entity->uuid(),
          'entity_type' => $linked_entity->getEntityTypeId(),
          'base_fields' => $this->exporter->exportBaseValues($linked_entity),
          'bundle' => $linked_entity->bundle(),
        ];
      }
    }

    foreach ($xpath->query('//img[@data-entity-type="file" and normalize-space(@data-entity-uuid)!=""]') as $node) {
      /** @var \DOMElement $node */
      $uuid = $node->getAttribute('data-entity-uuid');
      $file = $this->entityRepository->loadEntityByUuid('file', $uuid);
      assert($file === NULL || $file instanceof FileInterface);

      // File entity does not need a stub entity, so we do a full export.
      if ($file) {
        $embed_entities[] = $this->exporter->doExportToArray($file);
      }
    }

    $value['embed_entities'] = $embed_entities;
  }

  /**
   * Process wysiwyg import.
   */
  private function processWysiwygImport(array &$item) {
    $embed_entities = $item['embed_entities'] ?? [];

    if (array_key_exists('embed_entities', $item)) {
      unset($item['embed_entities']);
    }

    foreach ($embed_entities as $embed_entity) {
      if ($this->importer->isFullEntity($embed_entity)) {
        $this->importer->doImport($embed_entity);
      }
      else {
        $referenced_entity = $this
          ->entityRepository
          ->loadEntityByUuid($embed_entity['entity_type'], $embed_entity['uuid']);

        // Create a stub entity without custom field values.
        if (!$referenced_entity) {
          $this->importer->createStubEntity($embed_entity);
        }
      }
    }

    $text = $item['value'];
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $needs_update = FALSE;

    foreach ($xpath->query('//a[normalize-space(@href)!="" and normalize-space(@data-entity-type)!="" and normalize-space(@data-entity-uuid)!=""]') as $element) {
      /** @var \DOMElement $element */
      $entity_type_id = $element->getAttribute('data-entity-type');
      $uuid = $element->getAttribute('data-entity-uuid');
      $linked_entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
      assert($linked_entity === NULL || $linked_entity instanceof FieldableEntityInterface);

      if ($linked_entity) {
        $needs_update = TRUE;
        $element->setAttribute('href', $linked_entity->toUrl('canonical', [
          'alias' => TRUE,
          'path_processing' => FALSE,
        ])->toString());
      }
    }

    if ($needs_update) {
      $item['value'] = Html::serialize($dom);
    }
  }

  /**
   * Check if an array element represents an image media.
   *
   * @param array $element
   *   The array element to check.
   *
   * @return bool
   *   TRUE if this is an image media element, FALSE otherwise.
   */
  private function isMediaElement(array $element): bool {
    if (array_key_exists('single_content_sync_media', $element)) {
      return TRUE;
    }
    $element = reset($element);
    return is_array($element) && array_key_exists('media_library_update_widget', $element);
  }

}
