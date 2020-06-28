<?php

namespace Drupal\vactory_login_token\Controller;

use DateTime;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class TokenGenerateController.
 *
 * @package Drupal\vactory_login_token\Controller
 */
class TokenGenerateController extends ControllerBase {

  /**
   * Return page content.
   */
  public function content($user) {
    $_entity = User::load($user);
    $_token = $_entity->get('field_token')->getValue();
    if (empty($_token)) {
      $this->generateToken($user, "generate");
      $_entity = User::load($user);
      $_token = $_entity->get('field_token')->getValue();
    }
    $reginererUrl = Url::fromRoute('vactory_login_token.regenerate_token', ['user' => $user])->toString();
    $tokenUrl = Url::fromRoute('vactory_login_token.login_with_token', ['id' => $user, 'token' => $_token[0]['value']])->toString();
    return [
      '#theme' => 'token_details',
      '#content' => [
        "token" => $_token[0]['value'],
        'url' => $reginererUrl,
        'token_url' => $tokenUrl,
      ],
    ];
  }

  /**
   * Function to regenerate Token.
   */
  public function regenerer($user) {
    $this->generateToken($user, "regeneated");
    $token_url = Url::fromRoute('vactory_login_token.generate_token', ['user' => $user]);
    $redirect = new RedirectResponse($token_url->toString());
    $redirect->send();
  }

  /**
   * Function to generate Token.
   */
  public function generateToken($userId, $from) {
    $config = $this->config('token_login.settings');
    $expirationTime = $config->get('expiration_time');
    $entity = User::load($userId);
    $token = base64_encode(random_bytes(64));
    $token = strtr($token, '+/', '-_');
    $entity->set('field_token', $token);
    $date = new DateTime();
    $entity->set('field_token_experation_date', $date->getTimestamp() + (int) $expirationTime);
    $entity->save();
    if ($from == 'regeneated') {
      \Drupal::messenger()->addMessage(t('Your token has been regenrated.'), MessengerInterface::TYPE_STATUS);
    }
    elseif ($from == 'generate') {
      \Drupal::messenger()->addMessage(t('Your token has been generated.'), MessengerInterface::TYPE_STATUS);
    }

  }

  /**
   * Function login user.
   */
  public function login($id, $token) {
    $isAuthentifie = \Drupal::currentUser();
    if ($isAuthentifie->isAuthenticated()) {
      \Drupal::messenger()->addMessage(t('vous êtes déja connecter'), MessengerInterface::TYPE_WARNING);
      $profile_url = Url::fromRoute('vactory_login_token.authentication_profile');
      return new RedirectResponse($profile_url->toString());
    }
    else {
      $config = $this->config('token_login.settings');
      $experationStatus = $config->get('expirationStatus');
      \Drupal::service('page_cache_kill_switch')->trigger();
      $account = User::load($id);
      if (!empty($account)) {
        $tokenUser = $account->get('field_token')->getValue();
        if (!empty($tokenUser)) {
          $date = new DateTime();
          $dateActuel = $date->getTimestamp();
          $experationDate = $account->get('field_token_experation_date')
            ->getValue();
          if (strcmp($token, $tokenUser[0]['value']) == 0) {
            if ($experationStatus == 1 || ($dateActuel < $experationDate[0]['value'])) {
              user_login_finalize($account);
              $destination = $config->get('destination');
              $profile_url = Url::fromRoute('vactory_login_token.authentication_profile');
              return new RedirectResponse(($destination == '') ? $profile_url->toString() : $destination);
            }
            else {
              \Drupal::messenger()->addMessage(t('token experer'), MessengerInterface::TYPE_WARNING);
            }
          }
          else {
            \Drupal::messenger()->addMessage(t("token passer en parametre n'est pas valide"), MessengerInterface::TYPE_WARNING);
          }
        }
        else {
          \Drupal::messenger()->addMessage(t("token n'est pas encore générer"), MessengerInterface::TYPE_WARNING);
        }
      }
      \Drupal::messenger()->addMessage(t("utilisateur n'existe pas"), MessengerInterface::TYPE_WARNING);
      $profile_url = Url::fromRoute('vactory_login_token.authentication_profile');
      return new RedirectResponse($profile_url->toString());
    }
  }

  /**
   * Function Generate Token En Masse.
   */
  public function generateTokenEnMasse($selected) {
    $entity = \Drupal::entityTypeManager()->getStorage('user');
    $ids = $entity->getQuery()
      ->condition('status', 1);
    switch ($selected) {
      case 0: $ids = $ids->notExists('field_token');
        break;

      case 1: $ids = $ids->condition('field_token', NULL, 'is not');
        break;

    }
    $ids = $ids->execute();
    $users = $entity->loadMultiple($ids);
    if (empty($users)) {
      \Drupal::messenger()->addMessage(t('0 token generated'), MessengerInterface::TYPE_WARNING);
    }
    else {
      foreach ($users as $user) {
        $this->generateToken($user->id(), '');
      }
      \Drupal::messenger()->addMessage('Your token has been generated for ' . count($users) . ' users.', MessengerInterface::TYPE_STATUS);
    }
  }

}
