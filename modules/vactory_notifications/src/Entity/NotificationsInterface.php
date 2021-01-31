<?php

namespace Drupal\vactory_notifications\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Notification entity.
 *
 * @ingroup vactory_notifications
 */
interface NotificationsInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Check if a user is concened by current notification.
   *
   * @param $uid
   *
   * @return boolean
   */
  public function isUserConcerned($uid);

  /**
   * Check if current notification has been viewed by given user.
   *
   * @param $uid
   *
   * @return boolean
   */
  public function isViewedByUser($uid);

  /**
   * Check if current notification is published.
   *
   * @return boolean
   */
  public function isPublished();

  /**
   * Get current notification title.
   *
   * @return String
   */
  public function getTitle();

  /**
   * Set current notification title.
   *
   * @param $title
   *
   * @return \Drupal\vactory_notifications\Entity\NotificationsEntity
   */
  public function setTitle($title);

  /**
   * Get current notification message.
   *
   * @return mixed
   */
  public function getMessage();

  /**
   * Set current notification message.
   *
   * @param $message
   *
   * @return \Drupal\vactory_notifications\Entity\NotificationsEntity
   */
  public function setMessage($message);

  /**
   * Get current notification related content ID.
   *
   * @return mixed
   */
  public function getRelatedContent();

  /**
   * Get notification concerned users.
   *
   * @return array
   */
  public function getConcernedUsers();

  /**
   * Get notification viewers.
   *
   * @return array
   */
  public function getViewers();

  /**
   * Set notification concerned users.
   *
   * @param $concerned_users
   *
   * @return \Drupal\vactory_notifications\Entity\NotificationsEntity
   */
  public function setConcernedUsers(array $concerned_users);

  /**
   * Set notification viewers.
   *
   * @param $viewers_ids
   *
   * @return \Drupal\vactory_notifications\Entity\NotificationsEntity
   */
  public function setViewers(array $viewers_ids);

}
