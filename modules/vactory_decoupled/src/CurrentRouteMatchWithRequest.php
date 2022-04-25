<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Class CurrentRouteMatchWithRequest
 *
 * Sometimes we need access to the request property.
 *
 * @package Drupal\vactory_decoupled
 */
class CurrentRouteMatchWithRequest extends CurrentRouteMatch
{

  /**
   * {@inheritdoc}
   */
  public function getCurrentRequestStack()
  {
    return $this->requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRequest()
  {
    return $this->requestStack->getCurrentRequest();
  }
}
