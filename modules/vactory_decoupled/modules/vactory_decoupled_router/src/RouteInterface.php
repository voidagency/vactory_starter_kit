<?php

namespace Drupal\vactory_decoupled_router;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Route entity.
 */
interface RouteInterface extends ConfigEntityInterface
{
    /**
     * Returns the associated Route ID.
     *
     * @return string
     *   The Route ID.
     */
    public function getId();

    /**
     * Get the path that this route belongs to.
     *
     * @return string
     */
    public function getPath();

    /**
     * Get the alias used with this path.
     *
     * @return string
     */
    public function getAlias();
}
