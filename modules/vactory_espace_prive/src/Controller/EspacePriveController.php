<?php

namespace Drupal\vactory_espace_prive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\Form\UserPasswordForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EspacePriveController.
 *
 * @package Drupal\vactory_espace_prive\Controller
 */
class EspacePriveController extends ControllerBase {

  /**
   * Returns Login form.
   */
  public function login() {
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    if ($is_anonymous) {
      $login_form = \Drupal::formBuilder()->getForm(UserLoginForm::class);
      return [
        '#theme'      => 'espace_prive_login',
        '#login_form' => $login_form,
      ];
    }
    $profile_url = Url::fromRoute('vactory_espace_prive.profile');
    $redirect = new RedirectResponse($profile_url->toString());
    return $redirect->send();
  }

  /**
   * Returns Register form.
   */
  public function register() {
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    if ($is_anonymous) {
      $entity = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->create([]);
      $formObject = \Drupal::entityTypeManager()
        ->getFormObject('user', 'register')
        ->setEntity($entity);
      $registration_form = \Drupal::formBuilder()->getForm($formObject);
      return [
        '#theme'             => 'espace_prive_registration',
        '#registration_form' => $registration_form,
      ];
    }
    $profile_url = Url::fromRoute('vactory_espace_prive.profile');
    $redirect = new RedirectResponse($profile_url->toString());
    return $redirect->send();
  }

  /**
   * Returns Profile edit form.
   */
  public function profile() {
    $entity = User::load(\Drupal::currentUser()->id());
    $formObject = \Drupal::entityTypeManager()
      ->getFormObject('user', 'default')
      ->setEntity($entity);
    $profile_form = \Drupal::formBuilder()->getForm($formObject);
    return [
      '#theme'        => 'espace_prive_profile',
      '#profile_form' => $profile_form,
    ];
  }

  /**
   * Returns email form for reset password.
   */
  public function resetPassword() {
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    if ($is_anonymous) {
      $password_form = \Drupal::formBuilder()->getForm(UserPasswordForm::class);
      return [
        '#theme'         => 'espace_prive_password',
        '#password_form' => $password_form,
      ];
    }

    $profile_url = Url::fromRoute('vactory_espace_prive.profile');
    $redirect = new RedirectResponse($profile_url->toString());
    return $redirect->send();
  }

  /**
   * Returns profile view page.
   */
  public function welcome() {
    $params = \Drupal::request()->query->all();
    if (isset($params['user'])) {
      return $this->redirect('vactory_espace_prive.welcome');
    }
    $user = User::load(\Drupal::currentUser()->id());
    return [
      '#theme' => 'user',
      '#user'  => $user,
    ];
  }

}
