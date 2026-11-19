<?php

namespace Drupal\vactory_webform_typesafe\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\vactory_webform_typesafe\Service\AnalysisManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes queued submissions without blocking form submission.
 *
 * @QueueWorker(
 *   id = "vactory_webform_typesafe",
 *   title = @Translation("Webform TypeSafe classification"),
 *   cron = {"time" = 30}
 * )
 */
final class ClassifySubmission extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the service with its injected dependencies.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, private readonly AnalysisManager $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('vactory_webform_typesafe.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->manager->process((array) $data);
  }

}
