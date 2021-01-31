<?php

namespace Drupal\vactory_smart_search\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class VactorySmartSearchManager.
 *
 * @package Drupal\vactory_smart_search\Services
 */
class VactorySmartSearchManager {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * VactorySmartSearchManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Filter exposed form types options method.
   *
   * @param array $exposedFormTypesOptions
   *   The exposed form type options.
   *
   * @return array
   *   Filtred options array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function filterExposedFormTypesOptions(array $exposedFormTypesOptions) {
    $indexed_content = [];
    $new_options = [];
    $default_index_settings = $this->entityTypeManager->getStorage('search_api_index')
      ->load('default_content_index');
    $datasources = $default_index_settings->getDatasources();
    if (!empty($datasources) && isset($datasources['entity:node'])) {
      $config = $datasources['entity:node']->getConfiguration();
      if (!$config['bundles']['default']) {
        $indexed_content['is_allow_method'] = TRUE;
        $indexed_content['content_types'] = $config['bundles']['selected'];
      }
      else {
        $indexed_content['is_allow_method'] = FALSE;
        $indexed_content['content_types'] = $config['bundles']['selected'];
      }
    }
    if (!empty($indexed_content) && isset($indexed_content['content_types']) && !empty($exposedFormTypesOptions)) {
      $old_options = $exposedFormTypesOptions;
      foreach ($indexed_content['content_types'] as $type) {
        if ($indexed_content['is_allow_method']) {
          $new_options[$type] = $old_options[$type];
        }
        else {
          unset($old_options[$type]);
          $new_options = $old_options;
        }
      }
    }
    return $new_options;
  }

}
