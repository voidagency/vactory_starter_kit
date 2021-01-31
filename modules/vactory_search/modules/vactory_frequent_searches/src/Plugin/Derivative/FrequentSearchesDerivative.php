<?php

namespace Drupal\vactory_frequent_searches\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\search_api\Entity\Index;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for mymodule blocks.
 *
 * @see \Drupal\vactory_frequent_searches\Plugin\Block\FrequentSearchesBlock
 */

class FrequentSearchesDerivative extends DeriverBase  implements ContainerDeriverInterface  {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    /** @var Index[] $indexes */
    $indexes = Index::loadMultiple();
    foreach ($indexes as $key => $index) {
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['admin_label'] = t('Frequent Searches API block').': ' . $index->label();
      $this->derivatives[$key]['config_dependencies']['config'] = [];
    }

    return $this->derivatives;
  }

  /**
   * FrequentSearchesDerivative constructor.
   * @param $base_plugin_id
   * @param EntityTypeManagerInterface $entity_manager
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }
}
