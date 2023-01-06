<?php

namespace Drupal\vactory_push_notification\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class PushService extends Plugin {
  /**
   * The human-readable name of the push service plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;
}
