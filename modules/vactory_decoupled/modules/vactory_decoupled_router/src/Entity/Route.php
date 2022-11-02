<?php

namespace Drupal\vactory_decoupled_router\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\vactory_decoupled_router\RouteInterface;

/**
 * Defines the Route entity.
 *
 * @ConfigEntityType(
 *   id = "vactory_route",
 *   label = @Translation("Route"),
 *   handlers = {
 *     "list_builder" = "Drupal\vactory_decoupled_router\Controller\RouteListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_decoupled_router\Form\RouteForm",
 *       "edit" = "Drupal\vactory_decoupled_router\Form\RouteForm",
 *       "delete" = "Drupal\vactory_decoupled_router\Form\RouteDeleteForm",
 *     }
 *   },
 *   config_prefix = "vactory_route",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "alias",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/vactory_router/{vactory_route}",
 *     "delete-form" = "/admin/config/system/vactory_router/{vactory_route}/delete",
 *   }
 * )
 */
class Route extends ConfigEntityBase implements RouteInterface
{

    /**
     * The Route ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The Route label.
     *
     * @var string
     */
    protected $label;

    /**
     * The path that this route belongs to.
     *
     * @var string
     */
    protected $path;

    /**
     * An alias used with this path. Can be a tokenized string for route alias.
     *
     * @var string
     */
    protected $alias;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
