<?php

namespace Drupal\vactory_dynamic_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PlatformProvider item annotation object.
 *
 * @Annotation
 */
class PlatformProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
