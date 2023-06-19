<?php

namespace Drupal\vactory_decoupled_revalidator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines revalidator annotation object.
 *
 * @Annotation
 */
class Revalidator extends Plugin {

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
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
