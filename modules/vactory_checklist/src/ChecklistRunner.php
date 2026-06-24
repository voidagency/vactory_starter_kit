<?php

namespace Drupal\vactory_checklist;

/**
 * Runs all checklist plugins and returns structured results.
 */
class ChecklistRunner {

  /**
   * The checklist plugin manager.
   *
   * @var \Drupal\vactory_checklist\ChecklistPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a ChecklistRunner.
   *
   * @param \Drupal\vactory_checklist\ChecklistPluginManager $plugin_manager
   *   The checklist plugin manager.
   */
  public function __construct(ChecklistPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Executes every checklist plugin.
   */
  public function runAll(): array {
    $rows = [];
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $definition) {
      $plugin = $this->pluginManager->createInstance($plugin_id);
      $rows[] = [
        'id' => $plugin_id,
        'label' => (string) $plugin->getLabel(),
        'category' => $definition['category'] ?? 'general',
        'result' => $plugin->runCheck(),
      ];
    }
    return $rows;
  }

}
