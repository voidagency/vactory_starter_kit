<?php

namespace Drupal\vactory_icon;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides vactory icon provider base plugin.
 *
 * This is a helper class which makes it easier for other developers to
 * implement Taxonomy bulk actions plugins in their own modules.
 */
abstract class VactoryIconProviderBase extends PluginBase implements VactoryIconProviderInterface {
  use StringTranslationTrait;

  /**
   * Icon provider description.
   *
   * @return \Drupal\Core\Annotation\Translation
   *   Icon provider translatable description.
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Fetch icons.
   */
  public function fetchIcons(ImmutableConfig|Config $config) {}

}
