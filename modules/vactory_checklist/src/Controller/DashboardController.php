<?php

namespace Drupal\vactory_checklist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\vactory_checklist\ChecklistPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the checklist dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * The checklist plugin manager.
   *
   * @var \Drupal\vactory_checklist\ChecklistPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\vactory_checklist\ChecklistPluginManager $plugin_manager
   *   The checklist plugin manager.
   */
  public function __construct(ChecklistPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.vactory_checklist')
    );
  }

  /**
   * Displays the dashboard.
   *
   * @return array
   *   A render array representing the dashboard.
   */
  public function dashboard() {
    $plugins = $this->pluginManager->getDefinitions();
    $results = [];

    foreach ($plugins as $plugin_id => $definition) {
      $plugin = $this->pluginManager->createInstance($plugin_id);
      $check_result = $plugin->runCheck();

      $results[$definition['category']][] = [
        'label' => $plugin->getLabel(),
        'description' => $plugin->getDescription(),
        'result' => $check_result,
      ];
    }

    return [
      '#theme' => 'checklist_dashboard',
      '#results' => $results,
      '#attached' => [
        'library' => ['vactory_checklist/dashboard'],
      ],
    ];
  }

}
