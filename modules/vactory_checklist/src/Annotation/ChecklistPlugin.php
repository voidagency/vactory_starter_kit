<?php

namespace Drupal\vactory_checklist\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Checklist plugin annotation object.
 *
 * @Annotation
 */
class ChecklistPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The category of the plugin.
   *
   * @var string
   */
  public $category;

}
