<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Checklist plugins.
 */
abstract class ChecklistBase extends PluginBase implements ChecklistInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}
