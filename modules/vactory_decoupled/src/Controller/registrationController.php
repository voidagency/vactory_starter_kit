<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Datetime\TimeInterface;

/**
 * User registration password controller class.
 */
class registrationController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, TimeInterface $time) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('datetime.time')
    );
  }


  public function confirmAccount($uid, $timestamp, $hash) {
    $current_user = $this->currentUser();

    // Verify that the user exists.
    if ($current_user === NULL) {
      return new JsonResponse(['message' => $this->t('access denied')], 403);
    }

    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($current_user->isAuthenticated()) {
      return new JsonResponse(['message' => $this->t('User already logged in')], Response::HTTP_BAD_REQUEST);
    }
    // Time out, in seconds, until login URL expires. 24 hours = 86400
    // seconds.
    $timeout = $this->config('user_registrationpassword.settings')
      ->get('registration_ftll_timeout');
    $current = $this->time->getRequestTime();
    $timestamp_created = $timestamp - $timeout;

    // Some redundant checks for extra security ?
    $users = $this->userStorage->getQuery()
      ->condition('uid', $uid)
      ->condition('status', 0)
      ->condition('access', 0)
      ->execute();

    // Timestamp can not be larger then current.
    $valid = TRUE;
    if ($timestamp_created <= $current && !empty($users) && $account = $this->userStorage->load(reset($users))) {
      // Check if we have to enforce expiration for activation links.
      if ($this->config('user_registrationpassword.settings')
          ->get('registration_ftll_expire') && !$account->getLastLoginTime() && $current - $timestamp > $timeout) {
        // no longer valid.
        $valid = FALSE;
      }

      if ($valid && $account->id() && $timestamp >= $account->getCreatedTime() && !$account->getLastLoginTime() && $hash == user_pass_rehash($account, $timestamp)) {
        // Activate the user and update the access and login time to $current.
        $account
          ->activate()
          ->save();

        return new JsonResponse(['message' => $this->t('You have just used your account verification link. Your account is now active')], 200);
      }
    }


    return new JsonResponse([
      'message' => $this->t('You have tried to use a account verification link that has either been used or is no longer valid.'),
    ], Response::HTTP_BAD_REQUEST);

  }

}
