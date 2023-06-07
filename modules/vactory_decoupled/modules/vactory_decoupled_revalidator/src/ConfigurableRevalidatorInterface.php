<?php

namespace Drupal\vactory_decoupled_revalidator;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for the configurable revalidator plugin.
 */
interface ConfigurableRevalidatorInterface extends RevalidatorInterface, PluginFormInterface, ConfigurableInterface {

}
