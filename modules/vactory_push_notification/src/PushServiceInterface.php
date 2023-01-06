<?php

namespace Drupal\vactory_push_notification;

/**
 * An interface for all Push Services type plugins.
 */
interface PushServiceInterface {

  /**
   * TODO
   */
  public function getRequest($data);

}
