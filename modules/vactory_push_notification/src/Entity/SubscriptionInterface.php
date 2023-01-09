<?php

namespace Drupal\vactory_push_notification\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Notification subscription entities.
 *
 * @ingroup vactory_push_notification
 */
interface SubscriptionInterface extends ContentEntityInterface {

  /**
   * Gets the subscription AppId.
   *
   * @return string
   */
  public function getAppId();

  /**
   * Gets the subscription user.
   *
   * @return string
   */
  public function getUser();

  /**
   * Sets the subscription user.
   *
   * @param string $user
   *   The subscription user.
   *
   * @return $this
   */
  public function setUser($user);

  /**
   * Gets the subscription token.
   *
   * @return string
   */
  public function getToken();

  /**
   * Sets the subscription token.
   *
   * @param string $token
   *   The subscription token.
   *
   * @return $this
   */
  public function setToken($token);

  /**
   * Gets the subscription endpoint.
   *
   * @return string
   */
  public function getEndpoint();

  /**
   * Sets the subscription endpoint.
   *
   * @param string $endpoint
   *   The subscription endpoint.
   *
   * @return $this
   */
  public function setEndpoint($endpoint);

  /**
   * Gets the Notification subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Notification subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the Notification subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Notification subscription creation timestamp.
   *
   * @return \Drupal\vactory_push_notification\Entity\SubscriptionInterface
   *   The called Notification subscription entity.
   */
  public function setCreatedTime($timestamp);

}
