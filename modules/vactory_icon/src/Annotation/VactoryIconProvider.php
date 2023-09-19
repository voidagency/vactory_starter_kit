<?php

namespace Drupal\vactory_icon\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a vactory icon provider annotation object.
 *
 * @Annotation
 */
class VactoryIconProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The provider translatable description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
