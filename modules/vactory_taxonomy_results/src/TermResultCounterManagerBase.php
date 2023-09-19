<?php

namespace Drupal\vactory_taxonomy_results;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vactory_taxonomy_results\Services\TermResultCounterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Term result counter manager base.
 */
abstract class TermResultCounterManagerBase extends PluginBase implements TermResultCounterInterface, ContainerFactoryPluginInterface {

  /**
   * Taxonomy results helper service.
   *
   * @var \Drupal\vactory_taxonomy_results\Services\TermResultCounterHelper
   */
  protected $taxonomyResultsHelper;

  /**
   * Entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TermResultCounterHelper $taxonomyResultsHelper,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->taxonomyResultsHelper = $taxonomyResultsHelper;
    $this->entityTypeManager = $entityTypeManager;
  }


  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_taxonomy_results.helper'),
      $container->get('entity_type.manager')
    );
  }


}