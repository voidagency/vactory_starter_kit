<?php

namespace Drupal\vactory_amp\Services;

use Drupal\amp\EntityTypeInfo;
use Drupal\amp\Routing\AmpContext;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManager;
use Drupal\node\NodeInterface;

class VactoryAmpContextDecorator extends AmpContext {

  /**
   * Amp context service object.
   *
   * @var \Drupal\amp\Routing\AmpContext
   */
  protected $ampContext;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    AmpContext $ampContext,
    ConfigFactoryInterface $configFactory,
    ThemeManager $themeManager,
    EntityTypeInfo $entityTypeInfo,
    RouteMatchInterface $routeMatch,
    AdminContext $adminContext
  ) {
    $this->ampContext = $ampContext;
    parent::__construct($configFactory, $themeManager, $entityTypeInfo, $routeMatch, $adminContext);
  }

  /**
   * {@inheritDoc}
   */
  public function isAmpRoute(RouteMatchInterface $routeMatch = NULL, $entity = NULL, $checkTheme = TRUE) {
    $is_amp_route = parent::isAmpRoute($routeMatch, $entity, $checkTheme);
    if (!$routeMatch) {
      $routeMatch = $this->routeMatch;
    }
    $route_entity = $this->routeEntity($routeMatch);
    if ($route_entity instanceof NodeInterface) {
      $exclude_from_search = $route_entity->get('exclude_from_amp')->value;
      if ($exclude_from_search) {
        $is_amp_route = FALSE;
      }
    }
    return $is_amp_route;
  }

}
