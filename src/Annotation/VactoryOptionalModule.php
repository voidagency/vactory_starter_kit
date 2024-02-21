<?php

namespace Drupal\vactory_starter_kit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an optional module annotation object.
 *
 * @Annotation
 */
class VactoryOptionalModule extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The human-readable name of the widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $type;

  /**
   * The weight of the plugin in relation to other plugins.
   *
   * @var int
   */
  public $weight;

}
